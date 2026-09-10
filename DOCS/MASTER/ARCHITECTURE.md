# BLUEPRINT ARSITEKTUR SISTEM CBT
TAHAP 1 — ARSITEKTUR TEKNIS & TOPOLOGI SISTEM

Dokumen ini mendefinisikan arsitektur teknis sistem CBT mandiri berbasis jaringan lokal (LAN/Wi-Fi) tanpa ketergantungan internet.

---

## 1. TOPOLOGI JARINGAN LOKAL (OFFLINE LAN ARCHITECTURE)

Sistem dirancang bekerja secara 100% otonom di jaringan lokal sekolah.

```text
               ┌────────────────────────────────────────────────────────┐
               │              WINDOWS COMPUTER (HOST SERVER)            │
               │                                                        │
               │  ┌────────────────────────┐  ┌──────────────────────┐  │
               │  │      MySQL / MariaDB   │  │   LARAVEL REST API   │  │
               │  │   (Storage & Indexing) │◄─┤ (PHP + Laravel Core) │  │
               │  └────────────────────────┘  └──────────┬───────────┘  │
               │                                         │              │
               │  ┌────────────────────────┐             │              │
               │  │    WEB ADMIN / GURU    │◄────────────┘              │
               │  │     (Laravel Blade)    │  (Port 8000 / Localhost)   │
               │  └────────────────────────┘                            │
               └───────────────────────────┬────────────────────────────┘
                                           │ Ethernet / LAN Cable
                                           ▼
               ┌────────────────────────────────────────────────────────┐
               │        ACCESS POINT / ROUTER WI-FI (OFFLINE LAN)       │
               │             (Subnet: 192.168.x.x / 10.x.x.x)           │
               └──────┬────────────────────┬────────────────────┬───────┘
                      │ Wi-Fi              │ Wi-Fi              │ Wi-Fi
                      ▼                    ▼                    ▼
             ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
             │ FLUTTER ANDROID │  │ FLUTTER ANDROID │  │ FLUTTER ANDROID │
             │   (Peserta 1)   │  │   (Peserta 2)   │  │   (Peserta N)   │
             └─────────────────┘  └─────────────────┘  └─────────────────┘
```

### Karakteristik Jaringan:
1. **Tidak Ada Kebutuhan Internet**: Seluruh pertukaran data (login, unduh paket soal, autosave jawaban, submit, monitoring) terjadi di dalam router/switch lokal.
2. **Dynamic Server Endpoint**: Flutter Android APK menyediakan pengaturan input IP dan Port Server secara fleksibel (contoh: `http://192.168.1.100:8000`), disimpan di penyimpanan lokal aplikasi (SharedPreferences).
3. **Distribusikan APK Tanpa Play Store**: File APK Flutter dapat diunduh langsung oleh peserta melalui browser HP dengan mengakses halaman unduh lokal server (misal: `http://192.168.1.100:8000/download-cbt.apk`) atau dibagikan via flashdisk/Bluetooth.

---

## 2. KOMPONEN UTAMA SISTEM

### A. Windows Computer (Server Host)
- **Sistem Operasi**: Windows 10 / Windows 11 / Windows Server (64-bit).
- **Lokasi Project**: `./SERVER` (Aplikasi Laravel 13 murni terpisah dari client).
- **Runtime PHP**: PHP 8.3.33 (`C:\php83\php.exe`) portable mandiri (XAMPP PHP 8.0.30 tetap dipertahankan utuh).
- **Layanan Server**:
  - **Database Service**: MySQL / MariaDB dari XAMPP (`C:\xampp\mysql\bin\mysql.exe`, port default 3306).
  - **Backend & REST API**: Laravel 13 (Laravel Framework 13.30.1) melayani Web Dashboard dan REST API endpoints secara terpadu.
  - **Development Server Command**: `C:\php83\php.exe artisan serve` (berjalan di `http://127.0.0.1:8000`).
  - **Static Web Server**: Menangani Web Dashboard Admin/Guru (Blade) serta penyediaan endpoint unduh APK peserta.

### B. Web Dashboard (Admin & Guru)
- **Platform**: Web Browser modern (Chrome, Edge, Firefox) dengan Laravel Blade.
- **Pengguna**:
  - **Admin / Proktor**: Pengaturan sesi ujian, rilis token, pemantauan status peserta real-time, reset login peserta (jika ganti HP atau restart).
  - **Guru**: Manajemen bank soal, pembuatan paket ujian, verifikasi nilai, dan koreksi esai.

