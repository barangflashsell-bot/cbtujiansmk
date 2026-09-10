# MODUL: REPORTS & ACADEMIC ANALYSIS
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT) — TAHAP 3K

## 1. Tanggung Jawab Modul
- Menyediakan rekapitulasi data akademik dan performa pengerjaan ujian berbasis data existing (**Read-Only Reports Engine**).
- Menyediakan ringkasan statistik ujian bagi Guru dan Admin:
  - Total peserta terdaftar dan total siswa yang dinilai.
  - Jumlah siswa tuntas (lulus KKM) dan tidak tuntas beserta persentasenya.
  - Rata-rata nilai ujian, nilai tertinggi, dan nilai terendah.
  - Rincian performa nilai per siswa.
- Menyediakan rekapitulasi nilai ujian per kelas:
  - Ikhtisar siswa di suatu kelas beserta ujian dan perolehan skornya.
  - Filter berdasarkan ID paket ujian (`exam_id`).
- Menyediakan analisis butir soal ujian (**Item Analysis**):
  - Penghitungan tingkat kesukaran soal (*difficulty index*) secara otomatis berbasis rasio jawaban benar terhadap seluruh jawaban yang masuk.
  - Klasifikasi tingkat kesukaran soal:
    - `mudah`: difficulty index >= 70.0%
    - `sedang`: difficulty index 30.0% - 69.9%
    - `sukar`: difficulty index < 30.0%
- Menjamin integritas data:
  - Operasi bersifat **Read-Only**: tidak memodifikasi atau menghapus record pada tabel `exams`, `results`, `answers`, `exam_attempts`, maupun entitas master lainnya.
  - Proteksi kerahasiaan: kredensial sensitif pengguna (`password`, token) tidak dibocorkan dalam response.

---

## 2. Tabel Database Terkait (Data Existing)
- `exams` (informasi paket ujian, `passing_score`, jumlah soal)
- `results` (skor akhir siswa, jumlah benar, salah, tidak terjawab)
- `exam_participants` (peserta terdaftar pada ujian)
- `exam_attempts` (sesi pengerjaan ujian siswa)
- `exam_questions` (butir soal terdaftar dalam ujian dan bobot nilai)
- `questions` & `question_options` (konten soal dan opsi)
- `answers` (rekaman pilihan jawaban siswa dan status kebenaran `is_correct`)
- `students` & `users` (identitas dan NIS siswa)
- `classes` (data rombel / kelas siswa)
- `subjects` (data mata pelajaran)

---

## 3. Spesifikasi Endpoint REST API (/api/v1)

### 1. `GET /api/v1/reports/exams/{examId}`
- **Fungsi**: Menampilkan laporan statistik komprehensif paket ujian beserta rincian nilai siswa.
- **Akses**: `admin`, `teacher` (Siswa ditolak: `HTTP 403 Forbidden`).
- **Authentication**: Bearer Token (`auth:sanctum`).
- **Query Params**:
  - `class_id` (opsional): Memfilter peserta dan nilai berdasarkan kelas tertentu.
  - `per_page` (opsional, default 15, max 100): Jumlah data per halaman.
  - `page` (opsional): Nomor halaman paginasi.
- **Respon Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Laporan rekapitulasi ujian berhasil diambil",
    "data": {
      "exam": {
        "id": 1,
        "title": "Ujian Akhir Semester Matematika",
        "subject": {
          "id": 2,
          "name": "Matematika",
          "code": "MTK"
        },
        "passing_score": 75.0,
        "total_questions": 40
      },
      "statistics": {
        "total_participants": 32,
        "total_graded": 30,
        "passed_count": 25,
        "failed_count": 5,
        "pass_percentage": 83.3,
        "average_score": 81.25,
        "highest_score": 95.0,
        "lowest_score": 55.0
      },
      "items": [
        {
          "result_id": 10,
          "student": {
            "id": 5,
            "nis": "12345678",
            "name": "Budi Santoso",
            "class": {
              "id": 3,
              "name": "9-A",
              "level": "9"
            }
          },
          "correct_count": 35,
          "wrong_count": 5,
          "unanswered_count": 0,
          "final_score": 87.5,
          "is_passed": true,
          "status": "completed",
          "is_published": true,
          "graded_at": "2026-09-08T08:00:00Z"
        }
      ],
      "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 30,
        "last_page": 2
      }
    }
  }
  ```

---

### 2. `GET /api/v1/reports/classes/{classId}`
- **Fungsi**: Menampilkan rekapitulasi nilai seluruh ujian untuk siswa pada suatu kelas.
- **Akses**: `admin`, `teacher`.
- **Authentication**: Bearer Token (`auth:sanctum`).
- **Query Params**:
  - `exam_id` (opsional): Filter berdasarkan paket ujian spesifik.
  - `per_page` (opsional, default 15, max 100).
- **Respon Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Laporan rekapitulasi nilai kelas berhasil diambil",
    "data": {
      "class": {
        "id": 1,
        "name": "Kelas 9-A",
        "level": "9",
        "total_students": 32
      },
      "items": [
        {
          "result_id": 12,
          "exam": {
            "id": 1,
            "title": "UAS Matematika",
            "subject": "Matematika"
          },
          "student": {
            "id": 5,
            "nis": "12345678",
            "name": "Budi Santoso"
          },
          "final_score": 85.0,
          "is_passed": true,
          "correct_count": 34,
          "wrong_count": 6,
          "unanswered_count": 0,
          "graded_at": "2026-09-08T08:00:00Z"
        }
      ],
      "pagination": {
        "current_page": 1,
        "per_page": 15,
        "total": 32,
        "last_page": 3
      }
    }
  }
  ```

