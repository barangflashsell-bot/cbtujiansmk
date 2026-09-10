@extends('layouts.app')

@section('title', 'Buat Paket Ujian - CBT Administrator')

@section('content')
<div class="content-header">
    <div>
        <h1 class="page-title">Buat Paket Ujian Baru</h1>
        <p class="page-subtitle">Atur jadwal pelaksanaan, durasi, passing score, token, dan kebijakan ujian</p>
    </div>
    <a href="{{ route('admin.exams.index') }}" class="btn btn-secondary">&larr; Kembali ke Daftar Ujian</a>
</div>

<div class="card">
    <form method="POST" action="{{ route('admin.exams.store') }}">
        @csrf

        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label class="form-label" for="title">Judul Paket Ujian <span style="color:var(--color-rose-500)">*</span></label>
                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" placeholder="Contoh: Penilaian Akhir Semester (PAS) Matematika 9" value="{{ old('title') }}" required>
                @error('title')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="flex: 1;">
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
        </div>

        <div class="form-row" style="margin-top: 16px;">
            <div class="form-group" style="flex: 1;">
                <label class="form-label" for="duration_minutes">Durasi (Menit) <span style="color:var(--color-rose-500)">*</span></label>
                <input type="number" name="duration_minutes" id="duration_minutes" min="1" max="1440" class="form-control @error('duration_minutes') is-invalid @enderror" value="{{ old('duration_minutes', '90') }}" required>
                @error('duration_minutes')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="flex: 1;">
                <label class="form-label" for="passing_score">Passing Score (KKM)</label>
                <input type="number" step="0.1" min="0" max="100" name="passing_score" id="passing_score" class="form-control @error('passing_score') is-invalid @enderror" value="{{ old('passing_score', '75.00') }}">
                @error('passing_score')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="flex: 1;">
                <label class="form-label" for="token">Token Ujian (Opsional)</label>
                <input type="text" name="token" id="token" maxlength="16" class="form-control" placeholder="Contoh: PAS2026" style="text-transform: uppercase;" value="{{ old('token') }}">
            </div>

            <div class="form-group" style="flex: 1;">
                <label class="form-label" for="status">Status Ujian <span style="color:var(--color-rose-500)">*</span></label>
                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                    <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>Published (Terjadwal)</option>
                    <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active (Bisa Dikerjakan)</option>
                    <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Completed (Selesai)</option>
                </select>
                @error('status')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-row" style="margin-top: 16px;">
            <div class="form-group" style="flex: 1;">
                <label class="form-label" for="start_window">Jadwal Waktu Mulai <span style="color:var(--color-rose-500)">*</span></label>
                <input type="datetime-local" name="start_window" id="start_window" class="form-control @error('start_window') is-invalid @enderror" value="{{ old('start_window', now()->format('Y-m-d\TH:i')) }}" required>
                @error('start_window')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="flex: 1;">
                <label class="form-label" for="end_window">Jadwal Batas Akhir <span style="color:var(--color-rose-500)">*</span></label>
                <input type="datetime-local" name="end_window" id="end_window" class="form-control @error('end_window') is-invalid @enderror" value="{{ old('end_window', now()->addHours(3)->format('Y-m-d\TH:i')) }}" required>
                @error('end_window')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-group" style="margin-top: 16px;">
            <label class="form-label" for="instructions">Petunjuk Pengerjaan Ujian</label>
            <textarea name="instructions" id="instructions" class="textarea-control" rows="3" placeholder="Contoh: Berdoalah sebelum mengerjakan. Pilihlah satu jawaban yang paling tepat.">{{ old('instructions') }}</textarea>
        </div>

        <div style="margin-top: 20px; padding: 16px; background: var(--color-slate-50); border: 1px solid var(--color-slate-200); border-radius: 8px;">
            <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--color-slate-800); margin-bottom: 12px;">Opsi Kebijakan Ujian</h4>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="shuffle_questions" value="1" {{ old('shuffle_questions', '1') ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--color-primary-600);">
                    <span>Acak Urutan Butir Soal (Shuffle Questions)</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="shuffle_options" value="1" {{ old('shuffle_options', '1') ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--color-primary-600);">
                    <span>Acak Urutan Pilihan Jawaban (Shuffle Options)</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="show_result" value="1" {{ old('show_result') ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--color-primary-600);">
                    <span>Tampilkan Nilai ke Siswa Setelah Submit (Show Result)</span>
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="allow_review" value="1" {{ old('allow_review') ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--color-primary-600);">
                    <span>Izinkan Siswa Meninjau Kembali Lembar Jawaban (Allow Review)</span>
                </label>
            </div>
        </div>

        <div class="form-actions" style="margin-top: 24px;">
            <button type="submit" class="btn btn-primary">Simpan & Lanjutkan ke Detail Soal</button>
            <a href="{{ route('admin.exams.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
