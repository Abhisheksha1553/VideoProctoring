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
        Schema::create('detection_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('interview_sessions')->onDelete('cascade');
            $table->string('event_type'); // focus_lost, multiple_faces, no_face, phone_detected, etc.
            $table->text('description');
            $table->timestamp('detected_at');
            $table->integer('duration_seconds')->default(0);
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detection_logs');
    }
};