---

### 3. `GET /api/v1/reports/item-analysis/{examId}`
- **Fungsi**: Menampilkan analisis butir soal ujian meliputi jumlah jawaban benar/salah, rasio kesukaran (*difficulty index*), dan klasifikasi soal.
- **Akses**: `admin`, `teacher`.
- **Authentication**: Bearer Token (`auth:sanctum`).
- **Respon Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Analisis butir soal berhasil diambil",
    "data": {
      "exam": {
        "id": 1,
        "title": "UAS Matematika",
        "subject": {
          "id": 2,
          "name": "Matematika",
          "code": "MTK"
        },
        "total_questions": 40,
        "total_examined_attempts": 30
      },
      "items": [
        {
          "question_id": 101,
          "order_index": 1,
          "weight": 2.5,
          "question_type": "single_choice",
          "content_preview": "Berapakah hasil dari 2 + 2?",
          "total_attempts": 30,
          "total_answered": 30,
          "correct_count": 27,
          "wrong_count": 3,
          "difficulty_index": 90.0,
          "classification": "mudah"
        }
      ]
    }
  }
  ```

---

## 4. Security & Role Restriction
- **Unauthenticated (401)**: Request tanpa token Sanctum sah ditolak `HTTP 401 Unauthorized`.
- **Role Restriction (403)**: Siswa (`student`/`peserta`) dilarang mengakses seluruh endpoint Reports dan menerima respons `HTTP 403 Forbidden`.
- **404 Not Found Handling**:
  - `GET /api/v1/reports/exams/{id}` -> 404 jika `Exam` tidak ditemukan.
  - `GET /api/v1/reports/classes/{id}` -> 404 jika `Classes` tidak ditemukan.
  - `GET /api/v1/reports/item-analysis/{id}` -> 404 jika `Exam` tidak ditemukan.
- **Sensitive Data Isolation**: Response tidak menyertakan `password`, `remember_token`, atau kunci sensitif lainnya.

---

## 5. Test Coverage
- **File Test**: `SERVER/tests/Feature/ReportTest.php` & `SERVER/tests/Feature/ReportAcademicAnalysisWebTest.php`
- **Metode Pengujian**:
  1. `test_unauthenticated_requests_return_401`
  2. `test_student_role_cannot_access_reports_returns_403`
  3. `test_admin_and_teacher_can_view_exam_statistical_report`
  4. `test_exam_report_filters_by_class_id`
  5. `test_admin_and_teacher_can_view_class_report`
  6. `test_admin_and_teacher_can_view_item_analysis`
  7. `test_nonexistent_resources_return_404`
  8. `test_sensitive_data_not_leaked_in_reports`
  9. `test_01_guest_redirected_to_login`
  10. `test_02_student_forbidden_from_reports`
  11. `test_03_guru_forbidden_from_admin_reports`
  12. `test_04_admin_can_view_all_reports`
  13. `test_05_guru_reports_ownership_and_anti_idor`
  14. `test_06_nonexistent_resources_return_404`

---

## 6. Web UI Laporan & Analisis Akademik (Tahap 4F)
- **Controller**: `App\Http\Controllers\Web\WebReportController`
- **Views**:
  - Admin: `resources/views/admin/reports/index.blade.php`, `exam.blade.php`, `class.blade.php`, `item_analysis.blade.php`
  - Guru: `resources/views/guru/reports/index.blade.php`, `exam.blade.php`, `class.blade.php`, `item_analysis.blade.php`
- **Routes**:
  - `GET /admin/reports` & `GET /guru/reports`: Hub laporan per paket ujian dan per rombel kelas.
  - `GET /admin/reports/exams/{id}` & `GET /guru/reports/exams/{id}`: Laporan statistik ujian (total peserta, dinilai, lulus/belum KKM, rata-rata, tertinggi, terendah, dan rincian per siswa).
  - `GET /admin/reports/classes/{id}` & `GET /guru/reports/classes/{id}`: Rekapitulasi perolehan nilai siswa pada suatu rombel kelas.
  - `GET /admin/reports/item-analysis/{id}` & `GET /guru/reports/item-analysis/{id}`: Analisis butir soal (indeks kesukaran & klasifikasi: Mudah >= 70%, Sedang 30-69.9%, Sukar < 30%).
- **Otorisasi & Keamanan**:
  - Admin: Mengakses laporan seluruh sekolah.
  - Guru: Terisolasi ketat hanya pada paket ujian yang dibuatnya sendiri (`created_by = $user->id`). Akses ke ujian guru lain ditolak `HTTP 403 Forbidden` (Anti-IDOR).
  - Siswa: Dilarang keras mengakses Web Admin/Guru (`HTTP 403 Forbidden`).
  - Read-Only: Operasi murni membaca data tanpa mutasi atau penghapusan data master maupun pengerjaan.
