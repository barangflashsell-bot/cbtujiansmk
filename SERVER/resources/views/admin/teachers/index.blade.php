@extends('layouts.app')

@section('title', 'Manajemen Guru — CBT Local Server')
@section('page-title', 'Manajemen Guru')

@section('content')
<div style="display: flex; flex-direction: column; gap: 20px;">
    <!-- ACTION BAR -->
    <div class="action-bar">
        <div class="filter-group">
            <form action="{{ route('admin.teachers.index') }}" method="GET" style="display: flex; gap: 8px;">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    class="form-control" 
                    placeholder="Cari nama, username, NIP..."
                    style="max-width: 280px;"
                >
                <button type="submit" class="btn btn-secondary">Cari</button>
                @if(request('search'))
                    <a href="{{ route('admin.teachers.index') }}" class="btn btn-secondary">Reset</a>
                @endif
            </form>
        </div>

        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-secondary" onclick="openImportModal()" style="display: inline-flex; align-items: center; gap: 6px; border-color: #bae6fd; color: #0284c7;">
                <span>📊</span> Import Data Excel
            </button>

            <button type="button" class="btn btn-primary" onclick="document.getElementById('createTeacherCard').style.display = 'block'; window.scrollTo({top: document.getElementById('createTeacherCard').offsetTop - 80, behavior: 'smooth'});">
                <span>+</span> Tambah Guru Baru
            </button>
        </div>
    </div>

    <!-- CREATE FORM CARD -->
    <div class="card" id="createTeacherCard" style="display: none; border-color: var(--primary);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 class="card-title" style="margin-bottom: 0;">Tambah Akun & Data Guru Baru</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('createTeacherCard').style.display = 'none';">&times; Batal</button>
        </div>

        <form action="{{ route('admin.teachers.store') }}" method="POST">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Username Login *</label>
                    <input type="text" name="username" class="form-control" placeholder="Contoh: guru.budi" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nama Lengkap Guru *</label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Budi Santoso, S.Pd" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Password Awal *</label>
                    <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
                </div>

                <div class="form-group">
                    <label class="form-label">NIP (Opsional)</label>
                    <input type="text" name="nip" class="form-control" placeholder="Nomor Induk Pegawai">
                </div>

                <div class="form-group">
                    <label class="form-label">No. Telepon / WhatsApp</label>
                    <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px;">
                <button type="submit" class="btn btn-primary">Simpan Data Guru</button>
            </div>
        </form>
    </div>

    <!-- TEACHERS TABLE -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Nama Guru</th>
                        <th>Username</th>
                        <th>NIP</th>
                        <th>Telepon</th>
                        <th>Status Akun</th>
                        <th style="width: 140px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($teachers as $idx => $item)
                        <tr>
                            <td>{{ $teachers->firstItem() + $idx }}</td>
                            <td>
                                <strong style="font-size: 14px;">{{ $item->user?->name ?? 'Guru' }}</strong>
                            </td>
                            <td>
                                <code>{{ $item->user?->username ?? '-' }}</code>
                            </td>
                            <td>{{ $item->nip ?? '-' }}</td>
                            <td>{{ $item->phone ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $item->user?->is_active ? 'badge-success' : 'badge-danger' }}">
                                    {{ $item->user?->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <div class="action-btns">
                                    <button 
                                        type="button" 
                                        class="btn btn-secondary btn-sm"
                                        onclick="editTeacher('{{ $item->id }}', '{{ addslashes($item->user?->name ?? '') }}', '{{ addslashes($item->nip ?? '') }}', '{{ addslashes($item->phone ?? '') }}', '{{ $item->user?->is_active ? 1 : 0 }}')"
                                    >
                                        Edit
                                    </button>

                                    <form action="{{ route('admin.teachers.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus data guru {{ addslashes($item->user?->name ?? '') }}?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-state-icon">👨‍🏫</div>
                                    <p>Belum ada data guru yang terdaftar.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($teachers->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 12.5px; color: var(--text-muted);">
                    Menampilkan {{ $teachers->firstItem() }} - {{ $teachers->lastItem() }} dari {{ $teachers->total() }} guru
                </div>
                <div style="display: flex; gap: 6px;">
                    @if ($teachers->onFirstPage())
                        <span class="btn btn-secondary btn-sm" style="opacity: 0.5; cursor: not-allowed;">Prev</span>
                    @else
                        <a href="{{ $teachers->previousPageUrl() }}" class="btn btn-secondary btn-sm">Prev</a>
                    @endif

                    @if ($teachers->hasMorePages())
                        <a href="{{ $teachers->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                    @else
                        <span class="btn btn-secondary btn-sm" style="opacity: 0.5; cursor: not-allowed;">Next</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- EDIT FORM CARD -->
    <div class="card" id="editTeacherCard" style="display: none; border-color: var(--primary); margin-top: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 class="card-title" style="margin-bottom: 0;">Edit Data Guru</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('editTeacherCard').style.display = 'none';">&times; Tutup</button>
        </div>

        <form id="editTeacherForm" method="POST">
            @csrf
            @method('PUT')
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap Guru *</label>
                    <input type="text" id="edit_teacher_name" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Reset Password (Kosongkan jika tidak diubah)</label>
                    <input type="password" name="password" class="form-control" placeholder="Biarkan kosong jika tetap">
                </div>

                <div class="form-group">
                    <label class="form-label">NIP</label>
                    <input type="text" id="edit_teacher_nip" name="nip" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">No. Telepon / WhatsApp</label>
                    <input type="text" id="edit_teacher_phone" name="phone" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Status Akun</label>
                    <select id="edit_teacher_status" name="is_active" class="form-select" style="width: 100%;">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px;">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>

    <!-- MODAL IMPORT GURU EXCEL / CSV -->
    <div class="modal-overlay" id="importTeacherModal">
        <div class="modal-content-card" style="max-width: 520px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 20px;">📊</span>
                    <h3 class="modal-title" style="margin: 0;">Import Data Guru dari Excel</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeImportModal()">&times;</button>
            </div>

            <form action="{{ route('admin.teachers.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div style="margin-bottom: 16px;">
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 12px; line-height: 1.45;">
                        Unggah file daftar guru/pengajar dalam format <strong>Excel (.xlsx)</strong> atau <strong>CSV (.csv)</strong>. Sistem otomatis membuat akun login guru.
                    </p>

                    <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                        <div>
                            <div style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">Belum punya formatnya?</div>
                            <div style="font-size: 11px; color: var(--text-muted);">Unduh template standar data guru</div>
                        </div>
                        <a href="{{ route('admin.teachers.template') }}" class="btn btn-secondary btn-sm" style="color: #0284c7; border-color: #bae6fd; text-decoration: none;">
                            <span>📥</span> Unduh Template
                        </a>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label">Pilih File Excel / CSV *</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv,.txt" required style="padding: 7px 12px;">
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Mendukung .xlsx, .xls, dan .csv (Maksimal 10MB)</div>
                    </div>

                    <div style="margin-bottom: 16px; display: flex; align-items: flex-start; gap: 8px;">
                        <input type="checkbox" id="teacher_update_existing" name="update_existing" value="1" style="margin-top: 2px;">
                        <label for="teacher_update_existing" style="font-size: 12.5px; color: var(--text-primary); cursor: pointer; line-height: 1.35;">
                            <strong>Perbarui data jika NIP / Username sudah terdaftar</strong>
                        </label>
                    </div>

                    <div style="background: var(--bg-surface-elevated); border: 1px dashed var(--border-color); border-radius: 6px; padding: 10px 12px; font-size: 11px; color: var(--text-secondary);">
                        <strong style="display: block; margin-bottom: 4px; color: var(--text-primary);">Format Kolom File:</strong>
                        <code>Nama Lengkap | Username | Password | NIP | No HP</code>
                        <div style="margin-top: 4px; font-size: 10.5px; color: var(--text-muted);">
                            &bull; Jika password dikosongkan, default password adalah <code>123456</code> atau NIP.<br>
                            &bull; Jika username dikosongkan, otomatis menggunakan NIP.
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
</div>

<script>
function editTeacher(id, name, nip, phone, isActive) {
    const card = document.getElementById('editTeacherCard');
    const form = document.getElementById('editTeacherForm');
    form.action = '/admin/teachers/' + id;
    document.getElementById('edit_teacher_name').value = name;
    document.getElementById('edit_teacher_nip').value = nip;
    document.getElementById('edit_teacher_phone').value = phone;
    document.getElementById('edit_teacher_status').value = isActive;
    card.style.display = 'block';
    window.scrollTo({top: card.offsetTop - 80, behavior: 'smooth'});
}

function openImportModal() {
    const modal = document.getElementById('importTeacherModal');
    if (modal) modal.classList.add('active');
}

function closeImportModal() {
    const modal = document.getElementById('importTeacherModal');
    if (modal) modal.classList.remove('active');
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('importTeacherModal');
    if (modal && e.target === modal) closeImportModal();
});
</script>
@endsection
