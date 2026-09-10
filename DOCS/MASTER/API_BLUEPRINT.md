# REST API BLUEPRINT SISTEM CBT
TAHAP 1 — BLUEPRINT ENDPOINT SPESIFIKASI (POIN 15)

> [!NOTE]
> Sesuai **Aturan AI_RULES.md (Tahap 1)**: Dokumen ini murni spesifikasi kontrak REST API. **DILARANG** melakukan coding endpoint atau membuat controller pada tahap ini.

## 0. REST API FOUNDATION & CONVENTIONS (TAHAP 3A)

- **Base Path**: `/api/v1`
- **Health Check**: `GET /api/v1/health`
- **Standard Response Format**:
  - **Success Response (200, 201)**:
    ```json
    {
      "success": true,
      "message": "Deskripsi pesan keberhasilan",
      "data": {}
    }
    ```
  - **Error Response (400, 401, 403, 404, 422, 500)**:
    ```json
    {
      "success": false,
      "message": "Deskripsi error",
      "errors": null
    }
    ```
- **HTTP Error Conventions**:
  - `200 OK`: Request berhasil diproses.
  - `400 Bad Request`: Parameter atau payload tidak sesuai.
  - `401 Unauthorized`: Token tidak valid, kedaluwarsa, atau kredensial salah.
  - `403 Forbidden`: Pengguna tidak memiliki izin hak akses untuk resource ini.
  - `404 Not Found`: Endpoint atau entitas resource tidak ditemukan.
  - `422 Unprocessable Content`: Validasi request gagal.
  - `500 Internal Server Error`: Kegagalan tak terduga pada server.

---

## 1. DAFTAR ENDPOINT UTAMA

### 1. `POST /api/v1/auth/login`
- **Fungsi**: Otentikasi pengguna (Admin, Guru, Siswa) dan penerbitan secure token sesi Bearer (Laravel Sanctum).
- **Role**: Publik (Semua Pengguna Terdaftar), diproteksi rate limiter (10 request/menit).
- **Authentication**: None (Publik).
- **Request Body**:
  ```json
  {
    "username": "20240901",
    "password": "PasswordSiswa123"
  }
  ```
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Login berhasil",
    "data": {
      "user": {
        "id": 105,
        "username": "20240901",
        "name": "Ahmad Fauzi",
        "role": "student"
      },
      "token": "1|...",
      "token_type": "Bearer"
    }
  }
  ```
- **Kemungkinan Error**:
  - `401 Unauthorized`: Kredensial username atau password salah.
  - `403 Forbidden`: Akun dinonaktifkan atau login diblokir oleh admin.
  - `422 Unprocessable Content`: Field username atau password kosong.
  - `429 Too Many Requests`: Melebihi batas percobaan login.

### 2. `GET /api/v1/auth/me`
- **Fungsi**: Mengambil profil pengguna yang sedang login berdasarkan token aktif.
- **Role**: Pengguna terautentikasi (Admin, Guru, Siswa).
- **Authentication**: Bearer Token (`auth:sanctum`).
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Data pengguna berhasil diambil",
    "data": {
      "user": {
        "id": 105,
        "username": "20240901",
        "name": "Ahmad Fauzi",
        "role": "student"
      }
    }
  }
  ```

