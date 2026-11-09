<?php
namespace App\Livewire;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
class UserManager extends Component {
    public string $name='', $email='', $role='user';
    // Password input uses a normal POST form so it is not persisted in a Livewire snapshot.
    public function setRole(int $id, string $role): void {
        Gate::authorize('manage-integrations');
        if (!in_array($role,['it_admin','management','user'],true)) abort(422);
        if ($id === (int)auth()->id()) { $this->addError('users','You cannot change your own role.'); return; }
        DB::transaction(function () use ($id,$role) {
            $user=User::whereKey($id)->lockForUpdate()->firstOrFail(); if ($user->role === 'super_admin') abort(403); $user->role=$role; $user->save();
            DB::table('sessions')->where('user_id',$id)->delete();
            DB::table('audit_logs')->insert(['user_id'=>auth()->id(),'action'=>'user.role_changed','target'=>(string)$id,'created_at'=>now()]);
        });
        session()->flash('message','Role updated; the user must sign in again.');
    }
    public function toggle(int $id): void {
        Gate::authorize('manage-integrations');
        if ($id === (int)auth()->id()) { $this->addError('users','You cannot disable your own account.'); return; }
        DB::transaction(function () use ($id) {
            $user=User::whereKey($id)->lockForUpdate()->firstOrFail(); if ($user->role === 'super_admin') abort(403); $user->active=!$user->active; $user->save();
            DB::table('sessions')->where('user_id',$id)->delete();
            DB::table('audit_logs')->insert(['user_id'=>auth()->id(),'action'=>'user.status_changed','target'=>(string)$id,'created_at'=>now()]);
        });
        session()->flash('message','Account status updated.');
    }
    public function render() { Gate::authorize('manage-integrations'); return view('livewire.user-manager',['users'=>User::orderBy('email')->get(['id','name','email','role','active'])])->layout('layouts.app'); }
}
