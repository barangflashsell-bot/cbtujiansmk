# RANCANGAN DATABASE CBT (MYSQL / MARIADB)
TAHAP 1 — BLUEPRINT SKEMA & SPESIFIKASI TABEL

Dokumen ini mendefinisikan rancangan struktur database, relasi entitas, constraint integritas, dan strategi indexing untuk MySQL/MariaDB.

> [!NOTE]
> Dokumen ini adalah spesifikasi perancangan Tahap 1. **DILARANG** mengeksekusi DDL / migration ke server database sebelum memasuki Tahap 2.

---

## 1. DIAGRAM ENTITY RELATIONSHIP (LOGICAL ERD)

```text
  ┌──────────────┐          ┌──────────────┐
  │    users     │1       * │   classes    │
  │ (Admin/Guru/ ├─────────►│(Rombel Kelas)│
  │    Siswa)    │          └──────┬───────┘
  └──────┬───────┘                 │
         │1                        │1
         │                         │
         │                         │*
         │                  ┌──────▼───────┐
         │                  │ class_members│
         │                  └──────────────┘
         │
         │1                         ┌──────────────┐
         ├─────────────────────────►│   subjects   │
         │                          │(Mata Pelajar)│
         │                          └──────┬───────┘
         │                                 │1
         │                                 │
         │                          ┌──────▼───────┐
         │                          │question_banks│
         │                          └──────┬───────┘
         │                                 │1
         │                                 │*
         │                          ┌──────▼───────┐1     *┌──────────────┐
         │                          │  questions   ├──────►│question_opt..│
         │                          └──────┬───────┘       │(Opsi Jawaban)│
         │                                 │*              └──────────────┘
         │                                 │
         │1                         ┌──────▼───────┐
         ├─────────────────────────►│    exams     │
         │                          │(Jadwal Ujian)│
         │                          └──────┬───────┘
         │                                 │1
         │                                 │*
         │*                         ┌──────▼───────┐
         └─────────────────────────►│exam_attempts │
                                    │(Sesi Peserta)│
                                    └──────┬───────┘
                                           │1
                                           │*
                                    ┌──────▼───────┐
                                    │exam_answers  │
                                    │(Jawaban Sisw)│
                                    └──────────────┘
```

---

## 2. DETAIL SPESIFIKASI TABEL

### 1. Tabel: `users`
Menyimpan seluruh akun pengguna sistem (Admin, Guru, Siswa).

| Kolom | Tipe Data | Constraint / Default | Keterangan |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT UNSIGNED | AUTO_INCREMENT, PRIMARY KEY | ID unik |
| `username` | VARCHAR(64) | NOT NULL, UNIQUE | NIS/NIP/Username login |
| `password_hash` | VARCHAR(255) | NOT NULL | Hash Bcrypt / Argon2 (NO PLAINTEXT) |
| `name` | VARCHAR(128) | NOT NULL | Nama lengkap |
| `role` | ENUM | NOT NULL ('admin', 'teacher', 'student') | Hak akses |
| `nisn` | VARCHAR(32) | NULL, UNIQUE | Khusus siswa |
| `is_active` | TINYINT(1) | NOT NULL DEFAULT 1 | 1 = Aktif, 0 = Nonaktif |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Waktu pendaftaran |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Waktu pembaruan |

---

### 2. Tabel: `classes` & `class_members`
Pengelompokan kelas/rombel untuk distribusi ujian.

#### `classes`
- `id` (INT UNSIGNED, PK)
- `name` (VARCHAR(64), NOT NULL) — contoh: "9A", "8B"
- `level` (VARCHAR(16), NOT NULL) — contoh: "7", "8", "9"
- `academic_year` (VARCHAR(16), NOT NULL) — contoh: "2026/2027"

#### `class_members`
- `id` (BIGINT UNSIGNED, PK)
- `class_id` (INT UNSIGNED, FK `classes.id`)
- `student_id` (BIGINT UNSIGNED, FK `users.id`)
- `UNIQUE KEY uk_class_student (class_id, student_id)`

---

### 3. Tabel: `subjects`
Mata pelajaran sekolah.
- `id` (INT UNSIGNED, PK)
- `code` (VARCHAR(32), NOT NULL, UNIQUE) — contoh: "MAT-9"
- `name` (VARCHAR(128), NOT NULL) — contoh: "Matematika"

---

### 4. Tabel: `question_banks`
Wadah/paket bank soal per mata pelajaran.
- `id` (BIGINT UNSIGNED, PK)
- `subject_id` (INT UNSIGNED, FK `subjects.id`)
- `created_by` (BIGINT UNSIGNED, FK `users.id`)
- `title` (VARCHAR(128), NOT NULL)
- `total_questions` (INT, DEFAULT 0)
- `created_at`, `updated_at`

---

### 5. Tabel: `questions`
Butir-butir soal ujian.
- `id` (BIGINT UNSIGNED, PK)
- `bank_id` (BIGINT UNSIGNED, FK `question_banks.id` ON DELETE CASCADE)
- `question_type` (ENUM: `'single_choice'`, `'multiple_choice'`, `'essay'`, DEFAULT `'single_choice'`)
- `content` (TEXT, NOT NULL) — Teks soal (dapat berisi HTML aman / Markdown)
- `media_path` (VARCHAR(255), NULL) — Tautan file gambar pendukung soal jika ada
- `score_weight` (DECIMAL(5,2), DEFAULT 1.00) — Bobot nilai butir soal
- `created_at`, `updated_at`

---

