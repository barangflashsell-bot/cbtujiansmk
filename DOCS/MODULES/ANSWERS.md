# MODUL: ANSWERS & AUTOSAVE
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT) — TAHAP 3H

## 1. Tanggung Jawab Modul
- Menerima dan menyimpan jawaban butir soal siswa secara autosave (*Atomic Upsert*).
- Menjamin integritas data jawaban berdasarkan prinsip **Server sebagai Source of Truth**:
  - `question_id` wajib divalidasi server dan harus benar-benar merupakan butir soal yang terdaftar pada ujian tersebut (`HTTP 422` jika di luar ujian).
  - `selected_option_id` diverifikasi keberadaannya dan harus merupakan opsi jawaban dari soal yang bersangkutan (`HTTP 422` jika tidak cocok).
  - Status sesi ujian (`exam_attempts`) harus berstatus `in_progress` dan waktu belum habis (`now() <= ends_at`). Perubahan jawaban ditolak (`HTTP 403 Forbidden`) jika attempt sudah `submitted`, `timeout`, atau `blocked`.
  - Jawaban hanya dapat disimpan/diubah oleh pemilik sah sesi ujian (`HTTP 403 Forbidden` jika diakses peserta lain).
- **Idempotency & Atomic Upsert**:
  - Tabel `answers` memiliki `UNIQUE KEY uk_attempt_question (attempt_id, question_id)`.
  - Operasi penyimpanan menggunakan `updateOrCreate` di dalam database transaction, sehingga pemanggilan berulang (retry) tidak pernah menghasilkan baris duplikat.
- **Kerahasiaan Kunci Jawaban & Skor**:
  - Kolom `is_correct` dan `earned_score` tidak pernah diterima dari input client dan tidak pernah diekspos ke client siswa pada tahap pengerjaan.

---

## 2. Tabel Database Terkait
- `answers` (`id`, `attempt_id`, `question_id`, `selected_option_id`, `selected_option`, `essay_answer`, `is_marked`, `is_flagged`, `is_correct`, `earned_score`, `answered_at`, `synced_at`, `timestamps`)
- `exam_attempts` (validasi status sesi pengerjaan)
- `questions` & `exam_questions` (verifikasi integritas keikutsertaan butir soal dalam ujian)
- `question_options` (verifikasi relasi opsi jawaban terhadap soal)

---

## 3. Spesifikasi Endpoint REST API (/api/v1)

### 1. `POST /api/v1/attempts/{id}/answers`
- **Fungsi**: Autosave 1 butir jawaban peserta secara asinkron (Atomic Upsert / Idempotent).
- **Akses**: Siswa pemilik sesi ujian (`student`).
- **Request Body**:
  ```json
  {
    "question_id": 101,
    "selected_option_id": 402,
    "essay_answer": null,
    "is_flagged": false
  }
  ```
- **Respon Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Jawaban berhasil disimpan",
    "data": {
      "status": "SAVED",
      "synced_at": "2026-09-08T08:10:12Z",
      "remaining_seconds": 3540,
      "answer": {
        "id": 1,
        "attempt_id": 10,
        "question_id": 101,
        "selected_option_id": 402,
        "selected_option": "A",
        "essay_answer": null,
        "is_flagged": false,
        "answered_at": "2026-09-08T08:10:12Z"
      }
    }
  }
  ```
- **Penanganan Error**:
  - `401 Unauthorized`: Request tidak membawa bearer token.
  - `403 Forbidden`: Attempt bukan milik siswa yang login, ATAU attempt telah terkunci (`submitted`/`timeout`).
  - `404 Not Found`: Sesi ujian tidak ditemukan.
  - `422 Unprocessable Content`: Butir soal bukan bagian dari paket ujian, atau opsi tidak sesuai dengan butir soal.

### 2. `GET /api/v1/attempts/{id}/answers`
- **Fungsi**: Mengambil seluruh daftar jawaban yang telah tersimpan pada sesi ujian.
- **Akses**: Siswa pemilik attempt, Admin, atau Guru.

### 3. `POST /api/v1/attempts/{id}/sync`
- **Fungsi**: Sinkronisasi kumpulan jawaban dari antrean offline klien Android (*Batch Upsert / Idempotent*). Detail lengkap terdapat pada `DOCS/MODULES/SYNC.md`.
- **Akses**: Siswa pemilik attempt (`student`).

---

## 4. Direct Dependencies
- Modul `ATTEMPTS`
- Modul `EXAMS`
- Modul `QUESTIONS`
- Modul `SYNC`
