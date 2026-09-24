<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Plant;
use App\Models\SensorData;
use App\Models\PumpLog;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with initial demo data.
     */
    public function run(): void
    {
        // 1. Create Default User
        $user = User::firstOrCreate(
            ['email' => 'admin@smartplant.local'],
            [
                'name' => 'Faridz Plant Care',
                'password' => Hash::make('password123'),
            ]
        );

        // 2. Create Default Plant
        $plant = Plant::firstOrCreate(
            ['device_id' => 'SP001'],
            [
                'user_id' => $user->id,
                'plant_name' => 'Monstera Deliciosa',
                'soil_threshold' => 30,
                'watering_duration' => 5,
                'automatic_watering' => true,
            ]
        );

        // 3. Create initial sensor historical points if empty
        if ($plant->sensorData()->count() === 0) {
            $initialValues = [68, 66, 65, 62, 60, 58, 55, 52, 48, 45, 65];
            $now = Carbon::now();

            foreach ($initialValues as $index => $moisture) {
                SensorData::create([
                    'plant_id' => $plant->id,
                    'soil_moisture' => $moisture,
                    'created_at' => $now->copy()->subMinutes((count($initialValues) - $index) * 2),
                ]);
            }
        }

        // 4. Create initial sample pump logs if empty
        if ($plant->pumpLogs()->count() === 0) {
            PumpLog::create([
                'plant_id' => $plant->id,
                'mode' => 'automatic',
                'duration' => 5,
                'status' => 'completed',
                'created_at' => Carbon::now()->subHours(2),
            ]);

            PumpLog::create([
                'plant_id' => $plant->id,
                'mode' => 'manual',
                'duration' => 5,
                'status' => 'completed',
                'created_at' => Carbon::now()->subMinutes(30),
            ]);
        }
    }
}
