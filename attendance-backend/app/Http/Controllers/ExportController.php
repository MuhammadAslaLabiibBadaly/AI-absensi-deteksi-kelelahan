<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    public function exportCsv()
    {
        $logs = AttendanceLog::latest()->get();
        $filename = "laporan_presensi_" . date('Y-m-d_H-i-s') . ".csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // BOM untuk UTF-8 agar rapi saat dibuka di Microsoft Excel
            fputs($file, "\xEF\xBB\xBF");

            // Header Kolom
            fputcsv($file, ['ID', 'Waktu', 'Kode User', 'Nama Karyawan', 'Mata Terdeteksi', 'Status Kebugaran', 'Rekomendasi']);

            // Data Row
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at,
                    $log->user_code,
                    $log->user_name,
                    $log->eyes_detected,
                    $log->is_fatigue ? 'LELAH / KANTUK' : 'FIT',
                    $log->recommendation
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}