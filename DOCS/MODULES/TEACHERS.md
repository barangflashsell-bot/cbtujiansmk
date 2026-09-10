# MODUL: TEACHERS MANAGEMENT
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT) — TAHAP 3C

## 1. Tanggung Jawab Modul
- Mengelola data profil pengajar / guru (`teachers`) yang terhubung 1-to-1 dengan tabel `users`.
- Hak akses baca (`GET`) dapat diakses oleh **ADMIN** dan **GURU**.
- Hak akses tulis (`POST`, `PUT`, `DELETE`) eksklusif untuk **ADMIN**.
- Siswa (**PESERTA**) dilarang keras mengelola data guru (`HTTP 403`).
- Mendukung pembuatan akun `users` dan profil `teachers` sekaligus secara atomik melalui `DB::transaction()`.
- **Integritas Penghapusan**: Guru yang telah memiliki riwayat butir pertanyaan di bank soal (`questions`) dilarang dihapus (`HTTP 400`) demi melindungi integritas butir soal.

## 2. Tabel Database Terkait
- `teachers` (`id`, `user_id`, `nip`, `phone`, `timestamps`)
- `users` (induk profil autentikasi)

## 3. Spesifikasi Endpoint REST API
- `GET /api/v1/teachers`: List data pengajar terpaginasi dengan relasi `user` (Admin, Guru).
- `GET /api/v1/teachers/{id}`: Detail pengajar (Admin, Guru).
- `POST /api/v1/teachers`: Penambahan data guru baru (Admin only).
- `PUT/PATCH /api/v1/teachers/{id}`: Pembaruan data guru dan profil pengguna (Admin only).
- `DELETE /api/v1/teachers/{id}`: Penghapusan guru (Admin only).

---

## 4. Web UI Administration (Tahap 4C)
- **Controller**: `App\Http\Controllers\Web\WebTeacherController`
- **Views**: `resources/views/admin/teachers/index.blade.php`
- **Route Web**:
  - `GET /admin/teachers`: Halaman index tabel data guru terpaginasi dengan form pencarian dan penambahan/edit guru secara terintegrasi dengan akun `users`.
  - `POST /admin/teachers`: Simpan penambahan guru dan akun login `users` secara atomik (`DB::transaction`).
  - `PUT /admin/teachers/{id}`: Simpan pembaruan profil guru, status akun (`is_active`), dan kata sandi opsional.
  - `DELETE /admin/teachers/{id}`: Hapus profil guru dan akun user terkait.
- **Otorisasi**:
  - Administrator: Full Access (CRUD).
  - Guru / Siswa: Ditolak (`403 Forbidden`).

