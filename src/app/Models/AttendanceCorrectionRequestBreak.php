<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionRequestBreak extends Model
{
    use HasFactory;

    protected $table = 'attendance_correction_request_breaks';

    protected $fillable = [
        'request_id',
        'break_start_at',
        'break_end_at',
    ];

    protected $casts = [
        'break_start_at' => 'datetime',
        'break_end_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(AttendanceCorrectionRequest::class, 'request_id');
    }
}
