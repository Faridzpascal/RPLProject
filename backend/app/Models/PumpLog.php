<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PumpLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'pump_logs';

    protected $fillable = [
        'plant_id',
        'mode',
        'duration',
        'status',
        'created_at',
    ];

    protected $casts = [
        'duration' => 'integer',
        'created_at' => 'datetime',
    ];

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }
}