### 6. Tabel: `question_options`
Opsi pilihan jawaban untuk tipe soal pilihan ganda.
- `id` (BIGINT UNSIGNED, PK)
- `question_id` (BIGINT UNSIGNED, FK `questions.id` ON DELETE CASCADE)
- `option_label` (VARCHAR(8), NOT NULL) — contoh: "A", "B", "C", "D", "E"
- `content` (TEXT, NOT NULL) — Teks opsi
- `media_path` (VARCHAR(255), NULL) — Gambar pada opsi
- `is_correct` (TINYINT(1), NOT NULL DEFAULT 0) — **SERVER-SIDE ONLY** (Dilarang dikirim ke Android peserta)
- `INDEX idx_question (question_id)`

---

### 7. Tabel: `exams`
Sesi dan konfigurasi pelaksanaan ujian.
- `id` (BIGINT UNSIGNED, PK)
- `bank_id` (BIGINT UNSIGNED, FK `question_banks.id`)
- `title` (VARCHAR(128), NOT NULL) — contoh: "Penilaian Akhir Semester Ganjil"
- `token` (VARCHAR(16), NULL) — Token ujian (opsional jika proktor mensyaratkan)
- `duration_minutes` (INT, NOT NULL) — Durasi ujian (contoh: 90 menit)
- `start_window` (DATETIME, NOT NULL) — Waktu paling awal ujian dapat dibuka
- `end_window` (DATETIME, NOT NULL) — Batas akhir penutupan sesi
- `shuffle_questions` (TINYINT(1), DEFAULT 1) — Acak urutan nomor soal
- `shuffle_options` (TINYINT(1), DEFAULT 1) — Acak opsi jawaban A/B/C/D
- `status` (ENUM: `'draft'`, `'published'`, `'active'`, `'completed'`, DEFAULT `'draft'`)
- `created_by` (BIGINT UNSIGNED, FK `users.id`)
- `created_at`, `updated_at`

---

### 8. Tabel: `exam_attempts` (Sesi Pengerjaan Peserta — KRITIS)
Merekam status pengerjaan setiap siswa dalam sebuah ujian.
- `id` (BIGINT UNSIGNED, PK)
- `exam_id` (BIGINT UNSIGNED, FK `exams.id`)
- `student_id` (BIGINT UNSIGNED, FK `users.id`)
- `started_at` (DATETIME, NOT NULL) — Waktu server saat tombol mulai diklik
- `ends_at` (DATETIME, NOT NULL) — Waktu server saat ujian wajib selesai (`started_at + duration`)
- `submitted_at` (DATETIME, NULL) — Waktu server saat peserta submit / timeout
- `status` (ENUM: `'in_progress'`, `'submitted'`, `'timeout'`, `'blocked'`, DEFAULT `'in_progress'`)
- `total_score` (DECIMAL(5,2), NULL) — Nilai akhir kalkulasi server
- `ip_address` (VARCHAR(45), NULL) — IP Android peserta di jaringan Wi-Fi
- `user_agent` (VARCHAR(255), NULL) — Info perangkat
- `created_at`, `updated_at`
- `UNIQUE KEY uk_exam_student (exam_id, student_id)` — Mencegah duplicate attempt
- `INDEX idx_status (status)`
- `INDEX idx_ends_at (ends_at)`

---

### 9. Tabel: `exam_answers` (Jawaban Peserta — KRITIS & HIGH CONCURRENCY)
Merekam pilihan atau jawaban siswa per nomor butir soal.
- `id` (BIGINT UNSIGNED, PK)
- `attempt_id` (BIGINT UNSIGNED, FK `exam_attempts.id` ON DELETE CASCADE)
- `question_id` (BIGINT UNSIGNED, FK `questions.id`)
- `selected_option_id` (BIGINT UNSIGNED, NULL, FK `question_options.id`) — Untuk pilihan ganda
- `essay_answer` (TEXT, NULL) — Untuk esai
- `is_flagged` (TINYINT(1), DEFAULT 0) — Status ragu-ragu
- `is_correct` (TINYINT(1), NULL) — Hasil penilaian otomatis server (1 = Benar, 0 = Salah)
- `earned_score` (DECIMAL(5,2), DEFAULT 0.00) — Poin yang diperoleh
- `synced_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)
- `UNIQUE KEY uk_attempt_question (attempt_id, question_id)` — **Kunci Idempotensi & Upsert**
- `INDEX idx_attempt (attempt_id)`

---

## 3. STRATEGI CONCURRENCY & INTEGRITAS DATA

1. **Operasi Autosave Idempotent**:
   Dengan adanya `UNIQUE KEY uk_attempt_question (attempt_id, question_id)`, penyimpanan jawaban dari Android menggunakan kueri upsert aman:
   ```sql
   INSERT INTO exam_answers (attempt_id, question_id, selected_option_id, is_flagged, synced_at)
   VALUES (?, ?, ?, ?, NOW())
   ON DUPLICATE KEY UPDATE
       selected_option_id = VALUES(selected_option_id),
       is_flagged = VALUES(is_flagged),
       synced_at = NOW();
   ```
2. **Kunci Jawaban Terisolasi**:
   Kolom `is_correct` pada `question_options` tidak pernah disertakan dalam payload query saat server mengirim paket soal ke aplikasi Android peserta.
3. **Pemberian Skor Server-Side (Auto-Grading)**:
   Kalkulasi nilai dilakukan murni di server dengan membandingkan `exam_answers.selected_option_id` dengan opsi yang memiliki `question_options.is_correct = 1`.
