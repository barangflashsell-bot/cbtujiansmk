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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title', 128);
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->integer('duration_minutes');
            $table->decimal('passing_score', 5, 2)->default(75.00);
            $table->string('token', 16)->nullable();
            $table->dateTime('start_window');
            $table->dateTime('end_window');
            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('shuffle_options')->default(true);
            $table->boolean('show_result')->default(false);
            $table->boolean('allow_review')->default(false);
            $table->enum('status', ['draft', 'published', 'active', 'completed'])->default('draft');
            $table->timestamps();

            $table->index(['status', 'start_window', 'end_window']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
