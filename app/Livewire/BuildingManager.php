<?php
namespace App\Livewire;
use App\Models\Building;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
class BuildingManager extends Component {
    public ?int $editing = null;
    public string $name='', $code='', $location='', $description='';
    public function mount(): void { Gate::authorize('administer'); }
    public function edit(int $id): void {
        Gate::authorize('administer'); $b=Building::findOrFail($id); $this->editing=$b->id; $this->name=$b->name; $this->code=$b->code; $this->location=$b->location??''; $this->description=$b->description??'';
    }
    public function cancel(): void { $this->reset(['editing','name','code','location','description']); $this->resetValidation(); }
    public function save(): void {
        Gate::authorize('administer');
        $data=$this->validate(['name'=>'required|string|max:120', 'code'=>['required','string','max:40',Rule::unique('buildings','code')->ignore($this->editing)], 'location'=>'nullable|string|max:255', 'description'=>'nullable|string|max:2000']);
        DB::transaction(function () use ($data) {
            if ($this->editing) { $building=Building::findOrFail($this->editing); $building->update($data); $action='building.updated'; }
            else { $building=Building::create($data); $action='building.created'; }
            DB::table('audit_logs')->insert(['user_id'=>auth()->id(),'action'=>$action,'target'=>(string)$building->id,'created_at'=>now()]);
        });
        $this->cancel(); session()->flash('message','Building saved.');
    }
    public function toggle(int $id): void { Gate::authorize('administer'); $building=Building::findOrFail($id); DB::transaction(function () use ($building) { $building->update(['active'=>!$building->active]); DB::table('audit_logs')->insert(['user_id'=>auth()->id(),'action'=>'building.status_changed','target'=>(string)$building->id,'created_at'=>now()]); }); }
    public function render() { Gate::authorize('administer'); return view('livewire.building-manager',['buildings'=>Building::withCount('accessPoints')->orderBy('name')->get()])->layout('layouts.app'); }
}