### 3. `POST /api/v1/auth/logout`
- **Fungsi**: Merevoke token sesi aktif pengguna saat ini.
- **Role**: Pengguna terautentikasi (Admin, Guru, Siswa).
- **Authentication**: Bearer Token (`auth:sanctum`).
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Logout berhasil",
    "data": null
  }
  ```

---

### 4. `Users Management` (`/api/v1/users`)
- `GET /api/v1/users`: List data akun pengguna terpaginasi (Role: `admin`).
- `GET /api/v1/users/{id}`: Detail akun pengguna (Role: `admin`).
- `POST /api/v1/users`: Tambah akun pengguna baru (Role: `admin`).
- `PUT/PATCH /api/v1/users/{id}`: Update akun pengguna (Role: `admin`).
- `DELETE /api/v1/users/{id}`: Hapus akun pengguna (Role: `admin`).

### 5. `Students Management` (`/api/v1/students`)
- `GET /api/v1/students`: List data siswa dengan relasi user & kelas (Role: `admin`, `teacher`).
- `GET /api/v1/students/{id}`: Detail profil siswa (Role: `admin`, `teacher`).
- `POST /api/v1/students`: Pendaftaran siswa baru + akun user (Role: `admin`).
- `PUT/PATCH /api/v1/students/{id}`: Update profil siswa & user (Role: `admin`).
- `DELETE /api/v1/students/{id}`: Hapus siswa (Role: `admin`, ditolak jika sudah ada riwayat ujian).

### 6. `Teachers Management` (`/api/v1/teachers`)
- `GET /api/v1/teachers`: List data guru dengan relasi user (Role: `admin`, `teacher`).
- `GET /api/v1/teachers/{id}`: Detail profil guru (Role: `admin`, `teacher`).
- `POST /api/v1/teachers`: Pendaftaran guru baru + akun user (Role: `admin`).
- `PUT/PATCH /api/v1/teachers/{id}`: Update data guru & user (Role: `admin`).
- `DELETE /api/v1/teachers/{id}`: Hapus guru (Role: `admin`, ditolak jika terhubung bank soal).

### 7. `Classes Management` (`/api/v1/classes`)
- `GET /api/v1/classes`: List data kelas terpaginasi beserta jumlah siswa (`students_count`) (Role: `admin`, `teacher`).
- `GET /api/v1/classes/{id}`: Detail kelas beserta relasi daftar siswa (Role: `admin`, `teacher`).
- `POST /api/v1/classes`: Tambah kelas baru (Role: `admin`).
- `PUT/PATCH /api/v1/classes/{id}`: Update nama kelas / grade level (Role: `admin`).
- `DELETE /api/v1/classes/{id}`: Hapus kelas (Role: `admin`, ditolak jika masih terdapat siswa terdaftar).

### 8. `Subjects Management` (`/api/v1/subjects`)
- `GET /api/v1/subjects`: List data mata pelajaran terpaginasi beserta jumlah bank soal & ujian (Role: `admin`, `teacher`).
- `GET /api/v1/subjects/{id}`: Detail mata pelajaran (Role: `admin`, `teacher`).
- `POST /api/v1/subjects`: Tambah mata pelajaran baru dengan kode unik (Role: `admin`).
- `PUT/PATCH /api/v1/subjects/{id}`: Update mata pelajaran (Role: `admin`).
- `DELETE /api/v1/subjects/{id}`: Hapus mata pelajaran (Role: `admin`, ditolak jika masih terhubung ke bank soal atau ujian).

### 9. `Questions Management` (`/api/v1/questions`)
- `GET /api/v1/questions`: List butir soal terpaginasi dengan hitungan opsi (Role: `admin`, `teacher`).
- `GET /api/v1/questions/{id}`: Detail butir soal beserta daftar pilihan jawaban (Role: `admin`, `teacher`).
- `POST /api/v1/questions`: Tambah butir soal baru (Role: `admin`, `teacher`).
- `PUT/PATCH /api/v1/questions/{id}`: Update butir soal (Role: `admin`, `teacher`).
- `DELETE /api/v1/questions/{id}`: Hapus butir soal (Role: `admin`, `teacher`, ditolak jika sudah masuk ujian atau ada jawaban siswa).

### 10. `Question Options Management` (`/api/v1/questions/{questionId}/options`)
- `GET /api/v1/questions/{questionId}/options`: List pilihan jawaban terpaginasi dari soal (Role: `admin`, `teacher`).
- `GET /api/v1/questions/{questionId}/options/{id}`: Detail satu pilihan jawaban (Role: `admin`, `teacher`).
- `POST /api/v1/questions/{questionId}/options`: Tambah opsi jawaban dan kunci `is_correct` (Role: `admin`, `teacher`).
- `PUT/PATCH /api/v1/questions/{questionId}/options/{id}`: Update opsi jawaban dan status kunci (Role: `admin`, `teacher`).
- `DELETE /api/v1/questions/{questionId}/options/{id}`: Hapus opsi jawaban (Role: `admin`, `teacher`, ditolak jika sudah dipilih dalam lembar jawaban).

> [!CAUTION]
> **Answer Key Protection**: Kunci jawaban (`is_correct`) adalah data rahasia. Seluruh endpoint Bank Soal hanya dapat diakses oleh role `admin` dan `teacher`. Role `student` (peserta) diblokir dengan `HTTP 403 Forbidden` dan tidak menerima data kunci jawaban.

### 11. `Exams Management` (`/api/v1/exams`)
- `GET /api/v1/exams`: List paket ujian terpaginasi dengan jumlah butir soal (Role: `admin`, `teacher`, `student` untuk ujian aktif).
- `GET /api/v1/exams/{id}`: Detail paket ujian beserta butir soal (Role: `admin`, `teacher`, `student` tanpa kunci jawaban).
- `POST /api/v1/exams`: Buat paket ujian baru (Role: `admin`, `teacher`).
- `PUT/PATCH /api/v1/exams/{id}`: Update jadwal, durasi, token, atau konfigurasi ujian (Role: `admin`, `teacher`).
- `DELETE /api/v1/exams/{id}`: Hapus paket ujian (Role: `admin`, `teacher`, ditolak jika sudah ada riwayat pengerjaan siswa).

### 12. `Exam Questions Management` (`/api/v1/exams/{examId}/questions`)
- `GET /api/v1/exams/{examId}/questions`: List butir soal ujian terurut (Role: `admin`, `teacher`, `student` tanpa kunci jawaban).
- `POST /api/v1/exams/{examId}/questions`: Lampirkan butir soal ke ujian dengan nomor urut & bobot (Role: `admin`, `teacher`).
- `PUT/PATCH /api/v1/exams/{examId}/questions/{questionId}`: Update urutan atau bobot soal dalam ujian (Role: `admin`, `teacher`).
- `DELETE /api/v1/exams/{examId}/questions/{questionId}`: Lepaskan butir soal dari paket ujian (Role: `admin`, `teacher`, ditolak jika ujian sudah dikerjakan).

### 13. `Exam Participants Management` (`/api/v1/exams/{examId}/participants`)
- `GET /api/v1/exams/{examId}/participants`: List peserta terdaftar pada ujian terpaginasi (Role: `admin`, `teacher`).
- `GET /api/v1/exams/{examId}/participants/{participantId}`: Detail pendaftaran peserta (Role: `admin`, `teacher`).
- `POST /api/v1/exams/{examId}/participants`: Daftarkan siswa tunggal atau rombongan belajar/kelas ke ujian (Role: `admin`, `teacher`).
- `PUT/PATCH /api/v1/exams/{examId}/participants/{participantId}`: Update izin ujian ulang (`allow_retest`) (Role: `admin`, `teacher`).
- `DELETE /api/v1/exams/{examId}/participants/{participantId}`: Batalkan pendaftaran peserta (Role: `admin`, `teacher`, ditolak jika siswa sudah ada sesi ujian).

---

### 14. `GET /api/exams` (Client Ujian Siswa)
- **Fungsi**: Menampilkan daftar ujian yang aktif dan berhak diikuti oleh siswa saat ini.
- **Role**: `student`
- **Authentication**: Bearer Token
- **Request**: Tidak ada body (Header `Authorization: Bearer <token>`).
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 12,
        "title": "PAS Ganjil Matematika Kelas 9",
        "subject": "Matematika",
        "duration_minutes": 90,
        "start_window": "2026-09-08T07:30:00Z",
        "end_window": "2026-09-08T11:00:00Z",
        "requires_token": true,
        "attempt_status": "not_started"
      }
    ]
  }
  ```
