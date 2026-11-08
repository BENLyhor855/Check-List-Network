<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NetworkApiTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        $this->withCredentials();
        config(['app.url' => 'http://localhost:4200', 'cache.default' => 'array', 'session.secure' => false]);
        DB::unprepared('CREATE TABLE roles (id INTEGER PRIMARY KEY, name TEXT NOT NULL); CREATE TABLE permissions (id INTEGER PRIMARY KEY, name TEXT NOT NULL); CREATE TABLE role_permissions (role_id INTEGER, permission_id INTEGER); CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT UNIQUE, password_hash TEXT, role_id INTEGER, active INTEGER DEFAULT 1); CREATE TABLE sessions (token_hash TEXT PRIMARY KEY, user_id INTEGER, expires_at TEXT); CREATE TABLE buildings (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE); CREATE TABLE locations (id INTEGER PRIMARY KEY AUTOINCREMENT, building_id INTEGER, name TEXT, FOREIGN KEY(building_id) REFERENCES buildings(id)); CREATE TABLE devices (id INTEGER PRIMARY KEY AUTOINCREMENT, asset_id TEXT UNIQUE, name TEXT, manufacturer TEXT, model TEXT, serial_number TEXT, management_ip TEXT, category TEXT, location_id INTEGER, status TEXT DEFAULT "unknown", notes TEXT, FOREIGN KEY(location_id) REFERENCES locations(id)); CREATE TABLE audit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, action TEXT, target TEXT, result TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');
        DB::table('roles')->insert([['id'=>1,'name'=>'Super Administrator'], ['id'=>6,'name'=>'Read-only Viewer']]);
        foreach (['inventory.read','inventory.write','users.manage','audit.read'] as $i => $name) { DB::table('permissions')->insert(['id'=>$i+1,'name'=>$name]); DB::table('role_permissions')->insert(['role_id'=>1,'permission_id'=>$i+1]); }
        DB::table('role_permissions')->insert(['role_id'=>6,'permission_id'=>1]);
        DB::table('users')->insert([['id'=>1,'email'=>'admin@example.test','password_hash'=>password_hash('correct-horse-battery', PASSWORD_BCRYPT), 'role_id'=>1,'active'=>1],['id'=>2,'email'=>'viewer@example.test','password_hash'=>password_hash('correct-horse-battery', PASSWORD_BCRYPT), 'role_id'=>6,'active'=>1]]);
    }
    private function token(int $id = 1): string { $token = str_repeat((string)$id, 64); DB::table('sessions')->insert(['token_hash'=>hash('sha256',$token),'user_id'=>$id,'expires_at'=>now()->addHour()]); return $token; }
    public function test_login_existing_bcrypt_session_and_logout(): void {
        $this->withHeader('Origin','http://localhost:4200')->postJson('/api/v1/auth/login',['email'=>'admin@example.test','password'=>'wrong'])->assertStatus(401);
        $response = $this->withHeader('Origin','http://localhost:4200')->postJson('/api/v1/auth/login',['email'=>'admin@example.test','password'=>'correct-horse-battery'])->assertOk();
        $token = $response->getCookie('rac_session', false)->getValue();
        $this->withUnencryptedCookie('rac_session',$token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('email','admin@example.test');
        $this->withUnencryptedCookie('rac_session',$token)->call('POST','/api/v1/auth/logout',[],['rac_session'=>$token],[],['HTTP_ORIGIN'=>'http://localhost:4200','CONTENT_TYPE'=>'application/json'],'{}')->assertNoContent();
        $this->withUnencryptedCookie('rac_session',$token)->getJson('/api/v1/auth/me')->assertStatus(401);
    }
    public function test_inventory_and_audit_are_transactional_and_viewer_is_read_only(): void {
        $token=$this->token();
        $this->withUnencryptedCookie('rac_session',$token)->withHeader('Origin','http://localhost:4200')->postJson('/api/v1/buildings',['name'=>'Main'])->assertCreated();
        $this->assertDatabaseHas('audit_logs',['action'=>'buildings.create','target'=>'1']);
        $this->withUnencryptedCookie('rac_session',$token)->withHeader('Origin','http://localhost:4200')->postJson('/api/v1/buildings',['name'=>'Main'])->assertStatus(409);
        $this->assertSame  (1, DB::table('audit_logs')->count());
        $this->withUnencryptedCookie('rac_session',$this->token(2))->withHeader('Origin','http://localhost:4200')->postJson('/api/v1/buildings',['name'=>'Other'])->assertStatus(403);
        $this->withUnencryptedCookie('rac_session',str_repeat('2', 64))->getJson('/api/v1/audit')->assertStatus(403);
    }
    public function test_origin_validation_and_revocation(): void {
        $token=$this->token();
        $this->withUnencryptedCookie('rac_session',$token)->withHeader('Origin','http://evil.test')->postJson('/api/v1/buildings',['name'=>'No'])->assertStatus(403);
        $this->withUnencryptedCookie('rac_session',$token)->getJson('/api/v1/buildings?offset=-1')->assertStatus(400);
        $viewer=$this->token(2);
        $this->withUnencryptedCookie('rac_session',$token)->withHeader('Origin','http://localhost:4200')->patchJson('/api/v1/users/2',['role_id'=>6,'active'=>false])->assertOk();
        $this->withUnencryptedCookie('rac_session',$viewer)->getJson('/api/v1/auth/me')->assertStatus(401);
    }
}
