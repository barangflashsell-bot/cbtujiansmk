# PROJECT MAP — CBT SYSTEM

Dokumen ini memetakan seluruh modul, komponen sistem, dan struktur direktori perancangan CBT sesuai aturan **AI_RULES.md**.

---

## 1. PETA MODUL UTAMA

```text
CBT SYSTEM
│
├── 1. MODULE: AUTHENTICATION & USERS
│   ├── Login Admin & Guru (Web)
│   ├── Login Peserta (Android via Token/Nomor Ujian)
│   ├── Session & Secure Token Management (Role-based: Admin, Guru, Siswa)
│   └── Password Hashing (Bcrypt / Argon2)
│
├── 2. MODULE: MASTER DATA
│   ├── Data Sekolah & Tahun Ajaran
│   ├── Data Kelas & Rombel
│   ├── Data Siswa / Peserta
│   └── Data Guru & Mata Pelajaran
│
├── 3. MODULE: BANK SOAL (QUESTION BANK)
│   ├── Bank Soal per Mata Pelajaran & Tingkat
│   ├── Tipe Soal (Pilihan Ganda, Esai, dsb.)
│   ├── Opsi Jawaban & Media (Gambar/Audio jika ada)
│   └── Manajemen Kunci Jawaban (Server-Side ONLY)
│
├── 4. MODULE: EXAM CONFIGURATION & SCHEDULING
│   ├── Jadwal & Sesi Ujian (Tanggal, Jam Mulai, Durasi)
│   ├── Token Akses Ujian (Opsional rilis berkala oleh Proktor)
│   ├── Pengacakan Soal & Opsi (Server-side seeding per attempt)
│   └── Aturan Ujian (Batas waktu, submit otomatis, toleransi keterlambatan)
│
├── 5. MODULE: CBT ENGINE & ATTEMPTS (SERVER CORE)
│   ├── Mulai Ujian (Start Exam Attempt & Record Server Time)
│   ├── Distribusi Paket Soal ke Android (TANPA kunci jawaban)
│   ├── Autosave Jawaban Peserta (Idempotent & High Concurrency)
│   ├── Sinkronisasi Local Queue (Recovery saat jaringan terputus)
│   └── Submit Ujian (Validasi Waktu Server, Lock Attempt, Idempotent)
│
├── 6. MODULE: TIMER & SYNCHRONIZATION
│   ├── Server Time Service (Source of Truth)
│   ├── Heartbeat / Ping Sinkronisasi Sisa Waktu
│   └── Auto-Force Submit saat Server Time mencapai batas `ends_at`
│
├── 7. MODULE: SCORING & EVALUATION
│   ├── Server-Side Auto Grading (Pilihan Ganda)
│   ├── Penilaian Manual Guru (Esai)
│   └── Rekapitulasi Nilai & Bobot
│
├── 8. MODULE: PROCTORING & LIVE MONITORING
│   ├── Status Peserta Real-time (Belum Mulai, Sedang Mengerjakan, Putus Koneksi, Selesai)
│   ├── Reset Peserta / Buka Kunci Login (kasus HP mati/restart)
│   └── Log Aktivitas & Percobaan
│
├── 9. MODULE: REPORTS & EXPORT
│   ├── Laporan Nilai per Kelas / Sesi
│   ├── Analisis Butir Soal
│   └── Export Data (Excel / PDF)
│
├── 10. MODULE: SYSTEM UTILITIES & BACKUP
│   ├── Backup & Restore Database Lokal
│   ├── Deteksi IP Address Windows Server (LAN Config)
│   └── Health Check Server
│
└── 11. CLIENT: ANDROID APP (PESERTA)
    ├── Konfigurasi Dinamis Server IP & Port
    ├── Antarmuka Ujian Ramah Offline & Rendah Latensi
    ├── Local State Storage & Offline Retry Queue
    └── Timer Display (Countdown berdasarkan kalkulasi Server Time)
```

---

## 2. RANCANGAN STRUKTUR DIREKTORI (ARSITEKTUR FILE)