- **Kemungkinan Error**:
  - `401 Unauthorized`: Token tidak ada atau kedaluwarsa.
  - `403 Forbidden`: Role bukan student.

---

### 14. `POST /api/v1/exams/{id}/start`
- **Fungsi**: Memulai sesi pengerjaan ujian baru atau memulihkan sesi aktif (Recovery), mencatat `started_at` dan `ends_at` di server, serta mengunduh paket butir soal (**TANPA KUNCI JAWABAN**).
- **Role**: `student`
- **Authentication**: Bearer Token (`auth:sanctum`)
- **Request Body**:
  ```json
  {
    "token": "KLS9MA"
  }
  ```
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Sesi ujian berhasil dimulai",
    "data": {
      "attempt_id": 849,
      "exam_id": 12,
      "started_at": "2026-09-08T08:05:00Z",
      "ends_at": "2026-09-08T09:35:00Z",
      "duration_seconds": 5400,
      "remaining_seconds": 5088,
      "server_time": "2026-09-08T08:05:00Z",
      "status": "in_progress",
      "questions": [
        {
          "id": 301,
          "order_index": 1,
          "weight": 50,
          "question_type": "single_choice",
          "content": "Hasil dari 2x + 5 = 15 adalah...",
          "media_path": null,
          "options": [
            {"id": 1201, "label": "A", "content": "x = 3"},
            {"id": 1202, "label": "B", "content": "x = 5"},
            {"id": 1203, "label": "C", "content": "x = 7"},
            {"id": 1204, "label": "D", "content": "x = 10"}
          ]
        }
      ],
      "saved_answers": []
    }
  }
  ```
- **Kemungkinan Error**:
  - `400 Bad Request`: Token ujian salah, jadwal ujian belum mulai, atau sudah lewat `end_window`.
  - `403 Forbidden`: Siswa tidak terdaftar pada sesi ujian ini / attempt sudah pernah disubmit dan tidak diizinkan retest.
  - `404 Not Found`: Sesi ujian tidak ditemukan atau belum aktif.

---

### 15. `GET /api/v1/attempts/{id}`
- **Fungsi**: Mengambil informasi status sesi ujian, remaining time, dan detail paket ujian.
- **Role**: `student` (pemilik), `teacher`, `admin`
- **Authentication**: Bearer Token (`auth:sanctum`)
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Detail sesi ujian berhasil diambil",
    "data": {
      "id": 849,
      "exam_id": 12,
      "student_id": 105,
      "status": "in_progress",
      "remaining_seconds": 4500,
      "server_time": "2026-09-08T08:15:00Z"
    }
  }
  ```

