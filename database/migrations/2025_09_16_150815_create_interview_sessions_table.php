<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('interview_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('candidate_name');
            $table->string('candidate_email');
            $table->string('session_id')->unique();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->string('video_path')->nullable();
            $table->integer('focus_lost_count')->default(0);
            $table->integer('multiple_faces_count')->default(0);
            $table->integer('no_face_count')->default(0);
            $table->integer('phone_detected_count')->default(0);
            $table->integer('books_detected_count')->default(0);
            $table->integer('device_detected_count')->default(0);
            $table->decimal('integrity_score', 5, 2)->default(100.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_sessions');
    }
};
