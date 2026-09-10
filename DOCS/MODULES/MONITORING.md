# MODUL: PROCTORING & LIVE MONITORING
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT) — TAHAP 3J

## 1. Tanggung Jawab Modul
- Mengelola pemantauan langsung jalannya ujian (*Live Proctoring & Telemetry*) di jaringan lokal (LAN/Wi-Fi).
- Menyediakan data real-time kondisi ujian untuk Pengawas/Guru dan Administrator:
  - Ringkasan ujian aktif dan distribusi status peserta (`not_started`, `in_progress`, `submitted`, `timeout`).
  - Telemetri live per peserta: sisa waktu, IP address perangkat, info device, aktivitas terakhir, dan progres pengerjaan butir soal (`answered_count` vs `total_questions`).
- **Prinsip Keamanan & Hak Akses (Role Restriction)**:
  - Khusus untuk role `admin` dan `teacher`.
  - Siswa (`student`/`peserta`) dilarang keras mengakses endpoint monitoring (`HTTP 403 Forbidden`).
  - Endpoint monitoring bersifat murni *Read-Only* dan tidak memodifikasi data jawaban, nilai, status attempt, maupun kunci jawaban.
- **Proteksi Data Sensitif (Sensitive Data & Answer Key Protection)**:
  - Respon telemetri tidak pernah membocorkan password, remember_token, atau kunci jawaban (`is_correct`).
  - Tidak ada jalur modifikasi jawaban siswa melalui endpoint monitoring.

---

## 2. Tabel Database Terkait
- `exams` (identifikasi paket ujian aktif, jadwal, durasi)
- `exam_participants` (daftar peserta terdaftar pada ujian)
- `exam_attempts` (status sesi pengerjaan, waktu mulai/selesai, sisa waktu, IP address, device info)
- `answers` (penghitungan agregat progres soal terjawab)
- `students` & `users` (profil identitas siswa, nama, NIS, rombel kelas)

---

## 3. Spesifikasi Endpoint REST API (/api/v1)

### 1. `GET /api/v1/monitoring/live`
- **Fungsi**: Menampilkan ikhtisar live seluruh ujian yang sedang aktif dan hitungan peserta secara global.
- **Akses**: `admin`, `teacher` (Sanctum Bearer Token). Siswa: `HTTP 403 Forbidden`.
- **Respon Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Data live monitoring ujian berhasil diambil",
    "data": {
      "server_time": "2026-09-08T08:50:00Z",
      "active_exams": [
        {
          "id": 1,
          "title": "PAS Ganjil Matematika Kelas 9",
          "subject": { "id": 2, "name": "Matematika", "code": "MAT9" },
          "duration_minutes": 90,
          "status": "active",
          "participants_count": 32,
          "in_progress_count": 28,
          "submitted_count": 3,
          "timeout_count": 0,
          "not_started_count": 1
        }
      ]
    }
  }
  ```

### 2. `GET /api/v1/monitoring/exams/{examId}`
- **Fungsi**: Menampilkan daftar peserta terdaftar dalam ujian beserta status pengerjaan live masing-masing siswa.
- **Akses**: `admin`, `teacher` (Sanctum Bearer Token).
- **Query Params**:
  - `class_id` (opsional): memfilter berdasarkan ID kelas.
  - `status` (opsional): memfilter berdasarkan status attempt (`not_started`, `in_progress`, `submitted`, `timeout`).
  - `per_page` (opsional, default 15).
- **Respon Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Data live monitoring peserta ujian berhasil diambil",
    "data": {
      "exam": {
        "id": 1,
        "title": "PAS Ganjil Matematika Kelas 9",
        "total_questions": 40,
        "duration_minutes": 90
      },
      "summary": {
        "total_participants": 32,
        "in_progress_count": 28,
        "submitted_count": 3,
        "timeout_count": 0,
        "not_started_count": 1
      },
      "items": [
        {
          "participant_id": 105,
          "allow_retest": false,
          "student": {
            "id": 5,
            "nis": "20240901",
            "name": "Ahmad Fauzi",
            "class": { "id": 1, "name": "9A", "level": "9" }
          },
          "attempt": {
            "id": 849,
            "status": "in_progress",
            "started_at": "2026-09-08T08:05:00Z",
            "remaining_seconds": 4500,
            "last_activity_at": "2026-09-08T08:20:15Z",
            "ip_address": "192.168.1.55",
            "device_info": "Android Client 14 / SM-A525F",
            "answered_count": 15,
            "total_questions": 40,
            "progress_percentage": 37.5
          }
        }
      ],
      "pagination": { ... }
    }
  }
  ```

### 3. `GET /api/v1/monitoring/attempts/{attemptId}`
- **Fungsi**: Menampilkan detail live telemetri teknis sesi pengerjaan siswa tunggal.
- **Akses**: `admin`, `teacher` (Sanctum Bearer Token).
- **Respon Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Detail live telemetry sesi ujian berhasil diambil",
    "data": {
      "id": 849,
      "status": "in_progress",
      "started_at": "2026-09-08T08:05:00Z",
      "remaining_seconds": 4500,
      "ip_address": "192.168.1.55",
      "device_info": "Android Client 14 / SM-A525F",
      "exam": { ... },
      "student": { ... },
      "progress": {
        "total_questions": 40,
        "answered_count": 15,
        "unanswered_count": 25,
        "progress_percentage": 37.5
      }
    }
  }
  ```

---

## 4. Direct Dependencies
- Modul `EXAMS`
- Modul `ATTEMPTS`
- Modul `STUDENTS`
- Modul `ANSWERS`

---

## 5. Web UI Live Monitoring (Tahap 4D)
- **Controller**: `App\Http\Controllers\Web\WebMonitoringController`
- **Views**:
  - Admin: `resources/views/admin/monitoring/index.blade.php`, `show.blade.php`
  - Guru: `resources/views/guru/monitoring/index.blade.php`, `show.blade.php`
- **Route Web**:
  - `GET /admin/monitoring` & `GET /guru/monitoring`: Tinjauan live seluruh ujian aktif beserta ringkasan status pengerjaan siswa.
  - `GET /admin/monitoring/exams/{id}` & `GET /guru/monitoring/exams/{id}`: Telemetri detail per siswa dalam ujian (status attempt, sisa waktu hitung server, progres soal terjawab, IP address, device info, aktivitas terakhir).
- **Prinsip Server Authority**:
  - Waktu pengerjaan dan sisa waktu dihitung secara authoritative oleh server berdasarkan `ends_at` dan waktu server saat ini.
  - Tidak mengandalkan timer client sebagai sumber kebenaran.
- **Otorisasi**:
  - Admin: Dapat memantau seluruh paket ujian sekolah.
  - Guru: Hanya dapat memantau paket ujian yang dibuatnya sendiri.
  - Siswa: Dilarang keras mengakses (`HTTP 403 Forbidden`).

