<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlantController extends Controller
{
    /**
     * Display a listing of all plants.
     */
    public function index()
    {
        $plants = Plant::with(['latestSensorData'])->get();

        return response()->json([
            'success' => true,
            'data' => $plants
        ]);
    }

    /**
     * Display the specified plant details.
     */
    public function show($id)
    {
        $plant = Plant::with(['latestSensorData'])->find($id);

        if (!$plant) {
            return response()->json([
                'success' => false,
                'message' => 'Plant not found'
            ], 404);
        }

        $latest = $plant->latestSensorData;
        $moisture = $latest ? $latest->soil_moisture : 0;
        $statusInfo = $plant->getPlantStatus($moisture);
        $isOnline = $plant->isDeviceOnline($latest ? $latest->created_at : null);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $plant->id,
                'device_id' => $plant->device_id,
                'plant_name' => $plant->plant_name,
                'soil_threshold' => $plant->soil_threshold,
                'watering_duration' => $plant->watering_duration,
                'automatic_watering' => (bool)$plant->automatic_watering,
                'latest_soil_moisture' => $moisture,
                'plant_status' => $statusInfo['status'],
                'plant_status_badge' => $statusInfo['badge'],
                'plant_status_message' => $statusInfo['message'],
                'is_online' => $isOnline,
                'last_update' => $latest ? $latest->created_at->diffForHumans() : 'No data yet',
                'last_update_raw' => $latest ? $latest->created_at->toDateTimeString() : null,
            ]
        ]);
    }

    /**
     * Update plant settings.
     */
    public function updateSettings(Request $request, $id)
    {
        $plant = Plant::find($id);

        if (!$plant) {
            return response()->json([
                'success' => false,
                'message' => 'Plant not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'plant_name' => 'sometimes|required|string|max:100',
            'soil_threshold' => 'sometimes|required|integer|min:1|max:100',
            'watering_duration' => 'sometimes|required|integer|min:1|max:60',
            'automatic_watering' => 'sometimes|required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $plant->update($request->only([
            'plant_name',
            'soil_threshold',
            'watering_duration',
            'automatic_watering'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => $plant
        ]);
    }
}
