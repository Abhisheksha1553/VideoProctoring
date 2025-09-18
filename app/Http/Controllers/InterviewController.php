<?php

namespace App\Http\Controllers;

use App\Models\InterviewSession;
use App\Models\DetectionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class InterviewController extends Controller
{
    public function startSession(Request $request)
    {
        $request->validate([
            'candidate_name' => 'required|string|max:255',
            'candidate_email' => 'required|email|max:255'
        ]);

        $session = InterviewSession::create([
            'candidate_name' => $request->candidate_name,
            'candidate_email' => $request->candidate_email,
            'session_id' => Str::uuid(),
            'started_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'session_id' => $session->session_id,
            'message' => 'Interview session started successfully'
        ]);
    }

    public function endSession(Request $request)
    {
        try {
            // Debug: Log the incoming request
            Log::info('End Session Request:', [
                'session_id' => $request->session_id,
                'all_data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Validate the request
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|string|exists:interview_sessions,session_id'
            ]);

            if ($validator->fails()) {
                Log::error('End Session Validation Failed:', [
                    'errors' => $validator->errors()->toArray(),
                    'input' => $request->all()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $session = InterviewSession::where('session_id', $request->session_id)->first();

            if (!$session) {
                Log::error('Session not found:', ['session_id' => $request->session_id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found'
                ], 404);
            }

            // Check if session is already ended
            if ($session->ended_at) {
                Log::warning('Session already ended:', ['session_id' => $request->session_id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Session already ended',
                    'integrity_score' => $session->integrity_score
                ], 400);
            }

            $session->update([
                'ended_at' => now(),
                'duration_minutes' => now()->diffInMinutes($session->started_at)
            ]);

            $integrityScore = $session->calculateIntegrityScore();

            Log::info('Session ended successfully:', [
                'session_id' => $request->session_id,
                'integrity_score' => $integrityScore
            ]);

            return response()->json([
                'success' => true,
                'integrity_score' => $integrityScore,
                'message' => 'Interview session ended successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('End Session Exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }


    public function logDetection(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:interview_sessions,session_id',
            'event_type' => 'required|string',
            'description' => 'required|string',
            'duration_seconds' => 'integer|min:0',
            'confidence_score' => 'numeric|min:0|max:1'
        ]);

        $session = InterviewSession::where('session_id', $request->session_id)->first();

        DetectionLog::create([
            'session_id' => $session->id,
            'event_type' => $request->event_type,
            'description' => $request->description,
            'detected_at' => now(),
            'duration_seconds' => $request->duration_seconds ?? 0,
            'confidence_score' => $request->confidence_score
        ]);

        // Update session counters
        switch ($request->event_type) {
            case 'focus_lost':
                $session->increment('focus_lost_count');
                break;
            case 'multiple_faces':
                $session->increment('multiple_faces_count');
                break;
            case 'no_face':
                $session->increment('no_face_count');
                break;
            case 'phone_detected':
                $session->increment('phone_detected_count');
                break;
            case 'books_detected':
                $session->increment('books_detected_count');
                break;
            case 'device_detected':
                $session->increment('device_detected_count');
                break;
        }

        return response()->json([
            'success' => true,
            'message' => 'Detection logged successfully'
        ]);
    }

    public function getReport($sessionId)
    {
        $session = InterviewSession::with('detectionLogs')
            ->where('session_id', $sessionId)
            ->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        return response()->json([
            'success' => true,
            'session' => $session,
            'logs' => $session->detectionLogs->groupBy('event_type')
        ]);
    }

    public function generatePDFReport($sessionId)
    {
        $session = InterviewSession::with('detectionLogs')
            ->where('session_id', $sessionId)
            ->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        $pdf = Pdf::loadView('reports.interview-report', compact('session'));

        return $pdf->download('interview-report-' . $sessionId . '.pdf');
    }

    public function uploadVideo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'session_id' => 'required|exists:interview_sessions,session_id',
                'video' => 'required|file|mimetypes:video/webm,video/mp4,video/avi,video/quicktime,video/x-msvideo|max:204800'
            ]);

            if ($validator->fails()) {
                Log::error('Video Upload Validation Failed:', $validator->errors()->toArray());
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $session = InterviewSession::where('session_id', $request->session_id)->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session not found'
                ], 404);
            }

            if ($request->hasFile('video')) {
                // Create uploads directory if it doesn't exist
                if (!Storage::disk('public')->exists('interviews')) {
                    Storage::disk('public')->makeDirectory('interviews');
                }

                $videoFile = $request->file('video');
                $fileName = 'interview_' . $session->session_id . '_' . time() . '.webm';
                $videoPath = $videoFile->storeAs('interviews', $fileName, 'public');

                $session->update(['video_path' => $videoPath]);

                return response()->json([
                    'success' => true,
                    'video_path' => $videoPath,
                    'message' => 'Video uploaded successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No video file provided'
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Video Upload Exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function viewReport($sessionId)
    {
        $session = InterviewSession::with('detectionLogs')
            ->where('session_id', $sessionId)
            ->first();

        if (!$session) {
            abort(404, 'Session not found');
        }

        return view('reports.interview-report', compact('session'));
    }
}
