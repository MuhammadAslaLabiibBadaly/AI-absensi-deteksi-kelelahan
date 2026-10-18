<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $table = 'attendancelogs'; // Menunjuk ke nama tabel di MySQL

    protected $fillable = [
        'user_code',
        'user_name',
        'eyes_detected',
        'is_fatigue',
        'recommendation',
    ];
}
