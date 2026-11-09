<?php
namespace App\Livewire;

use App\Models\AccessPoint;
use App\Models\Building;
use App\Models\Integration;
use Illuminate\Support\Carbon;
use Livewire\Component;

class Overview extends Component {
    public function render() {
        $total=AccessPoint::count();
        $online=AccessPoint::where('status','online')->whereNotNull('observed_at')->count();
        $offline=AccessPoint::where('status','offline')->whereNotNull('observed_at')->count();
        $lastImport=Integration::where('vendor','meraki')->max('last_synced_at');
        return view('livewire.overview', [
            'buildings'=>Building::where('active',true)->count(),
            'accessPoints'=>$total,
            'online'=>$online,
            'offline'=>$offline,
            'unknown'=>max(0,$total-$online-$offline),
            'lastImport'=>$lastImport ? Carbon::parse($lastImport,'UTC')->utc() : null,
            'sites'=>Building::withCount('accessPoints')->orderBy('name')->limit(10)->get(),
        ])->layout('layouts.app');
    }
}
