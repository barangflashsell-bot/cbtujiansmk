# ARSITEKTUR SISTEM, WINDOWS SERVER, ANDROID, KINERJA, KEAMANAN & RISIKO
TAHAP 1 — BLUEPRINT TEKNIS KOMPREHENSIF (POIN 12 & 18 - 24)

Dokumen ini merinci aspek arsitektur tingkat tinggi, spesifikasi komputer Windows Server, perancangan aplikasi Android, strategi konkurensi/kinerja, protokol keamanan, penanganan bencana/cadangan, matriks risiko, dan daftar pertanyaan terbuka.

---

## 12. ARSITEKTUR SISTEM & DIAGRAM

Sistem terdiri atas 7 lapisan terintegrasi:
1. **Frontend Web (Admin/Guru)**: Laravel Blade + komponen UI ringan dan responsif (tanpa framework frontend berat, rendering cepat di browser lokal).
2. **Backend Engine**: PHP + Laravel yang bertindak sebagai pengontrol logika bisnis, penilaian, timer, validasi otoritas, dan queue.
3. **REST API**: Laravel REST API berbasis stateless JSON (`/api/*`) untuk pertukaran data antara client Flutter Android dan server Laravel.
4. **Database**: MySQL atau MariaDB dengan optimasi ACID, InnoDB buffer pool, indexing, dan relational constraints.
5. **Android Client**: Flutter (Dart) sebagai aplikasi mobile peserta ujian (BYOD), menghasilkan single standalone APK tanpa Google Play Store.
6. **Local Storage Android**: SQLite (via `sqflite`) & SharedPreferences pada Flutter untuk menyimpan konfigurasi IP Server, token autentikasi sesi, cache paket soal, dan state pilihan jawaban peserta.
7. **Synchronization Layer**: Background Service / Worker pada Flutter yang mengelola *Local Offline Queue* dan mengeksekusi pengiriman ulang (*batch sync retry*) secara otomatis saat koneksi Wi-Fi pulih.

### Diagram Arsitektur Sistem:
```text
┌─────────────────────────────────────────────────────────────────────────┐
│                      CLIENT LAYER (PESERTA UJIAN)                       │
│                                                                         │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │                     FLUTTER ANDROID CLIENT                        │  │
│  │                                                                   │  │
│  │  ┌────────────────────────┐         ┌──────────────────────────┐  │  │
│  │  │    Flutter UI Screen   │         │   Local Storage (HP)     │  │  │
│  │  │ (Soal, Opsi, Countdown)│◄───────►│ (SQLite Cache & State)   │  │  │
│  │  └───────────┬────────────┘         └────────────┬─────────────┘  │  │
│  │              │                                   │                │  │
│  │              ▼                                   ▼                │  │
│  │     [Debounce Autosave]              [Local Offline Queue]        │  │
│  │              │                                   │                │  │
│  │              └─────────────────┬─────────────────┘                │  │
│  │                                │                                  │  │
│  │                                ▼                                  │  │
│  │                    [SYNCHRONIZATION LAYER]                        │  │
│  │                   (Auto-Flush Queue Worker)                       │  │
│  └────────────────────────────────┬──────────────────────────────────┘  │
└───────────────────────────────────┼─────────────────────────────────────┘
                                    │ Wi-Fi / Local Area Network (Offline)
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                 HOST SERVER (WINDOWS COMPUTER RUANG UJIAN)              │
│                                                                         │
│  ┌─────────────────────────┐           ┌─────────────────────────────┐  │
│  │    WEB ADMIN & GURU     │           │      LARAVEL REST API       │  │
│  │     (Laravel Blade)     │◄─────────►│       (/api/v1/*)           │  │
│  └─────────────────────────┘           └──────────────┬──────────────┘  │
│                                                       │                 │
│                                        ┌──────────────┴──────────────┐  │
│                                        │    LARAVEL BACKEND CORE     │  │
│                                        │  ├── Auth & Sanctum Token   │  │
│                                        │  ├── Server-Time Authority  │  │
│                                        │  ├── Exam Engine & Locking  │  │
│                                        │  └── Auto-Grading Service   │  │
│                                        └──────────────┬──────────────┘  │
│                                                       │                 │
│                                                       ▼                 │
│                                        ┌─────────────────────────────┐  │
│                                        │       DATABASE LAYER        │  │
│                                        │      MySQL / MariaDB        │  │
│                                        │  (InnoDB, Connection Pool)  │  │
│                                        └─────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
```
│                                        ┌─────────────────────────────┐  │
│                                        │       DATABASE LAYER        │  │
│                                        │   MySQL / MariaDB Storage   │  │
│                                        │  (InnoDB, Connection Pool)  │  │
│                                        └─────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 18. SERVER WINDOWS (PENYIAPAN HOST PC LOKAL)

