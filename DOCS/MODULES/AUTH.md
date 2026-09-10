# MODUL: AUTHENTICATION & AUTHORIZATION FOUNDATION
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT) — TAHAP 3B

## 1. Tanggung Jawab Modul
- Mengelola autentikasi berbasis Bearer Token menggunakan **Laravel Sanctum** (v4.3.3).
- Autentikasi terpadu untuk peran: **ADMIN**, **GURU**, dan **PESERTA** (Android Client APK & Web Client).
- Hashing password mutlak menggunakan Bcrypt bawaan Laravel (`Hash::make()` / `Hash::check()`) — DILARANG plaintext, MD5, atau SHA1.
- Menerbitkan, memvalidasi, dan merevoke Bearer token saat sesi login/logout.
- Server-authoritative role authorization via middleware `role:...` (menolak role claim sepihak dari client).
- Proteksi brute-force login menggunakan rate limiting native Laravel (`throttle:10,1`).

## 2. Tabel Database Terkait
- `users` (akun autentikasi, status aktif)
- `roles` (peran: `admin`, `teacher`, `student`)
- `personal_access_tokens` (tabel resmi Laravel Sanctum untuk token Bearer)

## 3. Spesifikasi Endpoint REST API
- `POST /api/v1/auth/login`:
  - Publik dengan rate limit (10 request/menit).
  - Body: `{ "username": "...", "password": "..." }`.
  - Return: Token Sanctum Bearer dan metadata profil pengguna (tanpa password/hash).
- `GET /api/v1/auth/me`:
  - Memerlukan header `Authorization: Bearer <token>`.
  - Mengembalikan informasi identitas dan role pengguna terautentikasi.
- `POST /api/v1/auth/logout`:
  - Memerlukan header `Authorization: Bearer <token>`.
  - Merevoke token akses yang sedang aktif digunakan.

## 4. Role Authorization & HTTP Status Convention
- **HTTP 401 Unauthorized**:
  - Kredensial username/password salah saat login.
  - Request ke endpoint protected tanpa Bearer token atau menggunakan token yang sudah direvoke/tidak valid.
- **HTTP 403 Forbidden**:
  - Pengguna terautentikasi tetapi rolenya tidak memenuhi syarat akses middleware (contoh: Siswa mencoba mengakses endpoint Guru/Admin).
  - Akun pengguna berstatus nonaktif (`is_active = 0`).

## 5. Security Rules
- Token hanya diberikan setelah login sukses dan ditransmisikan via Bearer Header.
- Token tidak dicatat ke log aplikasi atau di-expose di endpoint manapun selain login.
- Hash password, `APP_KEY`, dan environment internals tidak pernah diikutsertakan dalam respons API.
- Akun development yang di-seed (`admin`, `guru`, `peserta`) hanya digunakan untuk pengujian lokal terisolasi.

---

## 6. Web Authentication & Dashboard Foundation (Tahap 4A)
- **Web Session Authentication**: Menggunakan cookie session bawaan Laravel (`Auth::attempt`, session regeneration, CSRF protection via `@csrf`).
- **Web Endpoints**:
  - `GET /login`: Menampilkan form login terpadu Web Admin & Guru.
  - `POST /login`: Memproses autentikasi web, meregenerasi session, dan me-redirect pengguna sesuai role (`/admin/dashboard` untuk Admin, `/guru/dashboard` untuk Guru). Peserta/Siswa diblokir dari login web.
  - `POST /logout`: Menghancurkan session, meregenerasi CSRF token, dan me-redirect ke `/login`.
  - `GET /admin/dashboard`: Dashboard shell khusus Admin (`role:admin`).
  - `GET /guru/dashboard`: Dashboard shell khusus Guru (`role:teacher,guru`).
- **Local Asset Strategy**:
  - Murni berkas lokal pada `public/css/cbt-offline.css` dan `public/js/cbt-offline.js`.
  - Zero CDN (tanpa Google Fonts, Bootstrap/Tailwind CDN, atau icon online) untuk menjamin 100% ketersediaan di jaringan LAN sekolah yang terisolasi tanpa internet.
- **Base Layout**: `resources/views/layouts/app.blade.php` dengan navigasi sidebar adaptif per role, mobile responsive toggle, dan flash alert handler. Menu yang belum diimplementasikan ditandai secara visual sebagai placeholder non-aktif (zero fake routes).

