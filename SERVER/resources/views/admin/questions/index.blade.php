@extends('layouts.app')

@section('title', 'Bank Soal - CBT Administrator')

@section('content')
<div class="content-header">
    <div>
        <h1 class="page-title">Bank Soal</h1>
        <p class="page-subtitle">Kelola butir soal ujian, pilihan jawaban, bobot nilai, dan kunci jawaban</p>
    </div>
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <button type="button" class="btn btn-secondary" onclick="openImportModal()" style="display: inline-flex; align-items: center; gap: 6px; border-color: #bae6fd; color: #0284c7;">
            <span>📊</span> Import Soal Excel
        </button>
        <a href="{{ route('admin.questions.create') }}" class="btn btn-primary">+ Buat Soal Baru</a>
    </div>
</div>

<div class="card">
    <form method="GET" action="{{ route('admin.questions.index') }}" class="search-filter-bar">
        <div class="search-input-group">
            <span class="search-icon">&#128269;</span>
            <input type="text" name="search" class="form-control" placeholder="Cari isi butir soal..." value="{{ request('search') }}">
        </div>
        <div class="filter-select-group">
            <select name="subject_id" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Mapel</option>
                @foreach($subjects as $sb)
                    <option value="{{ $sb->id }}" {{ request('subject_id') == $sb->id ? 'selected' : '' }}>
                        {{ $sb->name }}
                    </option>
                @endforeach
            </select>
            <select name="type" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Tipe</option>
                <option value="single_choice" {{ request('type') == 'single_choice' ? 'selected' : '' }}>Pilihan Ganda</option>
                <option value="multiple_choice" {{ request('type') == 'multiple_choice' ? 'selected' : '' }}>Pilihan Majemuk</option>
                <option value="essay" {{ request('type') == 'essay' ? 'selected' : '' }}>Uraian / Essay</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filter</button>
        @if(request('search') || request('subject_id') || request('type'))
            <a href="{{ route('admin.questions.index') }}" class="btn btn-secondary">Reset</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Mata Pelajaran</th>
                    <th>Tipe & Tingkat</th>
                    <th>Isi Butir Soal</th>
                    <th>Bobot</th>
                    <th>Pembuat</th>
                    <th style="width: 140px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($questions as $idx => $q)
                    <tr>
                        <td>{{ $questions->firstItem() + $idx }}</td>
                        <td>
                            <span class="badge badge-info">{{ $q->subject->name ?? '-' }}</span>
                        </td>
                        <td>
                            <div style="font-weight: 600; font-size: 0.85rem;">
                                @if($q->question_type === 'single_choice')
                                    Pilihan Ganda
                                @elseif($q->question_type === 'multiple_choice')
                                    Pilihan Majemuk
                                @else
                                    Uraian / Essay
                                @endif
                            </div>
                            <div style="margin-top: 4px;">
                                @if($q->difficulty === 'easy')
                                    <span class="badge badge-success">Mudah</span>
                                @elseif($q->difficulty === 'medium')
                                    <span class="badge badge-warning">Sedang</span>
                                @else
                                    <span class="badge badge-danger">Sulit</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div style="max-height: 80px; overflow: hidden; text-overflow: ellipsis;">
                                {{ Str::limit(strip_tags($q->content), 120) }}
                            </div>
                            @if($q->options->count() > 0)
                                <div style="margin-top: 6px; font-size: 0.8rem; color: var(--color-slate-500);">
                                    {{ $q->options->count() }} Pilihan Jawaban 
                                    @php
                                        $correct = $q->options->where('is_correct', true)->pluck('option_label')->implode(', ');
                                    @endphp
                                    @if($correct)
                                        | Kunci: <strong style="color: var(--color-emerald-600);">{{ $correct }}</strong>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $q->score_weight }}</strong>
                        </td>
                        <td>
                            <span style="font-size: 0.85rem; color: var(--color-slate-600);">
                                {{ $q->creator->name ?? 'Admin' }}
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: inline-flex; gap: 6px;">
                                <a href="{{ route('admin.questions.edit', $q->id) }}" class="btn btn-sm btn-secondary">Edit</a>
                                <form method="POST" action="{{ route('admin.questions.destroy', $q->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus butir soal ini?');" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">
                            <div class="empty-icon">&#128221;</div>
                            <div class="empty-title">Belum ada butir soal</div>
                            <div class="empty-desc">Klik tombol "Buat Soal Baru" di atas untuk menambahkan bank soal.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($questions->hasPages())
        <div class="pagination-wrapper">
            {{ $questions->links() }}
        </div>
    @endif
