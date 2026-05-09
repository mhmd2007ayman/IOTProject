<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    use HasFactory;

    protected $table = 'sensor_data';

    protected $fillable = [
        'temperature',
        'humidity',
        'flame',
        'gas',
    ];

    protected $casts = [
        'temperature' => 'float',
        'humidity'    => 'float',
        'flame'       => 'boolean',
        'gas'         => 'boolean',
    ];
}