---

### 16. `POST /api/v1/attempts/{id}/answers`
- **Fungsi**: Autosave 1 butir jawaban peserta secara asinkron (Atomic Upsert / Idempotent).
- **Role**: `student` (pemilik sesi)
- **Authentication**: Bearer Token (`auth:sanctum`)
- **Request Body**:
  ```json
  {
    "question_id": 301,
    "selected_option_id": 1202,
    "essay_answer": null,
    "is_flagged": false
  }
  ```
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Jawaban berhasil disimpan",
    "data": {
      "status": "SAVED",
      "synced_at": "2026-09-08T08:10:12Z",
      "remaining_seconds": 5088,
      "answer": {
        "id": 1,
        "attempt_id": 849,
        "question_id": 301,
        "selected_option_id": 1202,
        "selected_option": "B",
        "essay_answer": null,
        "is_flagged": false,
        "answered_at": "2026-09-08T08:10:12Z"
      }
    }
  }
  ```
- **Kemungkinan Error**:
  - `401 Unauthorized`: Token salah / tidak disertakan.
  - `403 Forbidden`: `attempt_id` bukan milik user login, ATAU status attempt sudah `submitted`/`timeout`.
  - `404 Not Found`: Sesi ujian tidak ditemukan.
  - `422 Unprocessable Content`: Butir soal bukan bagian dari paket ujian atau pilihan jawaban tidak sesuai.

---

### 17. `GET /api/v1/attempts/{id}/answers`
- **Fungsi**: Mengambil seluruh daftar jawaban yang telah tersimpan pada sesi ujian tanpa membocorkan kunci jawaban (`is_correct`) atau skor nilai.
- **Role**: `student` (pemilik), `teacher`, `admin`
- **Authentication**: Bearer Token (`auth:sanctum`)

---

### 18. `POST /api/v1/attempts/{id}/submit`
- **Fungsi**: Menyelesaikan ujian secara resmi, mengunci attempt, dan memicu status `SUBMITTED` (*Idempotent*).
- **Role**: `student` (pemilik sesi)
- **Authentication**: Bearer Token (`auth:sanctum`)
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Ujian telah berhasil diselesaikan",
    "data": {
      "status": "SUBMITTED",
      "submitted_at": "2026-09-08T09:20:00Z"
    }
  }
  ```
