<?php
namespace App\Features\OfflineSync\Models;
use Illuminate\Database\Eloquent\Model;
class OfflineSync extends Model {
    protected $table='offline_sync'; protected $fillable=['userId','syncAt','status','payload']; protected $casts=['syncAt'=>'datetime','status'=>'string']; public $timestamps=true;
}
