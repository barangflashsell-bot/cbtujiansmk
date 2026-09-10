# MODULAR ARCHITECTURE & STRATEGI ANTI-FULL SCAN
TAHAP 1 — BLUEPRINT 17 MODUL SISTEM & ATURAN NAVIGASI (POIN 16 & 17)

Dokumen ini merinci arsitektur 17 modul terisolasi serta menetapkan mekanisme navigasi konteks agar AI Agent tidak melakukan *full scan project* sesuai **Aturan 2, 20, dan 21 AI_RULES.md**.

---

## 16. SPESIFIKASI 17 MODUL SISTEM

| No | Modul | Tanggung Jawab Utama | File Rencana Masa Depan | Database Terkait | API Terkait | Direct Dependencies |
| :---: | :--- | :--- | :--- | :--- | :--- | :--- |
| **1** | **AUTH** | Otentikasi, verifikasi hash Bcrypt, pembuatan token Sanctum/session, validasi hak akses middleware. | `AuthController.php`, `AuthService.php`, `Authenticate.php` | `users`, `roles` | `POST /api/login`, `POST /api/logout` | Database |
| **2** | **USERS** | CRUD akun dasar pengguna, status aktif/nonaktif, reset password global. | `UserController.php`, `User.php` | `users`, `roles` | `GET/POST/PUT /api/users` | AUTH |
| **3** | **STUDENTS**| Manajemen data siswa, profil, NIS/NISN, alokasi kelas, import Excel siswa. | `StudentController.php`, `StudentService.php` | `students`, `classes`, `users` | `GET/POST /api/students`, `/import` | USERS, CLASSES |
| **4** | **TEACHERS**| Manajemen profil guru/pengawas, NIP, relasi mata pelajaran yang diajar. | `TeacherController.php`, `Teacher.php` | `teachers`, `users` | `GET/POST /api/teachers` | USERS |
| **5** | **CLASSES** | Manajemen jenjang, nama kelas (7A, 8B), tahun ajaran. | `ClassController.php`, `Classes.php` | `classes` | `GET/POST/DELETE /api/classes` | Database |
| **6** | **SUBJECTS**| Master mata pelajaran sekolah (kode, nama mapel). | `SubjectController.php`, `Subject.php` | `subjects` | `GET/POST /api/subjects` | Database |
| **7** | **QUESTIONS**| Manajemen butir soal (PG & Esai), opsi jawaban, upload gambar, isolasi kunci jawaban server. | `QuestionController.php`, `OptionService.php` | `questions`, `question_options`, `subjects` | `GET/POST/PUT /api/questions` | SUBJECTS, TEACHERS |
| **8** | **EXAMS** | Paket sesi ujian, durasi menit, token proktor, jadwal buka-tutup, alokasi peserta. | `ExamController.php`, `ExamService.php` | `exams`, `exam_questions`, `exam_participants` | `GET/POST/PUT /api/exams` | QUESTIONS, CLASSES |
| **9** | **ATTEMPTS**| Pembuatan sesi pengerjaan siswa, pencatatan `started_at`, `ends_at`, status pengerjaan, locking. | `AttemptController.php`, `AttemptService.php` | `exam_attempts` | `POST /api/exams/:id/start` | EXAMS, STUDENTS, TIMER |
| **10**| **ANSWERS** | Autosave jawaban siswa, atomic upsert, penanda ragu-ragu. | `AnswerController.php`, `AnswerService.php` | `answers`, `questions` | `POST /api/attempts/:id/answers` | ATTEMPTS, QUESTIONS |
| **11**| **TIMER** | Sinkronisasi waktu server mutlak (*authoritative*), validasi `ends_at`, auto force-submit saat waktu habis. | `TimerService.php` | `exam_attempts` | Heartbeat / Sisa waktu pada tiap response | ATTEMPTS |
| **12**| **SYNC** | Penampung dan pemroses sinkronisasi batch dari *Local Offline Queue* Android saat jaringan pulih. | `SyncController.php`, `SyncService.php` | `answers` | `POST /api/attempts/:id/sync` | ANSWERS, ATTEMPTS |
| **13**| **RESULTS** | Auto-grading pilihan ganda, modul penilaian manual esai guru, rekapitulasi nilai akhir. | `ScoringService.php`, `ResultController.php` | `results`, `answers`, `question_options` | `GET /api/results`, `POST /grade-essay` | ANSWERS, QUESTIONS |
| **14**| **MONITORING**| *Live proctoring* pengawas di LAN: status real-time siswa, reset sesi login siswa (ganti HP). | `MonitoringController.php`, `MonitoringService.php`| `exam_attempts`, `users` | `GET /api/monitoring/live`, `POST /reset-student` | ATTEMPTS, USERS |
| **15**| **REPORTS** | Rekapitulasi nilai per rombel/kelas, analisis butir soal, ekspor laporan resmi ke Excel & PDF. | `ReportService.php`, `ExportHelper.php` | `results`, `exams`, `classes` | `GET /api/reports/export-excel` | RESULTS, CLASSES |
| **16**| **BACKUP** | Pembuatan dump cadangan database MySQL lokal dan fitur restore data sesuai Aturan 36. | `BackupService.php`, `BackupController.php` | Seluruh tabel (Database Level) | `POST /api/backup/create`, `/restore`| Database Engine |
| **17**| **SETTINGS**| Konfigurasi identitas sekolah, logo, port server, pengaturan token proktor. | `SettingController.php` | Konfigurasi lokal / File config | `GET/POST /api/settings` | Database |

