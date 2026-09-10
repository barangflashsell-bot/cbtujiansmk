# MODUL: SETTINGS (PENGATURAN SISTEM)
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT) — TAHAP 3O

## 1. Tanggung Jawab Modul
- Menyediakan API terpusat untuk konfigurasi operasional server CBT lokal Windows.
- Menyimpan dan memuat konfigurasi server menggunakan mekanisme penyimpanan aman:
  - **Storage**: Berkas JSON lokal `storage/app/settings.json` dengan fallback terdefinisi (`defaultSettings`).
  - **Zero Migration**: Tanpa membuat tabel database baru atau mengubah skema database existing.
  - **Zero Package Dependency**: Menggunakan fasilitas bawaan Laravel (`File`, `Validator`).
- Menegakkan keamanan administratif:
  - **Otorisasi Ketat**: Hanya Administrator (`role:admin`) yang dapat memperbarui pengaturan. Siswa/Peserta diblokir total (`HTTP 403 Forbidden`).
  - **Whitelist Validation**: Hanya kunci konfigurasi terdaftar yang diizinkan (`school_name`, `school_address`, `academic_year`, `app_name`, `server_port`, `auto_token_release`, `token_refresh_minutes`, `allow_student_review`, `logo_url`). Kunci asing/arbitrer ditolak dengan `HTTP 422 Unprocessable Entity`.
  - **No Credential/Secret Leakage**: Tidak mengekspos kredensial database, JWT secret, app key, atau berkas `.env`.
  - **Audit Trail**: Setiap pembaruan pengaturan secara otomatis dicatat ke dalam tabel `activity_logs`.

---

## 2. Batasan Tahap 3O (Out of Scope)
- **Tidak ada Web Admin UI / Frontend**: Hanya REST API foundation.
- **Tidak ada Flutter / Android UI / APK**: Klien Android tidak mengelola settings.
- **Tidak ada Dynamic Table Altering**: Konfigurasi tidak mengubah skema database runtime.

---

## 3. Spesifikasi Endpoint REST API (/api/v1/settings)

Semua endpoint dilindungi oleh `auth:sanctum`.

### 1. `GET /api/v1/settings`
- **Fungsi**: Mengambil seluruh pengaturan konfigurasi aktif server CBT.
- **Akses**: Terotorisasi (`admin`, `guru`). Peserta/Siswa ditolak (`HTTP 403`).
- **Respon Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Pengaturan sistem berhasil diambil",
    "data": {
      "school_name": "CBT School",
      "school_address": "Jl. Pendidikan No. 1",
      "academic_year": "2025/2026",
      "app_name": "CBT Local Server",
      "server_port": 8000,
      "auto_token_release": false,
      "token_refresh_minutes": 15,
      "allow_student_review": false,
      "logo_url": null
    }
  }
  ```

---

### 2. `PUT /api/v1/settings` / `POST /api/v1/settings`
- **Fungsi**: Memperbarui satu atau beberapa nilai konfigurasi sistem (partial/full update).
- **Akses**: Khusus **Administrator** (`role:admin`).
- **Request Body (JSON)**:
  ```json
  {
    "school_name": "SMK Negeri 1 Surabaya",
    "school_address": "Jl. Ahmad Yani No. 10",
    "academic_year": "2025/2026",
    "server_port": 8080,
    "auto_token_release": true,
    "token_refresh_minutes": 30,
    "allow_student_review": true
  }
  ```
- **Aturan Validasi**:
  - `school_name`: required|string|max:255
  - `school_address`: nullable|string|max:500
  - `academic_year`: required|string|max:32
  - `app_name`: required|string|max:100
  - `server_port`: required|integer|between:80,65535
  - `auto_token_release`: required|boolean
  - `token_refresh_minutes`: required|integer|between:5,180
  - `allow_student_review`: required|boolean
  - `logo_url`: nullable|string|max:255
  - Kunci di luar daftar di atas akan ditolak (`422 Unprocessable Entity`).
- **Respon Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Pengaturan sistem berhasil diperbarui",
    "data": {
      "school_name": "SMK Negeri 1 Surabaya",
      "school_address": "Jl. Ahmad Yani No. 10",
      "academic_year": "2025/2026",
      "app_name": "CBT Local Server",
      "server_port": 8080,
      "auto_token_release": true,
      "token_refresh_minutes": 30,
      "allow_student_review": true,
      "logo_url": null
    }
  }
  ```
- **Respon Error (422 Unprocessable Entity - Unknown Key)**:
  ```json
  {
    "success": false,
    "message": "Terdapat kunci pengaturan yang tidak diizinkan atau tidak dikenal",
    "data": {
      "unknown_keys": ["malicious_key"]
    }
  }
  ```

---

## 4. Keamanan & Proteksi Data
1. **No Sensitive Leak**: Parameter database, host, password pengguna, token API, dan environment variable tidak pernah disimpan di `settings.json` ataupun diekspos melalui API.
2. **Actor Integrity**: Setiap modifikasi diverifikasi dan memicu pencatatan audit log server dengan ID actor terautentikasi.

---

## 5. Web UI Settings System (Tahap 4E)
- **Controller**: `App\Http\Controllers\Web\WebSettingController`
- **View**: `resources/views/admin/settings/index.blade.php`
- **Routes**:
  - `GET /admin/settings`: Form konfigurasi operasional server lokal CBT (Identitas Sekolah, Port Jaringan, Token & Peninjauan Siswa).
  - `PUT /admin/settings`: Simpan pembaruan pengaturan dengan validasi server-side.
- **Validasi Whitelist**:
  - `school_name`, `school_address`, `academic_year`, `app_name`, `server_port` (80-65535), `auto_token_release`, `token_refresh_minutes` (5-180), `allow_student_review`, `logo_url`.
- **Otorisasi**:
  - Khusus Administrator (`role:admin`). Guru dan Peserta diblokir (`HTTP 403 Forbidden`).
  - Audit Trail: Pembaruan pengaturan otomatis tercatat di `activity_logs` dengan modul `SETTINGS` dan rincian field yang diubah.
