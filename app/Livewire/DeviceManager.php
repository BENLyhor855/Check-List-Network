<?php
namespace App\Livewire;
use App\Models\AccessPoint;
use App\Models\Building;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
class DeviceManager extends Component {
    public array $buildingAssignments=[];
    public function mount(): void { Gate::authorize('administer'); foreach(AccessPoint::select('id','building_id')->get() as $device) $this->buildingAssignments[$device->id]=(string)($device->building_id ?? ''); }
    public function assign(int $id): void {
        Gate::authorize('administer'); $value=$this->buildingAssignments[$id] ?? '';
        if ($value !== '' && (!ctype_digit((string)$value) || !Building::whereKey((int)$value)->where('active',true)->exists())) { $this->addError('buildingAssignments.'.$id,'Select an active building.'); return; }
        DB::transaction(function () use ($id,$value) { AccessPoint::findOrFail($id)->update(['building_id'=>$value === '' ? null : (int)$value]); DB::table('audit_logs')->insert(['user_id'=>auth()->id(),'action'=>'device.building_assigned','target'=>(string)$id,'created_at'=>now()]); }); session()->flash('message','Building assignment saved.');
    }
    public function render() { Gate::authorize('administer'); return view('livewire.device-manager',['devices'=>AccessPoint::with('building')->orderBy('name')->get(),'buildings'=>Building::where('active',true)->orderBy('name')->get()])->layout('layouts.app'); }
}
