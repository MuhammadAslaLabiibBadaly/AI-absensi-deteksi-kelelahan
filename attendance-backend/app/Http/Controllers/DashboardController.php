<?php

namespace App\Http\Controllers;
use App\Models\AttendanceLog;
use Inertia\Inertia;

// use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $logs = AttendanceLog::latest()->get();
        
        return Inertia::render('Dashboard', [
            'logs' => $logs,
            'stats' => [
                'total' => $logs->count(),
                'fit' => $logs->where('is_fatigue', false)->count(),
                'fatigue' => $logs->where('is_fatigue', true)->count(),
            ]
        ]);
    }
}
