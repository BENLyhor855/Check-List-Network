<?php
namespace Tests\Feature;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
class IntegrationTest extends TestCase {
    use RefreshDatabase;
    private function user(string $role): User { $u=User::factory()->create(); $u->role=$role; $u->save(); return $u->fresh(); }
    public function test_only_super_admin_can_store_and_test_encrypted_credentials(): void {
        $admin=$this->user('super_admin'); $manager=$this->user('management'); $it=$this->user('it_admin');
        $key='meraki-secret-test-key';
        $this->actingAs($manager)->post('/admin/integrations',['name'=>'Main','vendor'=>'meraki','api_key'=>$key])->assertForbidden();
        $this->actingAs($it)->get('/admin/integrations')->assertForbidden();
        $this->actingAs($admin)->post('/admin/integrations',['name'=>'Main','vendor'=>'meraki','api_key'=>$key])->assertRedirect('/admin/integrations');
        $integration=Integration::firstOrFail();
        $this->assertStringNotContainsString($key,DB::table('integrations')->value('credentials'));
        $this->assertArrayNotHasKey('credentials',$integration->toArray());
        $this->actingAs($admin)->get('/admin/integrations')->assertOk()->assertDontSee($key);
        Http::fake(['api.meraki.com/*'=>Http::response([['id'=>'1','name'=>'Organization']],200)]);
        $this->actingAs($admin)->post('/admin/integrations/'.$integration->id.'/test')->assertSessionHas('message','Meraki connection succeeded.');
        Http::assertSent(fn ($request) => $request->url()==='https://api.meraki.com/api/v1/organizations' && $request->hasHeader('Authorization','Bearer '.$key));
    }
    public function test_provider_failure_does_not_expose_response_or_key(): void {
        $admin=$this->user('super_admin'); $key='meraki-secret-test-key';
        $i=Integration::create(['name'=>'Main','vendor'=>'meraki','credentials'=>['api_key'=>$key]]);
        Http::fake(['api.meraki.com/*'=>Http::response(['secret'=>$key],401)]);
        $response=$this->actingAs($admin)->post('/admin/integrations/'.$i->id.'/test');
        $response->assertSessionHas('connection_error','Meraki rejected the request. Check the API key and its permissions.');
        $this->assertStringNotContainsString($key,session('connection_error'));
    }
    public function test_meraki_sync_imports_only_mr_devices_and_is_idempotent(): void {
        $admin=$this->user('super_admin');
        $i=Integration::create(['name'=>'Main','vendor'=>'meraki','network_id'=>'N_test','credentials'=>['api_key'=>'meraki-secret-test-key']]);
        Http::fake(['api.meraki.com/*'=>Http::response([
            ['serial'=>'Q123-4567-ABCD','model'=>'MR44','name'=>'Lobby','mac'=>'00:11:22:33:44:55','lanIp'=>'192.0.2.4'],
            ['serial'=>'Q123-4567-ABCE','model'=>'MS120','name'=>'Switch']
        ],200)]);
        $this->actingAs($admin)->post('/admin/integrations/'.$i->id.'/sync')->assertSessionHas('message','Inventory sync completed: 1 access points processed. Status remains unknown.');
        $this->actingAs($admin)->post('/admin/integrations/'.$i->id.'/sync')->assertSessionHas('message');
        $this->assertDatabaseCount('access_points',1);
        $this->assertDatabaseHas('access_points',['serial'=>'Q123-4567-ABCD','model'=>'MR44','status'=>null]);
    }
    public function test_incomplete_paginated_inventory_does_not_change_devices(): void {
        $admin=$this->user('super_admin');
        $i=Integration::create(['name'=>'Main','vendor'=>'meraki','network_id'=>'N_test','credentials'=>['api_key'=>'meraki-secret-test-key']]);
        Http::fake(['api.meraki.com/*'=>Http::response([['serial'=>'Q1','model'=>'MR44']],200,['Link'=>'<https://api.meraki.com/next>; rel="next"'])]);
        $this->actingAs($admin)->post('/admin/integrations/'.$i->id.'/sync')->assertSessionHas('connection_error');
        $this->assertDatabaseCount('access_points',0);
    }

    public function test_unifi_cloud_connector_is_tested_without_exposing_clients(): void {
        $admin=$this->user('super_admin');
        $i=Integration::create(['name'=>'UniFi','vendor'=>'unifi','organization_id'=>'console-1','network_id'=>'site-1','credentials'=>['api_key'=>'unifi-secret-test-key']]);
        Http::fake(['api.ui.com/*'=>Http::response(['data'=>[['name'=>'Private client']],'count'=>1],200)]);
        $this->actingAs($admin)->post('/admin/integrations/'.$i->id.'/test')->assertSessionHas('message','Unifi connection succeeded.');
        Http::assertSent(fn($request)=>str_contains($request->url(),'api.ui.com/v1/connector/consoles/console-1/proxy/network/integration/v1/sites/site-1/clients') && $request->hasHeader('X-API-Key','unifi-secret-test-key'));
        $this->actingAs($admin)->get('/admin/integrations')->assertDontSee('Private client')->assertDontSee('unifi-secret-test-key');
    }

}
