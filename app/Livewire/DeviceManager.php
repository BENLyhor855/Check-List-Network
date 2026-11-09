<?php
namespace App\Livewire;

use App\Models\AccessPoint;
use App\Models\Building;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class DeviceManager extends Component {
    use WithPagination;

    public string $search = '';
    public string $vendor = '';
    public string $building = '';
    public array $buildingAssignments = [];

    public function mount(): void { Gate::authorize('administer'); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedVendor(): void { $this->resetPage(); }
    public function updatedBuilding(): void { $this->resetPage(); }
    public function clearFilters(): void { $this->reset(['search','vendor','building']); $this->resetPage(); }

    public function assign(int $id): void {
        Gate::authorize('administer');
        $value = $this->buildingAssignments[$id] ?? '';
        if (!is_string($value) && !is_int($value)) { $this->addError('buildingAssignments.'.$id, 'Select an active building.'); return; }
        $value = (string) $value;
        if ($value !== '' && (!ctype_digit($value) || !Building::whereKey((int)$value)->where('active',true)->exists())) { $this->addError('buildingAssignments.'.$id, 'Select an active building.'); return; }
        DB::transaction(function () use ($id,$value) {
            AccessPoint::findOrFail($id)->update(['building_id'=>$value === '' ? null : (int)$value]);
            DB::table('audit_logs')->insert(['user_id'=>auth()->id(),'action'=>'device.building_assigned','target'=>(string)$id,'created_at'=>now()]);
        });
        $this->resetErrorBag('buildingAssignments.'.$id);
        session()->flash('message','Building assignment saved.');
    }

    public function render() {
        Gate::authorize('administer');
        $query=AccessPoint::query()->with('building');
        $search=mb_substr(trim($this->search),0,100);
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $term='%'.$search.'%';
                $q->where('name','like',$term)->orWhere('serial','like',$term)->orWhere('model','like',$term)->orWhere('mac','like',$term)->orWhere('ip_address','like',$term);
            });
        }
        if ($this->vendor !== '') {
            if (in_array($this->vendor,['meraki','unifi'],true)) $query->where('vendor',$this->vendor);
            else $query->whereRaw('1 = 0');
        }
        if ($this->building === 'unassigned') $query->whereNull('building_id');
        elseif ($this->building !== '') {
            if (ctype_digit($this->building) && (int)$this->building > 0) $query->where('building_id',(int)$this->building);
            else $query->whereRaw('1 = 0');
        }
        $devices=$query->orderBy('name')->orderBy('id')->paginate(25);
        foreach ($devices as $device) {
            if (!array_key_exists($device->id,$this->buildingAssignments)) $this->buildingAssignments[$device->id]=(string)($device->building_id ?? '');
        }
        return view('livewire.device-manager',['devices'=>$devices,'buildings'=>Building::where('active',true)->orderBy('name')->get()])->layout('layouts.app');
    }
}
