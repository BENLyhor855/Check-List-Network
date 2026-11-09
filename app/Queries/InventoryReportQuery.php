<?php
namespace App\Queries;
use App\Models\AccessPoint;
use Illuminate\Database\Eloquent\Builder;
class InventoryReportQuery {
    public static function forFilters(array $filters): Builder {
        return AccessPoint::query()->with('building')
            ->when($filters['building'] ?? null, fn($q,$id)=>$q->where('building_id',$id))
            ->when($filters['vendor'] ?? null, fn($q,$vendor)=>$q->where('vendor',$vendor))
            ->orderBy('name')->orderBy('id');
    }
}
