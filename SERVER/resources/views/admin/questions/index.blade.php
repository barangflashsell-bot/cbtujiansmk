@extends('layouts.app')

@section('title', 'Bank Soal - CBT Administrator')

@section('content')
<div class="content-header">
    <div>
        <h1 class="page-title">Bank Soal</h1>
        <p class="page-subtitle">Kelola butir soal ujian per-mata pelajaran, pilihan jawaban, bobot nilai, dan kunci jawaban</p>
    </div>
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <button type="button" class="btn btn-secondary" onclick="openImportModal()" style="display: inline-flex; align-items: center; gap: 6px; border-color: #bae6fd; color: #0284c7;">
            <span>📊</span> Import Soal Excel
        </button>
        <a href="{{ route('admin.questions.create') }}" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
            <span>+</span> Buat Soal Baru
        </a>
    </div>
</div>

@php
    $selectedSubjectId = request('subject_id');
    $selectedSubject = $subjects->firstWhere('id', $selectedSubjectId);
@endphp

<!-- CARD DAFTAR MATA PELAJARAN (HANYA 4 KOLOM: NO, MATA PELAJARAN, BUAT SOAL PER-MATA PELAJARAN, AKSI) -->
<div class="card" style="margin-bottom: 24px;">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 20px;">📚</span>
            <div>
                <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: var(--text-primary);">Daftar Mata Pelajaran &amp; Bank Soal</h3>
                <span style="font-size: 12px; color: var(--text-muted);">Pilih mata pelajaran untuk membuat soal spesifik atau kelola butir soal</span>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <form method="GET" action="{{ route('admin.questions.index') }}" style="display: flex; gap: 6px; align-items: center;">
                @if(request('subject_id'))
                    <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
                @endif
                <input type="text" name="search_subject" class="form-control" placeholder="Cari mata pelajaran..." value="{{ request('search_subject') }}" style="padding: 6px 12px; font-size: 13px; width: 200px;">
                <button type="submit" class="btn btn-sm btn-secondary">Cari</button>
                @if(request('search_subject'))
                    <a href="{{ route('admin.questions.index') }}" class="btn btn-sm btn-secondary">Reset</a>
                @endif
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table" style="margin: 0;">
            <thead>
                <tr>
                    <th style="width: 60px; text-align: center;">No</th>
                    <th>Mata Pelajaran</th>
                    <th style="width: 280px; text-align: center;">Buat Soal Per-Mata Pelajaran</th>
                    <th style="width: 220px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subjects as $idx => $sb)
                    <tr class="{{ $selectedSubjectId == $sb->id ? 'table-row-selected' : '' }}" style="{{ $selectedSubjectId == $sb->id ? 'background-color: rgba(2, 132, 199, 0.06);' : '' }}">
                        <td style="text-align: center; font-weight: 600; color: var(--text-secondary);">
                            {{ $idx + 1 }}
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 38px; height: 38px; border-radius: 8px; background: rgba(2, 132, 199, 0.1); color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 700; flex-shrink: 0;">
                                    📖
                                </div>
                                <div>
                                    <div style="font-weight: 700; font-size: 14.5px; color: var(--text-primary);">
                                        {{ $sb->name }}
                                    </div>
                                    <div style="display: flex; gap: 6px; align-items: center; margin-top: 3px;">
                                        <span class="badge badge-secondary" style="font-size: 11px;">Kode: {{ $sb->code }}</span>
                                        <span class="badge {{ $sb->questions_count > 0 ? 'badge-info' : 'badge-light' }}" style="font-size: 11px;">
                                            {{ $sb->questions_count }} Butir Soal
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <a href="{{ route('admin.questions.create', ['subject_id' => $sb->id]) }}" class="btn btn-sm btn-primary" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; padding: 6px 14px;">
                                <span>➕</span> Buat Soal {{ $sb->name }}
                            </a>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: inline-flex; gap: 6px; align-items: center; justify-content: center;">
                                @if($selectedSubjectId == $sb->id)
                                    <a href="{{ route('admin.questions.index') }}" class="btn btn-sm btn-secondary" title="Sembunyikan daftar butir soal">
                                        <span>✕</span> Tutup Soal
                                    </a>
                                @else
                                    <a href="{{ route('admin.questions.index', ['subject_id' => $sb->id]) }}#detail-soal" class="btn btn-sm btn-secondary" style="border-color: #bae6fd; color: #0284c7;" title="Lihat dan kelola butir soal mata pelajaran ini">
                                        <span>📋</span> Kelola Soal ({{ $sb->questions_count }})
                                    </a>
                                @endif
                                <button type="button" class="btn btn-sm btn-secondary" onclick="openImportModalWithSubject('{{ $sb->id }}')" title="Import Soal Excel untuk Mapel Ini">
                                    <span>📥</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">
                            <div class="empty-icon">&#128218;</div>
                            <div class="empty-title">Belum ada mata pelajaran</div>
                            <div class="empty-desc">Tambahkan mata pelajaran terlebih dahulu melalui menu Data Mata Pelajaran.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- DETAIL DAFTAR BUTIR SOAL KETIKA MATA PELAJARAN DIKLIK ATAU CARI SOAL AKTIF -->
