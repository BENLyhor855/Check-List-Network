<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class SessionController extends Controller {
    public function create() { return view('auth.login'); }
    public function store(Request $request) {
        $data=$request->validate(['email'=>['required','email','max:254'],'password'=>['required','string']]);
        $key=Str::lower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) throw ValidationException::withMessages(['email'=>'Too many attempts. Please try again later.']);
        if (!Auth::attempt(['email'=>Str::lower($data['email']),'password'=>$data['password'],'active'=>true])) { RateLimiter::hit($key,60); throw ValidationException::withMessages(['email'=>'Invalid credentials.']); }
        RateLimiter::clear($key); $request->session()->regenerate(); return redirect()->intended(route('overview'));
    }
    public function destroy(Request $request) { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('login'); }
}