- **Kemungkinan Error**:
  - `401 Unauthorized`: Token salah / tidak disertakan.
  - `403 Forbidden`: Attempt bukan milik siswa yang login.
  - `404 Not Found`: Sesi ujian tidak ditemukan.
  - *Catatan Idempotensi*: Jika endpoint ini dipanggil dua kali untuk attempt yang sama, server **TIDAK** error dan langsung mengembalikan respon `200 OK` dengan status `SUBMITTED`.

---

### 5. `POST /api/attempts/{id}/sync`
- **Fungsi**: Sinkronisasi batch jawaban dari *Local Offline Queue* Android saat koneksi Wi-Fi pulih.
- **Role**: `student`
- **Authentication**: Bearer Token
- **Request Body**:
  ```json
  {
    "answers": [
      {
        "question_id": 301,
        "selected_option_id": 1202,
        "is_flagged": false,
        "client_timestamp": "2026-09-08T08:12:00Z"
      },
      {
        "question_id": 302,
        "selected_option_id": 1208,
        "is_flagged": true,
        "client_timestamp": "2026-09-08T08:14:20Z"
      }
    ]
  }
  ```
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "synced_count": 2,
    "server_time": "2026-09-08T08:15:01Z",
    "remaining_seconds": 4799
  }
  ```
- **Kemungkinan Error**:
  - `400 Bad Request`: Payload kosong atau tidak berformat array valid.
  - `403 Forbidden`: Attempt telah dikunci oleh server.

---

### 6. `POST /api/attempts/{id}/submit`
- **Fungsi**: Menyelesaikan ujian secara resmi, mengunci attempt, dan memicu penilaian otomatis (*Idempotent*).
- **Role**: `student`
- **Authentication**: Bearer Token
- **Request Body**:
  ```json
  {
    "confirm_finish": true
  }
  ```
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "status": "SUBMITTED",
    "submitted_at": "2026-09-08T09:20:00Z",
    "message": "Ujian telah berhasil diselesaikan."
  }
  ```
- **Kemungkinan Error**:
  - `400 Bad Request`: Konfirmasi submit tidak ada.
  - `403 Forbidden`: Attempt bukan milik siswa yang login.
  - *Catatan Idempotensi*: Jika endpoint ini dipanggil dua kali untuk attempt yang sama, server **TIDAK** error dan **TIDAK** melakukan grading ulang, melainkan langsung mengembalikan respon `200 OK` dengan status `SUBMITTED`.

---