1. **Kebutuhan Komputer (Spesifikasi Minimum & Rekomendasi)**:
   - *Minimum (Hingga 50 Peserta)*: Intel Core i3 / Ryzen 3, RAM 8 GB, SSD 128 GB, Windows 10 (64-bit).
   - *Rekomendasi (100–300+ Peserta)*: Intel Core i5 / Ryzen 5, RAM 16 GB, SSD NVMe 256 GB, Windows 10/11 Pro (64-bit), Gigabit Ethernet LAN port.
2. **IP Server**:
   - Komputer Windows diset menggunakan **Static IP** di adapter Ethernet LAN sekolah (contoh: `192.168.1.100` atau `10.0.0.2`), atau IP DHCP yang di-binding/reserved pada router sekolah.
3. **Port Server**:
   - Port REST API & Web Dashboard: Port `8000` (atau port HTTP standar `80`).
   - Port MySQL: Port internal `3306` (hanya dibuka untuk `127.0.0.1` / localhost demi keamanan).
4. **Firewall Windows (Inbound Rule)**:
   - Membuka port `8000` (TCP) pada *Windows Defender Firewall*:
     `Inbound Rule -> Allow Port 8000 TCP (Private Network)`.
5. **Cara Android Menemukan Server**:
   - Peserta/pengawas memasukkan IP dan Port server melalui menu **Pengaturan Server** di Android (contoh: `http://192.168.1.100:8000`).
   - Nilai IP disimpan di penyimpanan permanen HP sehingga cukup dikonfigurasi sekali.
   - Menyediakan fitur *Scan QR Code IP Server* di aplikasi Android (pengawas menampilkan QR Code IP server di layar proyektor).
6. **Konfigurasi Server**:
   - Server Node.js dijalankan dengan process manager (seperti PM2 atau Windows Service) agar otomatis restart jika terjadi crash tak terduga.
7. **Cadangan Data (Backup Server)**:
   - Skrip dump otomatis MySQL dijalankan secara lokal ke folder terpisah di Windows sebelum dan setelah sesi ujian berlangsung.

---

## 19. PERANCANGAN APLIKASI ANDROID (CLIENT EXPERIENCE)

Aplikasi Android peserta dibangun dengan fokus kecepatan, bobot ringan, dan ketahanan terhadap fluktuasi sinyal:

