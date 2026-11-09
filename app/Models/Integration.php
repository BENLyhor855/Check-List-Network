<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Integration extends Model {
    protected $fillable = ['name','vendor','credentials','organization_id','network_id'];
    protected $hidden = ['credentials'];
    protected function casts(): array { return ['credentials'=>'encrypted:array','last_synced_at'=>'datetime']; }
}
