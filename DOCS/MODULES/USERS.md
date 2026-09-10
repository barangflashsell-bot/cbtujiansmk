# MODUL: USERS MANAGEMENT
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT) — TAHAP 3C

## 1. Tanggung Jawab Modul
- Mengelola data akun otentikasi login pengguna CBT (`users`).
- Pengelolaan penuh (CRUD) hanya dapat diakses oleh role **ADMIN**.
- Hashing password otomatis menggunakan algoritma Bcrypt bawaan Laravel saat create atau update.
- Mencegah kebocoran data sensitif (password, password_hash, remember_token) pada seluruh respons API.
- Proteksi akun aktif sendiri agar tidak dapat dihapus oleh dirinya sendiri saat sedang login.

## 2. Tabel Database Terkait
- `users` (`id`, `role_id`, `username`, `name`, `email`, `password`, `is_active`, `timestamps`)
- `roles` (`id`, `name`, `display_name`)

## 3. Spesifikasi Endpoint REST API
- `GET /api/v1/users`: List data pengguna terpaginasi (Admin only). Query: `page`, `per_page` (max 100).
- `GET /api/v1/users/{id}`: Detail satu pengguna (Admin only).
- `POST /api/v1/users`: Pembuatan pengguna baru (Admin only).
- `PUT/PATCH /api/v1/users/{id}`: Pembaruan data pengguna (Admin only).
- `DELETE /api/v1/users/{id}`: Penghapusan pengguna (Admin only).

## 4. Validasi Data
- `username`: Required, string, max 64 karakter, unique.
- `name`: Required, string, max 128 karakter.
- `email`: Nullable, email valid, unique.
- `password`: Required saat create (min 6 karakter), nullable saat update.
- `role_id`: Required, foreign key valid pada tabel `roles`.
- `is_active`: Nullable boolean (default: true).
