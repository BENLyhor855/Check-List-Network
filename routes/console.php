<?php
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

Artisan::command('wifi:create-admin {email}', function (string $email) {
    if (User::where('role','super_admin')->exists()) { $this->error('A super administrator already exists.'); return 1; }
    $password = $this->secret('New administrator password (at least 14 characters)');
    $input=['email'=>Str::lower(trim($email)), 'password'=>$password];
    $v=Validator::make($input,['email'=>['required','email','max:254','unique:users,email'], 'password'=>['required','string',Password::min(14), 'max:72']]);
    if($v->fails()){ $this->error($v->errors()->first()); return 1; }
    DB::transaction(function () use ($input) { $user = new User(['name'=>'Administrator','email'=>$input['email'],'password'=>Hash::make($input['password'])]); $user->role='super_admin'; $user->active=true; $user->save(); });
    $this->info('Administrator created.'); return 0;
})->purpose('Create the first administrator');
