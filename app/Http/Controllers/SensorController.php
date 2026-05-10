<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Sensor;

class SensorController extends Controller
{
    public function store(Request $request)
    {
        try {
            Log::info('ESP32 Received', $request->all());

            $validated = $request->validate([
                'temperature' => 'nullable|numeric',
                'humidity'    => 'nullable|numeric',
                'flame'       => 'nullable|boolean',
                'gas'         => 'nullable|boolean',
            ]);

            $record = Sensor::create([
                'temperature' => $validated['temperature'] ?? 0,
                'humidity'    => $validated['humidity'] ?? 0,
                'flame'       => (bool) ($validated['flame'] ?? false),
                'gas'         => (bool) ($validated['gas'] ?? false),
            ]);

            return response()->json(['status' => 'ok', 'id' => $record->id]);

        } catch (\Exception $e) {
            Log::error('Store Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function latest()
    {
        $data = Sensor::latest()->first();

        if (!$data) {
            return response()->json([
                'temperature' => 0,
                'humidity'    => 0,
                'flame'       => false,
                'gas'         => false,
                'message'     => 'no_data'
            ]);
        }

        return response()->json($data);
    }

    public function dashboard()
    {
        return view('dashboard');
    }
}