</div>

<!-- MODAL IMPORT SOAL EXCEL / CSV -->
<div class="modal-overlay" id="importQuestionModal">
    <div class="modal-content-card" style="max-width: 540px;">
        <div class="modal-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 20px;">📊</span>
                <h3 class="modal-title" style="margin: 0;">Import Bank Soal dari Excel</h3>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeImportModal()">&times;</button>
        </div>

        <form action="{{ route('admin.questions.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom: 16px;">
                <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 12px; line-height: 1.45;">
                    Unggah butir soal dalam format <strong>Excel (.xlsx)</strong> atau <strong>CSV (.csv)</strong>. Sistem akan otomatis memasukkan teks pertanyaan, opsi jawaban A-E, kunci jawaban, dan bobot nilai.
                </p>

                <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                    <div>
                        <div style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">Belum punya formatnya?</div>
                        <div style="font-size: 11px; color: var(--text-muted);">Unduh template standar berisi contoh soal</div>
                    </div>
                    <a href="{{ route('admin.questions.template') }}" class="btn btn-secondary btn-sm" style="color: #0284c7; border-color: #bae6fd; text-decoration: none;">
                        <span>📥</span> Unduh Template
                    </a>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label">Pilih File Excel / CSV *</label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv,.txt" required style="padding: 7px 12px;">
                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Mendukung .xlsx, .xls, dan .csv (Maksimal 10MB)</div>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label">Mata Pelajaran Default (Opsional)</label>
                    <select name="default_subject_id" class="form-select">
                        <option value="">-- Otomatis Sesuai Kolom "Mata Pelajaran" di File --</option>
                        @foreach ($subjects as $sb)
                            <option value="{{ $sb->id }}">{{ $sb->name }} ({{ $sb->code }})</option>
                        @endforeach
                    </select>
                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Digunakan jika kolom mapel pada file kosong.</div>
                </div>

                <div style="background: var(--bg-surface-elevated); border: 1px dashed var(--border-color); border-radius: 6px; padding: 10px 12px; font-size: 11px; color: var(--text-secondary);">
                    <strong style="display: block; margin-bottom: 4px; color: var(--text-primary);">Format Kolom File yang Dikenali:</strong>
                    <code>Mata Pelajaran | Tipe Soal | Pertanyaan | Opsi A | Opsi B | Opsi C | Opsi D | Opsi E | Kunci Jawaban | Bobot | Tingkat Kesulitan</code>
                    <div style="margin-top: 4px; font-size: 10.5px; color: var(--text-muted);">
                        &bull; Tipe soal: <code>single_choice</code> (PG), <code>multiple_choice</code> (PG Majemuk), <code>essay</code>.<br>
                        &bull; Kunci jawaban isi dengan huruf opsi seperti <code>A</code> atau <code>A,C</code>.
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Batal</button>
                <button type="submit" class="btn btn-primary" style="background-color: #0095ff; border-color: #0095ff;">
                    <span>📤</span> Upload & Mulai Import
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openImportModal() {
    const modal = document.getElementById('importQuestionModal');
    if (modal) modal.classList.add('active');
}

function closeImportModal() {
    const modal = document.getElementById('importQuestionModal');
    if (modal) modal.classList.remove('active');
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('importQuestionModal');
    if (modal && e.target === modal) closeImportModal();
});
</script>
@endsection
