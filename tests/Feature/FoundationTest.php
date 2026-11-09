<?php
namespace Tests\Feature;
use App\Livewire\BuildingManager;
use App\Livewire\DeviceManager;
use App\Livewire\UserManager;
use App\Models\AccessPoint;
use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;
class FoundationTest extends TestCase {
    use RefreshDatabase;
    private function user(string $role, bool $active=true): User { $user=User::factory()->create(); $user->role=$role; $user->active=$active; $user->save(); return $user; }
    public function test_guests_are_redirected_and_authentication_rejects_bad_password(): void {
        $this->get('/')->assertRedirect('/login');
        $user=$this->user('user');
        $this->post('/login',['email'=>$user->email,'password'=>'incorrect'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
    public function test_management_cannot_access_admin_but_can_view_overview(): void {
        $user=$this->user('management');
        $this->actingAs($user)->get('/')->assertOk()->assertSee('Provider data is not connected yet');
        $this->actingAs($user)->get('/admin/buildings')->assertForbidden();
        Livewire::actingAs($user)->test(BuildingManager::class)->assertForbidden();
    }
    public function test_administrator_can_create_edit_and_disable_building(): void {
        $admin=$this->user('it_admin');
        Livewire::actingAs($admin)->test(BuildingManager::class)->set('name','Main')->set('code','MAIN')->set('location','Campus')->call('save')->assertHasNoErrors();
        $building=Building::where('code','MAIN')->firstOrFail();
        Livewire::actingAs($admin)->test(BuildingManager::class)->call('edit',$building->id)->set('name','Main Building')->call('save')->assertHasNoErrors()->call('toggle',$building->id);
        $this->assertDatabaseHas('buildings',['id'=>$building->id,'name'=>'Main Building','active'=>false]);
    }
    public function test_inactive_user_cannot_keep_an_existing_session(): void {
        $user=$this->user('super_admin',false);
        $this->actingAs($user)->get('/')->assertRedirect('/login'); $this->assertGuest();
    }
    public function test_administrator_can_assign_device_to_building(): void {
        $admin=$this->user('it_admin');
        $b=Building::create(['name'=>'First','code'=>'FIRST','active'=>true]);
        $ap=AccessPoint::create(['name'=>'AP','vendor'=>'meraki','serial'=>'Q1']);
        $this->actingAs($admin)->get('/admin/devices')->assertOk()->assertSee('AP');
        Livewire::actingAs($admin)->test(DeviceManager::class)->set('buildingAssignments.'.$ap->id,(string)$b->id)->call('assign',$ap->id)->assertHasNoErrors();
        $this->assertDatabaseHas('access_points',['id'=>$ap->id,'building_id'=>$b->id]);
    }

    public function test_only_super_admin_can_manage_users_and_role_changes_revoke_sessions(): void {
        $admin=$this->user('super_admin'); $it=$this->user('it_admin');
        $this->actingAs($it)->get('/admin/users')->assertForbidden();
        $payload=['name'=>'Manager','email'=>'manager@example.test','password'=>'a-very-long-password','role'=>'management'];
        $this->actingAs($admin)->post('/admin/users',$payload)->assertRedirect();
        $target=User::where('email','manager@example.test')->firstOrFail();
        $this->assertSame('management',$target->role);
        DB::table('sessions')->insert(['id'=>'test-session','user_id'=>$target->id,'payload'=>'','last_activity'=>time()]);
        Livewire::actingAs($admin)->test(UserManager::class)->call('setRole',$target->id,'user')->assertHasNoErrors();
        $this->assertDatabaseMissing('sessions',['id'=>'test-session']);
        $this->assertDatabaseHas('audit_logs',['action'=>'user.role_changed','target'=>(string)$target->id]);
        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertDontSee('a-very-long-password');
    }

    public function test_inventory_reports_filter_and_protect_csv_from_formulas(): void {
        $user=$this->user('management');
        $b=Building::create(['name'=>'Campus','code'=>'CAMPUS','active'=>true]);
        AccessPoint::create(['name'=>'=CMD()','vendor'=>'meraki','serial'=>'Q1','building_id'=>$b->id]);
        AccessPoint::create(['name'=>'Other','vendor'=>'unifi','serial'=>'U1']);
        $this->actingAs($user)->get('/reports/inventory?vendor=meraki')->assertOk()->assertSee('=CMD()')->assertDontSee('Other');
        $csv=$this->actingAs($user)->get('/reports/inventory.csv?vendor=meraki')->assertOk()->streamedContent();
        $this->assertStringContainsString("'=CMD()",$csv); $this->assertStringNotContainsString('Other',$csv);
    }

    public function test_excel_and_pdf_exports_are_authenticated_filtered_and_formula_safe(): void {
        $this->get('/reports/inventory.xlsx')->assertRedirect('/login');
        $this->get('/reports/inventory.pdf')->assertRedirect('/login');
        $user=$this->user('management');
        $building=Building::create(['name'=>'Campus','code'=>'CAMPUS','active'=>true]);
        AccessPoint::create(['name'=>'=1+1','vendor'=>'meraki','serial'=>'M1','building_id'=>$building->id]);
        AccessPoint::create(['name'=>'Excluded AP','vendor'=>'unifi','serial'=>'U1']);
        $response=$this->actingAs($user)->get('/reports/inventory.xlsx?vendor=meraki')->assertOk();
        $this->assertStringContainsString('spreadsheetml',$response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store',$response->headers->get('Cache-Control'));
        $sheet=\PhpOffice\PhpSpreadsheet\IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet();
        $this->assertSame('Name',$sheet->getCell('A1')->getValue());
        $this->assertSame('=1+1',$sheet->getCell('A2')->getValue());
        $this->assertSame(\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING,$sheet->getCell('A2')->getDataType());
        $this->assertSame('Unavailable',$sheet->getCell('F2')->getValue());
        $this->assertNull($sheet->getCell('A3')->getValue());
        $pdf=$this->actingAs($user)->get('/reports/inventory.pdf?vendor=meraki')->assertOk();
        $this->assertStringContainsString('application/pdf',$pdf->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->assertStringContainsString('no-store',$pdf->headers->get('Cache-Control'));
        $this->actingAs($user)->get('/reports/inventory.xlsx?vendor=invalid')->assertSessionHasErrors('vendor');
    }

}
