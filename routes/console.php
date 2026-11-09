<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

Artisan::command('wifi:setup-admin', function () {
    $admin=DB::table('users')->where('email','admin@example.com')->where('role','super_admin')->first();
    if (!$admin || !$admin->password_setup_required) { $this->error('The default administrator is missing or its password is already set. Existing accounts were not changed.'); return 1; }
    $password=$this->secret('Set the password for admin@example.com (at least 14 characters)');
    $v=Validator::make(['password'=>$password],['password'=>['required','string',Password::min(14),'max:72']]);
    if ($v->fails() || strlen((string)$password) > 72) { $this->error('Use a password of at least 14 characters and at most 72 bytes.'); return 1; }
    $changed=DB::table('users')->where('id',$admin->id)->where('role','super_admin')->where('password_setup_required',true)->update(['password'=>Hash::make($password),'password_setup_required'=>false,'updated_at'=>now()]);
    if ($changed !== 1) { $this->error('The administrator changed during setup. No password was updated.'); return 1; }
    $this->info('Password set for admin@example.com.'); return 0;
})->purpose('Set the default super administrator password once');
