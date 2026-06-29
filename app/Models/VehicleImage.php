<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleImage extends Model
{
    //

    protected $table = 'vehicle_images';

    protected $fillable = ['vehicle_id', 'path', 'is_main'];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
