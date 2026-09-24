<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorData extends Model
{
    use HasFactory;

    public $timestamps = false;
    
    protected $table = 'sensor_data';

    protected $fillable = [
        'plant_id',
        'soil_moisture',
        'created_at',
    ];

    protected $casts = [
        'soil_moisture' => 'integer',
        'created_at' => 'datetime',
    ];

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }
}