### 19. `GET /api/v1/results`
- **Fungsi**: Menampilkan riwayat hasil ujian siswa atau rekapitulasi nilai bagi guru/admin dengan paginasi dan filter.
- **Role**: `student` (terisolasi hanya melihat hasil miliknya yang telah dipublish), `teacher`, `admin`.
- **Authentication**: Bearer Token (`auth:sanctum`)
- **Request Params**: `?exam_id=12&class_id=3&student_id=5&is_published=true&per_page=15`
- **Response (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Daftar hasil ujian berhasil diambil",
    "data": {
      "items": [
        {
          "id": 1,
          "attempt_id": 849,
          "exam_id": 12,
          "student_id": 105,
          "correct_count": 18,
          "wrong_count": 2,
          "unanswered_count": 0,
          "final_score": 90.00,
          "is_published": true,
          "graded_at": "2026-09-08T09:20:00Z"
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
- **Kemungkinan Error**:
  - `401 Unauthorized`: Token tidak sah.
  - `403 Forbidden`: Siswa mencoba melihat hasil ujian yang belum di-publish oleh guru/admin.

---

### 20. `GET /api/v1/results/{id}`
- **Fungsi**: Menampilkan detail lengkap hasil ujian siswa.
- **Role**: `student` (pemilik, jika dipublish), `teacher`, `admin`.
- **Authentication**: Bearer Token (`auth:sanctum`)

---

### 21. `POST /api/v1/attempts/{id}/grade`
- **Fungsi**: Menjalankan penilaian server-side untuk attempt yang sudah berstatus `submitted` atau `timeout`.
- **Role**: `student` (pemilik attempt), `teacher`, `admin`.
- **Authentication**: Bearer Token (`auth:sanctum`)

---

### 22. `PATCH /api/v1/results/{id}/publish`
- **Fungsi**: Mengubah status publikasi nilai ujian siswa.
- **Role**: `teacher`, `admin`.
- **Authentication**: Bearer Token (`auth:sanctum`)
- **Request Body**: `{"is_published": true}`

---

### 23. `GET /api/v1/monitoring/live`
- **Fungsi**: Menampilkan ikhtisar live seluruh ujian yang sedang aktif dan hitungan peserta secara global.
- **Role**: `admin`, `teacher` (Siswa: `HTTP 403 Forbidden`).
- **Authentication**: Bearer Token (`auth:sanctum`)

---

### 24. `GET /api/v1/monitoring/exams/{examId}`
- **Fungsi**: Menampilkan daftar peserta terdaftar dalam ujian beserta status pengerjaan live dan telemetri perangkat.
- **Role**: `admin`, `teacher`.
- **Authentication**: Bearer Token (`auth:sanctum`)
- **Request Params**: `?class_id=1&status=in_progress&per_page=15`

---

### 25. `GET /api/v1/monitoring/attempts/{attemptId}`
- **Fungsi**: Menampilkan detail live telemetri teknis sesi pengerjaan siswa tunggal (sisa waktu, IP address, device, progres).
- **Role**: `admin`, `teacher`.
- **Authentication**: Bearer Token (`auth:sanctum`)

---

### 26. `GET /api/v1/reports/exams/{examId}`
- **Fungsi**: Menampilkan rekapitulasi statistik paket ujian (total peserta, jumlah lulus/gagal, persentase kelulusan, rata-rata, tertinggi, terendah) dan performa nilai siswa.
- **Role**: `admin`, `teacher` (Siswa: `HTTP 403 Forbidden`).
- **Authentication**: Bearer Token (`auth:sanctum`)
- **Request Params**: `?class_id=1&per_page=15&page=1`
- **Response Format**: Mengikuti standard `ApiResponse::successResponse`.

---

### 27. `GET /api/v1/reports/classes/{classId}`
- **Fungsi**: Menampilkan rekapitulasi nilai seluruh ujian untuk rombel / kelas tertentu.
- **Role**: `admin`, `teacher`.
- **Authentication**: Bearer Token (`auth:sanctum`)
- **Request Params**: `?exam_id=1&per_page=15&page=1`
- **Response Format**: Mengikuti standard `ApiResponse::successResponse`.

---

### 28. `GET /api/v1/reports/item-analysis/{examId}`
- **Fungsi**: Menampilkan analisis butir soal ujian (rasio kesukaran / difficulty index, jumlah benar/salah, dan klasifikasi mudah/sedang/sukar).
- **Role**: `admin`, `teacher`.
- **Authentication**: Bearer Token (`auth:sanctum`)
- **Response Format**: Mengikuti standard `ApiResponse::successResponse`.

---

### 29. `GET /api/v1/backups`
- **Fungsi**: Menampilkan daftar seluruh berkas cadangan (snapshot) database yang tersedia di server lokal.
- **Role**: `admin` (Guru & Siswa: `HTTP 403 Forbidden`).
- **Authentication**: Bearer Token (`auth:sanctum`)
- **Response Format**: Mengikuti standard `ApiResponse::successResponse`.

---

### 30. `POST /api/v1/backups`
- **Fungsi**: Memicu pembuatan berkas cadangan baru database MySQL secara aman (read-only, pure PDO query).
- **Role**: `admin`.
- **Authentication**: Bearer Token (`auth:sanctum`)
- **Request Body**: `{"type": "manual"}` (`manual`, `pre_exam`, `post_exam`)
- **Response Format**: Mengikuti standard `ApiResponse::successResponse` (HTTP 201).

---

### 31. `GET /api/v1/backups/{filename}/download`
- **Fungsi**: Mengunduh berkas cadangan database `.sql` secara aman dengan proteksi path traversal.
- **Role**: `admin`.
- **Authentication**: Bearer Token (`auth:sanctum`)
- **Response**: Binary File Download (`application/sql`).

---

### 32. `DELETE /api/v1/backups/{filename}`
- **Fungsi**: Menghapus berkas cadangan database tertentu dari storage server.
- **Role**: `admin`.
- **Authentication**: Bearer Token (`auth:sanctum`)
- **Response Format**: Mengikuti standard `ApiResponse::successResponse`.

---

### 33. `GET /api/v1/attempts/{id}/timer`
- **Fungsi**: Menampilkan status timer otoritatif server untuk sesi ujian tertentu (sisa waktu, status, expired, can_continue).
- **Role**: Siswa pemilik attempt, Guru, Admin (Siswa lain: `HTTP 403 Forbidden`).
- **Authentication**: Bearer Token (`auth:sanctum`)
- **Response Format**: Mengikuti standard `ApiResponse::successResponse`.

---

### 34. `GET /api/v1/exams/{id}/timer`
- **Fungsi**: Menampilkan status waktu ujian siswa pada paket ujian (mendukung state `not_started`, pemulihan sesi, dan jadwal ujian).
- **Role**: Siswa terdaftar, Guru, Admin.
- **Authentication**: Bearer Token (`auth:sanctum`)
- **Response Format**: Mengikuti standard `ApiResponse::successResponse`.

---

### 35. `POST /api/v1/attempts/{id}/extend-time`
- **Fungsi**: Memperpanjang batas waktu (`ends_at`) sesi ujian siswa secara manual dan mencatat audit ke `activity_logs`.
- **Role**: `admin`, `teacher` (Siswa: `HTTP 403 Forbidden`).
- **Authentication**: Bearer Token (`auth:sanctum`)
- **Request Body**: `{"added_minutes": 15, "reason": "Kendala teknis Wi-Fi"}`
- **Response Format**: Mengikuti standard `ApiResponse::successResponse`.

---

### 36. `POST /api/v1/attempts/{id}/sync`
- **Fungsi**: Sinkronisasi kumpulan jawaban dari antrean offline klien Android (*Batch Upsert / Idempotent*).
- **Role**: Siswa pemilik attempt (`student`).
- **Authentication**: Bearer Token (`auth:sanctum`)
- **Request Body**: `{"answers": [{"question_id": 101, "selected_option_id": 402, "essay_answer": null, "is_flagged": false}]}`
- **Response Format**: Mengikuti standard `ApiResponse::successResponse`.

---

### 37. `GET /api/v1/settings` & `PUT /api/v1/settings`
- **Fungsi**: Membaca dan memperbarui pengaturan operasional server CBT lokal Windows.
- **Role**: 
  - `GET`: Admin & Guru (`admin`, `teacher`). Siswa ditolak (`HTTP 403 Forbidden`).
  - `PUT`: Khusus Admin (`admin`). Guru & Siswa ditolak (`HTTP 403 Forbidden`).
- **Authentication**: Bearer Token (`auth:sanctum`).
- **Storage**: Berkas JSON lokal `storage/app/settings.json` dengan fallback default array (0 migration).
- **Security**: Whitelist field (`school_name`, `school_address`, `academic_year`, `app_name`, `server_port`, `auto_token_release`, `token_refresh_minutes`, `allow_student_review`, `logo_url`). Kunci arbitrer ditolak (`422`). Tidak membocorkan credential, password, atau `.env`.
- **Response Format**: Mengikuti standard `ApiResponse::successResponse`.

---

### 38. `GET /api/v1/activity-logs` & `POST /api/v1/activity-logs/event`
- **Fungsi**: Menginspeksi riwayat audit aktivitas sistem dan mencatat event integritas/anti-cheat dari klien.
- **Role**:
  - `GET /api/v1/activity-logs`: Khusus Admin (`admin`). Siswa & Guru ditolak (`HTTP 403 Forbidden`).
  - `POST /api/v1/activity-logs/event`: Seluruh pengguna terautentikasi (Peserta ujian/Siswa).
- **Authentication**: Bearer Token (`auth:sanctum`).
- **Database**: Skema tabel existing `activity_logs` (0 migration baru).
- **Query Filter GET**: `module`, `action`, `user_id`, `per_page` (1-100), `page`.
- **Actor Integrity POST**: Actor ID (`user_id`) dipaksa server berasal dari user terautentikasi (`$request->user()->id`), mencegah manipulasi spoofing ID oleh client.
- **Whitelisted Actions POST**: `WINDOW_FOCUS_LOST`, `APP_BACKGROUNDED`, `DEVICE_LOCKED`, `SCREEN_UNPINNED`.
- **Response Format**: Mengikuti standard `ApiResponse::successResponse`.
