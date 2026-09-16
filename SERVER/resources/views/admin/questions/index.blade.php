@extends('layouts.app')

@section('title', 'Bank Soal - CBT Administrator')

@section('content')
<style>
    .example-table th {
        color: #2563eb !important;
        font-weight: 800 !important;
        font-size: 12px !important;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        border-bottom: 2px solid #e2e8f0 !important;
        white-space: nowrap;
        padding: 14px 12px !important;
    }
    .sort-icon {
        display: inline-block;
        font-size: 10px;
        color: #94a3b8;
        margin-left: 3px;
        vertical-align: middle;
    }
    .btn-buat-soal-purple {
        background: #5b47fb !important;
        color: #ffffff !important;
        border: none !important;
        border-radius: 6px !important;
        padding: 8px 18px !important;
        font-size: 13px !important;
        font-weight: 700 !important;
        box-shadow: 0 6px 16px rgba(91, 71, 251, 0.35) !important;
        text-decoration: none !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        transition: all 0.2s ease;
    }
    .btn-buat-soal-purple:hover {
        background: #4935e8 !important;
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(91, 71, 251, 0.45) !important;
        color: #ffffff !important;
    }
    .action-stack {
        display: flex;
        flex-direction: column;
        gap: 6px;
        align-items: center;
        justify-content: center;
    }
    .btn-action-ubah {
        background: #2563eb !important;
        color: #ffffff !important;
        border: none !important;
        border-radius: 6px !important;
        padding: 6px 18px !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25) !important;
        width: 86px;
        text-align: center;
        cursor: pointer;
        text-decoration: none !important;
        display: inline-block;
        transition: all 0.2s ease;
    }
    .btn-action-ubah:hover {
        background: #1d4ed8 !important;
        transform: translateY(-1px);
        color: #ffffff !important;
    }
    .btn-action-arsipkan {
        background: #e59324 !important;
        color: #ffffff !important;
        border: none !important;
        border-radius: 6px !important;
        padding: 6px 18px !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        box-shadow: 0 4px 10px rgba(229, 147, 36, 0.28) !important;
        width: 86px;
        text-align: center;
        cursor: pointer;
        text-decoration: none !important;
        display: inline-block;
        transition: all 0.2s ease;
    }
    .btn-action-arsipkan:hover {
        background: #c97d1b !important;
        transform: translateY(-1px);
        color: #ffffff !important;
    }
    .btn-action-cetak {
        background: #0ea5e9 !important;
        color: #ffffff !important;
        border: none !important;
        border-radius: 6px !important;
        padding: 6px 18px !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        box-shadow: 0 4px 10px rgba(14, 165, 233, 0.25) !important;
        width: 86px;
        text-align: center;
        cursor: pointer;
        text-decoration: none !important;
        display: inline-block;
        transition: all 0.2s ease;
    }
    .btn-action-cetak:hover {
        background: #0284c7 !important;
        transform: translateY(-1px);
        color: #ffffff !important;
    }
</style>

<div class="content-header">
    <div>
        <h1 class="page-title">Bank Soal</h1>
        <p class="page-subtitle">Daftar bank soal ujian, komposisi butir pertanyaan, bobot penilaian, dan aksi kelola</p>
    </div>
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <button type="button" class="btn btn-secondary" onclick="openCreateSubjectModal()" style="display: inline-flex; align-items: center; gap: 6px; border-color: #fbcfe8; color: #db2777; font-weight: 600;">
            <span>📚</span> + Tambah Bank Soal
        </button>
    </div>
</div>

@php
    $selectedSubjectId = request('subject_id');
    $selectedSubject = $subjects->firstWhere('id', $selectedSubjectId);
@endphp

