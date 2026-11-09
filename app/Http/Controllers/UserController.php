<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
class UserController extends Controller {
    public function store(Request $request) {
        Gate::authorize('manage-integrations');
        $input=$request->only('name','email','password','role');
        $input['email']=Str::lower(trim((string)($input['email'] ?? '')));
        $v=Validator::make($input,['name'=>'required|string|max:255','email'=>'required|email|max:254|unique:users,email','password'=>['required','string',Password::min(14),'max:72'],'role'=>'required|in:it_admin,management,user']);
        if ($v->fails()) return back()->withErrors($v);
        DB::transaction(function() use ($v) { $data=$v->validated(); $user=new User(['name'=>$data['name'],'email'=>$data['email'],'password'=>$data['password']]); $user->role=$data['role']; $user->active=true; $user->save(); DB::table('audit_logs')->insert(['user_id'=>auth()->id(),'action'=>'user.created','target'=>(string)$user->id,'created_at'=>now()]); });
        return back()->with('message','User created. Share the initial password securely.');
    }
}
