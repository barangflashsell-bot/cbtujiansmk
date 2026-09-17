@extends('layouts.app')

@section('title', 'Edit Butir Soal - CBT Administrator')

@section('content')
<div class="content-header">
    <div>
        <h1 class="page-title">Edit Butir Soal #{{ $question->id }}</h1>
        <p class="page-subtitle">Perbarui pertanyaan, pilihan jawaban, tingkat kesulitan, atau kunci jawaban</p>
    </div>
    <a href="{{ route('admin.questions.index') }}" class="btn btn-secondary">&larr; Kembali ke Bank Soal</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('admin.questions.update', $question->id) }}">
        @csrf
        @method('PUT')

        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label class="form-label" for="subject_id">Mata Pelajaran <span style="color:var(--color-rose-500)">*</span></label>
                <select name="subject_id" id="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Mata Pelajaran --</option>
                    @foreach($subjects as $sb)
                        <option value="{{ $sb->id }}" {{ old('subject_id', $question->subject_id) == $sb->id ? 'selected' : '' }}>
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
                    <option value="single_choice" {{ old('question_type', $question->question_type) == 'single_choice' ? 'selected' : '' }}>Pilihan Ganda</option>
                    <option value="multiple_choice" {{ old('question_type', $question->question_type) == 'multiple_choice' ? 'selected' : '' }}>Pilihan Majemuk</option>
                    <option value="essay" {{ old('question_type', $question->question_type) == 'essay' ? 'selected' : '' }}>Uraian / Essay</option>
                </select>
                @error('question_type')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="flex: 1;">
                <label class="form-label" for="difficulty">Tingkat Kesulitan <span style="color:var(--color-rose-500)">*</span></label>
                <select name="difficulty" id="difficulty" class="form-select @error('difficulty') is-invalid @enderror" required>
                    <option value="easy" {{ old('difficulty', $question->difficulty) == 'easy' ? 'selected' : '' }}>Mudah</option>
                    <option value="medium" {{ old('difficulty', $question->difficulty) == 'medium' ? 'selected' : '' }}>Sedang</option>
                    <option value="hard" {{ old('difficulty', $question->difficulty) == 'hard' ? 'selected' : '' }}>Sulit</option>
                </select>
                @error('difficulty')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            @php
                $currentWeight = (float) old('score_weight', $question->score_weight);
                $currentType = old('question_type', $question->question_type);
                $isDefaultWeight = ($currentType === 'essay' && abs($currentWeight - 10.0) < 0.01) || ($currentType !== 'essay' && abs($currentWeight - 2.5) < 0.01);
                $initialMode = $isDefaultWeight ? 'auto' : 'manual';
            @endphp

            <div class="form-group" style="flex: 1;" id="group_score_weight">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 4px;">
                    <label class="form-label" for="score_weight" style="margin: 0;">Bobot Nilai <span style="color:var(--color-rose-500)">*</span></label>
                    <div class="cbt-weight-mode-toggle" style="display: inline-flex; background: #e2e8f0; border-radius: 6px; padding: 2px; gap: 2px; font-size: 11px; user-select: none;">
                        <label id="lbl_weight_auto" style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 4px; cursor: pointer; font-weight: {{ $initialMode === 'auto' ? '700' : '600' }}; background: {{ $initialMode === 'auto' ? '#2563eb' : 'transparent' }}; color: {{ $initialMode === 'auto' ? '#ffffff' : '#475569' }}; transition: all 0.2s;">
                            <input type="radio" name="weight_mode" value="auto" {{ $initialMode === 'auto' ? 'checked' : '' }} onchange="toggleWeightCalculationMode('auto')" style="display: none;">
                            ⚡ Otomatis
                        </label>
                        <label id="lbl_weight_manual" style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 4px; cursor: pointer; font-weight: {{ $initialMode === 'manual' ? '700' : '600' }}; background: {{ $initialMode === 'manual' ? '#2563eb' : 'transparent' }}; color: {{ $initialMode === 'manual' ? '#ffffff' : '#475569' }}; transition: all 0.2s;">
                            <input type="radio" name="weight_mode" value="manual" {{ $initialMode === 'manual' ? 'checked' : '' }} onchange="toggleWeightCalculationMode('manual')" style="display: none;">
                            ✍️ Manual
                        </label>
                    </div>
                </div>
                <div style="position: relative;">
                    <input type="number" step="0.1" min="0.1" max="100" name="score_weight" id="score_weight" class="form-control @error('score_weight') is-invalid @enderror" value="{{ old('score_weight', $question->score_weight) }}" required {{ $initialMode === 'auto' ? 'readonly' : '' }} style="background: {{ $initialMode === 'auto' ? '#f8fafc' : '#ffffff' }}; font-weight: 700; color: #1e293b; padding-right: {{ $initialMode === 'auto' ? '75px' : '12px' }};">
                    <span id="badge_weight_auto" style="display: {{ $initialMode === 'auto' ? 'inline-block' : 'none' }}; position: absolute; right: 8px; top: 50%; transform: translateY(-50%); font-size: 10.5px; background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 4px; font-weight: 700; pointer-events: none;">
                        Otomatis
                    </span>
                </div>
                <small id="hint_weight_mode" style="display: block; font-size: 11px; color: #64748b; margin-top: 4px; line-height: 1.3;">
                    @if($initialMode === 'auto')
                        ⚡ Bobot dihitung otomatis oleh sistem (2.5 per soal PG, 10 untuk Essai).
                    @else
                        ✍️ Mode manual: Anda bebas menentukan angka bobot nilai butir soal ini.
                    @endif
                </small>
                @error('score_weight')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-group" style="margin-top: 16px;">
            <label class="form-label" for="content">Isi Pertanyaan / Butir Soal <span style="color:var(--color-rose-500)">*</span></label>
            <textarea name="content" id="content" class="textarea-control @error('content') is-invalid @enderror" rows="5" placeholder="Tuliskan butir soal di sini..." required>{{ old('content', $question->content) }}</textarea>
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
                $existingOptions = $question->options->keyBy('option_label');
                $defaultCorrect = $question->options->where('is_correct', true)->first()->option_label ?? 'A';
            @endphp

            @foreach($labels as $idx => $label)
                @php
                    $optObj = $existingOptions->get($label);
                    $optContent = old("options.{$idx}.content", $optObj ? $optObj->content : '');
                @endphp
                <div class="option-row">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="radio" name="correct_option" value="{{ $label }}" {{ old('correct_option', $defaultCorrect) == $label ? 'checked' : '' }} style="accent-color: var(--color-primary-600); width: 18px; height: 18px;">
                        <span class="option-badge">{{ $label }}</span>
                    </label>
                    <input type="hidden" name="options[{{ $idx }}][label]" value="{{ $label }}">
                    <input type="text" name="options[{{ $idx }}][content]" class="form-control" placeholder="Teks pilihan jawaban {{ $label }}..." value="{{ $optContent }}">
                </div>
            @endforeach
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <label class="form-label" for="explanation">Pembahasan / Catatan (Opsional)</label>
            <textarea name="explanation" id="explanation" class="textarea-control" rows="3" placeholder="Pembahasan atau penjelasan kunci jawaban...">{{ old('explanation', $question->explanation) }}</textarea>
        </div>

        <div class="form-actions" style="margin-top: 24px;">
            <button type="submit" class="btn btn-primary">Perbarui Butir Soal</button>
            <a href="{{ route('admin.questions.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

