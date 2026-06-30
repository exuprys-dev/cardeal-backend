<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestDriveRequest extends Model
{
    protected $fillable = [
        'vehicle_id',
        'requested_date',
        'phone',
        'status',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
