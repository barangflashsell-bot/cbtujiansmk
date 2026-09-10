# MODUL: ACTIVITY LOGS & AUDIT TRAIL
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT) — TAHAP 3O

## 1. Tanggung Jawab Modul
- Mencatat riwayat audit aktivitas penting aplikasi CBT secara terpusat dan andal.
- Menggunakan skema tabel existing `activity_logs` (Tabel 15 pada `DATABASE_DESIGN.md`):
  - `id` (bigint unsigned, auto-increment, primary key)
  - `user_id` (bigint unsigned, foreign key ke `users.id`, nullable)
  - `action` (varchar 64, nama aksi/event)
  - `module` (varchar 32, modul target, misal: `AUTH`, `SETTINGS`, `ANTI_CHEAT`, `BACKUP`)
  - `ip_address` (varchar 45, alamat IP pengakses)
  - `details` (text, detail kontekstual payload/keterangan)
  - `created_at` (timestamp, waktu pencatatan log)
- Menyediakan endpoint bagi Administrator untuk menginspeksi log audit dengan aman:
  - **Paginasi Terbatas**: `per_page` antara 1 sampai 100 untuk mencegah kelebihan muatan memori/DOS.
  - **Filter Whitelist**: Hanya mengizinkan filter `module`, `action`, dan `user_id`. Parameter query liar diabaikan.
- Menyediakan endpoint klien ujian (Android / Desktop) untuk mencatat event integritas/anti-cheat:
  - **Actor Spoofing Protection**: `user_id` actor diambil murni dari sesi terautentikasi server (`$request->user()->id`). Input `user_id` atau `actor_id` dari client request body diabaikan.
  - **Whitelisted Actions**: `WINDOW_FOCUS_LOST`, `APP_BACKGROUNDED`, `DEVICE_LOCKED`, `SCREEN_UNPINNED`.

---

## 2. Batasan Tahap 3O (Out of Scope)
- **Tidak ada Web UI / Dashboard Viewer**: Hanya REST API foundation.
- **Tidak ada Mutasi Log Sembarangan**: Endpoint log murni `GET` untuk membaca dan `POST /event` untuk mencatat event integritas; tidak ada endpoint update/delete log publik.
- **Zero Migration**: Menggunakan tabel `activity_logs` yang telah ada di database.

---

## 3. Spesifikasi Endpoint REST API (/api/v1/activity-logs)

Semua endpoint dilindungi oleh `auth:sanctum`.

### 1. `GET /api/v1/activity-logs`
- **Fungsi**: Menampilkan daftar log audit aktivitas dengan paginasi dan filter.
- **Akses**: Khusus **Administrator** (`role:admin`). Guru dan Siswa diblokir (`HTTP 403 Forbidden`).
- **Query Parameters**:
  - `module` (opsional, string, maks 32 karakter)
  - `action` (opsional, string, maks 64 karakter)
  - `user_id` (opsional, integer, harus terdaftar di `users.id`)
  - `per_page` (opsional, integer, 1 - 100, default: 15)
  - `page` (opsional, integer, min 1)
- **Respon Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Log aktivitas sistem berhasil diambil",
    "data": {
      "items": [
        {
          "id": 1,
          "action": "UPDATE_SETTINGS",
          "module": "SETTINGS",
          "ip_address": "127.0.0.1",
          "details": "{\"updated_fields\":[\"school_name\"]}",
          "created_at": "2026-09-08T16:00:00+07:00",
          "user": {
            "id": 1,
            "name": "Administrator",
            "username": "admin"
          }
        }
      ],
      "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 1,
        "last_page": 1
      }
    }
  }
  ```

---

### 2. `POST /api/v1/activity-logs/event`
- **Fungsi**: Mencatat event integritas/anti-cheat sesi ujian dari klien peserta.
- **Akses**: Terautentikasi (`peserta`, `siswa`, dsb).
- **Request Body (JSON)**:
  ```json
  {
    "action": "WINDOW_FOCUS_LOST",
    "details": "Peserta beralih dari aplikasi ujian"
  }
  ```
  *Aksi yang didukung*: `WINDOW_FOCUS_LOST`, `APP_BACKGROUNDED`, `DEVICE_LOCKED`, `SCREEN_UNPINNED`.
- **Integritas Actor**:
  - Server otomatis mengaitkan log dengan ID user terautentikasi (`$request->user()->id`).
  - Payload manipulatif seperti `{"user_id": 999}` tidak akan pernah mempengaruhi actor identitas di database.
- **Respon Sukses (201 Created)**:
  ```json
  {
    "success": true,
    "message": "Event integritas berhasil dicatat",
    "data": {
      "id": 2,
      "action": "WINDOW_FOCUS_LOST",
      "module": "ANTI_CHEAT",
      "actor_id": 3,
      "created_at": "2026-09-08T16:05:00+07:00"
    }
  }
  ```

---

## 4. Keamanan Audit
1. **Sensitivitas Data**: Log audit tidak pernah merekam password, hash kredensial, token Sanctum, atau kunci jawaban ujian.
2. **Anti-Tampering**: Akses riwayat audit sepenuhnya read-only bagi administrator, mencegah penghapusan jejak aktivitas berbahaya dari client.

---

## 5. Web UI Activity Logs & Audit Trail (Tahap 4E)
- **Controller**: `App\Http\Controllers\Web\WebActivityLogController`
- **View**: `resources/views/admin/activity_logs/index.blade.php`
- **Routes**:
  - `GET /admin/activity-logs`: Penampil riwayat audit aktivitas terpusat terpaginasi dengan filter modul (`AUTH`, `SETTINGS`, `ANTI_CHEAT`, `BACKUP`), pengguna/aktor, dan pencarian kata kunci.
- **Karakteristik & Keamanan**:
  - Murni **Read-Only**: Tanpa tombol/rute mutasi atau penghapusan log audit.
  - Otorisasi: Khusus Administrator (`role:admin`). Guru dan Peserta diblokir (`HTTP 403 Forbidden`).
  - Proteksi Kredensial: Kolom rincian tidak menampilkan credential rahasia atau password hash.
