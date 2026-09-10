# MODUL: STUDENTS MANAGEMENT
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT) — TAHAP 3C

## 1. Tanggung Jawab Modul
- Mengelola data profil akademik siswa (`students`) yang terhubung 1-to-1 dengan tabel `users` dan Many-to-1 dengan `classes`.
- Hak akses baca (`GET`) dapat diakses oleh **ADMIN** dan **GURU**.
- Hak akses tulis (`POST`, `PUT`, `DELETE`) eksklusif untuk **ADMIN**.
- Siswa (**PESERTA**) dilarang keras mengelola data siswa (`HTTP 403`).
- Mendukung pembuatan akun `users` dan profil `students` sekaligus secara atomik melalui `DB::transaction()`.
- **Integritas Penghapusan**: Siswa yang telah memiliki riwayat sesi ujian (`exam_attempts`) dilarang dihapus (`HTTP 400`) demi melindungi integritas hasil ujian. Alternatif yang dianjurkan adalah menonaktifkan akun user (`is_active = false`).

## 2. Tabel Database Terkait
- `students` (`id`, `user_id`, `class_id`, `nis`, `nisn`, `gender`, `timestamps`)
- `users` (induk profil autentikasi)
- `classes` (rombongan belajar)

## 3. Spesifikasi Endpoint REST API
- `GET /api/v1/students`: List data siswa terpaginasi dengan relasi `user` dan `school_class` (Admin, Guru).
- `GET /api/v1/students/{id}`: Detail siswa (Admin, Guru).
- `POST /api/v1/students`: Penambahan data siswa baru (Admin only).
- `PUT/PATCH /api/v1/students/{id}`: Pembaruan data siswa dan profil pengguna (Admin only).
- `DELETE /api/v1/students/{id}`: Penghapusan siswa (Admin only).

---

## 4. Web UI Administration & Teacher View (Tahap 4C)
- **Controller**: `App\Http\Controllers\Web\WebStudentController`
- **Views**:
  - `resources/views/admin/students/index.blade.php` (Admin Full Management)
  - `resources/views/guru/students/index.blade.php` (Guru Read-Only Reference)
- **Route Web**:
  - `GET /admin/students`: Halaman index manajemen data peserta (search, filter kelas, create, edit, delete).
  - `POST /admin/students`: Simpan peserta dan akun login `users` baru secara atomik.
  - `PUT /admin/students/{id}`: Simpan pembaruan peserta dan status akun (`is_active`).
  - `DELETE /admin/students/{id}`: Hapus peserta (dicek agar tidak menghapus peserta yang memiliki rekaman attempt).
  - `GET /guru/students`: Halaman index peserta baca-saja untuk Guru dengan filter kelas dan pencarian.
- **Otorisasi**:
  - Administrator: Full Access (CRUD).
  - Guru: Read-Only via `/guru/students`. Akses ke `/admin/students/*` ditolak (`403 Forbidden`).
  - Siswa: Ditolak (`403 Forbidden`).

