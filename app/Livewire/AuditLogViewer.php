<?php
namespace App\Livewire;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
class AuditLogViewer extends Component {
    public function render() {
        Gate::authorize('administer');
        $items=DB::table('audit_logs as a')->leftJoin('users as u','u.id','=','a.user_id')->select('a.action','a.target','a.created_at','u.email')->orderByDesc('a.id')->limit(100)->get();
        return view('livewire.audit-log-viewer',['items'=>$items])->layout('layouts.app');
    }
}
