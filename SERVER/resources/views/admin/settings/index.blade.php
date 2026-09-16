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

@php
    $serverPort = (int)($settings['server_port'] ?? 8000);
    $detectedIps = [];

    // 1. Deteksi semua alamat IP IPv4 aktif pada antarmuka jaringan komputer ini
    $hostIps = @gethostbynamel(gethostname());
    if (is_array($hostIps)) {
        foreach ($hostIps as $hip) {
            if ($hip !== '127.0.0.1' && !str_starts_with($hip, '169.254.') && filter_var($hip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $detectedIps[] = $hip;
            }
        }
    }

    // 2. Deteksi dari header request server jika diakses via IP jaringan
    $serverAddr = request()->server('SERVER_ADDR');
    if ($serverAddr && $serverAddr !== '127.0.0.1' && !str_starts_with($serverAddr, '169.254.') && !in_array($serverAddr, $detectedIps)) {
        $detectedIps[] = $serverAddr;
    }

    $reqHost = request()->getHost();
    if ($reqHost && $reqHost !== 'localhost' && $reqHost !== '127.0.0.1' && filter_var($reqHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        if (!in_array($reqHost, $detectedIps)) {
            array_unshift($detectedIps, $reqHost);
        }
    }

    // 3. Fallback jika komputer benar-benar belum tersambung ke jaringan manapun
    if (empty($detectedIps)) {
        $detectedIps[] = '192.168.1.11';
    }

    $primaryHostIp = $detectedIps[0];
    $localhostUrl = "http://localhost:{$serverPort}";
    $lanUrl = "http://{$primaryHostIp}:{$serverPort}";
@endphp

    <!-- CARD: ALAMAT AKSES SERVER CBT (LOCALHOST & WI-FI LAN) -->
    <div class="card" style="margin-bottom: 24px; border: 1px solid var(--primary-border); background: var(--bg-surface);">
        <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 class="card-title" style="margin-bottom: 4px; display: flex; align-items: center; gap: 8px; color: var(--primary);">
                    <span>🌐</span> Alamat Akses Server CBT (Otomatis Mendeteksi Jaringan Wi-Fi/LAN)
                </h3>
                <span style="font-size: 0.8rem; color: var(--text-muted);">
                    Sistem mendeteksi alamat IP komputer ini secara real-time. Jika Anda berpindah Wi-Fi atau ganti komputer, alamat ini akan menyesuaikan otomatis.
                </span>
            </div>
            <span class="badge badge-success" style="font-size: 0.75rem; padding: 4px 10px;">
                ● Server Lokal Siap (Offline LAN)
            </span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 16px;">
            <!-- LOCALHOST -->
            <div style="background: var(--bg-main); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 14px 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">
                        💻 Komputer Server (Localhost)
                    </span>
                    <span style="font-size: 0.7rem; background: var(--primary-light); color: var(--primary); padding: 2px 6px; border-radius: 4px; font-weight: 600;">Lokal</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="text" id="serverLocalhostUrl" readonly value="{{ $localhostUrl }}" class="form-control" style="font-family: monospace; font-size: 0.9rem; font-weight: 600; background: var(--bg-surface); cursor: text;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="copyServerUrl('serverLocalhostUrl', this)" title="Salin Alamat">
                        <span>📋</span> Salin
                    </button>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;">
                    Dibuka khusus pada browser komputer server ini sendiri.
                </div>
            </div>

            <!-- WI-FI LAN IP -->
            <div style="background: var(--bg-main); border: 1px solid var(--primary-border); border-radius: var(--radius-sm); padding: 14px 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--primary);">
                        📶 Jaringan Wi-Fi / LAN Aktif
                    </span>
                    <span style="font-size: 0.7rem; background: #dcfce7; color: #15803d; padding: 2px 6px; border-radius: 4px; font-weight: 700;">Untuk Siswa &amp; Guru</span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="text" id="serverLanUrl" readonly value="{{ $lanUrl }}" class="form-control" style="font-family: monospace; font-size: 0.9rem; font-weight: 700; color: var(--primary); background: var(--bg-surface); cursor: text;">
                    <button type="button" class="btn btn-primary btn-sm" onclick="copyServerUrl('serverLanUrl', this)" title="Salin Alamat">
                        <span>📋</span> Salin
                    </button>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;">
                    Bagikan alamat ini kepada siswa untuk dimasukkan ke browser HP/Laptop atau aplikasi Android CBT.
                </div>
            </div>
        </div>

        @if(count($detectedIps) > 1)
        <div style="margin-bottom: 14px; background: var(--bg-main); border: 1px solid var(--border-color); border-radius: 6px; padding: 10px 14px;">
            <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase;">
                Alamat Antarmuka Lain yang Terdeteksi di Komputer Ini:
            </span>
            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px;">
                @foreach($detectedIps as $idx => $altIp)
                    @if($altIp !== $primaryHostIp)
                    <span style="font-family: monospace; font-size: 0.8rem; background: var(--bg-surface); border: 1px solid var(--border-color); padding: 4px 10px; border-radius: 4px; display: inline-flex; align-items: center; gap: 6px;">
                        <span>📡</span> http://{{ $altIp }}:{{ $serverPort }}
                    </span>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 12px 14px; font-size: 0.8rem; color: #1e40af;">
            <div style="font-weight: 700; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                <span style="font-size: 1rem;">🔄</span> Panduan Jika Berpindah Wi-Fi atau Ganti Komputer Server:
            </div>
            <ul style="margin: 0; padding-left: 18px; line-height: 1.6;">
                <li><strong>Jika ganti jaringan Wi-Fi / Router:</strong> Komputer server akan mendapatkan alamat IP baru secara otomatis. Cukup refresh halaman ini atau lihat jendela <code>JALANKAN_SERVER_CBT.bat</code>, alamat baru akan otomatis muncul untuk dibagikan ke siswa.</li>
                <li><strong>Jika pindah ke laptop / komputer lain:</strong> Cukup salin seluruh folder <code>CBT V1</code> ini ke laptop baru, lalu klik ganda berkas <code>JALANKAN_SERVER_CBT.bat</code>. Server akan langsung menyala tanpa perlu konfigurasi ulang.</li>
                <li><strong>Di HP Siswa:</strong> Siswa hanya perlu mengganti Server URL di aplikasi HP sesuai alamat yang tertera di atas, lalu klik <strong>"Cek Koneksi"</strong>.</li>
            </ul>
        </div>
    </div>

    <!-- CARD: BERKAS DISTRIBUSI APLIKASI ANDROID (APK) -->
    <div class="card" style="margin-bottom: 24px; border: 1px solid var(--border-color); background: var(--bg-surface);">
        <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 class="card-title" style="margin-bottom: 4px; display: flex; align-items: center; gap: 8px; color: #15803d;">
                    <span>📱</span> Unduh Berkas Aplikasi Android Peserta (APK)
                </h3>
                <span style="font-size: 0.8rem; color: var(--text-muted);">
                    Paket instalasi aplikasi ujian mandiri untuk smartphone Android siswa dengan fitur Kiosk Lockdown dan Anti-Keluar.
                </span>
            </div>
            <span class="badge badge-success" style="font-size: 0.75rem; padding: 4px 10px;">
                v1.0.0 &bull; Universal Release
            </span>
        </div>

        <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap; margin-bottom: 16px;">
            <div style="width: 64px; height: 64px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 32px; flex-shrink: 0;">
                🤖
            </div>
            <div style="flex: 1; min-width: 240px;">
                <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary); margin-bottom: 2px;">
                    cbt-peserta-v1.0.apk
                </div>
                <div style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 6px;">
                    Ukuran Berkas: <strong>51.3 MB</strong> &bull; Target OS: <strong>Android 6.0 s/d 14+</strong> &bull; Arsitektur: <strong>Universal Release (ARM & x86)</strong>
                </div>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <span style="font-size: 0.7rem; background: var(--bg-main); border: 1px solid var(--border-color); padding: 2px 8px; border-radius: 4px; color: var(--text-muted);">🔒 Mode Kiosk Kunci Layar</span>
                    <span style="font-size: 0.7rem; background: var(--bg-main); border: 1px solid var(--border-color); padding: 2px 8px; border-radius: 4px; color: var(--text-muted);">🚫 Anti Keluar-Masuk / Alt-Tab</span>
                    <span style="font-size: 0.7rem; background: var(--bg-main); border: 1px solid var(--border-color); padding: 2px 8px; border-radius: 4px; color: var(--text-muted);">📶 100% Wi-Fi LAN Lokal</span>
                </div>
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <a href="/downloads/cbt-peserta-v1.0.apk" download="cbt-peserta-v1.0.apk" class="btn btn-primary" style="padding: 10px 20px; font-weight: 700; font-size: 0.9rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(37,99,235,0.25);">
                    <span>⬇️</span> Unduh Berkas APK Android (51.3 MB)
                </a>
                <a href="/downloads/cbt-peserta-v1.0.apk" download style="font-size: 0.75rem; text-align: center; color: var(--primary); text-decoration: underline;">
                    Tautan Langsung (/downloads/cbt-peserta-v1.0.apk)
                </a>
            </div>
        </div>

        <div style="background: var(--bg-main); border: 1px solid var(--border-color); border-radius: 6px; padding: 12px 14px; font-size: 0.8rem; color: var(--text-secondary);">
            <strong>Langkah Pemasangan di HP Siswa:</strong>
            <ol style="margin: 6px 0 0; padding-left: 18px; line-height: 1.6;">
                <li>Klik tombol <strong>Unduh Berkas APK Android</strong> di atas atau bagikan berkas APK ke siswa via Wi-Fi/Flashdisk.</li>
                <li>Pasang aplikasi di smartphone siswa (Izinkan <em>"Install unknown apps"</em> jika diminta).</li>
                <li>Buka aplikasi, masukkan IP Wi-Fi Server (<code>{{ $detectedHostIp }}:{{ $serverPort }}</code>), lalu siswa masuk menggunakan <strong>NIS</strong> dan kata sandi ujian.</li>
            </ol>
        </div>
    </div>

    <script>
    function copyServerUrl(elementId, btn) {
        var copyText = document.getElementById(elementId);
        if (!copyText) return;
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value).then(function() {
            var oldHtml = btn.innerHTML;
            btn.innerHTML = '<span>✓</span> Tersalin!';
            btn.classList.add('btn-success');
            setTimeout(function() {
                btn.innerHTML = oldHtml;
                btn.classList.remove('btn-success');
            }, 2000);
        }).catch(function() {
            document.execCommand('copy');
            alert('Alamat berhasil disalin: ' + copyText.value);
        });
    }
    </script>

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
