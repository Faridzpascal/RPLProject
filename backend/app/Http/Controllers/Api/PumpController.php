<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plant;
use App\Models\PumpLog;
use App\Services\WateringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PumpController extends Controller
{
    protected WateringService $wateringService;

    public function __construct(WateringService $wateringService)
    {
        $this->wateringService = $wateringService;
    }

    /**
     * Trigger manual watering.
     */
    public function pump(Request $request, $id)
    {
        $plant = Plant::find($id);

        if (!$plant) {
            return response()->json([
                'success' => false,
                'message' => 'Plant not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'duration' => 'nullable|integer|min:1|max:60'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid watering duration',
                'errors' => $validator->errors()
            ], 422);
        }

        $duration = $request->input('duration', $plant->watering_duration ?: 5);

        $result = $this->wateringService->executeManualWatering($plant, (int)$duration);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 429); // 429 Too Many Requests / Cooldown active
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['log']
        ]);
    }

    /**
     * Get watering history logs.
     */
    public function history($id)
    {
        $plant = Plant::find($id);

        if (!$plant) {
            return response()->json([
                'success' => false,
                'message' => 'Plant not found'
            ], 404);
        }

        $logs = PumpLog::where('plant_id', $plant->id)
            ->orderBy('created_at', 'desc')
            ->take(25)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }
}