---

## 17. STRATEGI AGAR AI TIDAK FULL SCAN (CONTEXT LOADING HIERARCHY)

Sesuai **Aturan 2 & 21 AI_RULES.md**:

### A. Struktur Folder Dokumentasi Modular
```text
DOCS/
├── MASTER/
│   ├── PROJECT_MAP.md              # Peta Navigasi Induk & Lokasi Modul
│   ├── REQUIREMENTS_ANALYSIS.md    # Kebutuhan & Peran Pengguna
│   ├── ROLES_PERMISSIONS.md        # Matriks Hak Akses
│   ├── MENU_STRUCTURE.md           # Struktur Menu Antarmuka
│   ├── FLOWCHARTS.md               # 6 Alur Proses Inti (Peserta, Jaringan, Timer, dll)
│   ├── DATABASE_DESIGN.md          # Spesifikasi 15 Tabel & ERD Mermaid
│   ├── API_BLUEPRINT.md            # Kontrak Spesifikasi Endpoint REST API
│   ├── MODULAR_ARCHITECTURE.md     # Arsitektur 17 Modul & Anti-Full Scan (File ini)
│   └── SYSTEM_ARCHITECTURE.md      # Server Windows, Android, Kinerja, Keamanan, Risiko
│
└── MODULES/
    ├── AUTH.md                     # Panduan khusus modul Autentikasi
    ├── USERS.md                    # Panduan khusus modul Pengguna
    ├── QUESTIONS.md                # Panduan khusus modul Bank Soal
    ├── EXAMS.md                    # Panduan khusus modul Ujian
    ├── ATTEMPTS.md                 # Panduan khusus modul Attempt
    ├── ANSWERS.md                  # Panduan khusus modul Jawaban
    ├── TIMER.md                    # Panduan khusus modul Timer
    ├── SYNC.md                     # Panduan khusus modul Sinkronisasi
    ├── SCORING.md                  # Panduan khusus modul Penilaian
    ├── MONITORING.md               # Panduan khusus modul Pengawasan
    └── BACKUP.md                   # Panduan khusus modul Cadangan Data
```

### B. Mekanisme AI Menemukan File Relevan Tanpa Full Scan:
Ketika ada tugas atau perbaikan bug (misalnya: *"Perbaiki timer yang lompat"*):
1. **LANGKAH 1 — BACA ATURAN**: Buka `AI_RULES.md` (pastikan batasan dipatuhi).
2. **LANGKAH 2 — IDENTIFIKASI MODUL DARI MAP**: Buka `DOCS/MASTER/PROJECT_MAP.md` untuk menemukan nama modul yang bertanggung jawab (`TIMER`).
3. **LANGKAH 3 — BACA DOKUMENTASI MODUL TERKAIT**: Buka `DOCS/MODULES/TIMER.md` (hanya membaca 1 file dokumentasi modul tersebut).
4. **LANGKAH 4 — IDENTIFIKASI FILE YANG BERSANGKUTAN**: Dapatkan nama file langsung dari dokumentasi modul (misal: `server/src/services/timer.service.js`).
5. **LANGKAH 5 — CEK DIRECT DEPENDENCY**: Periksa hanya modul yang terhubung langsung (misal: `ATTEMPTS`).
6. **LANGKAH 6 — EDIT & TEST**: Lakukan perubahan minimal terarah pada file tersebut, lalu lakukan pengujian.

> [!IMPORTANT]
> **Dengan metode ini, AI HANYA membaca 2–3 file relevan, BUKAN membaca puluhan file atau melakukan pemindaian menyeluruh di seluruh folder project.**
