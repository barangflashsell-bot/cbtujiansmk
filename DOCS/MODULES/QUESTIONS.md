# MODUL: QUESTIONS & QUESTION OPTIONS (BANK SOAL)
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT) — TAHAP 3E

## 1. Tanggung Jawab Modul
- Mengelola data master butir pertanyaan (**Questions**) dan opsi jawaban pilihan ganda (**Question Options**).
- Hak akses penuh (**CRUD**) dipegang oleh **ADMIN** dan **GURU** (sebagai pembuat/penyusun soal ujian).
- Siswa (**PESERTA**) dilarang keras mengakses endpoint Bank Soal (`HTTP 403 Forbidden`).
- **Answer Key Protection**: Kunci jawaban (`is_correct`) adalah data rahasia. Akses kunci jawaban hanya diizinkan untuk Admin dan Guru demi keperluan penyusunan soal. Peserta tidak dapat melihat kunci jawaban.
- **Integritas Relasi**:
  - `Question` terikat ke `Subject` (`subject_id` foreign key) dan `Teacher` (`created_by` foreign key).
  - `QuestionOption` terikat ke `Question` (`question_id` foreign key dengan cascade delete).
- **Integritas Penghapusan (Delete Safety)**:
  - Menghapus `Question` ditolak (`HTTP 400 Bad Request`) jika soal telah terhubung ke paket ujian (`exam_questions`) atau telah memiliki respon jawaban peserta (`answers`).
  - Menghapus `Question` **TIDAK** akan menghapus entitas `Subject`.
  - Menghapus `QuestionOption` ditolak (`HTTP 400 Bad Request`) jika opsi tersebut sudah dipilih siswa dalam lembar jawaban (`answers`).
  - Menghapus `QuestionOption` **TIDAK** menghapus opsi lain ataupun induk `Question`.

---

## 2. Tabel Database Terkait
- `questions` (`id`, `subject_id`, `created_by`, `question_type`, `content`, `media_path`, `score_weight`, `difficulty`, `explanation`, `status`, `timestamps`)
- `question_options` (`id`, `question_id`, `option_label`, `content`, `media_path`, `is_correct`, `timestamps`)
- `subjects` (induk mata pelajaran)
- `teachers` (pembuat soal)
- `exam_questions` & `answers` (relasi integritas penghapusan)

---

## 3. Spesifikasi Endpoint REST API

### Questions
- `GET /api/v1/questions`: Menampilkan daftar butir soal terpaginasi beserta hitungan opsi (`options_count`) dan data mata pelajaran (Admin, Guru).
- `GET /api/v1/questions/{id}`: Menampilkan detail butir soal beserta daftar opsi jawaban (Admin, Guru).
- `POST /api/v1/questions`: Membuat butir soal baru. Field `created_by` otomatis mengikat profil guru jika dibuat oleh Guru (Admin, Guru).
- `PUT/PATCH /api/v1/questions/{id}`: Memperbarui data butir soal (Admin, Guru).
- `DELETE /api/v1/questions/{id}`: Menghapus butir soal yang belum masuk paket ujian atau lembar jawaban (Admin, Guru).

### Question Options (Nested)
- `GET /api/v1/questions/{questionId}/options`: Menampilkan daftar opsi jawaban terpaginasi dari suatu butir soal (Admin, Guru).
- `GET /api/v1/questions/{questionId}/options/{id}`: Menampilkan detail satu opsi jawaban (Admin, Guru).
- `POST /api/v1/questions/{questionId}/options`: Menambahkan pilihan jawaban baru untuk butir soal terkait (Admin, Guru).
- `PUT/PATCH /api/v1/questions/{questionId}/options/{id}`: Memperbarui pilihan jawaban dan kunci `is_correct` (Admin, Guru).
- `DELETE /api/v1/questions/{questionId}/options/{id}`: Menghapus pilihan jawaban jika belum pernah dipilih dalam ujian (Admin, Guru).

---

## 4. Validasi Data

### Questions
- `subject_id`: Required, integer, harus ada di tabel `subjects`.
- `created_by`: Optional/Nullable, integer, harus ada di tabel `teachers`.
- `question_type`: Required, string, salah satu dari: `single_choice`, `multiple_choice`, `essay`.
- `content`: Required, string.
- `media_path`: Nullable, string, maksimal 255 karakter.
- `score_weight`: Nullable, numeric, minimal 0, maksimal 999.99 (default: 1.00).
- `difficulty`: Nullable, string, salah satu dari: `easy`, `medium`, `hard` (default: 'medium').
- `explanation`: Nullable, string.
- `status`: Nullable, string, maksimal 32 karakter (default: 'active').

### Question Options
- `option_label`: Required, string, maksimal 8 karakter (contoh: "A", "B", "C", "D", "E").
- `content`: Required, string.
- `media_path`: Nullable, string, maksimal 255 karakter.
- `is_correct`: Nullable, boolean (default: false).

---

## 5. Perlindungan Kunci Jawaban (Answer Key Protection)
1. Seluruh endpoint `/api/v1/questions` dan `/api/v1/questions/{questionId}/options` dijaga ketat oleh middleware `auth:sanctum` dan `role:admin,teacher`.
2. Siswa (role: `student` / `peserta`) yang mencoba mengakses endpoint Bank Soal akan langsung diblokir dengan respon **HTTP 403 Forbidden**.
3. Nilai `is_correct` hanya dikirimkan kepada Admin dan Guru saat mengelola Bank Soal.

---

## 6. Web UI Bank Soal (Tahap 4C)
- **Controller**: `App\Http\Controllers\Web\WebQuestionController`
- **Views**:
  - Admin: `resources/views/admin/questions/index.blade.php`, `create.blade.php`, `edit.blade.php`
  - Guru: `resources/views/guru/questions/index.blade.php`, `create.blade.php`, `edit.blade.php`
- **Route Web**:
  - Admin:
    - `GET /admin/questions`: List seluruh butir bank soal dengan filter mata pelajaran, tipe soal, pencarian, dan badge kunci.
    - `GET /admin/questions/create`: Form pembuatan butir soal baru + pilihan jawaban A-E + kunci jawaban.
    - `POST /admin/questions`: Simpan butir soal baru dan relasi options.
    - `GET /admin/questions/{id}/edit`: Form edit butir soal dan opsi jawaban.
    - `PUT /admin/questions/{id}`: Update butir soal dan opsi jawaban.
    - `DELETE /admin/questions/{id}`: Hapus butir soal beserta opsi terkait.
  - Guru:
    - `GET /guru/questions`: List butir soal yang dibuat oleh guru login saja (`where created_by = $teacher->id`).
    - `GET /guru/questions/create`: Form penyusunan butir soal untuk guru.
    - `POST /guru/questions`: Simpan butir soal guru login.
    - `GET /guru/questions/{id}/edit`: Form edit butir soal milik guru login.
    - `PUT /guru/questions/{id}`: Simpan perubahan butir soal milik guru login.
    - `DELETE /guru/questions/{id}`: Hapus butir soal milik guru login.
- **Otorisasi & Ownership Guard**:
  - Administrator memiliki akses global ke seluruh bank soal sekolah.
  - Guru memiliki akses eksklusif ke butir soal yang dibuatnya sendiri. Upaya guru mengakses, mengedit, atau menghapus butir soal milik guru lain diblokir dengan **HTTP 403 Forbidden** di server-side.
  - Siswa ditolak (**HTTP 403 Forbidden**).