<!-- TABEL UTAMA PERSIS SEPERTI CONTOH -->
<div class="card" style="margin-bottom: 24px; padding: 0; overflow: hidden; border-radius: 10px;">
    <div class="table-responsive">
        <table class="table example-table" style="margin: 0; background: #ffffff;">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">NO. <span class="sort-icon">⇅</span></th>
                    <th>GURU <span class="sort-icon">⇅</span></th>
                    <th>JUDUL <span class="sort-icon">⇅</span></th>
                    <th style="text-align: center; width: 60px;">PG <span class="sort-icon">⇅</span></th>
                    <th style="text-align: center; width: 85px;">PG MULTI <span class="sort-icon">⇅</span></th>
                    <th style="text-align: center; width: 70px;">ESSAI <span class="sort-icon">⇅</span></th>
                    <th style="text-align: center; width: 60px;">B/S <span class="sort-icon">⇅</span></th>
                    <th style="text-align: center; width: 75px;">JODOH <span class="sort-icon">⇅</span></th>
                    <th style="text-align: center; width: 110px;">WAKTU <span class="sort-icon">⇅</span></th>
                    <th style="text-align: center; width: 160px;">DAFTAR PERTANYAAN <span class="sort-icon">⇅</span></th>
                    <th style="text-align: center; width: 120px;">AKSI <span class="sort-icon">⇅</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($subjects as $idx => $sb)
                    <tr style="vertical-align: middle; {{ $selectedSubjectId == $sb->id ? 'background: #f5f3ff;' : '' }}">
                        <td style="text-align: center; color: #475569; font-weight: 600; padding: 18px 10px;">
                            {{ $idx + 1 }}
                        </td>
                        <td style="color: #475569; font-size: 13.5px; padding: 18px 12px;">
                            {{ $sb->teacher ?? 'Demo' }}
                        </td>
                        <td style="padding: 18px 12px;">
                            <div style="font-weight: 600; font-size: 14px; color: #334155; line-height: 1.4;">
                                {{ $sb->name }}
                            </div>
                            <div style="font-size: 11.5px; color: #94a3b8; margin-top: 2px;">
                                Kode: {{ $sb->code }}
                            </div>
                        </td>
                        <td style="text-align: center; color: #64748b; font-weight: 500; font-size: 14px; padding: 18px 8px;">
                            {{ $sb->pg_count ?? 0 }}
                        </td>
                        <td style="text-align: center; color: #64748b; font-weight: 500; font-size: 14px; padding: 18px 8px;">
                            {{ $sb->pg_multi_count ?? 0 }}
                        </td>
                        <td style="text-align: center; color: #64748b; font-weight: 500; font-size: 14px; padding: 18px 8px;">
                            {{ $sb->essay_count ?? 0 }}
                        </td>
                        <td style="text-align: center; color: #64748b; font-weight: 500; font-size: 14px; padding: 18px 8px;">
                            {{ $sb->tf_count ?? 0 }}
                        </td>
                        <td style="text-align: center; color: #64748b; font-weight: 500; font-size: 14px; padding: 18px 8px;">
                            {{ $sb->match_count ?? 0 }}
                        </td>
                        <td style="text-align: center; color: #475569; font-size: 13px; white-space: nowrap; padding: 18px 10px;">
                            120 menit
                        </td>
                        <td style="text-align: center; padding: 18px 12px;">
                            <a href="?subject_id={{ $sb->id }}#daftar-pertanyaan" class="btn-buat-soal-purple" title="Buka Daftar Pertanyaan Soal untuk {{ $sb->name }}">
                                Buat Soal
                            </a>
                        </td>
                        <td style="text-align: center; padding: 14px 10px;">
                            <div class="action-stack">
                                <button type="button" class="btn-action-ubah" onclick="openEditSubjectModal('{{ $sb->id }}', '{{ addslashes($sb->code) }}', '{{ addslashes($sb->name) }}')">
                                    Ubah
                                </button>
                                <form method="POST" action="{{ route('admin.subjects.destroy', $sb->id) }}" onsubmit="return confirm('Arsipkan atau hapus bank soal {{ $sb->name }}?');" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-action-arsipkan">
                                        Arsipkan
                                    </button>
                                </form>
                                <button type="button" class="btn-action-cetak" onclick="window.print()">
                                    Cetak
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="empty-state" style="padding: 40px; text-align: center;">
                            <div class="empty-icon" style="font-size: 32px; margin-bottom: 8px;">📝</div>
                            <div class="empty-title" style="font-weight: 700; color: #334155;">Belum ada data bank soal</div>
                            <div class="empty-desc" style="color: #94a3b8; font-size: 13px;">Klik tombol "+ Tambah Bank Soal" di atas untuk menambahkan.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- 7. REPOSITORI DAFTAR PERTANYAAN SOAL (SETELAH KLIK BUAT SOAL) -->
