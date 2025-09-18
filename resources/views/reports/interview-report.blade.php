<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Interview Proctoring Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table th, .info-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .info-table th { background-color: #f2f2f2; }
        .score-good { color: #28a745; }
        .score-warning { color: #ffc107; }
        .score-danger { color: #dc3545; }
        .section { margin-bottom: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Video Interview Proctoring Report</h1>
        <p>Generated on: {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
    
    <div class="section">
        <h2>Session Information</h2>
        <table class="info-table">
            <tr>
                <th>Candidate Name</th>
                <td>{{ $session->candidate_name }}</td>
            </tr>
            <tr>
                <th>Candidate Email</th>
                <td>{{ $session->candidate_email }}</td>
            </tr>
            <tr>
                <th>Session ID</th>
                <td>{{ $session->session_id }}</td>
            </tr>
            <tr>
                <th>Started At</th>
                <td>{{ $session->started_at->format('Y-m-d H:i:s') }}</td>
            </tr>
            <tr>
                <th>Ended At</th>
                <td>{{ $session->ended_at ? $session->ended_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
            </tr>
            <tr>
                <th>Duration</th>
                <td>{{ $session->duration_minutes }} minutes</td>
            </tr>
        </table>
    </div>
    
    <div class="section">
        <h2>Integrity Assessment</h2>
        <table class="info-table">
            <tr>
                <th>Final Integrity Score</th>
                <td class="
                    @if($session->integrity_score >= 80) score-good
                    @elseif($session->integrity_score >= 60) score-warning
                    @else score-danger
                    @endif
                ">
                    {{ $session->integrity_score }}%
                </td>
            </tr>
        </table>
    </div>
    
    <div class="section">
        <h2>Detection Summary</h2>
        <table class="info-table">
            <tr>
                <th>Event Type</th>
                <th>Count</th>
                <th>Score Impact</th>
            </tr>
            <tr>
                <td>Focus Lost (>5 seconds)</td>
                <td>{{ $session->focus_lost_count }}</td>
                <td>-{{ $session->focus_lost_count * 2 }} points</td>
            </tr>
            <tr>
                <td>Multiple Faces Detected</td>
                <td>{{ $session->multiple_faces_count }}</td>
                <td>-{{ $session->multiple_faces_count * 5 }} points</td>
            </tr>
            <tr>
                <td>No Face Present (>10 seconds)</td>
                <td>{{ $session->no_face_count }}</td>
                <td>-{{ $session->no_face_count * 3 }} points</td>
            </tr>
            <tr>
                <td>Mobile Phone Detected</td>
                <td>{{ $session->phone_detected_count }}</td>
                <td>-{{ $session->phone_detected_count * 10 }} points</td>
            </tr>
            <tr>
                <td>Books/Notes Detected</td>
                <td>{{ $session->books_detected_count }}</td>
                <td>-{{ $session->books_detected_count * 8 }} points</td>
            </tr>
            <tr>
                <td>Electronic Devices Detected</td>
                <td>{{ $session->device_detected_count }}</td>
                <td>-{{ $session->device_detected_count * 7 }} points</td>
            </tr>
        </table>
    </div>
    
    @if($session->detectionLogs->count() > 0)
    <div class="section">
        <h2>Detailed Event Log</h2>
        <table class="info-table">
            <tr>
                <th>Timestamp</th>
                <th>Event Type</th>
                <th>Description</th>
                <th>Duration</th>
            </tr>
            @foreach($session->detectionLogs as $log)
            <tr>
                <td>{{ $log->detected_at->format('H:i:s') }}</td>
                <td>{{ ucwords(str_replace('_', ' ', $log->event_type)) }}</td>
                <td>{{ $log->description }}</td>
                <td>{{ $log->duration_seconds }}s</td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif
    
    <div class="section">
        <h2>Summary</h2>
        <p>
            @if($session->integrity_score >= 90)
                <strong>Excellent:</strong> The candidate demonstrated high integrity throughout the interview with minimal suspicious activity.
            @elseif($session->integrity_score >= 80)
                <strong>Good:</strong> The candidate showed acceptable behavior with minor issues that may need attention.
            @elseif($session->integrity_score >= 60)
                <strong>Fair:</strong> Several integrity concerns were detected that require careful review.
            @else
                <strong>Poor:</strong> Significant integrity issues were detected that compromise the interview validity.
            @endif
        </p>
    </div>
</body>
</html>