1. **Login**: Layar login bersih dengan input NIS/Username dan Password.
2. **Server Configuration**: Layar pengaturan alamat IP Server dan Port yang dapat diakses sebelum maupun setelah login.
3. **Test Connection**: Tombol ping cepat untuk memastikan HP terhubung ke server Windows lokal sebelum mencoba login.
4. **Dashboard**: Menampilkan identitas peserta, nama sekolah, status jaringan, dan kartu ujian aktif hari ini.
5. **Exam List**: Daftar jadwal ujian yang ditugaskan ke kelas siswa tersebut.
6. **Exam Detail**: Informasi judul ujian, mata pelajaran, durasi waktu, dan status apakah membutuhkan token proktor.
7. **Instructions**: Halaman petunjuk teknis pengerjaan, skema tombol, dan peringatan batas waktu.
8. **Exam Screen**: Layar inti ujian dengan antarmuka bebas distraksi (Kiosk / Lock-task mode jika diaktifkan).
9. **Question Navigation**: Panel kisi nomor soal (1 s.d N) dengan status warna: *Abu-abu (Belum dijawab)*, *Hijau (Sudah tersimpan)*, *Kuning (Ragu-ragu)*.
10. **Answer**: Pilihan opsi responsif (A, B, C, D, E) untuk PG atau area teks untuk esai.
11. **Timer**: Countdown sisa waktu di bagian atas layar berbasis sinkronisasi jam server.
12. **Sync**: Indikator status sinkronisasi jawaban (*Tersinkron* / *Menyimpan di HP...*).
13. **Submit**: Tombol selesai dengan konfirmasi modal ganda untuk mencegah klik tidak sengaja.
14. **Result**: Layar penutup ujian (tanda terima sukses pengerjaan dan skor jika diizinkan sekolah).

---

## 20. KINERJA, BEBAN & KONKURENSI (LOAD & PERFORMANCE STRATEGY)

Sistem dirancang untuk menangani lonjakan konkurensi (1 s.d 100+ siswa simultan di jaringan LAN):

1. **Skalabilitas Jumlah Peserta**:
   - *1 - 10 Siswa*: Beban sangat rendah (< 1% CPU, < 5 MB RAM server).
   - *30 - 50 Siswa (1 Ruang Kelas)*: Standar operasi sekolah, response time API < 20ms.
   - *100+ Siswa (Skala Sekolah)*: Akses bersamaan saat klik tombol "Mulai" dan "Selesai".
2. **Database Indexing**:
   - `INDEX (attempt_id, question_id)` pada tabel `answers`.
   - `INDEX (exam_id, student_id)` pada tabel `exam_attempts`.
   - `INDEX (status, ends_at)` pada tabel `exam_attempts` untuk query monitoring & timer.
3. **Strategi API & Network Efficiency**:
   - **Download Sekali**: Soal diunduh lengkap di awal saat attempt dibuat, menghindari request unduh berulang tiap ganti nomor soal.
   - **Debounced Autosave (300ms)**: Jika siswa mengklik opsi A lalu ganti C dengan cepat, hanya pilihan terakhir yang dikirim ke server.
4. **Batch Synchronization**:
   - Pengiriman kumpulan jawaban sekaligus (*batch array*) saat menguras *offline queue*, mereduksi 20 request terpisah menjadi 1 request tunggal.
5. **Caching**:
   - Paket soal ujian di-cache di memori server (In-memory cache) sehingga ratusan siswa yang mengunduh paket soal yang sama tidak membebani kueri pembacaan database.
6. **Connection Handling**:
   - Database Connection Pool MySQL diset pada kisaran 50–100 koneksi aktif dengan timeout agresif agar koneksi lekas kembali ke pool.

---

## 21. KEAMANAN SISTEM (SECURITY PROTOCOLS)

