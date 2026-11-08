<?php
namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Cookie;

class NetworkController extends Controller
{
    private function error(string $message, int $status) { return response()->json(['error' => $message], $status); }
    private function actor(Request $request) {
        $token = $request->cookie('rac_session');
        if (!is_string($token) || $token === '') return null;
        return DB::table('sessions as s')->join('users as u', 'u.id', '=', 's.user_id')->join('roles as r', 'r.id', '=', 'u.role_id')
            ->where('s.token_hash', hash('sha256', $token))->where('s.expires_at', '>', now('UTC'))->where('u.active', true)
            ->select('u.id', 'u.email', 'r.name as role')->first();
    }
    private function allowed($actor, string $permission): bool {
        return DB::table('users as u')->join('role_permissions as rp', 'rp.role_id', '=', 'u.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')->where('u.id', $actor->id)->where('p.name', $permission)->exists();
    }
    private function audit(int $user, string $action, string $target): void {
        DB::table('audit_logs')->insert(['user_id' => $user, 'action' => $action, 'target' => $target, 'result' => 'success']);
    }
    private function cookie(string $value, int $expires): Cookie {
        return Cookie::create('rac_session', $value, $expires, '/', null, (bool) config('session.secure'), true, false, 'strict');
    }
    private function body(Request $request, array $allowed): ?array {
        $body = json_decode($request->getContent(), true);
        return array_diff(array_keys($body), $allowed) ? null : $body;
    }
    public function live() { return response()->json(['status' => 'alive']); }
    public function ready() {
        try { DB::connection()->getPdo(); return response()->json(['status' => 'ready']); }
        catch (\Throwable $e) { return $this->error('database unavailable', 503); }
    }
    public function login(Request $request) {
        $in = $this->body($request, ['email', 'password']);
        if ($in === null || !is_string($in['email'] ?? null) || !is_string($in['password'] ?? null)) return $this->error('invalid JSON request', 400);
        $user = DB::table('users')->where('email', strtolower(trim($in['email'])))->where('active', true)->first();
        $dummy = '$2a$12$R9h/cIPz0gi.URNNX3kh2OPST9/PgBkqquzi.Ss7KIUgO2t0jWMUW';
        if (!password_verify($in['password'], $user->password_hash ?? $dummy) || !$user) return $this->error('invalid credentials', 401);
        $token = bin2hex(random_bytes(32));
        DB::transaction(function () use ($user, $token) {
            DB::table('sessions')->insert(['token_hash' => hash('sha256', $token), 'user_id' => $user->id, 'expires_at' => now('UTC')->addHours(8)]);
            $this->audit($user->id, 'auth.login', 'session');
        });
        return response()->json(['status' => 'authenticated'])->withCookie($this->cookie($token, time() + 28800));
    }
    public function logout(Request $request) {
        $token = $request->cookie('rac_session');
        if (is_string($token) && $token !== '') DB::table('sessions')->where('token_hash', hash('sha256', $token))->delete();
        return response('', 204)->withCookie($this->cookie('', time() - 3600));
    }
    public function me(Request $request) { $actor = $this->actor($request); return $actor ? response()->json($actor) : $this->error('authentication required', 401); }
    public function inventory(Request $request, string $resource) {
        $actor = $this->actor($request);
        if (!$actor) return $this->error('authentication required', 401);
        $permission = $resource === 'audit' ? 'audit.read' : ($request->isMethod('post') ? 'inventory.write' : 'inventory.read');
        if (!$this->allowed($actor, $permission)) return $this->error('permission denied', 403);
        if ($request->isMethod('get')) {
            $offset = $request->query('offset', '0');
            if (!is_string($offset) || !preg_match('/^\d+$/D', $offset) || (float) $offset > 1000000) return $this->error('invalid offset', 400);
            $query = match ($resource) {
                'buildings' => DB::table('buildings')->select('id', 'name')->orderBy('id'),
                'locations' => DB::table('locations')->select('id', 'building_id', 'name')->orderBy('id'),
                'devices' => DB::table('devices as d')->join('locations as l', 'l.id', '=', 'd.location_id')->join('buildings as b', 'b.id', '=', 'l.building_id')->select('d.id', 'd.asset_id', 'd.name', 'd.manufacturer', 'd.model', 'd.management_ip', 'd.category', 'd.status', 'l.name as location', 'b.name as building')->orderBy('d.id'),
                'audit' => DB::table('audit_logs')->select('id', 'user_id', 'action', 'target', 'result', 'created_at')->orderByDesc('id'),
            };
            return response()->json(['items' => $query->offset((int)$offset)->limit(100)->get(), 'limit' => 100, 'offset' => (int)$offset]);
        }
        if ($resource === 'audit') return $this->error('read only', 405);
        $keys = ['name','building_id','location_id','asset_id','manufacturer','model','serial_number','management_ip','category','notes'];
        $in = $this->body($request, $keys);
        if ($in === null) return $this->error('invalid JSON request', 400);
        foreach ($keys as $key) if (isset($in[$key]) && !in_array($key, ['building_id','location_id'], true) && !is_string($in[$key])) return $this->error('invalid JSON request', 400);
        $name = trim($in['name'] ?? '');
        if ($name === '' || strlen($name) > 120) return $this->error('name required, maximum 120 bytes', 400);
        $data = ['name' => $name];
        if ($resource === 'locations') {
            if (!is_int($in['building_id'] ?? null) || $in['building_id'] < 1) return $this->error('building required', 400);
            $data['building_id'] = $in['building_id'];
        }
        if ($resource === 'devices') {
            $asset = $in['asset_id'] ?? ''; $category = $in['category'] ?? ''; $ip = $in['management_ip'] ?? '';
            if (trim($asset) === '' || strlen($asset) > 80 || !is_int($in['location_id'] ?? null) || $in['location_id'] < 1 || $category === '' || strlen($category) > 50 || strlen($in['manufacturer'] ?? '') > 80 || strlen($in['model'] ?? '') > 80 || strlen($in['serial_number'] ?? '') > 120 || ($ip !== '' && !filter_var($ip, FILTER_VALIDATE_IP))) return $this->error('valid asset ID, location, category and management IP required', 400);
            $data += ['asset_id' => $asset, 'location_id' => $in['location_id'], 'category' => $category, 'manufacturer' => $in['manufacturer'] ?? '', 'model' => $in['model'] ?? '', 'serial_number' => $in['serial_number'] ?? '', 'management_ip' => $ip, 'notes' => $in['notes'] ?? ''];
        }
        try {
            $id = DB::transaction(function () use ($resource, $data, $actor) { $id = DB::table($resource)->insertGetId($data); $this->audit($actor->id, $resource.'.create', (string)$id); return $id; });
            return response()->json(['id' => $id], 201);
        } catch (QueryException $e) { if (in_array($e->errorInfo[0] ?? '', ['23000', '23505'], true)) return $this->error('record conflicts or referenced location/building does not exist', 409); throw $e; }
    }
    public function users(Request $request, ?string $id = null) {
        $actor = $this->actor($request);
        if (!$actor) return $this->error('authentication required', 401);
        if (!$this->allowed($actor, 'users.manage')) return $this->error('permission denied', 403);
        if ($request->isMethod('get')) return response()->json(['items' => DB::table('users as u')->join('roles as r', 'r.id', '=', 'u.role_id')->select('u.id','u.email','r.name as role','u.active')->orderBy('u.id')->limit(100)->get()]);
        $in = $this->body($request, ['email','password','role_id','active']);
        if ($in === null) return $this->error('invalid JSON request', 400);
        if (!is_int($in['role_id'] ?? null) || $in['role_id'] < 1 || $in['role_id'] > 6) return $this->error('role_id must be 1 through 6', 400);
        if ($request->isMethod('post')) {
            $email = $in['email'] ?? null; $password = $in['password'] ?? null;
            if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254 || !is_string($password) || strlen($password) < 14 || strlen($password) > 72) return $this->error('valid email and 14–72 byte password required', 400);
            try { $target = DB::transaction(function () use ($in, $email, $password, $actor) {
                $id = DB::table('users')->insertGetId(['email' => strtolower($email), 'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), 'role_id' => $in['role_id'], 'active' => true]);
                $this->audit($actor->id, 'users.post', (string)$id); return $id;
            }); return response()->json(['id' => $target], 201); }
            catch (QueryException $e) { if (($e->errorInfo[0] ?? '') === '23000') return $this->error('email conflicts', 409); throw $e; }
        }
        if (!$id || !ctype_digit($id) || (int)$id < 1 || (int)$id === (int)$actor->id || !is_bool($in['active'] ?? null)) return $this->error('valid other user ID and active state required', 400);
        $target = (int)$id;
        $updated = DB::transaction(function () use ($in, $actor, $target) {
            $current = DB::table('users')->where('id', $target)->lockForUpdate()->first();
            if (!$current || ((int)$current->role_id === $in['role_id'] && (bool)$current->active === $in['active'])) return false;
            DB::table('users')->where('id', $target)->update(['role_id' => $in['role_id'], 'active' => $in['active']]);
            DB::table('sessions')->where('user_id', $target)->delete();
            $this->audit($actor->id, 'users.patch', (string)$target); return true;
        });
        return $updated ? response()->json(['id' => $target]) : $this->error('user missing or unchanged', 404);
    }
}
