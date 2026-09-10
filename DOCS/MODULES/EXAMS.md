# MODUL: EXAMS & EXAM QUESTIONS
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT) — TAHAP 3F

## 1. Tanggung Jawab Modul
- Mengelola paket jadwal dan sesi pelaksanaan ujian (**Exams**).
- Mengelola penyusunan butir soal ke dalam paket ujian melalui tabel pivot (**Exam Questions**).
- Hak kelola penuh (**CRUD**) dipegang oleh **ADMIN** dan **GURU**.
- Siswa (**PESERTA**) dapat melihat daftar ujian yang berstatus aktif/published (`GET /api/v1/exams`) dan detail butir soal ujian tanpa kunci jawaban. Siswa dilarang keras memodifikasi data ujian (`HTTP 403 Forbidden`).
- **Answer Key Protection**: Kunci jawaban (`is_correct`) pada butir soal ujian diisolasi secara ketat dan disembunyikan jika endpoint dikonsumsi oleh peserta (`student`).
- **Integritas Relasi**:
  - `Exam` terikat ke `Subject` (`subject_id` foreign key) dan pengguna pembuat (`created_by` foreign key).
  - Butir soal yang dimasukkan ke dalam paket ujian harus berasal dari mata pelajaran (`subject_id`) yang sama.
  - Duplikasi butir soal dalam satu paket ujian dilarang (`unique(['exam_id', 'question_id'])`).
- **Integritas Penghapusan (Delete Safety)**:
  - Menghapus `Exam` ditolak (`HTTP 400 Bad Request`) jika ujian sudah memiliki riwayat pengerjaan siswa (`exam_attempts`).
  - Melepas butir soal dari ujian (`detach`) ditolak (`HTTP 400 Bad Request`) jika ujian telah memiliki riwayat pengerjaan siswa.
  - Melepas butir soal dari ujian **TIDAK** menghapus butir soal asli di Bank Soal (`questions`).

---

## 2. Tabel Database Terkait
- `exams` (`id`, `subject_id`, `created_by`, `title`, `description`, `instructions`, `duration_minutes`, `passing_score`, `token`, `start_window`, `end_window`, `shuffle_questions`, `shuffle_options`, `show_result`, `allow_review`, `status`, `timestamps`)
- `exam_questions` (`id`, `exam_id`, `question_id`, `order_index`, `weight`, `timestamps`)
- `subjects` (mata pelajaran yang diujikan)
- `users` (pembuat jadwal ujian)
- `questions` (butir soal yang dilampirkan)
- `exam_attempts` (relasi integritas pengerjaan siswa)

---

## 3. Spesifikasi Endpoint REST API

### Exams
- `GET /api/v1/exams`: Menampilkan daftar paket ujian terpaginasi beserta hitungan soal (`questions_count`). Peserta hanya dapat melihat ujian aktif/published (Admin, Guru, Peserta).
- `GET /api/v1/exams/{id}`: Menampilkan detail paket ujian beserta butir soal. Kunci jawaban otomatis disembunyikan untuk peserta (Admin, Guru, Peserta).
- `POST /api/v1/exams`: Membuat paket ujian baru (Admin, Guru).
- `PUT/PATCH /api/v1/exams/{id}`: Memperbarui konfigurasi jadwal, durasi, token, atau status ujian (Admin, Guru).
- `DELETE /api/v1/exams/{id}`: Menghapus paket ujian yang belum pernah dikerjakan oleh siswa (Admin, Guru).

### Exam Questions (Penyusunan Butir Soal Ujian)
- `GET /api/v1/exams/{examId}/questions`: Menampilkan daftar butir soal ujian berurutan (`order_index`). Kunci jawaban tidak pernah diekspos kepada peserta (Admin, Guru, Peserta).
- `POST /api/v1/exams/{examId}/questions`: Melampirkan butir soal ke dalam paket ujian dengan urutan dan bobot nilai (Admin, Guru).
- `PUT/PATCH /api/v1/exams/{examId}/questions/{questionId}`: Memperbarui urutan (`order_index`) atau bobot poin (`weight`) soal dalam ujian (Admin, Guru).
- `DELETE /api/v1/exams/{examId}/questions/{questionId}`: Melepaskan butir soal dari paket ujian (Admin, Guru).

