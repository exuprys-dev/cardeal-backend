<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'brand',
        'model',
        'year',
        'mileage',
        'price',
        'fuel_type',
        'transmission',
        'condition',
        'status',
        'description',
        'color',
        'engine',
        'doors',
        'user_id',
    ];

    protected $dates = ['created_at', 'updated_at'];

    protected $casts = [
        'year' => 'integer',
        'mileage' => 'integer',
        'price' => 'decimal:2',
        'doors' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->hasMany(VehicleImage::class);
    }
}
