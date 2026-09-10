# MODUL: TIMER (SERVER-AUTHORITATIVE)
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT) — TAHAP 3M

## 1. Tanggung Jawab Modul
- Server adalah satu-satunya sumber mutlak kebenaran (*Single Source of Truth*) untuk penghitungan dan validasi waktu ujian.
- **Client/Android Immunity**:
  - Jam perangkat Android/client **TIDAK PERNAH** menjadi sumber kebenaran.
  - Client dilarang menentukan `remaining_seconds`, `started_at`, atau `ends_at`.
  - Android hanya menggunakan selisih `ends_at` dan `server_time` untuk menampilkan countdown visual di layar.
- **Perhitungan Batas Akhir Ujian (Boundary & Late Participant)**:
  - `ends_at = exam.end_window ? MIN(started_at + INTERVAL duration_minutes MINUTE, exam.end_window) : (started_at + duration_minutes)`.
  - Peserta yang terlambat masuk tetap dibatasi oleh `exam.end_window`. Tidak ada penambahan waktu otomatis.
- **Siklus Status Attempt & Timeout**:
  - `not_started`: Sesi belum berjalan, sisa waktu sama dengan durasi penuh paket ujian.
  - `in_progress`: Sesi aktif, sisa waktu dihitung secara dinamis dari `max(0, ends_at - server_now)`. Jika waktu habis (`NOW() >= ends_at`), server otomatis mengubah status menjadi `timeout`.
  - `submitted`: Sesi telah diselesaikan secara resmi, sisa waktu 0, tidak dapat melanjutkan pengerjaan (`can_continue: false`).
  - `timeout`: Waktu telah habis, sisa waktu 0, pengerjaan dikunci (`can_continue: false`).
- **Mekanisme Pemulihan Sesi (Recovery)**:
  - Jika aplikasi tertutup, browser crash, atau koneksi Wi-Fi terputus, sisa waktu saat client kembali online dihitung murni berdasarkan `ends_at` yang tersimpan di database server vs `NOW()` server. Replay atau pengulangan request tidak pernah memperpanjang sisa waktu.
- **Perpanjangan Waktu Manual (Admin & Pengawas)**:
  - Administrator dan Guru/Pengawas dapat melakukan perpanjangan waktu manual jika terjadi kendala teknis/listrik padam (`POST /api/v1/attempts/{id}/extend-time`).
  - Setiap tindakan perpanjangan waktu wajib dicatat secara permanen ke tabel `activity_logs`.

---

## 2. Tabel Database Terkait
- `exam_attempts` (`started_at`, `ends_at`, `status`, `last_activity_at`)
- `exams` (`duration_minutes`, `start_window`, `end_window`, `status`)
- `exam_participants` (verifikasi status pendaftaran ujian siswa)
- `activity_logs` (pencatatan audit trail perpanjangan waktu)

---

## 3. Spesifikasi Endpoint REST API (/api/v1)

### 1. `GET /api/v1/attempts/{id}/timer`
- **Fungsi**: Mengambil data timer sinkronisasi server untuk sesi pengerjaan ujian tertentu.
- **Akses**: Siswa pemilik attempt (`student_id` cocok), atau Admin/Guru. Siswa lain ditolak (`HTTP 403 Forbidden`).
- **Respon Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Status timer ujian berhasil diambil",
    "data": {
      "attempt_id": 10,
      "exam_id": 2,
      "student_id": 5,
      "status": "in_progress",
      "server_time": "2026-09-08T15:40:00+07:00",
      "started_at": "2026-09-08T15:00:00+07:00",
      "ends_at": "2026-09-08T16:00:00+07:00",
      "duration_seconds": 3600,
      "remaining_seconds": 1200,
      "elapsed_seconds": 2400,
      "is_expired": false,
      "can_continue": true
    }
  }
  ```

---

### 2. `GET /api/v1/exams/{id}/timer`
- **Fungsi**: Membaca status timer siswa pada paket ujian (mendukung state `not_started`, pemulihan pengerjaan, dan jadwal global).
- **Akses**: Siswa terdaftar pada paket ujian, atau Admin/Guru.
- **Respon Sukses (200 OK - Siswa Belum Mulai)**:
  ```json
  {
    "success": true,
    "message": "Status timer ujian berhasil diambil",
    "data": {
      "attempt_id": null,
      "exam_id": 2,
      "student_id": 5,
      "status": "not_started",
      "server_time": "2026-09-08T14:55:00+07:00",
      "started_at": null,
      "ends_at": null,
      "duration_seconds": 3600,
      "remaining_seconds": 3600,
      "elapsed_seconds": 0,
      "is_expired": false,
      "can_continue": true
    }
  }
  ```

---

### 3. `POST /api/v1/attempts/{id}/extend-time`
- **Fungsi**: Memperpanjang batas waktu (`ends_at`) sesi ujian siswa secara manual oleh Admin/Guru.
- **Akses**: `admin`, `teacher` (Siswa ditolak: `HTTP 403 Forbidden`).
- **Request Body (JSON)**:
  ```json
  {
    "added_minutes": 15,
    "reason": "Kendala teknis Wi-Fi terputus di ruang 2"
  }
  ```
- **Respon Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Waktu ujian berhasil diperpanjang",
    "data": {
      "attempt_id": 10,
      "exam_id": 2,
      "student_id": 5,
      "status": "in_progress",
      "server_time": "2026-09-08T15:40:00+07:00",
      "started_at": "2026-09-08T15:00:00+07:00",
      "ends_at": "2026-09-08T16:15:00+07:00",
      "duration_seconds": 3600,
      "remaining_seconds": 2100,
      "elapsed_seconds": 2400,
      "is_expired": false,
      "can_continue": true
    }
  }
  ```

---

## 4. Security & Safety Rules
1. **Authoritative Timestamping**: Semua kalkulasi waktu menggunakan `now()` dari server PHP/MySQL.
2. **Client Override Immunity**: Request GET/POST dengan query atau payload `remaining_seconds`, `started_at`, atau `ends_at` tidak pernah mempengaruhi nilai di server.
3. **IDOR & Ownership Protection**: Siswa hanya dapat membaca timer miliknya sendiri. Akses ke attempt siswa lain menghasilkan `HTTP 403 Forbidden`.
4. **Audit Logging**: Perpanjangan waktu manual dicatat pada tabel `activity_logs` dengan informasi user, IP, menit tambahan, timestamp lama, dan timestamp baru.

---

## 5. Test Coverage
- **File Test**: `SERVER/tests/Feature/TimerTest.php`
- **Metode Pengujian**:
  1. `test_unauthenticated_requests_return_401`
  2. `test_unauthorized_student_cannot_view_other_student_attempt_timer_returns_403`
  3. `test_not_started_state_returns_full_duration_and_can_continue`
  4. `test_in_progress_state_calculates_remaining_time_server_side`
  5. `test_submitted_state_cannot_continue_and_remaining_is_zero`
  6. `test_timeout_state_cannot_continue_and_auto_transitions`
  7. `test_client_cannot_override_remaining_time_or_timestamps`
  8. `test_repeated_requests_do_not_extend_time`
  9. `test_admin_and_teacher_can_extend_time_and_audit_log_is_written`
  10. `test_student_cannot_extend_time_returns_403`
  11. `test_boundary_window_caps_ends_at_for_late_student`
