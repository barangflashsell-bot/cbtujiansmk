# MODUL: RESULTS & SCORING
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT) — TAHAP 3I

## 1. Tanggung Jawab Modul
- Mengelola data hasil dan penilaian ujian siswa (**Results & Scoring Engine**).
- Menjamin prinsip **Server sebagai Source of Truth**:
  - Penilaian (**Grading**) dilakukan sepenuhnya di sisi server.
  - Client dilarang mengirimkan nilai score, `correct_count`, `wrong_count`, atau `unanswered_count` sebagai sumber kebenaran.
  - Nilai dihitung dari butir soal ujian (`exam_questions`), opsi jawaban (`question_options`), dan jawaban tersimpan (`answers`).
- **Integritas Penilaian & Kondisi Attempt**:
  - Hanya attempt yang berstatus `submitted` atau `timeout` yang dapat dinilai.
  - Attempt yang masih berjalan (`in_progress`) dan belum melewati batas waktu `ends_at` ditolak untuk dinilai (`HTTP 400 Bad Request`).
  - Saat sesi ujian diselesaikan via `POST /api/v1/attempts/{id}/submit`, proses grading otomatis dipicu secara atomik di dalam database transaction.
- **Idempotensi & Pencegahan Duplikasi**:
  - Tabel `results` memiliki `UNIQUE KEY uk_attempt_result (attempt_id)`.
  - Proses penilaian menggunakan mekanisme `updateOrCreate` pada `attempt_id`.
  - Pemanggilan grading berulang (retry) bersifat aman dan tidak menghasilkan record ganda.
- **Isolasi Akses & Kerahasiaan Data (Data Protection)**:
  - Siswa hanya dapat melihat hasil ujian miliknya sendiri (`HTTP 403 Forbidden` jika mengakses hasil siswa lain).
  - Siswa hanya dapat melihat hasil yang telah berstatus dipublikasikan (`is_published = true`) atau paket ujian mengatur `show_result = true` (`HTTP 403 Forbidden` jika belum dipublikasikan).
  - Admin dan Guru memiliki akses penuh melihat seluruh hasil dan memfilter berdasarkan `exam_id`, `class_id`, atau `student_id`.
  - Guru/Admin dapat mengatur status publikasi nilai via `PATCH /api/v1/results/{id}/publish`.
  - Data sensitif pengguna (`password`, token) dan kunci jawaban (`is_correct`) tidak pernah dibocorkan.

---

## 2. Tabel Database Terkait
- `results` (`id`, `attempt_id`, `exam_id`, `student_id`, `correct_count`, `wrong_count`, `unanswered_count`, `mc_score`, `essay_score`, `score`, `final_score`, `status`, `is_published`, `graded_at`, `timestamps`)
- `exam_attempts` (relasi attempt sumber penilaian)
- `exam_questions` (komposisi butir soal dan bobot/weight nilai)
- `questions` & `question_options` (pencocokan kunci jawaban `is_correct`)
- `answers` (rekaman pilihan jawaban siswa)
- `students` & `users` (identitas pemilik hasil ujian)

---

## 3. Spesifikasi Endpoint REST API (/api/v1)

### 1. `GET /api/v1/results`
- **Fungsi**: Menampilkan daftar rekapitulasi hasil ujian dengan paginasi dan filter.
- **Akses**: `student` (terisolasi hanya melihat miliknya yang sudah dipublish), `teacher`, `admin`.
- **Query Params**: `?exam_id=...&class_id=...&student_id=...&is_published=...&per_page=...`

### 2. `GET /api/v1/results/{id}`
- **Fungsi**: Menampilkan detail hasil ujian siswa (skor, jumlah benar, salah, tidak terjawab).
- **Akses**: Siswa pemilik hasil (jika sudah dipublikasikan), Guru, Admin.
- **Respon Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Detail hasil ujian berhasil diambil",
    "data": {
      "id": 1,
      "attempt_id": 10,
      "exam_id": 2,
      "student_id": 5,
      "correct_count": 8,
      "wrong_count": 2,
      "unanswered_count": 0,
      "mc_score": 80.00,
      "essay_score": 0.00,
      "score": 80.00,
      "final_score": 80.00,
      "status": "completed",
      "is_published": true,
      "graded_at": "2026-09-08T08:20:00Z",
      "exam": { ... },
      "student": { ... }
    }
  }
  ```

### 3. `GET /api/v1/attempts/{id}/result`
- **Fungsi**: Mengambil data hasil penilaian berdasarkan ID attempt ujian.
- **Akses**: Siswa pemilik attempt (jika dipublikasikan), Guru, Admin.

### 4. `POST /api/v1/attempts/{id}/grade`
- **Fungsi**: Memproses atau menghitung ulang penilaian sesi ujian yang telah selesai/timeout (*Server Source of Truth / Idempotent*).
- **Akses**: Siswa pemilik attempt, Guru, Admin.

### 5. `PATCH /api/v1/results/{id}/publish`
- **Fungsi**: Memperbarui status publikasi hasil ujian (`is_published: true/false`).
- **Akses**: `admin`, `teacher`.

---

## 4. Direct Dependencies
- Modul `ATTEMPTS`
- Modul `EXAMS`
- Modul `ANSWERS`
- Modul `STUDENTS`

---

## 5. Web UI Hasil & Nilai Ujian (Tahap 4D)
- **Controller**: `App\Http\Controllers\Web\WebResultController`
- **Views**:
  - Admin: `resources/views/admin/results/index.blade.php`, `show.blade.php`
  - Guru: `resources/views/guru/results/index.blade.php`, `show.blade.php`
- **Route Web**:
  - `GET /admin/results` & `GET /guru/results`: Rekapitulasi hasil ujian dengan filter paket ujian, rombel kelas, status publikasi, dan pencarian siswa.
  - `GET /admin/results/{id}` & `GET /guru/results/{id}`: Detail skor peserta (nilai akhir, status KKM, statistik benar/salah/kosong, dan rincian respon lembar jawaban).
  - `POST /admin/results/{id}/publish` & `POST /guru/results/{id}/publish`: Toggle publikasi hasil ujian ke siswa.
- **Prinsip Keamanan & Otorisasi**:
  - Grading tetap sepenuhnya di sisi server.
  - Kunci jawaban tidak pernah dibocorkan ke siswa.
  - Admin dapat mengelola seluruh hasil ujian sekolah.
  - Guru dibatasi hanya pada hasil ujian untuk paket ujian yang dibuatnya sendiri.
  - Siswa dilarang mengakses Web Admin/Guru (`HTTP 403 Forbidden`).