@if($selectedSubject)
<div id="daftar-pertanyaan" class="card" style="margin-bottom: 24px; border-top: 4px solid #5b47fb;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 20px;">📋</span>
                <h2 style="font-size: 1.15rem; font-weight: 700; color: #1e293b; margin: 0;">
                    Daftar Pertanyaan: {{ $selectedSubject->name }}
                </h2>
            </div>
            <span style="font-size: 0.85rem; color: #64748b; margin-top: 4px; display: inline-block;">
                Disini Anda bisa membuat soal ujian berbentuk pilihan ganda dan essai. Klik tombol <strong>👁️ Ikon Mata</strong> untuk menonaktifkan/mengaktifkan soal.
            </span>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="{{ route('admin.questions.create', ['subject_id' => $selectedSubject->id]) }}" class="btn btn-primary" style="background: #5b47fb; border-color: #5b47fb; font-weight: 600;">
                + Tambah Soal
            </a>
            <a href="{{ route('admin.questions.template') }}" class="btn btn-secondary">
                📥 Template Excel
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 45px; text-align: center;">No</th>
                    <th style="width: 90px;">Tipe</th>
                    <th>Pertanyaan &amp; Pilihan Jawaban</th>
                    <th style="width: 75px; text-align: center;">Bobot</th>
                    <th style="width: 75px; text-align: center;">Kunci</th>
                    <th style="width: 95px; text-align: center;">Status</th>
                    <th style="width: 120px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $subjectQuestions = $questions->filter(fn($q) => $q->subject_id == $selectedSubject->id);
                @endphp
                @forelse($subjectQuestions as $qIdx => $q)
                    @php $isInactive = ($q->status === 'inactive'); @endphp
                    <tr style="{{ $isInactive ? 'background: #f8fafc;' : '' }}">
                        <td style="text-align: center; font-weight: 600; color: #64748b;">
                            {{ $qIdx + 1 }}
                        </td>
                        <td>
                            @if($q->question_type === 'essay')
                                <span class="badge" style="background: #fef3c7; color: #92400e; font-weight: 700;">ESSAI</span>
                            @elseif($q->question_type === 'multiple_choice')
                                <span class="badge" style="background: #e0e7ff; color: #3730a3; font-weight: 700;">PG MULTI</span>
                            @else
                                <span class="badge" style="background: #dbeafe; color: #1e40af; font-weight: 700;">PG</span>
                            @endif
                        </td>
                        <td>
                            <div style="{{ $isInactive ? 'text-decoration: line-through; opacity: 0.55; color: #94a3b8; font-style: italic;' : 'color: #1e293b; font-size: 13.5px; line-height: 1.5;' }}">
                                {!! nl2br(e($q->content)) !!}
                            </div>
                            @if($q->options && $q->options->count() > 0 && $q->question_type !== 'essay')
                                <div style="margin-top: 8px; font-size: 12px; color: #64748b; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 6px; {{ $isInactive ? 'opacity: 0.5;' : '' }}">
                                    @foreach($q->options as $opt)
                                        <div style="{{ $opt->is_correct ? 'font-weight: 700; color: #16a34a;' : '' }}">
                                            <strong>{{ $opt->option_label }}.</strong> {{ Str::limit($opt->content, 35) }}
                                            @if($opt->is_correct) <span style="font-size: 10.5px;">✓ (Kunci)</span> @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td style="text-align: center; font-weight: 600; color: #334155;">
                            {{ $q->score_weight }}
                        </td>
                        <td style="text-align: center;">
                            @php
                                $correctOpt = $q->options->firstWhere('is_correct', true);
                            @endphp
                            @if($q->question_type === 'essay')
                                <span style="color: #94a3b8; font-size: 11.5px;">(Bobot Essai)</span>
                            @else
                                <span class="badge" style="background: #dcfce7; color: #15803d; font-weight: 700;">
                                    {{ $correctOpt->option_label ?? '-' }}
                                </span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if($isInactive)
                                <span class="badge" style="background: #f1f5f9; color: #94a3b8; border: 1px solid #cbd5e1;" title="Soal tidak akan tampil di siswa (dicoret)">Dicoret</span>
                            @else
                                <span class="badge" style="background: #dcfce7; color: #15803d;">Aktif</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <div style="display: inline-flex; gap: 6px; align-items: center; justify-content: center;">
                                <!-- ICON MATA (TOGGLE AKTIF / NONAKTIF) -->
                                <form method="POST" action="{{ route('admin.questions.toggle-status', $q->id) }}" style="display:inline; margin: 0;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm" style="padding: 4px 8px; border-radius: 4px; background: {{ $isInactive ? '#f1f5f9' : '#e0e7ff' }}; color: {{ $isInactive ? '#64748b' : '#4338ca' }}; border: 1px solid {{ $isInactive ? '#cbd5e1' : '#c7d2fe' }}; cursor: pointer;" title="{{ $isInactive ? 'Klik ikon mata untuk mengaktifkan kembali soal ini' : 'Klik ikon mata untuk menonaktifkan soal ini (pertanyaan akan dicoret dan tidak tampil pada siswa)' }}">
                                        {{ $isInactive ? '👁️‍🗨️' : '👁️' }}
                                    </button>
                                </form>
                                <a href="{{ route('admin.questions.edit', $q->id) }}" class="btn btn-sm btn-secondary" style="padding: 4px 8px;" title="Ubah Soal">✏️</a>
                                <form method="POST" action="{{ route('admin.questions.destroy', $q->id) }}" onsubmit="return confirm('Hapus butir pertanyaan ini?');" style="display:inline; margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" style="padding: 4px 8px;" title="Hapus Soal">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state" style="padding: 30px; text-align: center;">
                            <div style="font-size: 26px; margin-bottom: 6px;">📝</div>
                            <div style="font-weight: 600; color: #334155;">Belum ada butir pertanyaan untuk bank soal ini</div>
                            <div style="color: #94a3b8; font-size: 13px; margin-top: 4px;">Klik "+ Tambah Soal" di atas untuk memasukkan pertanyaan pilihan ganda atau essai.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- MODAL TAMBAH BANK SOAL (SESUAI SPESIFIKASI LENGKAP PANDUAN) -->
