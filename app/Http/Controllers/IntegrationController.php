<?php
namespace App\Http\Controllers;
use App\Models\Integration;
use App\Services\MerakiService;
use App\Services\UniFiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
class IntegrationController extends Controller {
    public function index() { Gate::authorize('manage-integrations'); return view('integrations.index',['integrations'=>Integration::select('id','name','vendor')->orderBy('name')->get()]); }
    public function store(Request $request) {
        Gate::authorize('manage-integrations');
        $v=Validator::make($request->only('name','vendor','api_key','network_id','console_id'),['name'=>'required|string|max:120','vendor'=>'required|in:meraki,unifi','api_key'=>'required|string|min:10|max:255','network_id'=>['nullable','required_if:vendor,unifi','string','max:128','regex:/^[A-Za-z0-9_-]+$/'],'console_id'=>['nullable','required_if:vendor,unifi','string','max:128','regex:/^[A-Za-z0-9_-]+$/']]);
        if ($v->fails()) return back()->withErrors($v);
        $data=$v->validated(); DB::transaction(function () use ($data) { $i=Integration::create(['name'=>$data['name'],'vendor'=>$data['vendor'],'credentials'=>['api_key'=>$data['api_key']],'network_id'=>$data['network_id'] ?? null,'organization_id'=>$data['console_id'] ?? null]); DB::table('audit_logs')->insert(['user_id'=>auth()->id(),'action'=>'integration.created','target'=>(string)$i->id,'created_at'=>now()]); });
        return redirect()->route('admin.integrations')->with('message','Integration saved. Test the connection to verify access.');
    }
    public function test(Integration $integration, MerakiService $meraki, UniFiService $unifi) {
        Gate::authorize('manage-integrations');
        try { ($integration->vendor === 'unifi' ? $unifi : $meraki)->testConnection($integration); return back()->with('message',ucfirst($integration->vendor).' connection succeeded.'); }
        catch (RuntimeException $e) { return back()->with('connection_error',$e->getMessage()); }
    }
    public function sync(Integration $integration, MerakiService $service) {
        Gate::authorize('manage-integrations');
        try { $count=$service->syncDevices($integration, (int) auth()->id()); return back()->with('message',"Inventory sync completed: {$count} access points processed. Status remains unknown."); }
        catch (RuntimeException $e) { return back()->with('connection_error',$e->getMessage()); }
    }

}
