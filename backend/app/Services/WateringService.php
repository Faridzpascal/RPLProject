<?php

namespace App\Services;

use App\Models\Plant;
use App\Models\PumpLog;
use Carbon\Carbon;

class WateringService
{
    /**
     * Cooldown period in seconds to prevent continuous flooding
     */
    protected int $cooldownSeconds = 45;

    /**
     * Evaluate soil moisture and trigger automatic watering if conditions are met
     */
    public function evaluateAutomaticWatering(Plant $plant, int $moisture): ?PumpLog
    {
        // 1. Check if automatic watering is turned on
        if (!$plant->automatic_watering) {
            return null;
        }

        // 2. Check if moisture is below threshold
        if ($moisture >= $plant->soil_threshold) {
            return null;
        }

        // 3. Check cooldown: is there any recent pump activation?
        $lastLog = $plant->pumpLogs()->first();
        if ($lastLog) {
            $secondsSinceLastWatering = abs(now()->diffInSeconds($lastLog->created_at));
            if ($secondsSinceLastWatering < $this->cooldownSeconds) {
                // Still in cooldown period
                return null;
            }
        }

        // 4. Trigger Automatic Watering Log
        return PumpLog::create([
            'plant_id' => $plant->id,
            'mode' => 'automatic',
            'duration' => $plant->watering_duration ?: 5,
            'status' => 'completed',
            'created_at' => now(),
        ]);
    }

    /**
     * Execute manual watering
     */
    public function executeManualWatering(Plant $plant, int $duration): array
    {
        // Cooldown check for manual watering to prevent spamming
        $lastLog = $plant->pumpLogs()->first();
        if ($lastLog) {
            $secondsSinceLastWatering = abs(now()->diffInSeconds($lastLog->created_at));
            if ($secondsSinceLastWatering < 10) {
                return [
                    'success' => false,
                    'message' => 'Pump is currently busy or cooling down. Please wait ' . (10 - $secondsSinceLastWatering) . ' seconds.',
                    'log' => null
                ];
            }
        }

        $log = PumpLog::create([
            'plant_id' => $plant->id,
            'mode' => 'manual',
            'duration' => $duration,
            'status' => 'completed',
            'created_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Manual watering completed successfully for ' . $duration . ' seconds.',
            'log' => $log
        ];
    }
}
