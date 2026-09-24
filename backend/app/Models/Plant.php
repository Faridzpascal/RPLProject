<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Plant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'plant_name',
        'soil_threshold',
        'watering_duration',
        'automatic_watering',
    ];

    protected $casts = [
        'soil_threshold' => 'integer',
        'watering_duration' => 'integer',
        'automatic_watering' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sensorData()
    {
        return $this->hasMany(SensorData::class);
    }

    public function latestSensorData()
    {
        return $this->hasOne(SensorData::class)->latestOfMany();
    }

    public function pumpLogs()
    {
        return $this->hasMany(PumpLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Determine plant condition status based on moisture percentage
     * 0-30% = DRY
     * 31-60% = MODERATE
     * 61-100% = GOOD
     */
    public function getPlantStatus(int $moisture): array
    {
        if ($moisture <= $this->soil_threshold) {
            return [
                'status' => 'DRY',
                'badge' => 'danger',
                'message' => 'Your plant needs water immediately.'
            ];
        } elseif ($moisture <= 60) {
            return [
                'status' => 'MODERATE',
                'badge' => 'warning',
                'message' => 'Soil moisture level is moderate.'
            ];
        } else {
            return [
                'status' => 'GOOD',
                'badge' => 'success',
                'message' => 'Soil moisture level is optimal.'
            ];
        }
    }

    /**
     * Check if device is online (data received within past 45 seconds)
     */
    public function isDeviceOnline(?string $lastTimestamp): bool
    {
        if (!$lastTimestamp) {
            return false;
        }

        return Carbon::parse($lastTimestamp)->diffInSeconds(now()) <= 45;
    }
}
