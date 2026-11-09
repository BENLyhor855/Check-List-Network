<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) { $table->string('role', 32)->default('user'); $table->boolean('active')->default(true); });
        Schema::create('buildings', function (Blueprint $table) { $table->id(); $table->string('name',120); $table->string('code',40)->unique(); $table->string('location')->nullable(); $table->text('description')->nullable(); $table->boolean('active')->default(true); $table->timestamps(); });
        Schema::create('integrations', function (Blueprint $table) { $table->id(); $table->string('name',120); $table->string('vendor',20); $table->text('credentials'); $table->string('organization_id')->nullable(); $table->string('network_id')->nullable(); $table->timestamp('last_synced_at')->nullable(); $table->timestamps(); });
        Schema::create('audit_logs', function (Blueprint $table) { $table->id(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('action',80); $table->string('target',120); $table->timestamp('created_at')->useCurrent(); $table->index(['created_at','action']); });
        Schema::create('access_points', function (Blueprint $table) { $table->id(); $table->foreignId('building_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('integration_id')->nullable()->constrained()->nullOnDelete(); $table->string('name'); $table->string('vendor',20); $table->string('model')->nullable(); $table->string('serial')->nullable(); $table->string('mac',32)->nullable(); $table->ipAddress('ip_address')->nullable(); $table->string('status',20)->nullable(); $table->timestamp('observed_at')->nullable(); $table->timestamps(); $table->unique(['vendor','serial']); $table->index(['building_id','status']); });
    }
    public function down(): void { Schema::dropIfExists('access_points'); Schema::dropIfExists('audit_logs'); Schema::dropIfExists('integrations'); Schema::dropIfExists('buildings'); Schema::table('users', function (Blueprint $table) { $table->dropColumn(['role','active']); }); }
};