### C. Android APK (Peserta Ujian - Flutter)
- **Teknologi**: Flutter (Dart) — Menghasilkan single standalone APK langsung.
- **Kompatibilitas**: Android OS 7.0 (Nougat) hingga Android versi terbaru.
- **Karakteristik Kunci**:
  - **Lightweight & High Responsiveness**: Antarmuka cepat, bebas lag saat berpindah soal.
  - **Offline-Resilient Local State**: Jawaban disimpan instan di memory & local SQLite (`sqflite`) perangkat sebelum dikirim ke API.
  - **Retry Queue**: Jika sinyal Wi-Fi melemah/terputus, request autosave masuk antrean lokal dan otomatis disinkronisasi saat koneksi pulih tanpa mengganggu peserta menjawab soal berikutnya.
  - **No Answer Key**: Kunci jawaban tidak pernah dikirim ke Android.
  - **Server-driven Timer**: Menampilkan countdown berdasarkan selisih waktu server (`ends_at - server_time`).

---

## 3. ALUR KERJA SISTEM (DATA FLOW & PROTOCOLS)

### Alur 1: Autentikasi & Mulai Ujian (Start Exam)
```text
Android Siswa                     Server CBT                        Database
     │                                │                                │
     ├──── POST /api/auth/login ─────►│                                │
     │    (Username/NoPeserta, Pass)  ├──── Validasi Akun & Password ─►│
     │                                │    (Hash verification)         │
     │◄─── Token JWT / Sesi ──────────┤                                │
     │                                │                                │
     ├──── POST /api/exam/start ─────►│                                │
     │    (ExamID, SesiToken)         ├──── Cek Jadwal & Validasi ────►│
     │                                ├──── Catat started_at & ends_at │
     │                                ├──── Siapkan Acak Soal/Opsi ────┤
     │◄─── Paket Soal (TANPA KUNCI) ──┤                                │
     │     + Waktu Selesai (Server)   │                                │
```

### Alur 2: Autosave Jawaban Peserta (High Concurrency & Idempotency)
Sesuai **Aturan 13 & 30**:
```text
PESERTA PILIH JAWABAN
        │
        ▼
SIMPAN DI LOCAL STATE (HP) ──► Layar langsung ter-update (Responsif, 0ms latency)
        │
        ▼
KIRIM KE REST API (/api/exam/save-answer)
        │
        ├─── BERHASIL ──► Tandai jawaban "Tersinkronisasi"
        │
        └─── GAGAL (Wi-Fi putus / Time out)
                 │
                 ▼
             MASUKKAN KE LOCAL QUEUE
                 │
                 ▼
             BACKGROUND RETRY WORKER
                 │ (Coba kirim ulang saat ping ke server sukses)
                 ▼
             SINKRONISASI KE SERVER
```

### Alur 3: Timer, Peserta Terlambat & Perpanjangan Manual Admin
Sesuai **Aturan 10 & 12**:
1. **Perhitungan Waktu Akhir Siswa (`ends_at`)**:
   - Server menentukan `ends_at = MIN(started_at + INTERVAL duration_minutes MINUTE, exam.end_window)`.
   - **Peserta Terlambat**: Siswa tetap diizinkan masuk selama jadwal ujian masih aktif, namun batas pengerjaan tetap berakhir di `exam.end_window` (atau `ends_at` yang telah ditetapkan). **Tidak ada tambahan waktu otomatis**.
   - **Perpanjangan Waktu Manual oleh Admin**: Admin/Proktor dapat memperpanjang `ends_at` secara manual untuk peserta tertentu (misal akibat kendala teknis). Setiap penambahan waktu dicatat ke `activity_logs` beserta alasan dan durasi tambahannya.
2. Tiap request autosave atau heartbeat, server memeriksa `NOW() > ends_at`.
3. Jika waktu habis:
   - Server mengubah status attempt menjadi `TIMEOUT`.
   - Mengunci attempt (menolak update jawaban baru).
   - Menjalankan auto-grading (penilaian otomatis).
   - Mengembalikan respon ke Android agar layar berganti ke "Waktu Habis / Ujian Selesai".

### Alur 4: Penyelesaian Ujian (Submit Idempotent)
Sesuai **Aturan 14**:
1. Peserta mengklik "Selesai Ujian" di Android.
2. Android memastikan antrean lokal kosong (semua jawaban tersinkron).
3. Android mengirim `POST /api/exam/submit` dengan `attempt_id`.
4. Server memeriksa status attempt:
   - Jika status masih `IN_PROGRESS`: Server ubah menjadi `SUBMITTED`, kunci jawaban, dan hitung skor.
   - Jika status sudah `SUBMITTED` atau `TIMEOUT`: Server mengembalikan hasil yang sudah ada tanpa melakukan kalkulasi ulang atau duplikasi data.

