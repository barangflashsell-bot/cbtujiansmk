# MODUL: CLASSES MANAGEMENT
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT) — TAHAP 3D

## 1. Tanggung Jawab Modul
- Mengelola data master rombongan belajar / ruang kelas (`classes`).
- Hak akses baca (`GET`) dapat diakses oleh **ADMIN** dan **GURU**.
- Hak akses tulis (`POST`, `PUT`, `DELETE`) eksklusif untuk **ADMIN**.
- Siswa (**PESERTA**) dilarang keras mengelola data kelas (`HTTP 403`).
- **Integritas Relasi**: Menghubungkan kelas dengan siswa melalui relasi `Class` → `Students` (`class_id` foreign key).
- **Integritas Penghapusan**: Kelas yang masih memiliki siswa terdaftar (`students`) dilarang dihapus (`HTTP 400`) demi melindungi integritas data akademik siswa.

## 2. Tabel Database Terkait
- `classes` (`id`, `name`, `level`, `academic_year`, `status`, `timestamps`)
- `students` (anak relasi)

## 3. Spesifikasi Endpoint REST API
- `GET /api/v1/classes`: List data kelas terpaginasi dengan metadata `students_count` (Admin, Guru).
- `GET /api/v1/classes/{id}`: Detail satu kelas dengan metadata `students_count` (Admin, Guru).
- `POST /api/v1/classes`: Penambahan kelas baru (Admin only).
- `PUT/PATCH /api/v1/classes/{id}`: Pembaruan data kelas (Admin only).
- `DELETE /api/v1/classes/{id}`: Penghapusan kelas (Admin only, ditolak jika memiliki siswa terdaftar).

## 4. Validasi Data
- `name`: Required, string, maksimal 64 karakter.
- `level`: Required, string, maksimal 16 karakter (contoh: '7', '8', '9').
- `academic_year`: Required, string, maksimal 16 karakter (contoh: '2026/2027').
- `status`: Nullable string (default: 'active').

---

## 5. Web UI Administration (Tahap 4C)
- **Controller**: `App\Http\Controllers\Web\WebClassController`
- **Views**: `resources/views/admin/classes/index.blade.php`
- **Route Web**:
  - `GET /admin/classes`: Halaman index tabel data kelas terpaginasi dengan search dan collapsible create/edit form.
  - `POST /admin/classes`: Simpan penambahan data kelas baru.
  - `PUT /admin/classes/{id}`: Simpan pembaruan kelas.
  - `DELETE /admin/classes/{id}`: Hapus data kelas (ditolak jika memiliki siswa terdaftar).
- **Otorisasi**:
  - Administrator: Full Access (CRUD).
  - Guru / Siswa: Ditolak (`403 Forbidden`).