### Exam Participants (Pendaftaran Peserta Ujian)
- `GET /api/v1/exams/{examId}/participants`: Menampilkan daftar peserta terdaftar pada ujian terpaginasi (Admin, Guru).
- `POST /api/v1/exams/{examId}/participants`: Mendaftarkan satu siswa (`student_id`) atau seluruh siswa dalam satu kelas (`class_id`) ke dalam ujian (Admin, Guru).
- `GET /api/v1/exams/{examId}/participants/{participantId}`: Menampilkan detail data pendaftaran peserta (Admin, Guru).
- `PUT/PATCH /api/v1/exams/{examId}/participants/{participantId}`: Memperbarui hak izin ujian ulang (`allow_retest`) (Admin, Guru).
- `DELETE /api/v1/exams/{examId}/participants/{participantId}`: Membatalkan/menghapus pendaftaran peserta ujian jika belum mengerjakan ujian (Admin, Guru).

---

## 4. Validasi Data

### Exams
- `subject_id`: Required, integer, harus ada di tabel `subjects`.
- `title`: Required, string, maksimal 128 karakter.
- `description`: Nullable, string/text.
- `instructions`: Nullable, string/text.
- `duration_minutes`: Required, integer, 1 s.d. 1440 menit.
- `passing_score`: Nullable, numeric, 0 s.d. 100 (default: 75.00).
- `token`: Nullable, string, maksimal 16 karakter.
- `start_window`: Required, format datetime valid.
- `end_window`: Required, format datetime valid, harus setelah `start_window`.
- `shuffle_questions`: Nullable, boolean (default: true).
- `shuffle_options`: Nullable, boolean (default: true).
- `show_result`: Nullable, boolean (default: false).
- `allow_review`: Nullable, boolean (default: false).
- `status`: Nullable, salah satu dari: `draft`, `published`, `active`, `completed` (default: 'draft').

### Exam Questions
- `question_id`: Required, integer, harus ada di tabel `questions`.
- `order_index`: Nullable, integer, minimal 0.
- `weight`: Nullable, numeric, minimal 0.
- Aturan Validasi Khusus:
  - Mata pelajaran butir soal harus cocok dengan mata pelajaran ujian.
  - Butir soal tidak boleh duplikat dalam paket ujian yang sama.

---

## 5. Web UI Exams & Peserta Ujian (Tahap 4D)
- **Controller**: `App\Http\Controllers\Web\WebExamController`
- **Views**:
  - Admin: `resources/views/admin/exams/index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`
  - Guru: `resources/views/guru/exams/index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`
- **Route Web**:
  - `GET /admin/exams` & `GET /guru/exams`: List paket ujian terpaginasi dengan filter mapel dan status.
  - `GET /admin/exams/create` & `GET /guru/exams/create`: Form pembuatan paket ujian baru.
  - `POST /admin/exams` & `POST /guru/exams`: Simpan paket ujian baru.
  - `GET /admin/exams/{id}` & `GET /guru/exams/{id}`: Detail paket ujian, manajemen butir soal lampiran (`exam_questions`), dan pendaftaran peserta (`exam_participants`).
  - `GET /admin/exams/{id}/edit` & `GET /guru/exams/{id}/edit`: Form pembaruan konfigurasi ujian.
  - `PUT /admin/exams/{id}` & `PUT /guru/exams/{id}`: Simpan pembaruan paket ujian.
  - `DELETE /admin/exams/{id}` & `DELETE /guru/exams/{id}`: Hapus paket ujian (dicegah jika memiliki attempt pengerjaan siswa).
  - `POST /.../exams/{id}/questions`: Lampirkan butir soal dari bank soal.
  - `DELETE /.../exams/{id}/questions/{questionId}`: Lepaskan butir soal dari paket ujian.
  - `POST /.../exams/{id}/participants`: Daftarkan peserta per siswa atau per rombel/kelas sekaligus.
  - `DELETE /.../exams/{id}/participants/{participantId}`: Batalkan pendaftaran peserta.
  - `POST /.../exams/{id}/participants/{participantId}/retest`: Toggle hak ujian ulang (`allow_retest`).
- **Otorisasi & Ownership**:
  - Admin: Akses penuh ke seluruh paket ujian, butir soal terlampir, dan peserta.
  - Guru: Dibatasi hanya pada paket ujian yang dibuatnya sendiri (`where created_by = $user->id`). Akses ke paket ujian guru lain diblokir dengan HTTP 403 Forbidden.
  - Siswa: Dilarang keras mengakses Web Admin/Guru (`HTTP 403 Forbidden`).