1. **Authentication**: Password disimpan dalam format hash kriptografis yang aman (**Bcrypt / Argon2**). Dilarang keras plaintext (**Aturan 15 AI_RULES.md**).
2. **Authorization**: Hak akses diverifikasi di setiap route server berdasarkan role token JWT pengguna.
3. **Isolasi Kunci Jawaban**: Kolom `is_correct` pada database **TIDAK PERNAH** dikirim dalam payload API ke Android peserta (**Aturan 11**).
4. **Server-Side Validation**: Seluruh input teks, ID opsi, dan panjang string divalidasi ketat di sisi backend (**Aturan 17**).
5. **Server-Side Timer & Locking**: Batas akhir pengerjaan dikontrol mutlak oleh jam server. Attempt yang sudah `SUBMITTED` atau `TIMEOUT` langsung dikunci dan menolak revisi jawaban baru (**Aturan 10, 12, 14**).
6. **Proteksi Duplikasi Request**: Constraint unik database mencegah duplikasi attempt atau duplikasi baris jawaban.
7. **Proteksi Injeksi & XSS**: Menggunakan Parameterized Queries / Prepared Statements pada seluruh interaksi database MySQL dan sanitasi teks HTML pada pembuatan konten soal.
8. **Rate Limiting**: Pembatasan laju request per IP client untuk mencegah flooding atau serangan DoS lokal.
9. **Activity Logging**: Pencatatan log audit penting tanpa membocorkan data sensitif:
   - `RESET_LOGIN`: Pengawas mereset status peserta (HP mati/restart).
   - `TIME_EXTENDED`: Admin memperpanjang waktu pengerjaan peserta tertentu (mencatat durasi dan alasan).
   - `APP_BACKGROUNDED` / `WINDOW_FOCUS_LOST`: Android peserta mendeteksi aplikasi kehilangan fokus/berpindah ke background (tanpa menghapus jawaban).

---

## 22. CADANGAN & PEMULIHAN (BACKUP & RECOVERY STRATEGY)

Sesuai **Aturan 36 AI_RULES.md**:

1. **Backup Sebelum Ujian**:
   - Admin membuat file dump snapshot (`cbt_backup_pre_exam_YYYYMMDD.sql`) sebelum jadwal ujian dimulai. Berisi data master siswa, kelas, guru, dan bank soal yang sudah final.
2. **Backup Setelah Ujian**:
   - Setelah ujian berakhir dan seluruh siswa submit, sistem membuat dump snapshot hasil (`cbt_backup_post_exam_YYYYMMDD.sql`) yang berisi data attempt, lembar jawaban aktual, dan skor nilai.
3. **Mekanisme Restore**:
   - Fitur restore di menu Admin Web untuk memulihkan database dari berkas backup `.sql` terverifikasi.
4. **Pemulihan Jika Server Restart Selama Ujian**:
   - Data attempt dan jawaban yang sudah tersimpan di MySQL tidak boleh hilang (ACID persistence).
   - Setelah server kembali online: peserta dapat reconnect, attempt tetap ada, jawaban tersimpan tetap utuh, dan peserta dapat melanjutkan pengerjaan jika waktu server belum habis.
5. **Pemulihan Jika HP Peserta Mati / Crash / Tertutup**:
   - Attempt tidak otomatis hilang.
   - Saat peserta login kembali, server mencari attempt `in_progress` yang masih valid (`NOW() < ends_at`).
   - Jika ditemukan: server mengembalikan state terakhir, memuat jawaban yang sudah ada, menyinkronkan antrean lokal, dan melanjutkan dari kondisi terakhir. Jika waktu habis: lakukan timeout/submit otomatis.

---

## 23. ANALISIS 10 SKENARIO RISIKO & SOLUSI KONKRET

