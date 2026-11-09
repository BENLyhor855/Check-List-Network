<?php
namespace App\Services;
use App\Models\Integration;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
class MerakiService {
    /** Test access without exposing provider response bodies or credentials to users/logs. */
    public function testConnection(Integration $integration): void {
        if ($integration->vendor !== 'meraki') throw new RuntimeException('Unsupported provider.');
        $key=$integration->credentials['api_key'] ?? '';
        if (!is_string($key) || $key === '') throw new RuntimeException('API key is missing.');
        try {
            $response=Http::baseUrl('https://api.meraki.com/api/v1')->acceptJson()->withToken($key)->timeout(10)->connectTimeout(5)->withoutRedirecting()->get('/organizations');
        } catch (ConnectionException $e) { throw new RuntimeException('Could not connect to Meraki. Check network access and try again.'); }
        if ($response->status() === 429) throw new RuntimeException('Meraki rate limit reached. Try again later.');
        if (!$response->successful()) throw new RuntimeException('Meraki rejected the request. Check the API key and its permissions.');
        if (!is_array($response->json())) throw new RuntimeException('Meraki returned an unexpected response.');
    }
    /** Read AP inventory only; does not infer online status or remove missing records. */
    public function syncDevices(Integration $integration, int $actorId): int {
        if ($integration->vendor !== 'meraki') throw new RuntimeException('Unsupported provider.');
        $network=$integration->network_id;
        $key=$integration->credentials['api_key'] ?? '';
        if (!is_string($network) || !preg_match('/^[A-Za-z0-9_-]{1,128}$/D',$network) || !is_string($key) || $key === '') throw new RuntimeException('A valid network ID and API key are required.');
        try { $response=Http::baseUrl('https://api.meraki.com/api/v1')->acceptJson()->withToken($key)->timeout(20)->connectTimeout(5)->withoutRedirecting()->get('/networks/'.$network.'/devices'); }
        catch (ConnectionException $e) { throw new RuntimeException('Could not connect to Meraki. Check network access and try again.'); }
        if ($response->status() === 429) throw new RuntimeException('Meraki rate limit reached. Try again later.');
        if (!$response->successful()) throw new RuntimeException('Meraki rejected the request. Check the network ID, API key and permissions.');
        if (str_contains((string)$response->header('Link'), 'rel="next"')) throw new RuntimeException('The response has additional pages. Synchronization was not applied.');
        $devices=$response->json();
        if (!is_array($devices) || !array_is_list($devices)) throw new RuntimeException('Meraki returned an unexpected response.');
        return \Illuminate\Support\Facades\DB::transaction(function () use ($devices,$integration,$actorId) {
            $count=0;
            foreach($devices as $device) {
                if (!is_array($device) || !is_string($device['serial'] ?? null) || !is_string($device['model'] ?? null) || !str_starts_with(strtoupper($device['model']),'MR')) continue;
                $serial=$device['serial']; $model=$device['model'];
                if (strlen($serial)>255 || strlen($model)>255 || $serial==='') throw new RuntimeException('Meraki returned an invalid device identifier.');
                $ip=$device['lanIp'] ?? null; if (!is_string($ip) || !filter_var($ip,FILTER_VALIDATE_IP)) $ip=null;
                $name=$device['name'] ?? $serial; if (!is_string($name) || $name==='') $name=$serial;
                $mac=$device['mac'] ?? null; if (!is_string($mac) || strlen($mac)>32) $mac=null;
                \App\Models\AccessPoint::updateOrCreate(['vendor'=>'meraki','serial'=>$serial], ['integration_id'=>$integration->id,'name'=>mb_substr($name,0,255),'model'=>$model,'mac'=>$mac,'ip_address'=>$ip]);
                $count++;
            }
            $integration->last_synced_at=now(); $integration->save();
            \Illuminate\Support\Facades\DB::table('audit_logs')->insert(['user_id'=>$actorId,'action'=>'integration.inventory_synced','target'=>(string)$integration->id,'created_at'=>now()]);
            return $count;
        });
    }

}
