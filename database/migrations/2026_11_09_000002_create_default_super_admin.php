<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void {
        $count=DB::table('users')->where('role','super_admin')->count();
        if ($count > 1) throw new RuntimeException('More than one super administrator exists. Resolve this before migrating; no users were changed.');
        if ($count === 0 && DB::table('users')->where('email','admin@example.com')->exists()) throw new RuntimeException('admin@example.com already belongs to another user. Resolve the email conflict before migrating.');
        Schema::table('users', function (Blueprint $table) { $table->boolean('password_setup_required')->default(false); });
        if ($count === 0) {
            DB::table('users')->insert(['name'=>'Super Administrator','email'=>'admin@example.com','password'=>Hash::make(Str::random(64)),'role'=>'super_admin','active'=>true,'password_setup_required'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
    }
    public function down(): void {
        // Keep all user accounts on rollback; this migration never deletes an administrator.
        Schema::table('users', function (Blueprint $table) { $table->dropColumn('password_setup_required'); });
    }
};
