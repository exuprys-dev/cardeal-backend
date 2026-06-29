<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactRequest extends Model
{
    //
    protected $table = 'contact_requests';

    protected $fillable = [
        'vehicle_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'message',
        'is_read',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
