<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function storeInternalLog(Request $request)
    {
        $validated = $request->validate([
            'user_code' => 'nullable|string',
            'user_name' => 'nullable|string',
            'eyes_detected' => 'required|integer',
            'is_fatigue' => 'required|boolean',
            'recommendation' => 'required|string',
        ]);

        $log = AttendanceLog::create([
            'user_code' => $validated['user_code'] ?? 'USR-001',
            'user_name' => $validated['user_name'] ?? 'Muhammad Asla',
            'eyes_detected' => $validated['eyes_detected'],
            'is_fatigue' => $validated['is_fatigue'],
            'recommendation' => $validated['recommendation'],
        ]);

        return response()->json([
            'status' => 'SUCCESS',
            'message' => 'Log absensi berhasil disimpan ke MySQL!',
            'data' => $log
        ], 201);
    }

    public function getLogs()
    {
        $logs = AttendanceLog::orderBy('created_at', 'desc')->get();
        return response()->json([
            'status' => 'SUCCESS',
            'data' => $logs
        ]);
    }
}