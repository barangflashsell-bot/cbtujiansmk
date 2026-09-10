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
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->restrictOnDelete();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('ends_at');
            $table->dateTime('submitted_at')->nullable();
            $table->enum('status', ['not_started', 'in_progress', 'submitted', 'timeout', 'blocked'])->default('in_progress');
            $table->dateTime('last_activity_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_info', 255)->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'student_id'], 'uk_exam_student_attempt');
            $table->index(['status', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
