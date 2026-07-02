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
        'requested_time',
        'first_name',
        'last_name',
        'email',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
