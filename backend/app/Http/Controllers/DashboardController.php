<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Render the main Smart Plant Web Dashboard.
     */
    public function index()
    {
        $plant = Plant::first();

        return view('dashboard', [
            'plant' => $plant
        ]);
    }
}
