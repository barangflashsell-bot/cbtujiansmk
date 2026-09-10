# MODUL: BACKUP & DATABASE SNAPSHOT
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT) — TAHAP 3L

## 1. Tanggung Jawab Modul
- Mengelola pembuatan, penyimpanan, pendaftaran, dan pengunduhan berkas cadangan database MySQL lokal (**CBT Database Backup Engine**).
- Memastikan integritas data dan keamanan aplikasi CBT lokal Windows:
  - **Read-Only**: Operasi backup bersifat murni ekstraksi data (`SHOW FULL TABLES`, `SHOW CREATE TABLE`, `SELECT`), tidak melakukan perubahan skema atau mutasi data.
  - **Zero Command Injection**: Proses dump database dieksekusi murni melalui query PDO internal tanpa memanggil shell/command eksternal (`exec`, `system`, dsb).
  - **Path Traversal Protection**: Akses berkas dibatasi ketat pada direktori aman `storage/app/backups/`. Parameter nama berkas divalidasi dengan pola regex ketat (`^[a-zA-Z0-9_\-]+\.sql$`).
  - **Kerahasiaan Kredensial**: Tidak ada kredensial database (username, password, host) atau secret `.env` yang dibocorkan dalam response API maupun header berkas cadangan.
- Memberikan dukungan snapshot sesuai skenario operasional ujian:
  - `manual`: Cadangan manual rutin oleh administrator.
  - `pre_exam`: Cadangan data master sebelum pelaksanaan ujian.
  - `post_exam`: Cadangan hasil nilai dan jawaban setelah ujian selesai.

---

## 2. Batasan Tahap 3L (Out of Scope)
- **Tidak ada Web UI / Dashboard Admin**: Hanya mencakup Foundation REST API.
- **Tidak ada Flutter / Android / APK**: Klien Android tidak memiliki akses ke fitur ini.
- **Tidak ada Scheduler Otomatis**: Pencadangan dipicu sesuai kebutuhan melalui API.
- **Tidak ada Cloud Backup / Upload Internet**: Seluruh berkas disimpan pada storage lokal server Windows.
- **Tidak ada Fitur Restore Database**: Pemulihan database berada di luar ruang lingkup Tahap 3L.

---

## 3. Spesifikasi Endpoint REST API (/api/v1/backups)

Semua endpoint dilindungi oleh autentikasi Sanctum dan otorisasi ketat khusus **Administrator** (`role:admin`).  
Siswa dan Guru ditolak dengan respons `HTTP 403 Forbidden`.

### 1. `GET /api/v1/backups`
- **Fungsi**: Menampilkan daftar seluruh berkas snapshot cadangan database yang tersimpan di storage lokal.
- **Akses**: `admin` only.
- **Respon Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Daftar berkas cadangan database berhasil diambil",
    "data": {
      "total_backups": 1,
      "items": [
        {
          "filename": "cbt_backup_manual_20260908_153000_a1b2c3d4.sql",
          "size_bytes": 45120,
          "size_human": "44.06 KB",
          "created_at": "2026-09-08T15:30:00+07:00",
          "checksum_sha256": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"
        }
      ]
    }
  }
  ```

---

### 2. `POST /api/v1/backups`
- **Fungsi**: Memicu pembuatan berkas cadangan database MySQL baru.
- **Akses**: `admin` only.
- **Request Body (JSON)**:
  ```json
  {
    "type": "manual"
  }
  ```
  *Pilihan type*: `manual`, `pre_exam`, `post_exam` (default: `manual`).
- **Respon Sukses (201 Created)**:
  ```json
  {
    "success": true,
    "message": "Cadangan database berhasil dibuat",
    "data": {
      "filename": "cbt_backup_manual_20260908_153000_a1b2c3d4.sql",
      "type": "manual",
      "size_bytes": 45120,
      "size_human": "44.06 KB",
      "tables_count": 18,
      "total_records": 150,
      "checksum_sha256": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
      "created_at": "2026-09-08T15:30:00+07:00"
    }
  }
  ```

---

### 3. `GET /api/v1/backups/{filename}/download`
- **Fungsi**: Mengunduh berkas snapshot database `.sql` secara aman.
- **Akses**: `admin` only.
- **Parameter URL**:
  - `filename`: Nama berkas backup (harus berekstensi `.sql` dan sesuai regex `^[a-zA-Z0-9_\-]+\.sql$`).
- **Respon Sukses (200 OK)**:
  - Header: `Content-Type: application/sql`, `Content-Disposition: attachment; filename="cbt_backup_..."`
  - Body: Berkas biner `.sql`.
- **Error**:
  - `400 Bad Request`: Jika nama berkas mengandung karakter terlarang / percobaan path traversal (`..`, `/`, `\`).
  - `404 Not Found`: Jika berkas cadangan tidak ditemukan di storage lokal.

---

### 4. `DELETE /api/v1/backups/{filename}`
- **Fungsi**: Menghapus berkas cadangan database tertentu dari storage server.
- **Akses**: `admin` only.
- **Respon Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Berkas cadangan berhasil dihapus",
    "data": {
      "filename": "cbt_backup_manual_20260908_153000_a1b2c3d4.sql"
    }
  }
  ```