<div class="modal-overlay" id="createSubjectModal">
    <div class="modal-content-card" style="max-width: 580px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 16px 20px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 20px;">📚</span>
                <h3 class="modal-title" style="margin: 0; font-size: 16px;">Tambah Bank Soal Baru</h3>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeCreateSubjectModal()">&times;</button>
        </div>
        <form action="{{ route('admin.subjects.store') }}" method="POST">
            @csrf
            <div style="padding: 20px;">
                @if($userRole === 'admin')
                    <!-- GURU: Muncul jika admin, otomatis jika guru -->
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label" style="font-weight: 600;">Guru Pemilik Akses *</label>
                        <select name="teacher_id" class="form-select">
                            <option value="">-- Pilih Guru yang Memiliki Akses --</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}">{{ $t->user->name ?? 'Guru '.$t->id }} ({{ $t->nip ?? 'NIP -' }})</option>
                            @endforeach
                        </select>
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                            Guru yang akan memiliki akses ke bank soal yang akan kita buat.
                        </div>
                    </div>
                @endif

                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label" style="font-weight: 600;">Judul Bank Soal *</label>
                    <input type="text" name="name" id="bs_judul" class="form-control" placeholder="Contoh: Testing Ujian Tryout, Matematika X" oninput="autoGenerateCode(this.value)" required>
                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Merupakan nama atau identitas bank soal.</div>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label" style="font-weight: 600;">Kode Bank Soal (Otomatis/Singkatan) *</label>
                    <input type="text" name="code" id="bs_kode" class="form-control" placeholder="Contoh: TRY-01, MAT-X" style="text-transform: uppercase;" required>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label" style="font-weight: 600;">Format Penilaian *</label>
                    <select name="format" class="form-select">
                        <option value="standard">Standard (Penilaian Nilai Biasa)</option>
                        <option value="tryout">Tryout (UTBK / SNMPTN / CPNS)</option>
                    </select>
                    <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 4px;">
                        Merupakan tipe format penilaian apakah menggunakan metode standard atau tryout. Lihat <a href="https://e-ujian.id/panduan-menggunakan-cbt-eujian/membuat-soal-tryout-utbk-snmptn-dan-cpns-online/" target="_blank" style="color: #2563eb; text-decoration: underline;">penjelasan format tryout</a>.
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label" style="font-weight: 600;">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Penjelasan singkat tentang bank soal yang akan kita buat..."></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" style="font-weight: 600;">Waktu (Menit) *</label>
                    <input type="number" name="duration" class="form-control" value="120" min="5" max="1440" required>
                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Durasi atau timer berapa lama ujian dapat dilakukan oleh siswa.</div>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" style="font-weight: 600;">Bobot Macam-Macam Tipe Soal (Total Harus 100%) *</label>
                    <div style="font-size: 11.5px; color: #64748b; margin-bottom: 8px;">
                        Total bobot harus 100% dan jika ada soal yang tidak digunakan, maka silakan beri bobot 0.
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap: 8px;">
                        <div>
                            <label style="font-size: 11px; font-weight: 600; color: #475569;">PG (%)</label>
                            <input type="number" id="bw_pg" class="form-control" value="70" min="0" max="100" oninput="calcWeights()">
                        </div>
                        <div>
                            <label style="font-size: 11px; font-weight: 600; color: #475569;">PG MULTI (%)</label>
                            <input type="number" id="bw_multi" class="form-control" value="0" min="0" max="100" oninput="calcWeights()">
                        </div>
                        <div>
                            <label style="font-size: 11px; font-weight: 600; color: #475569;">ESSAI (%)</label>
                            <input type="number" id="bw_essay" class="form-control" value="30" min="0" max="100" oninput="calcWeights()">
                        </div>
                        <div>
                            <label style="font-size: 11px; font-weight: 600; color: #475569;">B/S (%)</label>
                            <input type="number" id="bw_tf" class="form-control" value="0" min="0" max="100" oninput="calcWeights()">
                        </div>
                        <div>
                            <label style="font-size: 11px; font-weight: 600; color: #475569;">JODOH (%)</label>
                            <input type="number" id="bw_match" class="form-control" value="0" min="0" max="100" oninput="calcWeights()">
                        </div>
                    </div>
                    <div id="weight-alert" style="margin-top: 8px; font-size: 12px; font-weight: 600; color: #16a34a;">
                        Total Bobot: <span id="weight-total">100</span>% (Sesuai)
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateSubjectModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background: #5b47fb; border-color: #5b47fb; font-weight: 700;">
                        Klik Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT MATA PELAJARAN -->