### Alur 5: Recovery Ujian (HP Mati, Crash, Tertutup, atau Ganti Perangkat)
Jika terjadi kendala pada perangkat peserta:
1. **Attempt Tidak Pernah Hilang**: Record `exam_attempts` dan `answers` tetap tersimpan aman di database MySQL.
2. **Login Kembali**:
   - Ketika siswa login kembali, server memeriksa apakah terdapat attempt dengan status `IN_PROGRESS` yang belum habis waktunya (`NOW() < ends_at`).
   - Jika ditemukan attempt valid:
     - Server mengembalikan state ujian terakhir beserta seluruh jawaban yang sudah tersimpan di server.
     - Android mencocokkan dengan antrean lokal (*Local Queue*) jika ada jawaban offline yang belum sempat terkirim, lalu melakukan sinkronisasi otomatis.
     - Siswa langsung melanjutkan pengerjaan dari kondisi dan nomor soal terakhir.
3. **Jika Waktu Server Sudah Habis saat Login Kembali**:
   - Server otomatis mengubah status attempt menjadi `TIMEOUT` / `SUBMITTED` dan menolak pengerjaan ulang.

### Alur 6: Ketahanan Terhadap Server Restart
1. Jika server Windows mengalami restart tak terduga di tengah ujian:
   - Data attempt dan seluruh jawaban yang telah tersimpan di MySQL tetap utuh (ACID persistence).
   - Sisa waktu ujian peserta tidak bertambah atau ter-reset, karena `ends_at` telah dipatok absolut di awal.
   - Begitu service server dan database kembali aktif, aplikasi Android peserta secara otomatis melakukan reconnect, memulihkan status pengerjaan, dan melanjutkan pengerjaan normal.

### Alur 7: Mekanisme Anti-Cheat Android & Keterbatasan Teknis
1. **Penerapan Lock/Exam Mode**:
   - Memanfaatkan fitur *Screen Pinning* / *Lock Task Mode* pada Android untuk membatasi peserta keluar dari aplikasi.
   - Menonaktifkan tombol navigasi (Home, Recent Apps) dan gesture keluar layar sejauh didukung oleh OS perangkat.
2. **Pencatatan Event Hilang Fokus (Focus Loss Logging)**:
   - Event aplikasi saat kehilangan fokus (berpindah ke background, membuka split screen, pop-up aplikasi lain) dideteksi melalui lifecycle Android (`onPause` / `onWindowFocusChanged`).
   - Event ini langsung dicatat ke `activity_logs` di server (misal: `WINDOW_FOCUS_LOST`, `APP_BACKGROUNDED`).
   - **Toleransi Integritas**: Kehilangan fokus **TIDAK MENGHAPUS** jawaban siswa yang telah tersimpan.
   - Saat siswa kembali ke aplikasi, attempt yang sama langsung dilanjutkan.
3. **Keterbatasan Teknis Nyata (Technical Limitations)**:
   - Tidak mengklaim keamanan 100% pada seluruh perangkat Android di dunia.
   - Keterbatasan teknis dipengaruhi oleh kustomisasi vendor Android (OEM custom ROM seperti MIUI/HyperOS, ColorOS, OneUI) yang memiliki mekanisme gesture navigasi, floating window, atau edge panel yang berbeda-beda.

---

## 4. PENANGANAN KONKURENSI TINGGI (HIGH CONCURRENCY LAN DESIGN)

Sesuai **Aturan 31 & 32**:
Di sebuah sekolah dengan 100 - 300 siswa yang ujian bersamaan di satu Access Point/Server lokal:
1. **Connection Pooling**: Backend mengelola pool koneksi database MySQL secara optimal (misal 50–100 active connections) agar tidak overload.
2. **Batch / Throttled Autosave**: Mencegah serangan request berlebih saat siswa mengklik opsi berkali-kali. Debounce autosave 300-500ms pada client Android.
3. **Database Transactions**: Gunakan `START TRANSACTION` dan `COMMIT` pada proses pembentukan attempt dan submit ujian.
4. **Unique Constraints**:
   - `UNIQUE KEY (attempt_id, question_id)` pada tabel jawaban peserta untuk menjamin operasi `INSERT ... ON DUPLICATE KEY UPDATE` atau `UPSERT` yang aman dari duplicate race-condition.
   - `UNIQUE KEY (user_id, exam_id)` jika ujian hanya mengizinkan 1 attempt per peserta.
5. **Database Indexing**: Indeks komprehensif pada foreign keys, kolom pencarian (`user_id`, `exam_id`, `attempt_id`, `status`), dan `created_at`.
