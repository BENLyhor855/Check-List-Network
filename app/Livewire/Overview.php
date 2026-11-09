<?php
namespace App\Livewire;
use App\Models\AccessPoint;
use App\Models\Building;
use Livewire\Component;
class Overview extends Component {
    public function render() {
        return view('livewire.overview', [
            'buildings'=>Building::where('active',true)->count(),
            'accessPoints'=>AccessPoint::count(),
            'observed'=>AccessPoint::whereNotNull('observed_at')->count(),
            'online'=>AccessPoint::where('status','online')->whereNotNull('observed_at')->count(),
            'offline'=>AccessPoint::where('status','offline')->whereNotNull('observed_at')->count(),
            'sites'=>Building::withCount('accessPoints')->orderBy('name')->limit(10)->get(),
        ])->layout('layouts.app');
    }
}
