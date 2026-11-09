<?php
namespace App\Http\Controllers;
use App\Models\AccessPoint;
use App\Models\Building;
use Illuminate\Http\Request;
class InventoryReportController extends Controller {
    private function filters(Request $request): array { return $request->validate(['building'=>'nullable|integer|exists:buildings,id','vendor'=>'nullable|in:meraki,unifi']); }
    private function query(array $filters) {
        return AccessPoint::query()->with('building')->when($filters['building'] ?? null, fn($q,$id)=>$q->where('building_id',$id))->when($filters['vendor'] ?? null, fn($q,$vendor)=>$q->where('vendor',$vendor))->orderBy('name');
    }
    public function index(Request $request) { $filters=$this->filters($request); return view('reports.inventory',['devices'=>$this->query($filters)->get(),'buildings'=>Building::orderBy('name')->get(),'filters'=>$filters]); }
    public function download(Request $request) {
        $filters=$this->filters($request);
        return response()->streamDownload(function () use ($filters) {
            $out=fopen('php://output','w');
            fputcsv($out,['Name','Vendor','Model','Serial','Building','Last observed status','Observed at (UTC)']);
            foreach($this->query($filters)->cursor() as $device) {
                $values=[$device->name,$device->vendor,$device->model,$device->serial,$device->building?->name,$device->observed_at ? $device->status : null,$device->observed_at?->utc()->toIso8601String()];
                $safe=array_map(function($value) { $text=(string)($value ?? ''); return preg_match('/^[\\x00-\\x20]*[=+@-]/u',$text) ? "'".$text : $text; },$values);
                fputcsv($out,$safe);
            }
            fclose($out);
        },'access-point-inventory.csv',['Content-Type'=>'text/csv; charset=UTF-8','Cache-Control'=>'no-store']);
    }
}
