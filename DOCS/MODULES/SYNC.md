# MODUL: SYNC (OFFLINE QUEUE RECOVERY & BATCH AUTOSAVE)
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT) — TAHAP 3N

## 1. Tanggung Jawab Modul
- Mengelola sinkronisasi data jawaban antara antrean lokal Android (*Local Storage & Offline Queue*) dengan server database MySQL.
- **Connection Recovery (Wi-Fi Putus / Sinyal Hilang)**:
  - Selama koneksi terputus, aplikasi Android menyimpan setiap perubahan jawaban ke antrean lokal (SQLite/Room/Memory).
  - Saat koneksi pulih kembali, Android mengirimkan seluruh antrean jawaban ke endpoint batch sync (`POST /api/v1/attempts/{id}/sync`).
  - Klien **DILARANG MENGHAPUS** data antrean lokal sebelum server mengirimkan konfirmasi sukses (`ACK / 200 OK`).
- **Idempotency & Retry Safety**:
  - Request sinkronisasi yang dikirim berulang kali (akibat timeout jaringan, retry loop, atau reconnect) tidak menghasilkan duplikasi baris jawaban di database (`uk_attempt_question` unique key).
  - Server melakukan *Atomic Upsert* (`updateOrCreate`) pada setiap butir soal.
- **Integritas Waktu & Kunci Jawaban**:
  - Server memvalidasi bahwa sesi ujian masih berstatus `in_progress` dan waktu belum kedaluwarsa (`now() <= ends_at`).
  - Jawaban pada sesi yang sudah `submitted` atau `timeout` ditolak (`HTTP 403 Forbidden`).
  - Kolom `is_correct` dan `earned_score` **TIDAK PERNAH** dibocorkan ke respon API.

---

## 2. Tabel Database Terkait
- `answers` (`id`, `attempt_id`, `question_id`, `selected_option_id`, `selected_option`, `essay_answer`, `is_flagged`, `answered_at`, `synced_at`)
- `exam_attempts` (validasi kepemilikan, waktu akhir, dan status sesi)
- `exam_questions` & `questions` (validasi relasi butir soal terhadap paket ujian)
- `question_options` (validasi relasi opsi jawaban terhadap butir soal)

---

## 3. Spesifikasi Endpoint REST API (/api/v1)

### `POST /api/v1/attempts/{id}/sync`
- **Fungsi**: Sinkronisasi kumpulan jawaban dari antrean offline klien Android (*Batch Upsert / Idempotent*).
- **Akses**: Role `student` pemilik attempt (`auth:sanctum`). Siswa lain ditolak (`HTTP 403 Forbidden`).
- **Request Body (JSON)**:
  ```json
  {
    "answers": [
      {
        "question_id": 101,
        "selected_option_id": 402,
        "essay_answer": null,
        "is_flagged": false,
        "answered_at": "2026-09-08T08:15:00Z"
      },
      {
        "question_id": 102,
        "selected_option_id": 405,
        "essay_answer": null,
        "is_flagged": true,
        "answered_at": "2026-09-08T08:15:30Z"
      }
    ]
  }
  ```
- **Respon Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Antrean jawaban berhasil disinkronkan",
    "data": {
      "status": "SYNCED",
      "synced_count": 2,
      "synced_at": "2026-09-08T15:55:00+07:00",
      "remaining_seconds": 2400,
      "server_time": "2026-09-08T15:55:00+07:00",
      "answers": [
        {
          "id": 1,
          "question_id": 101,
          "selected_option_id": 402,
          "selected_option": "A",
          "is_flagged": false,
          "synced_at": "2026-09-08T15:55:00+07:00"
        },
        {
          "id": 2,
          "question_id": 102,
          "selected_option_id": 405,
          "selected_option": "B",
          "is_flagged": true,
          "synced_at": "2026-09-08T15:55:00+07:00"
        }
      ]
    }
  }
  ```
- **Error Responses**:
  - `401 Unauthorized`: Request tidak menyertakan Bearer Token yang sah.
  - `403 Forbidden`: Attempt bukan milik siswa yang login, atau attempt telah berstatus `submitted` / `timeout`.
  - `404 Not Found`: Attempt tidak ditemukan.
  - `422 Unprocessable Content`: Butir soal bukan bagian dari paket ujian atau opsi jawaban tidak cocok.

---

## 4. Direct Dependencies
- Modul `ANSWERS`
- Modul `ATTEMPTS`
- Modul `TIMER`
- Modul `EXAMS`
