# MODUL: SUBJECTS MANAGEMENT
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT) — TAHAP 3D

## 1. Tanggung Jawab Modul
- Mengelola data master mata pelajaran sekolah (`subjects`).
- Hak akses baca (`GET`) dapat diakses oleh **ADMIN** dan **GURU**.
- Hak akses tulis (`POST`, `PUT`, `DELETE`) eksklusif untuk **ADMIN**.
- Siswa (**PESERTA**) dilarang keras mengelola data mata pelajaran (`HTTP 403`).
- **Integritas Relasi**: Menjadi entitas induk bagi butir soal (`questions`) dan paket ujian (`exams`).
- **Integritas Penghapusan**: Mata pelajaran yang terhubung dengan butir soal di bank soal atau paket ujian dilarang dihapus (`HTTP 400`) demi melindungi integritas bank soal dan rekaman ujian.

## 2. Tabel Database Terkait
- `subjects` (`id`, `code`, `name`, `status`, `timestamps`)
- `questions` (anak relasi)
- `exams` (anak relasi)

## 3. Spesifikasi Endpoint REST API
- `GET /api/v1/subjects`: List data mata pelajaran terpaginasi dengan metadata `questions_count` & `exams_count` (Admin, Guru).
- `GET /api/v1/subjects/{id}`: Detail mata pelajaran dengan metadata `questions_count` & `exams_count` (Admin, Guru).
- `POST /api/v1/subjects`: Penambahan mata pelajaran baru (Admin only).
- `PUT/PATCH /api/v1/subjects/{id}`: Pembaruan data mata pelajaran (Admin only).
- `DELETE /api/v1/subjects/{id}`: Penghapusan mata pelajaran (Admin only, ditolak jika terikat bank soal/ujian).

## 4. Validasi Data
- `code`: Required, string, maksimal 32 karakter, unique pada tabel `subjects`.
- `name`: Required, string, maksimal 128 karakter.
- `status`: Nullable string (default: 'active').

---

## 5. Web UI Administration (Tahap 4C)
- **Controller**: `App\Http\Controllers\Web\WebSubjectController`
- **Views**: `resources/views/admin/subjects/index.blade.php`
- **Route Web**:
  - `GET /admin/subjects`: Halaman index tabel data mata pelajaran terpaginasi dengan search dan collapsible create/edit form.
  - `POST /admin/subjects`: Simpan penambahan mata pelajaran baru.
  - `PUT /admin/subjects/{id}`: Simpan pembaruan mata pelajaran.
  - `DELETE /admin/subjects/{id}`: Hapus mata pelajaran (ditolak jika memiliki soal atau ujian terhubung).
- **Otorisasi**:
  - Administrator: Full Access (CRUD).
  - Guru / Siswa: Ditolak (`403 Forbidden`).

