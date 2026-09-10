@extends('layouts.app')

@section('title', 'Pengaturan Sistem — CBT Administrator')
@section('page-title', 'Pengaturan Sistem Server')

@section('content')
<div style="max-width: 900px;">
    <div style="margin-bottom: 20px;">
        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
            Konfigurasi Operasional Server Lokal CBT
        </h2>
        <p style="font-size: 0.85rem; color: var(--text-secondary);">
            Pengaturan tersimpan aman dalam konfigurasi server lokal (storage/app/settings.json) dengan pencatatan audit trail otomatis.
        </p>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        <!-- SECTION 1: IDENTITAS SEKOLAH & AKADEMIK -->
        <div class="card" style="margin-bottom: 24px;">
            <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                <h3 class="card-title" style="margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                    <span>🏫</span> Identitas Sekolah & Tahun Ajaran
                </h3>
                <span style="font-size: 0.8rem; color: var(--text-muted);">
                    Informasi nama institusi dan tahun pelajaran yang akan tercantum pada kartu ujian dan lembar laporan.
                </span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Nama Sekolah / Lembaga <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="school_name" class="form-control" value="{{ old('school_name', $settings['school_name'] ?? '') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Tahun Ajaran Aktif <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="academic_year" class="form-control" placeholder="Contoh: 2025/2026" value="{{ old('academic_year', $settings['academic_year'] ?? '') }}" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Alamat Lengkap Sekolah</label>
                <textarea name="school_address" class="form-control" rows="2" placeholder="Alamat jalan, kelurahan, kecamatan, kota/kabupaten">{{ old('school_address', $settings['school_address'] ?? '') }}</textarea>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">URL / Path Berkas Logo Sekolah (Opsional)</label>
                <input type="text" name="logo_url" class="form-control" placeholder="/images/logo.png" value="{{ old('logo_url', $settings['logo_url'] ?? '') }}">
            </div>
        </div>

        <!-- SECTION 2: SERVER LOKAL & JARINGAN -->
        <div class="card" style="margin-bottom: 24px;">
            <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                <h3 class="card-title" style="margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                    <span>🖥️</span> Aplikasi & Jaringan Lokal Server
                </h3>
                <span style="font-size: 0.8rem; color: var(--text-muted);">
                    Parameter nama sistem dan port layanan untuk distribusi jaringan Wi-Fi/LAN lokal.
                </span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Nama Aplikasi CBT <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="app_name" class="form-control" value="{{ old('app_name', $settings['app_name'] ?? '') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Port Server Lokal (HTTP) <span style="color: var(--danger);">*</span></label>
                    <input type="number" name="server_port" class="form-control" min="80" max="65535" value="{{ old('server_port', $settings['server_port'] ?? 8000) }}" required>
                    <span style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; display: block;">
                        Rentang port valid: 80 - 65535 (default 8000).
                    </span>
                </div>
            </div>
        </div>

        <!-- SECTION 3: TOKEN & AKSES PESERTA -->
        <div class="card" style="margin-bottom: 24px;">
            <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                <h3 class="card-title" style="margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                    <span>🔑</span> Token Ujian & Kebijakan Siswa
                </h3>
                <span style="font-size: 0.8rem; color: var(--text-muted);">
                    Pengaturan perilisan token dinamis dan hak akses peninjauan hasil oleh peserta.
                </span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Interval Refresh Token Ujian (Menit) <span style="color: var(--danger);">*</span></label>
                    <input type="number" name="token_refresh_minutes" class="form-control" style="max-width: 200px;" min="5" max="180" value="{{ old('token_refresh_minutes', $settings['token_refresh_minutes'] ?? 15) }}" required>
                    <span style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; display: block;">
                        Rentang waktu: 5 sampai 180 menit.
                    </span>
                </div>

                <div style="background: var(--bg-main); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px 16px; display: flex; flex-direction: column; gap: 12px;">
                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="auto_token_release" value="1" {{ old('auto_token_release', $settings['auto_token_release'] ?? false) ? 'checked' : '' }} style="margin-top: 3px; width: 18px; height: 18px;">
                        <div>
                            <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-primary);">Rilis Token Otomatis (Auto Token Release)</div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                Jika diaktifkan, token ujian akan diperbarui secara otomatis sesuai interval refresh di atas.
                            </div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="allow_student_review" value="1" {{ old('allow_student_review', $settings['allow_student_review'] ?? false) ? 'checked' : '' }} style="margin-top: 3px; width: 18px; height: 18px;">
                        <div>
                            <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-primary);">Izinkan Peninjauan Soal & Jawaban oleh Siswa (Allow Review)</div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                Mengizinkan peserta melihat kembali lembar respon jawaban mereka setelah sesi ujian diselesaikan.
                            </div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- SUBMIT BUTTON -->
        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-bottom: 40px;">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                <span>💾</span> Simpan Seluruh Pengaturan
            </button>
        </div>
    </form>
</div>
@endsection