@if($selectedSubject || request('search') || request('type'))
    <div class="card" id="detail-soal" style="border: 1px solid #bae6fd; box-shadow: 0 4px 14px rgba(2, 132, 199, 0.08); margin-bottom: 24px;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #e0f2fe; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 42px; height: 42px; border-radius: 8px; background: #0284c7; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 800;">
                    📝
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #0284c7; letter-spacing: 0.5px;">Repository Butir Soal</div>
                    <div style="font-size: 17px; font-weight: 800; color: #0369a1; margin-top: 1px;">
                        @if($selectedSubject)
                            {{ $selectedSubject->name }} <span style="font-size: 13px; font-weight: 600; opacity: 0.85;">({{ $selectedSubject->code }})</span>
                        @else
                            Hasil Pencarian Butir Soal
                        @endif
                    </div>
                    <div style="font-size: 12px; color: #475569; margin-top: 2px;">
                        Total: <strong>{{ $questions->total() }} Butir Soal</strong> ditemukan
                    </div>
                </div>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                @if($selectedSubject)
                    <a href="{{ route('admin.questions.create', ['subject_id' => $selectedSubject->id]) }}" class="btn btn-sm btn-primary" style="font-weight: 700;">
                        <span>+</span> Buat Soal {{ $selectedSubject->name }}
                    </a>
                @endif
                <a href="{{ route('admin.questions.index') }}" class="btn btn-sm btn-secondary">
                    <span>✕</span> Tutup Detail
                </a>
            </div>
        </div>

        <div style="padding: 14px 20px; border-bottom: 1px solid var(--border-color);">
            <form method="GET" action="{{ route('admin.questions.index') }}" class="search-filter-bar" style="margin: 0;">
                @if($selectedSubject)
                    <input type="hidden" name="subject_id" value="{{ $selectedSubject->id }}">
                @endif
                <div class="search-input-group">
                    <span class="search-icon">&#128269;</span>
                    <input type="text" name="search" class="form-control" placeholder="Cari isi butir pertanyaan..." value="{{ request('search') }}">
                </div>
                <div class="filter-select-group">
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Tipe Soal</option>
                        <option value="single_choice" {{ request('type') == 'single_choice' ? 'selected' : '' }}>Pilihan Ganda</option>
                        <option value="multiple_choice" {{ request('type') == 'multiple_choice' ? 'selected' : '' }}>Pilihan Majemuk</option>
                        <option value="essay" {{ request('type') == 'essay' ? 'selected' : '' }}>Uraian / Essay</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary">Filter</button>
                @if(request('search') || request('type'))
                    <a href="{{ route('admin.questions.index', $selectedSubject ? ['subject_id' => $selectedSubject->id] : []) }}" class="btn btn-secondary">Reset</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table" style="margin: 0;">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Mata Pelajaran</th>
                        <th>Tipe &amp; Tingkat</th>
                        <th>Isi Pertanyaan Soal</th>
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
                                <div style="max-height: 80px; overflow: hidden; text-overflow: ellipsis; font-size: 13.5px; line-height: 1.45;">
                                    {{ Str::limit(strip_tags($q->content), 140) }}
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
                                <div class="empty-title">Belum ada butir soal untuk mata pelajaran ini</div>
                                <div class="empty-desc">
                                    @if($selectedSubject)
                                        Klik tombol "Buat Soal {{ $selectedSubject->name }}" untuk mulai menambahkan butir soal.
                                    @else
                                        Tidak ada butir soal yang sesuai dengan kriteria pencarian.
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($questions->hasPages())
            <div class="pagination-wrapper" style="padding: 12px 20px;">
                {{ $questions->links() }}
            </div>
        @endif
    </div>
@endif

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
                    <select name="default_subject_id" id="import_default_subject_id" class="form-select">
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
                    <span>📤</span> Upload &amp; Mulai Import
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

function openImportModalWithSubject(subjectId) {
    const select = document.getElementById('import_default_subject_id');
    if (select && subjectId) {
        select.value = subjectId;
    }
    openImportModal();
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