Dokumentasi rancangan tata letak direktori masa depan (tanpa implementasi kode pada Tahap 1):

```text
CBT V1/
├── AI_RULES.md                     # 44 Aturan Wajib
├── PROJECT_RULES.md                # Aturan Proyek
├── DOCS/                           # Dokumentasi Modular
│   ├── MASTER/
│   │   ├── PROJECT_MAP.md          # Peta Modul & Navigasi Induk
│   │   ├── REQUIREMENTS_ANALYSIS.md # Analisis Kebutuhan & Peran Sistem (Poin 1)
│   │   ├── ROLES_PERMISSIONS.md    # Matriks Hak Akses & Permission (Poin 2)
│   │   ├── MENU_STRUCTURE.md       # Struktur Menu Lengkap (Poin 3)
│   │   ├── FLOWCHART_ADMIN.md      # Alur Kerja 12 Langkah Administrator (Poin 4)
│   │   ├── FLOWCHART_GURU.md       # Alur Kerja Lengkap Guru (Poin 5)
│   │   ├── FLOWCHARTS.md           # 6 Master Flowcharts (Poin 6-11)
│   │   ├── DATABASE_DESIGN.md      # Spesifikasi 15 Tabel & ERD Mermaid (Poin 13-14)
│   │   ├── API_BLUEPRINT.md        # Spesifikasi Kontrak REST API (Poin 15)
│   │   ├── MODULAR_ARCHITECTURE.md # 17 Modul & Strategi Anti-Full Scan (Poin 16-17)
│   │   └── SYSTEM_ARCHITECTURE.md  # Server Windows, Android, Load, Security, Backup, Risks, Open Questions (Poin 12, 18-24)
│   └── MODULES/                    # Dokumentasi Khusus per Modul Terisolasi
│       ├── AUTH.md
│       ├── USERS.md
│       ├── QUESTIONS.md
│       ├── EXAMS.md
│       ├── ATTEMPTS.md
│       ├── TIMER.md
│       ├── SCORING.md
│       ├── MONITORING.md
│       └── ANDROID_CLIENT.md
│
├── server/                         # Backend CBT Server & API (Windows)
│   ├── config/                     # Konfigurasi DB, App, Port
│   ├── src/
│   │   ├── controllers/            # Controller per modul
│   │   ├── models/                 # Model/Entitas DB
│   │   ├── routes/                 # Endpoint REST API
│   │   ├── services/               # Logika bisnis (Timer, Scoring, ExamEngine)
│   │   └── middlewares/            # Auth, RateLimiting, Validasi
│   └── migrations/                 # Skrip migrasi skema database
│
├── web-admin/                      # Dashboard Web untuk Admin & Guru
│   ├── public/
│   └── src/
│       ├── components/
│       ├── views/
│       └── services/               # Komunikasi ke API Server
│
└── android-client/                 # Proyek Aplikasi Android Peserta
    └── app/
        └── src/
            └── main/
                ├── java/ (atau dart/src sesuai stack terpilih)
                └── res/
```

---

## 3. NAVIGASI KONTEKS & DEPENDENSI ANTAR MODUL

Sesuai Aturan 21 (`READ LESS, UNDERSTAND ENOUGH, CHANGE ONLY REQUIRED FILES`):

| Modul | Bergantung Langsung Pada (Direct Dependencies) | Modul yang Bergantung Padanya |
| :--- | :--- | :--- |
| **AUTH** | Database, Settings | Semua Modul |
| **MASTER DATA** | Database | Questions, Exams, Users |
| **BANK SOAL** | Master Data, Database | Exams |
| **EXAMS** | Bank Soal, Master Data | Attempts, Monitoring |
| **ATTEMPTS** | Exams, Auth, Database | Scoring, Monitoring |
| **TIMER** | Attempts, Server System Time | Attempts, Android Client |
| **SCORING** | Attempts, Bank Soal | Reports |
| **MONITORING**| Attempts, Timer, Users | - |
| **REPORTS** | Scoring, Master Data | - |
| **ANDROID** | Server REST API, Local SQLite/Preferences | - |