<div class="modal-overlay" id="editSubjectModal">
    <div class="modal-content-card" style="max-width: 480px;">
        <div class="modal-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 20px;">✏️</span>
                <h3 class="modal-title" style="margin: 0;">Edit Data</h3>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeEditSubjectModal()">&times;</button>
        </div>
        <form id="editSubjectForm" action="" method="POST">
            @csrf
            @method('PUT')
            <div style="padding: 20px;">
                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label">Kode Mata Pelajaran *</label>
                    <input type="text" name="code" id="edit_sb_code" class="form-control" style="text-transform: uppercase;" required>
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Nama Mata Pelajaran *</label>
                    <input type="text" name="name" id="edit_sb_name" class="form-control" required>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="closeEditSubjectModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateSubjectModal() {
    const modal = document.getElementById('createSubjectModal');
    if (modal) modal.classList.add('active');
}

function closeCreateSubjectModal() {
    const modal = document.getElementById('createSubjectModal');
    if (modal) modal.classList.remove('active');
}

function openEditSubjectModal(id, code, name) {
    const form = document.getElementById('editSubjectForm');
    if (form) form.action = '/admin/subjects/' + id;
    const codeInput = document.getElementById('edit_sb_code');
    const nameInput = document.getElementById('edit_sb_name');
    if (codeInput) codeInput.value = code;
    if (nameInput) nameInput.value = name;
    const modal = document.getElementById('editSubjectModal');
    if (modal) modal.classList.add('active');
}

function closeEditSubjectModal() {
    const modal = document.getElementById('editSubjectModal');
    if (modal) modal.classList.remove('active');
}

function autoGenerateCode(val) {
    var codeInput = document.getElementById('bs_kode');
    if (codeInput && (!codeInput.value || codeInput.dataset.autogen === "true" || codeInput.value.length <= 4)) {
        var words = val.trim().split(/\s+/);
        var code = "";
        if (words.length > 1) {
            code = words.map(function(w) { return w[0]; }).join("").toUpperCase().substring(0, 5);
        } else if (val.length > 0) {
            code = val.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().substring(0, 4);
        }
        if (code) {
            codeInput.value = code;
            codeInput.dataset.autogen = "true";
        }
    }
}

function calcWeights() {
    var pg = parseInt(document.getElementById('bw_pg')?.value || 0, 10);
    var multi = parseInt(document.getElementById('bw_multi')?.value || 0, 10);
    var essay = parseInt(document.getElementById('bw_essay')?.value || 0, 10);
    var tf = parseInt(document.getElementById('bw_tf')?.value || 0, 10);
    var match = parseInt(document.getElementById('bw_match')?.value || 0, 10);
    var total = pg + multi + essay + tf + match;

    var totalEl = document.getElementById('weight-total');
    var alertEl = document.getElementById('weight-alert');
    if (totalEl) totalEl.innerText = total;

    if (alertEl) {
        if (total === 100) {
            alertEl.style.color = '#16a34a';
            alertEl.innerHTML = 'Total Bobot: <span id="weight-total">' + total + '</span>% (Sesuai 100%)';
        } else {
            alertEl.style.color = '#dc2626';
            alertEl.innerHTML = 'Total Bobot: <span id="weight-total">' + total + '</span>% (Harus 100%, selisih: ' + (100 - total) + '%)';
        }
    }
}
</script>
@endsection
