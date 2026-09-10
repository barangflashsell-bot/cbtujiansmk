# MODUL: ATTEMPTS & RECOVERY UJIAN
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT) — TAHAP 3H

## 1. Tanggung Jawab Modul
- Membuat record sesi pengerjaan siswa (`exam_attempts`).
- Mengelola siklus status attempt sesuai schema database:
  - `not_started`
  - `in_progress`
  - `submitted`
  - `timeout`
  - `blocked`
- Server sebagai **Source of Truth**:
  - `started_at` dan `ends_at` ditentukan dan divalidasi oleh server.
  - Perhitungan waktu akhir: `ends_at = exam.end_window ? MIN(started_at + duration_minutes, exam.end_window) : (started_at + duration_minutes)`.
  - Siswa terlambat tetap dibatasi oleh `exam.end_window`.
- **Proteksi Integritas & Keamanan**:
  - Attempt harus terkait dengan siswa yang terdaftar aktif dalam ujian (`exam_participants`).
  - Siswa dilarang memulai ujian sebelum jadwal `start_window` atau setelah `end_window`.
  - Token ujian diverifikasi jika ujian membutuhkan token.
  - Kunci jawaban (`is_correct`) **TIDAK PERNAH** dibocorkan ke payload soal ujian.
  - Siswa hanya dapat melihat dan mengakses attempt miliknya sendiri (`HTTP 403 Forbidden` jika mengakses milik siswa lain).
- **Mekanisme Recovery Ujian (Single Active Attempt)**:
  - Tabel `exam_attempts` memiliki unique constraint `(exam_id, student_id)`.
  - Pemanggilan `POST /api/v1/exams/{id}/start` secara berulang saat status masih `in_progress` berfungsi sebagai recovery (mengembalikan data attempt aktif, waktu tersisa, dan jawaban yang sudah tersimpan tanpa membuat record duplikat).
  - Jika waktu ujian sudah habis (`now() > ends_at`), status otomatis ditandai `timeout`.
  - Pengulangan ujian untuk attempt yang sudah `submitted`/`timeout` diblokir kecuali siswa memiliki hak `allow_retest = true`.
- **Idempotensi Penyelesaian (Submit)**:
  - `POST /api/v1/attempts/{id}/submit` bersifat idempotent. Jika dipanggil berulang, server mengembalikan respon sukses `200 OK` tanpa error.

---

## 2. Tabel Database Terkait
- `exam_attempts` (`id`, `exam_id`, `student_id`, `started_at`, `ends_at`, `submitted_at`, `status`, `last_activity_at`, `ip_address`, `device_info`, `timestamps`)
- `exam_participants` (relasi pendaftaran siswa & izin `allow_retest`)
- `exams` (konfigurasi durasi, token, jadwal `start_window` & `end_window`)
- `students` & `users` (identitas peserta)

---

## 3. Spesifikasi Endpoint REST API (/api/v1)

### 1. `POST /api/v1/exams/{id}/start` (atau `POST /api/v1/exams/{id}/attempts`)
- **Fungsi**: Memulai sesi ujian baru atau memulihkan (recovery) sesi ujian yang sedang berjalan.
- **Akses**: Role `student` (terdaftar di `exam_participants`).
- **Validasi**:
  - Paket ujian harus berstatus `published` atau `active`.
  - Waktu server berada di dalam `start_window` dan `end_window`.
  - Token ujian sesuai (jika ujian memakai token).
  - Siswa belum menyelesaikan ujian, atau memiliki hak `allow_retest`.
- **Payload Respon**: Data sesi ujian, waktu server, detik tersisa, butir soal (tanpa `is_correct`), dan jawaban yang sudah tersimpan.

### 2. `GET /api/v1/attempts/{id}`
- **Fungsi**: Menampilkan status sesi pengerjaan ujian, sisa waktu, dan ringkasan paket.
- **Akses**: Siswa pemilik attempt (`student_id` cocok), atau Admin/Guru.

### 3. `POST /api/v1/attempts/{id}/submit`
- **Fungsi**: Mengunci dan menyelesaikan sesi ujian secara resmi (*Idempotent*).
- **Akses**: Siswa pemilik attempt.

---

## 4. Direct Dependencies
- Modul `EXAMS`
- Modul `STUDENTS`
- Modul `ANSWERS`

---

## 5. Web UI Telemetry & Attempt Retest Integration (Tahap 4D)
- **Monitoring Integration**: Status attempt (`not_started`, `in_progress`, `submitted`, `timeout`), IP address, dan durasi sisa waktu dipantau secara visual melalui modul Live Monitoring (`/admin/monitoring`, `/guru/monitoring`).
- **Retest Permission**: Admin dan Guru dapat mengelola hak ujian ulang (`allow_retest`) langsung dari halaman detail peserta ujian (`/admin/exams/{id}`, `/guru/exams/{id}`).

