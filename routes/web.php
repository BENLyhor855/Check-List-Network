<?php
use App\Http\Controllers\SessionController;
use App\Livewire\Overview;
use App\Livewire\BuildingManager;
use App\Livewire\DeviceManager;
use App\Livewire\AuditLogViewer;
use App\Livewire\UserManager;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Controllers\IntegrationController;
use Illuminate\Support\Facades\Route;
Route::middleware('guest')->group(function () { Route::get('/login',[SessionController::class,'create'])->name('login'); Route::post('/login',[SessionController::class,'store'])->name('login.store'); });
Route::middleware('auth')->group(function () {
    Route::post('/logout',[SessionController::class,'destroy'])->name('logout');
    Route::get('/', Overview::class)->name('overview');
    Route::get('/reports/inventory', [InventoryReportController::class,'index'])->name('reports.inventory');
    Route::get('/reports/inventory.csv', [InventoryReportController::class,'download'])->name('reports.inventory.download');
    Route::get('/admin/integrations', [IntegrationController::class,'index'])->middleware('can:manage-integrations')->name('admin.integrations');
    Route::post('/admin/integrations', [IntegrationController::class,'store'])->middleware('can:manage-integrations')->name('admin.integrations.store');
    Route::post('/admin/integrations/{integration}/test', [IntegrationController::class,'test'])->middleware('can:manage-integrations')->name('admin.integrations.test');
    Route::post('/admin/integrations/{integration}/sync', [IntegrationController::class,'sync'])->middleware('can:manage-integrations')->name('admin.integrations.sync');
    Route::get('/admin/users', UserManager::class)->middleware('can:manage-integrations')->name('admin.users');
    Route::post('/admin/users', [UserController::class,'store'])->middleware('can:manage-integrations')->name('admin.users.store');
    Route::get('/admin/audit', AuditLogViewer::class)->middleware('can:administer')->name('admin.audit');
    Route::get('/admin/devices', DeviceManager::class)->middleware('can:administer')->name('admin.devices');
    Route::get('/admin/buildings', BuildingManager::class)->middleware('can:administer')->name('admin.buildings');
});
