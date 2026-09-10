@extends('layouts.app')

@section('title', 'Buat Soal Baru - CBT Guru')

@section('content')
<div class="content-header">
    <div>
        <h1 class="page-title">Buat Butir Soal Baru</h1>
        <p class="page-subtitle">Susun butir soal, pilihan jawaban, bobot nilai, dan kunci jawaban</p>
    </div>
    <a href="{{ route('guru.questions.index') }}" class="btn btn-secondary">&larr; Kembali ke Bank Soal</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('guru.questions.store') }}">
        @csrf

        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label class="form-label" for="subject_id">Mata Pelajaran <span style="color:var(--color-rose-500)">*</span></label>
                <select name="subject_id" id="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Mata Pelajaran --</option>
                    @foreach($subjects as $sb)
                        <option value="{{ $sb->id }}" {{ old('subject_id') == $sb->id ? 'selected' : '' }}>
                            {{ $sb->name }} ({{ $sb->code }})
                        </option>
                    @endforeach
                </select>
                @error('subject_id')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="flex: 1;">
                <label class="form-label" for="question_type">Tipe Soal <span style="color:var(--color-rose-500)">*</span></label>
                <select name="question_type" id="question_type" class="form-select @error('question_type') is-invalid @enderror" onchange="toggleQuestionType(this.value)" required>
                    <option value="single_choice" {{ old('question_type', 'single_choice') == 'single_choice' ? 'selected' : '' }}>Pilihan Ganda</option>
                    <option value="multiple_choice" {{ old('question_type') == 'multiple_choice' ? 'selected' : '' }}>Pilihan Majemuk</option>
                    <option value="essay" {{ old('question_type') == 'essay' ? 'selected' : '' }}>Uraian / Essay</option>
                </select>
                @error('question_type')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="flex: 1;">
                <label class="form-label" for="difficulty">Tingkat Kesulitan <span style="color:var(--color-rose-500)">*</span></label>
                <select name="difficulty" id="difficulty" class="form-select @error('difficulty') is-invalid @enderror" required>
                    <option value="easy" {{ old('difficulty') == 'easy' ? 'selected' : '' }}>Mudah</option>
                    <option value="medium" {{ old('difficulty', 'medium') == 'medium' ? 'selected' : '' }}>Sedang</option>
                    <option value="hard" {{ old('difficulty') == 'hard' ? 'selected' : '' }}>Sulit</option>
                </select>
                @error('difficulty')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="flex: 1;">
                <label class="form-label" for="score_weight">Bobot Nilai <span style="color:var(--color-rose-500)">*</span></label>
                <input type="number" step="0.1" min="0.1" max="100" name="score_weight" id="score_weight" class="form-control @error('score_weight') is-invalid @enderror" value="{{ old('score_weight', '2.0') }}" required>
                @error('score_weight')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-group" style="margin-top: 16px;">
            <label class="form-label" for="content">Isi Pertanyaan / Butir Soal <span style="color:var(--color-rose-500)">*</span></label>
            <textarea name="content" id="content" class="textarea-control @error('content') is-invalid @enderror" rows="5" placeholder="Tuliskan butir soal di sini..." required>{{ old('content') }}</textarea>
            @error('content')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>

        <!-- Section Pilihan Jawaban (untuk Pilihan Ganda / Majemuk) -->
        <div id="options-container" style="margin-top: 24px; padding: 20px; background: var(--color-slate-50); border: 1px solid var(--color-slate-200); border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="font-size: 1rem; font-weight: 600; color: var(--color-slate-800); margin: 0;">
                    Pilihan Jawaban & Kunci Jawaban
                </h3>
                <span style="font-size: 0.85rem; color: var(--color-slate-500);">
                    Pilih radio button di samping untuk menentukan Kunci Jawaban Benar
                </span>
            </div>

            @php
                $labels = ['A', 'B', 'C', 'D', 'E'];
            @endphp

            @foreach($labels as $idx => $label)
                <div class="option-row">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="correct_option" value="{{ $label }}" {{ old('correct_option', 'A') == $label ? 'checked' : '' }} style="accent-color: var(--color-primary-600); width: 18px; height: 18px;">
                        <span class="option-badge">{{ $label }}</span>
                    </label>
                    <input type="hidden" name="options[{{ $idx }}][label]" value="{{ $label }}">
                    <input type="text" name="options[{{ $idx }}][content]" class="form-control" placeholder="Teks pilihan jawaban {{ $label }}..." value="{{ old("options.{$idx}.content") }}">
                </div>
            @endforeach
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <label class="form-label" for="explanation">Pembahasan / Catatan (Opsional)</label>
            <textarea name="explanation" id="explanation" class="textarea-control" rows="3" placeholder="Pembahasan atau penjelasan kunci jawaban...">{{ old('explanation') }}</textarea>
        </div>

        <div class="form-actions" style="margin-top: 24px;">
            <button type="submit" class="btn btn-primary">Simpan Butir Soal</button>
            <a href="{{ route('guru.questions.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<script>
    function toggleQuestionType(type) {
        var optContainer = document.getElementById('options-container');
        if (optContainer) {
            if (type === 'essay') {
                optContainer.style.display = 'none';
            } else {
                optContainer.style.display = 'block';
            }
        }
    }
    document.addEventListener('DOMContentLoaded', function() {
        var typeSelect = document.getElementById('question_type');
        if (typeSelect) {
            toggleQuestionType(typeSelect.value);
        }
    });
</script>
@endsection
