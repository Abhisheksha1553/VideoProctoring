<?php

use App\Http\Controllers\InterviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('interview.index');
});

Route::get('/interview', function () {
    return view('interview');
})->name('interview.index');

Route::get('/report/{sessionId}', [InterviewController::class, 'viewReport'])->name('interview.report');

// API Routes
Route::prefix('api')->group(function () {
    Route::post('/interview/start', [InterviewController::class, 'startSession'])->name('api.interview.start');
    Route::post('/interview/end', [InterviewController::class, 'endSession'])->name('api.interview.end');
    Route::post('/interview/log-detection', [InterviewController::class, 'logDetection'])->name('api.interview.log');
    Route::get('/interview/report/{sessionId}', [InterviewController::class, 'getReport'])->name('api.interview.report');
    Route::get('/interview/report/{sessionId}/pdf', [InterviewController::class, 'generatePDFReport'])->name('api.interview.pdf');
    Route::post('/interview/upload-video', [InterviewController::class, 'uploadVideo'])->name('api.interview.upload');
});
