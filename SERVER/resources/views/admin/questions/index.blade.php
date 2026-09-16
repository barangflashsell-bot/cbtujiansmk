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
        <p class="page-subtitle">Daftar bank soal ujian, komposisi butir pertanyaan, dan aksi kelola</p>
    </div>
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <button type="button" class="btn btn-secondary" onclick="openCreateSubjectModal()" style="display: inline-flex; align-items: center; gap: 6px; border-color: #fbcfe8; color: #db2777; font-weight: 600;">
            <span>📚</span> + Tambah Mapel
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
                    <tr style="vertical-align: middle;">
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
                            <a href="{{ route('admin.questions.create', ['subject_id' => $sb->id]) }}" class="btn-buat-soal-purple" title="Buat Butir Soal untuk {{ $sb->name }}">
                                Buat Soal
                            </a>
                        </td>
                        <td style="text-align: center; padding: 14px 10px;">
                            <div class="action-stack">
                                <button type="button" class="btn-action-ubah" onclick="openEditSubjectModal('{{ $sb->id }}', '{{ addslashes($sb->code) }}', '{{ addslashes($sb->name) }}')">
                                    Ubah
                                </button>
                                <form method="POST" action="{{ route('admin.subjects.destroy', $sb->id) }}" onsubmit="return confirm('Arsipkan atau hapus mata pelajaran {{ $sb->name }}?');" style="margin: 0;">
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
                            <div class="empty-title" style="font-weight: 700; color: #334155;">Belum ada data mata pelajaran / bank soal</div>
                            <div class="empty-desc" style="color: #94a3b8; font-size: 13px;">Klik tombol "+ Tambah Mapel" di atas untuk menambahkan.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL TAMBAH MATA PELAJARAN -->
<div class="modal-overlay" id="createSubjectModal">
    <div class="modal-content-card" style="max-width: 480px;">
        <div class="modal-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 20px;">📚</span>
                <h3 class="modal-title" style="margin: 0;">Tambah Mata Pelajaran Baru</h3>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeCreateSubjectModal()">&times;</button>
        </div>
        <form action="{{ route('admin.subjects.store') }}" method="POST">
            @csrf
            <div style="padding: 20px;">
                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label">Kode Mata Pelajaran *</label>
                    <input type="text" name="code" class="form-control" placeholder="Contoh: MAT, BIND, RPL" style="text-transform: uppercase;" required>
                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Singkatan atau kode unik mapel.</div>
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Nama Mata Pelajaran *</label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Testing Ujian Tryout, Matematika X" required>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateSubjectModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Mata Pelajaran</button>
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

document.addEventListener('click', function(e) {
    const createSubjectModal = document.getElementById('createSubjectModal');
    if (createSubjectModal && e.target === createSubjectModal) closeCreateSubjectModal();
    const editSubjectModal = document.getElementById('editSubjectModal');
    if (editSubjectModal && e.target === editSubjectModal) closeEditSubjectModal();
});
</script>
@endsection
