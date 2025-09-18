<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetectionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'event_type',
        'description',
        'detected_at',
        'duration_seconds',
        'confidence_score'
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'confidence_score' => 'decimal:4'
    ];

    public function session()
    {
        return $this->belongsTo(InterviewSession::class, 'session_id');
    }
}
