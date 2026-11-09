<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Building extends Model {
    protected $fillable = ['name','code','location','description','active'];
    protected function casts(): array { return ['active'=>'boolean']; }
    public function accessPoints(): HasMany { return $this->hasMany(AccessPoint::class); }
}
