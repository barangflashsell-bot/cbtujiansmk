<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'exam_id',
        'student_id',
        'correct_count',
        'wrong_count',
        'unanswered_count',
        'mc_score',
        'essay_score',
        'score',
        'final_score',
        'status',
        'is_published',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'correct_count' => 'integer',
            'wrong_count' => 'integer',
            'unanswered_count' => 'integer',
            'mc_score' => 'decimal:2',
            'essay_score' => 'decimal:2',
            'score' => 'decimal:2',
            'final_score' => 'decimal:2',
            'is_published' => 'boolean',
            'graded_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'attempt_id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
