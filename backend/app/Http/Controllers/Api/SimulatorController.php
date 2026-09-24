<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plant;
use App\Models\SensorData;
use App\Services\WateringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SimulatorController extends Controller
{
    protected WateringService $wateringService;

    public function __construct(WateringService $wateringService)
    {
        $this->wateringService = $wateringService;
    }

    /**
     * Generate or ingest simulated sensor data.
     * Accessible via POST /api/simulator/generate
     */
    public function generate(Request $request)
    {
        // 1. Validation according to requirement:
        // Must reject values outside 0 - 100% (e.g. 150 must fail with HTTP 422)
        $validator = Validator::make($request->all(), [
            'device_id' => 'nullable|string',
            'soil_moisture' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: Soil moisture must be an integer between 0 and 100.',
                'errors' => $validator->errors()
            ], 422);
        }

        $deviceId = $request->input('device_id', 'SP001');

        $plant = Plant::where('device_id', $deviceId)->first();

        if (!$plant) {
            return response()->json([
                'success' => false,
                'message' => "Plant with device_id '{$deviceId}' not found."
            ], 404);
        }

        // Determine soil moisture: use provided value or calculate realistic step
        if ($request->has('soil_moisture')) {
            $moisture = (int)$request->input('soil_moisture');
        } else {
            // Auto calculate realistic natural progression
            $latest = $plant->latestSensorData;
            if ($latest) {
                // Natural drying: decrease by 2-5%, or if low reset after watering
                if ($latest->soil_moisture <= 25) {
                    $moisture = rand(70, 85); // Simulated post-watering jump
                } else {
                    $moisture = max(5, $latest->soil_moisture - rand(1, 4));
                }
            } else {
                $moisture = rand(65, 75);
            }
        }

        // 2. Save to database
        $sensorData = SensorData::create([
            'plant_id' => $plant->id,
            'soil_moisture' => $moisture,
            'created_at' => now(),
        ]);

        // 3. Trigger automatic watering evaluation
        $pumpLog = $this->wateringService->evaluateAutomaticWatering($plant, $moisture);

        $statusInfo = $plant->getPlantStatus($moisture);

        return response()->json([
            'success' => true,
            'message' => 'Sensor reading successfully stored in database.',
            'data' => [
                'device_id' => $plant->device_id,
                'soil_moisture' => $sensorData->soil_moisture,
                'timestamp' => $sensorData->created_at->toDateTimeString(),
            ],
            'plant_status' => $statusInfo['status'],
            'auto_watering_triggered' => $pumpLog !== null,
            'pump_log' => $pumpLog
        ], 201);
    }
}
