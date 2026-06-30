<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VehicleImage extends Model
{
    use HasFactory;

    protected $table = 'vehicle_images';

    protected $fillable = ['vehicle_id', 'path', 'is_main'];

    public $timestamps = false;

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