---

## 4. Security & Safety Rules
1. **Role Restriction**: Hanya `admin` yang berhak membuat, melihat, mengunduh, atau menghapus cadangan database. Role lain (`teacher`, `student`) diblokir dengan `HTTP 403 Forbidden`.
2. **Path Traversal Protection**:
   - Client dilarang menentukan path direktori penyimpanan.
   - Nama berkas divalidasi dengan regex whitelist.
   - Pengecekan `realpath` memastikan target berkas berada di dalam `storage/app/backups`.
3. **No Shell Command Injection**:
   - Pembuatan dump menggunakan PDO driver native Laravel, bukan shell command `mysqldump` yang rentan injection.
4. **Data Protection**:
   - Parameter response tidak memuat credential database.

---

## 5. Test Coverage
- **File Test**: `SERVER/tests/Feature/BackupTest.php` & `SERVER/tests/Feature/SystemBackupSettingsWebTest.php`
- **Metode Pengujian**:
  1. `test_unauthenticated_requests_return_401`
  2. `test_unauthorized_student_and_teacher_roles_return_403`
  3. `test_admin_can_create_backup_with_valid_response_format`
  4. `test_backup_response_does_not_leak_database_credentials_or_secrets`
  5. `test_admin_can_list_download_and_delete_backup`
  6. `test_path_traversal_attempts_are_blocked`
  7. `test_command_injection_payload_in_type_is_rejected`
  8. `test_nonexistent_backup_file_returns_404`
  9. `test_database_remains_consistent_after_backup`
  10. `test_04_admin_backup_lifecycle` (Web UI index, create, download, delete)

---

## 6. Web UI Backup System (Tahap 4E)
- **Controller**: `App\Http\Controllers\Web\WebBackupController`
- **View**: `resources/views/admin/backups/index.blade.php`
- **Routes**:
  - `GET /admin/backups`: Tinjauan berkas snapshot database, statistik jumlah berkas, dan kapasitas storage.
  - `POST /admin/backups`: Trigger pembuatan snapshot SQL baru (`manual`, `pre_exam`, `post_exam`).
  - `GET /admin/backups/{filename}/download`: Unduh berkas `.sql` snapshot aman.
  - `DELETE /admin/backups/{filename}`: Hapus berkas snapshot dari server.
- **Keamanan & Otorisasi**:
  - Khusus Administrator (`role:admin`). Guru dan Peserta diblokir (`HTTP 403 Forbidden`).
  - Zero Command Injection: Menggunakan export query PDO murni internal tanpa `exec` atau `mysqldump`.
  - Path Traversal Guard: Regex `^[a-zA-Z0-9_\-]+\.sql$` dan verifikasi `realpath`.
  - Audit Trail: Seluruh aksi pembuatan, pengunduhan, dan penghapusan snapshot dicatat ke `activity_logs`.
