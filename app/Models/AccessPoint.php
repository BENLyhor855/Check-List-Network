<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AccessPoint extends Model {
    protected $fillable = ['building_id','integration_id','name','vendor','model','serial','mac','ip_address','status','observed_at'];
    protected function casts(): array { return ['observed_at'=>'datetime']; }
    public function building(): BelongsTo { return $this->belongsTo(Building::class); }
}
