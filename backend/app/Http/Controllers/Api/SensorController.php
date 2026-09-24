<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plant;
use App\Models\SensorData;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SensorController extends Controller
{
    /**
     * Get the latest sensor reading for a plant.
     */
    public function latest($id)
    {
        $plant = Plant::find($id);

        if (!$plant) {
            return response()->json([
                'success' => false,
                'message' => 'Plant not found'
            ], 404);
        }

        $latest = $plant->latestSensorData;

        if (!$latest) {
            return response()->json([
                'success' => true,
                'data' => [
                    'device_id' => $plant->device_id,
                    'soil_moisture' => 0,
                    'plant_status' => 'NO_DATA',
                    'plant_status_badge' => 'secondary',
                    'plant_status_message' => 'No sensor data recorded yet.',
                    'is_online' => false,
                    'last_update' => 'Never',
                    'timestamp' => null,
                ]
            ]);
        }

        $statusInfo = $plant->getPlantStatus($latest->soil_moisture);
        $isOnline = $plant->isDeviceOnline($latest->created_at);

        return response()->json([
            'success' => true,
            'data' => [
                'device_id' => $plant->device_id,
                'soil_moisture' => $latest->soil_moisture,
                'plant_status' => $statusInfo['status'],
                'plant_status_badge' => $statusInfo['badge'],
                'plant_status_message' => $statusInfo['message'],
                'is_online' => $isOnline,
                'last_update' => $latest->created_at->diffForHumans(),
                'timestamp' => $latest->created_at->toDateTimeString(),
            ]
        ]);
    }

    /**
     * Get historical sensor data for Chart.js.
     */
    public function history(Request $request, $id)
    {
        $plant = Plant::find($id);

        if (!$plant) {
            return response()->json([
                'success' => false,
                'message' => 'Plant not found'
            ], 404);
        }

        $filter = $request->query('filter', 'today');

        $query = SensorData::where('plant_id', $plant->id);

        if ($filter === 'today') {
            $query->where('created_at', '>=', Carbon::today()->startOfDay());
        } elseif ($filter === '7days') {
            $query->where('created_at', '>=', Carbon::now()->subDays(7));
        }

        // Get the latest 50 records in reverse order, then sort chronologically for chart display
        $records = $query->orderBy('created_at', 'desc')->take(50)->get()->reverse()->values();

        $labels = [];
        $data = [];

        foreach ($records as $record) {
            $labels[] = Carbon::parse($record->created_at)->format('H:i:s');
            $data[] = $record->soil_moisture;
        }

        return response()->json([
            'success' => true,
            'filter' => $filter,
            'labels' => $labels,
            'data' => $data,
            'records' => $records
        ]);
    }
}