<script>
    function toggleWeightCalculationMode(mode) {
        var input = document.getElementById('score_weight');
        var badge = document.getElementById('badge_weight_auto');
        var hint = document.getElementById('hint_weight_mode');
        var lblAuto = document.getElementById('lbl_weight_auto');
        var lblManual = document.getElementById('lbl_weight_manual');
        var qType = document.getElementById('question_type') ? document.getElementById('question_type').value : 'single_choice';

        if (mode === 'manual') {
            if (lblAuto) {
                lblAuto.style.background = 'transparent';
                lblAuto.style.color = '#475569';
                lblAuto.style.fontWeight = '600';
            }
            if (lblManual) {
                lblManual.style.background = '#2563eb';
                lblManual.style.color = '#ffffff';
                lblManual.style.fontWeight = '700';
            }
            if (input) {
                input.readOnly = false;
                input.style.background = '#ffffff';
                input.style.cursor = 'text';
                input.style.paddingRight = '12px';
                input.focus();
                input.select();
            }
            if (badge) badge.style.display = 'none';
            if (hint) hint.innerHTML = '✍️ <strong>Mode Manual:</strong> Anda bebas menentukan angka bobot nilai butir soal ini.';
        } else {
            if (lblAuto) {
                lblAuto.style.background = '#2563eb';
                lblAuto.style.color = '#ffffff';
                lblAuto.style.fontWeight = '700';
            }
            if (lblManual) {
                lblManual.style.background = 'transparent';
                lblManual.style.color = '#475569';
                lblManual.style.fontWeight = '600';
            }
            if (input) {
                input.readOnly = true;
                input.style.background = '#f8fafc';
                input.style.cursor = 'default';
                input.style.paddingRight = '75px';
                if (qType === 'essay') {
                    input.value = '10.0';
                } else {
                    input.value = '2.5';
                }
            }
            if (badge) badge.style.display = 'inline-block';
            if (hint) hint.innerHTML = '⚡ <strong>Mode Otomatis:</strong> Bobot dihitung otomatis oleh sistem (2.5 per soal PG, 10 untuk Essai).';
        }
    }

    function toggleQuestionType(type) {
        var optContainer = document.getElementById('options-container');
        var weightInput = document.getElementById('score_weight');
        var modeRadio = document.querySelector('input[name="weight_mode"]:checked');

        if (optContainer) {
            if (type === 'essay') {
                optContainer.style.display = 'none';
            } else {
                optContainer.style.display = 'block';
            }
        }

        if (!modeRadio || modeRadio.value === 'auto') {
            if (weightInput) {
                weightInput.value = (type === 'essay') ? '10.0' : '2.5';
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
