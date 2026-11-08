<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyCheckController extends NetworkController
{
    private function dateValid($date): bool {
        if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)) return false;
        [$y,$m,$d] = array_map('intval', explode('-', $date)); return checkdate($m,$d,$y);
    }
    public function index(Request $request) {
        $actor = $this->actor($request);
        if (!$actor) return response()->json(['error'=>'authentication required'],401);
        if (!$this->allowed($actor, 'inventory.read')) return response()->json(['error'=>'permission denied'],403);
        $date = $request->query('date');
        if (!$this->dateValid($date)) return response()->json(['error'=>'valid date required'],400);
        $items = DB::table('devices as d')->join('locations as l','l.id','=','d.location_id')->join('buildings as b','b.id','=','l.building_id')
            ->leftJoin('daily_checks as c', function($join) use ($date) { $join->on('c.device_id','=','d.id')->where('c.check_date','=',$date); })
            ->leftJoin('users as u','u.id','=','c.checked_by')
            ->select('d.id','d.name','d.asset_id','d.manufacturer','d.model','d.category','l.name as location','b.name as building','c.result','c.notes','c.updated_at','u.email as checked_by')->orderBy('d.id')->get();
        $summary = ['total'=>$items->count(),'pending'=>0,'ok'=>0,'issue'=>0,'not_applicable'=>0];
        foreach ($items as $item) { $key=$item->result ?? 'pending'; if (isset($summary[$key])) $summary[$key]++; }
        return response()->json(['date'=>$date,'items'=>$items,'summary'=>$summary]);
    }
    public function save(Request $request) {
        $actor = $this->actor($request);
        if (!$actor) return response()->json(['error'=>'authentication required'],401);
        if (!$this->allowed($actor, 'inventory.write')) return response()->json(['error'=>'permission denied'],403);
        $in=json_decode($request->getContent(),true);
        if (array_diff(array_keys($in),['device_id','date','result','notes']) || !is_int($in['device_id'] ?? null) || $in['device_id']<1 || !$this->dateValid($in['date'] ?? null) || !in_array($in['result'] ?? null,['ok','issue','not_applicable'],true) || !is_string($in['notes'] ?? '') || strlen($in['notes'] ?? '')>2000) return response()->json(['error'=>'valid device, date, result and notes required'],400);
        if (!DB::table('devices')->where('id',$in['device_id'])->exists()) return response()->json(['error'=>'device not found'],404);
        DB::transaction(function() use ($in,$actor) {
            DB::table('daily_checks')->upsert([['device_id'=>$in['device_id'],'check_date'=>$in['date'],'result'=>$in['result'],'notes'=>$in['notes'] ?? '', 'checked_by'=>$actor->id,'updated_at'=>now('UTC')]], ['device_id','check_date'], ['result','notes','checked_by','updated_at']);
            $this->audit($actor->id,'check.record', $in['device_id'].':'.$in['date']);
        });
        return response()->json(['status'=>'saved']);
    }
}
