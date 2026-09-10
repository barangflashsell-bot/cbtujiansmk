<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionOptionController extends ApiController
{
    /**
     * Display a listing of options for the specified question.
     */
    public function index(Request $request, string $questionId): JsonResponse
    {
        $question = Question::find($questionId);

        if (! $question) {
            return $this->errorResponse('Butir soal induk tidak ditemukan', null, 404);
        }

        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $paginator = $question->options()
            ->orderBy('option_label')
            ->paginate($perPage);

        return $this->successResponse([
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 'Daftar pilihan jawaban berhasil diambil');
    }

    /**
     * Store a newly created option for the specified question.
     */
    public function store(Request $request, string $questionId): JsonResponse
    {
        $question = Question::find($questionId);

        if (! $question) {
            return $this->errorResponse('Butir soal induk tidak ditemukan', null, 404);
        }

        $validated = $request->validate([
            'option_label' => ['required', 'string', 'max:8'],
            'content' => ['required', 'string'],
            'media_path' => ['nullable', 'string', 'max:255'],
            'is_correct' => ['nullable', 'boolean'],
        ]);

        $option = $question->options()->create([
            'option_label' => $validated['option_label'],
            'content' => $validated['content'],
            'media_path' => $validated['media_path'] ?? null,
            'is_correct' => (bool) ($validated['is_correct'] ?? false),
        ]);

        return $this->successResponse($option, 'Opsi jawaban berhasil dibuat', 201);
    }

    /**
     * Display the specified option.
     */
    public function show(string $questionId, string $id): JsonResponse
    {
        $question = Question::find($questionId);

        if (! $question) {
            return $this->errorResponse('Butir soal induk tidak ditemukan', null, 404);
        }

        $option = $question->options()->find($id);

        if (! $option) {
            return $this->errorResponse('Opsi jawaban tidak ditemukan', null, 404);
        }

        return $this->successResponse($option, 'Detail opsi jawaban berhasil diambil');
    }

    /**
     * Update the specified option.
     */
    public function update(Request $request, string $questionId, string $id): JsonResponse
    {
        $question = Question::find($questionId);

        if (! $question) {
            return $this->errorResponse('Butir soal induk tidak ditemukan', null, 404);
        }

        $option = $question->options()->find($id);

        if (! $option) {
            return $this->errorResponse('Opsi jawaban tidak ditemukan', null, 404);
        }

        $validated = $request->validate([
            'option_label' => ['sometimes', 'required', 'string', 'max:8'],
            'content' => ['sometimes', 'required', 'string'],
            'media_path' => ['nullable', 'string', 'max:255'],
            'is_correct' => ['nullable', 'boolean'],
        ]);

        $option->update($validated);

        return $this->successResponse($option, 'Opsi jawaban berhasil diperbarui');
    }

    /**
     * Remove the specified option.
     */
    public function destroy(string $questionId, string $id): JsonResponse
    {
        $question = Question::find($questionId);

        if (! $question) {
            return $this->errorResponse('Butir soal induk tidak ditemukan', null, 404);
        }

        $option = $question->options()->find($id);

        if (! $option) {
            return $this->errorResponse('Opsi jawaban tidak ditemukan', null, 404);
        }

        // Integrity check: prevent deletion if option is already chosen in student answers
        if ($option->answers()->exists()) {
            return $this->errorResponse('Tidak dapat menghapus opsi jawaban karena sudah dipilih oleh peserta dalam ujian', null, 400);
        }

        $option->delete();

        return $this->successResponse(null, 'Opsi jawaban berhasil dihapus');
    }
}
