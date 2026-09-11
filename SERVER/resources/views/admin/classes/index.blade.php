@extends('layouts.app')

@section('title', 'Manajemen Kelas — CBT Local Server')
@section('page-title', 'Manajemen Kelas')

@section('content')
<div style="display: flex; flex-direction: column; gap: 20px;">
    <!-- ACTION BAR -->
    <div class="action-bar">
        <div class="filter-group">
            <form action="{{ route('admin.classes.index') }}" method="GET" style="display: flex; gap: 8px;">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    class="form-control" 
                    placeholder="Cari nama / tingkat kelas..."
                    style="max-width: 260px;"
                >
                <button type="submit" class="btn btn-secondary">Cari</button>
                @if(request('search'))
                    <a href="{{ route('admin.classes.index') }}" class="btn btn-secondary">Reset</a>
                @endif
            </form>
        </div>

        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-secondary" onclick="openImportModal()" style="display: inline-flex; align-items: center; gap: 6px; border-color: #bae6fd; color: #0284c7;">
                <span>📊</span> Import Data Excel
            </button>

            <button type="button" class="btn btn-primary" onclick="document.getElementById('createClassCard').style.display = 'block'; window.scrollTo({top: document.getElementById('createClassCard').offsetTop - 80, behavior: 'smooth'});">
                <span>+</span> Tambah Kelas Baru
            </button>
        </div>
    </div>

    <!-- CREATE FORM CARD (Collapsible) -->
    <div class="card" id="createClassCard" style="display: none; border-color: var(--primary);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 class="card-title" style="margin-bottom: 0;">Tambah Kelas / Rombel Baru</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('createClassCard').style.display = 'none';">&times; Batal</button>
        </div>

        <form action="{{ route('admin.classes.store') }}" method="POST">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama Kelas / Rombel *</label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: VII-A, X-RPL 1" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Tingkat / Jenjang *</label>
                    <input type="text" name="level" class="form-control" placeholder="Contoh: 7, 8, 9, 10, 11, 12" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Tahun Ajaran *</label>
                    <input type="text" name="academic_year" class="form-control" value="2025/2026" placeholder="Contoh: 2025/2026" required>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px;">
                <button type="submit" class="btn btn-primary">Simpan Kelas</button>
            </div>
        </form>
    </div>

    <!-- CLASSES TABLE -->
    <div class="card" style="padding: 0; overflow: hidden;">
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Nama Kelas</th>
                        <th>Tingkat</th>
                        <th>Tahun Ajaran</th>
                        <th>Jumlah Siswa</th>
                        <th>Status</th>
                        <th style="width: 140px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classes as $idx => $item)
                        <tr>
                            <td>{{ $classes->firstItem() + $idx }}</td>
                            <td>
                                <strong style="font-size: 14px;">{{ $item->name }}</strong>
                            </td>
                            <td>{{ $item->level }}</td>
                            <td>{{ $item->academic_year }}</td>
                            <td>
                                <span class="badge badge-primary">{{ $item->students_count }} Siswa</span>
                            </td>
                            <td>
                                <span class="badge {{ $item->status === 'active' ? 'badge-success' : 'badge-neutral' }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <div class="action-btns">
                                    <button 
                                        type="button" 
                                        class="btn btn-secondary btn-sm"
                                        onclick="editClass('{{ $item->id }}', '{{ addslashes($item->name) }}', '{{ $item->level }}', '{{ $item->academic_year }}', '{{ $item->status }}')"
                                    >
                                        Edit
                                    </button>

                                    <form action="{{ route('admin.classes.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus kelas {{ addslashes($item->name) }}?');" style="display: inline;">
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
                                    <div class="empty-state-icon">🏫</div>
                                    <p>Belum ada data kelas yang terdaftar.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($classes->hasPages())
            <div style="padding: 16px 20px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 12.5px; color: var(--text-muted);">
                    Menampilkan {{ $classes->firstItem() }} - {{ $classes->lastItem() }} dari {{ $classes->total() }} kelas
                </div>
                <div style="display: flex; gap: 6px;">
                    @if ($classes->onFirstPage())
                        <span class="btn btn-secondary btn-sm" style="opacity: 0.5; cursor: not-allowed;">Prev</span>
                    @else
                        <a href="{{ $classes->previousPageUrl() }}" class="btn btn-secondary btn-sm">Prev</a>
                    @endif

                    @if ($classes->hasMorePages())
                        <a href="{{ $classes->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
                    @else
                        <span class="btn btn-secondary btn-sm" style="opacity: 0.5; cursor: not-allowed;">Next</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- EDIT FORM MODAL / CARD -->
    <div class="card" id="editClassCard" style="display: none; border-color: var(--primary); margin-top: 12px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 class="card-title" style="margin-bottom: 0;">Edit Data Kelas</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('editClassCard').style.display = 'none';">&times; Tutup</button>
        </div>

        <form id="editClassForm" method="POST">
            @csrf
            @method('PUT')
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nama Kelas / Rombel *</label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Tingkat / Jenjang *</label>
                    <input type="text" id="edit_level" name="level" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Tahun Ajaran *</label>
                    <input type="text" id="edit_academic_year" name="academic_year" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select id="edit_status" name="status" class="form-select" style="width: 100%;">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px;">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>

    <!-- MODAL IMPORT KELAS EXCEL / CSV -->
    <div class="modal-overlay" id="importClassModal">
        <div class="modal-content-card" style="max-width: 520px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 20px;">📊</span>
                    <h3 class="modal-title" style="margin: 0;">Import Data Kelas dari Excel</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeImportModal()">&times;</button>
            </div>

            <form action="{{ route('admin.classes.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div style="margin-bottom: 16px;">
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 12px; line-height: 1.45;">
                        Unggah daftar rombongan belajar / kelas dalam format <strong>Excel (.xlsx)</strong> atau <strong>CSV (.csv)</strong>.
                    </p>

                    <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                        <div>
                            <div style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">Belum punya formatnya?</div>
                            <div style="font-size: 11px; color: var(--text-muted);">Unduh template standar data kelas</div>
                        </div>
                        <a href="{{ route('admin.classes.template') }}" class="btn btn-secondary btn-sm" style="color: #0284c7; border-color: #bae6fd; text-decoration: none;">
                            <span>📥</span> Unduh Template
                        </a>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label">Pilih File Excel / CSV *</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv,.txt" required style="padding: 7px 12px;">
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Mendukung .xlsx, .xls, dan .csv (Maksimal 10MB)</div>
                    </div>

                    <div style="margin-bottom: 16px; display: flex; align-items: flex-start; gap: 8px;">
                        <input type="checkbox" id="class_update_existing" name="update_existing" value="1" style="margin-top: 2px;">
                        <label for="class_update_existing" style="font-size: 12.5px; color: var(--text-primary); cursor: pointer; line-height: 1.35;">
                            <strong>Perbarui data jika Nama Kelas sudah ada</strong>
                        </label>
                    </div>

                    <div style="background: var(--bg-surface-elevated); border: 1px dashed var(--border-color); border-radius: 6px; padding: 10px 12px; font-size: 11px; color: var(--text-secondary);">
                        <strong style="display: block; margin-bottom: 4px; color: var(--text-primary);">Format Kolom File:</strong>
                        <code>Nama Kelas | Tingkat | Tahun Ajaran | Status</code>
                        <div style="margin-top: 4px; font-size: 10.5px; color: var(--text-muted);">
                            &bull; Status diisi <code>active</code> atau <code>inactive</code> (default: active).<br>
                            &bull; Jika Tahun Ajaran kosong, otomatis diisi tahun ajaran berjalan.
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
function editClass(id, name, level, academicYear, status) {
    const card = document.getElementById('editClassCard');
    const form = document.getElementById('editClassForm');
    form.action = '/admin/classes/' + id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_level').value = level;
    document.getElementById('edit_academic_year').value = academicYear;
    document.getElementById('edit_status').value = status;
    card.style.display = 'block';
    window.scrollTo({top: card.offsetTop - 80, behavior: 'smooth'});
}

function openImportModal() {
    const modal = document.getElementById('importClassModal');
    if (modal) modal.classList.add('active');
}

function closeImportModal() {
    const modal = document.getElementById('importClassModal');
    if (modal) modal.classList.remove('active');
}

document.addEventListener('click', function(e) {
    const modal = document.getElementById('importClassModal');
    if (modal && e.target === modal) closeImportModal();
});
</script>
@endsection
