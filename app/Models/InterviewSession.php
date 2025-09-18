<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_name',
        'candidate_email',
        'session_id',
        'started_at',
        'ended_at',
        'duration_minutes',
        'video_path',
        'focus_lost_count',
        'multiple_faces_count',
        'no_face_count',
        'phone_detected_count',
        'books_detected_count',
        'device_detected_count',
        'integrity_score'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'integrity_score' => 'decimal:2'
    ];

    public function detectionLogs()
    {
        return $this->hasMany(DetectionLog::class, 'session_id');
    }

    public function calculateIntegrityScore()
    {
        $deductions = 0;

        // Deduction rules
        $deductions += $this->focus_lost_count * 2;
        $deductions += $this->multiple_faces_count * 5;
        $deductions += $this->no_face_count * 3;
        $deductions += $this->phone_detected_count * 10;
        $deductions += $this->books_detected_count * 8;
        $deductions += $this->device_detected_count * 7;

        $this->integrity_score = max(0, 100 - $deductions);
        $this->save();

        return $this->integrity_score;
    }
}