| No | Skenario Risiko Lapangan | Dampak | Solusi Teknis & Mitigasi |
| :---: | :--- | :--- | :--- |
| **1** | **Wi-Fi Lokal Terputus** | Android tidak dapat mengirim data ke server. | Aplikasi Android beralih ke mode offline, jawaban disimpan ke antrean lokal (*Local Queue*). Begitu Wi-Fi tersambung, antrean otomatis disinkronkan. **Dilarang menghapus data lokal sebelum server memberikan ACK**. |
| **2** | **Server PC Restart / Crash** | Server sempat mati sementara. | Data attempt dan jawaban di MySQL tetap aman. Setelah server kembali aktif, peserta otomatis reconnect dan melanjutkan pengerjaan jika `NOW() < ends_at`. |
| **3** | **Listrik Ruangan Padam** | Router Wi-Fi & PC Server mati bersamaan. | Disarankan server menggunakan laptop (memiliki baterai) dan router diberi mini-UPS. Data jawaban di HP siswa tetap aman di penyimpanan lokal HP. |
| **4** | **Baterai HP Siswa Habis / Mati** | Siswa terhenti mengerjakan ujian. | Siswa meminjam HP lain/cadangan. Proktor melakukan **Reset Login** pada dashboard monitoring, siswa login di HP baru, dan server memulihkan attempt aktif serta seluruh jawaban yang sudah tersimpan. |
| **5** | **Aplikasi Android Tertutup / Keluar** | Layar ujian tertutup ke home Android. | Saat siswa membuka kembali aplikasi CBT, sistem otomatis mendeteksi attempt aktif dan langsung melanjutkan lembar soal di nomor terakhir. Kehilangan fokus dicatat ke `activity_logs` tanpa menghapus jawaban. |
| **6** | **Database Error / Crash** | Kueri gagal dieksekusi. | Gunakan engine InnoDB dengan transaction rollback untuk menjaga integritas tabel. Log error dicatat terpisah untuk analisa teknisi. |
| **7** | **Duplicate Request (Klik Submit Berkali-kali)** | Risiko dobel skor / duplikasi data nilai. | Submit bersifat **Idempotent**; request submit kedua hanya akan membaca hasil attempt yang sudah ada tanpa melakukan perhitungan ganda. |
| **8** | **Peserta Login Bersamaan (100 Siswa Sekaligus)** | Lonjakan beban koneksi di awal ujian. | Gunakan Connection Pool database MySQL yang memadai dan asynchronous I/O pada backend untuk memproses autentikasi dalam antrean non-blocking. |
| **9** | **Server Overload / Memory Spike** | Respon server melambat. | Implementasikan In-memory caching untuk paket soal statis, sehingga server tidak melakukan ratusan query pembacaan soal yang sama ke database. |
| **10**| **Peserta Terlambat Masuk** | Waktu ujian tersisa sedikit. | Peserta tetap dapat masuk selama ujian aktif. Batas waktu mengikuti `ends_at` server (tidak ada tambahan otomatis). Admin dapat memberikan perpanjangan manual jika ada kendala khusus. |

---

## 24. FINALISASI KEPUTUSAN TEKNOLOGI & ZERO OPEN QUESTIONS

Seluruh keputusan teknologi telah ditetapkan secara resmi oleh User dan dikunci:

| Komponen | Keputusan Resmi Terpilih | Alasan & Standar Penerapan |
| :--- | :--- | :--- |
| **Backend Server** | **PHP + Laravel** | Sangat cocok untuk aplikasi CBT berbasis DB relasional, memiliki migration, validation, authentication, authorization, REST API, logging, queue, testing, dan ekosistem stabil. |
| **Database** | **MySQL / MariaDB** | Database relasional ACID teruji, InnoDB engine, connection pooling, dan transaksi atomic upsert jawaban. |
| **Web Dashboard** | **Laravel Blade + UI Ringan** | Rendering cepat di browser server lokal Windows tanpa beban build framework JS yang berat. |
| **Android Client** | **Flutter (Dart)** | Menghasilkan single standalone APK langsung (tanpa Play Store), UI konsisten antar perangkat, mendukung local storage SQLite (`sqflite`), background synchronization, dan kapabilitas lock mode. |
| **API Protocol** | **REST API (JSON)** | Stateless, format JSON ringan, efisien di jaringan lokal Wi-Fi. |
| **Host Server** | **Windows Computer** | Dijalankan langsung pada PC Windows sekolah via PHP runtime & web server lokal. |
| **Jaringan & Akses**| **Wi-Fi / LAN Lokal** | 100% Offline tanpa kebutuhan internet. |
| **Distribusi APK** | **Direct Sideloading APK** | File APK diunduh langsung dari server lokal atau dibagikan via storage offline. |

> [!NOTE]
> **REMAINING OPEN QUESTIONS: NIHIL (TIDAK ADA).**
> Seluruh aspek arsitektur, peran, alur, basis data, keamanan, recovery, dan teknologi telah disepakati dan dikunci. Bluepint Tahap 1 siap penuh untuk masuk ke **Tahap 2: Database**.
