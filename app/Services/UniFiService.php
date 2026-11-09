<?php
namespace App\Services;
use App\Models\Integration;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
class UniFiService {
    /** Verify access via the documented UniFi cloud connector; no client data is persisted. */
    public function testConnection(Integration $integration): void {
        if ($integration->vendor !== 'unifi') throw new RuntimeException('Unsupported provider.');
        $console=$integration->organization_id; $site=$integration->network_id; $key=$integration->credentials['api_key'] ?? '';
        if (!is_string($console) || !preg_match('/^[A-Za-z0-9_-]{1,128}$/D',$console) || !is_string($site) || !preg_match('/^[A-Za-z0-9_-]{1,128}$/D',$site) || !is_string($key) || $key==='') throw new RuntimeException('A valid console ID, site ID and API key are required.');
        try { $response=Http::baseUrl('https://api.ui.com')->acceptJson()->withHeaders(['X-API-Key'=>$key])->timeout(10)->connectTimeout(5)->withoutRedirecting()->get('/v1/connector/consoles/'.$console.'/proxy/network/integration/v1/sites/'.$site.'/clients',['offset'=>0,'limit'=>1]); }
        catch (ConnectionException $e) { throw new RuntimeException('Could not connect to UniFi. Check network access and try again.'); }
        if ($response->status() === 429) throw new RuntimeException('UniFi rate limit reached. Try again later.');
        if (!$response->successful()) throw new RuntimeException('UniFi rejected the request. Check the console, site, API key and permissions.');
        $data=$response->json(); if (!is_array($data) || !is_array($data['data'] ?? null)) throw new RuntimeException('UniFi returned an unexpected response.');
    }
}
