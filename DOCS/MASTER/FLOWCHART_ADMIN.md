# FLOWCHART ADMIN SISTEM CBT
TAHAP 1 — BLUEPRINT ALUR KERJA LENGKAP ADMIN

Dokumen ini memetakan alur kerja Administrator/Proktor secara runtut sesuai 12 langkah resmi operasional CBT.

---

## 1. DIAGRAM ALUR UTAMA (12 LANGKAH RESMI)

```text
       ┌────────────────────────┐
       │       1. Login         │
       └───────────┬────────────┘
                   │
                   ▼
       ┌────────────────────────┐
       │   2. Authentication    │
       └───────────┬────────────┘
                   │ (Valid & Role: Admin)
                   ▼
       ┌────────────────────────┐
       │      3. Dashboard      │
       └───────────┬────────────┘
                   │
                   ▼
       ┌────────────────────────┐
       │    4. Kelola Data      │
       │ (Siswa, Guru, Kelas,   │
       │    Mata Pelajaran)     │
       └───────────┬────────────┘
                   │
                   ▼
       ┌────────────────────────┐
       │      5. Bank Soal      │
       │ (Cek Butir Soal, Opsi, │
       │  Kunci & Gambar/Media) │
       └───────────┬────────────┘
                   │
                   ▼
       ┌────────────────────────┐
       │    6. Membuat Ujian    │
       │ (Pilih Bank Soal &     │
       │     Judul Sesi)        │
       └───────────┬────────────┘
                   │
                   ▼
       ┌────────────────────────┐
       │ 7. Menentukan Peserta  │
       │ (Alokasi Rombel/Kelas/ │
       │     Daftar Siswa)      │
       └───────────┬────────────┘
                   │
                   ▼
       ┌────────────────────────┐
       │  8. Menentukan Jadwal  │
       │  (Tanggal, Jam Mulai,  │
       │  Durasi, Token, Acak)  │
       └───────────┬────────────┘
                   │
                   ▼
       ┌────────────────────────┐
       │   9. Aktifkan Ujian    │
       │ (Status ACTIVE / Rilis │
       │      Token Sesi)       │
       └───────────┬────────────┘
                   │
                   ▼
       ┌────────────────────────┐
       │     10. Monitoring     │
       │ (Live Proctoring LAN,  │
       │  Status Siswa, Reset)  │
       └───────────┬────────────┘
                   │
                   ▼
       ┌────────────────────────┐
       │       11. Hasil        │
       │ (Skor Auto-Grading PG, │
       │   Koreksi Nilai Esai)  │
       └───────────┬────────────┘
                   │
                   ▼
       ┌────────────────────────┐
       │      12. Laporan       │
       │ (Rekapitulasi Nilai &  │
       │   Export Excel/PDF)    │
       └────────────────────────┘
```

---

## 2. PENJELASAN SETIAP TAHAPAN ALUR

| No | Tahapan | Deskripsi Operasional & Teknis |
| :---: | :--- | :--- |
| **1** | **Login** | Admin membuka browser di komputer Windows server lokal (`http://localhost:8000` atau IP LAN) dan menginput `Username` serta `Password`. |
| **2** | **Authentication** | Server memvalidasi hash password (`Bcrypt/Argon2`) di database MySQL. Jika valid dan akun memiliki `role = 'admin'`, server menerbitkan token otentikasi sesi terenkripsi (**Aturan 15 AI_RULES.md**). |
| **3** | **Dashboard** | Layar menyajikan ringkasan statistik: total peserta terdaftar, status konektivitas server, database, dan jadwal ujian aktif hari ini. |
| **4** | **Kelola Data** | Admin mengelola dan memverifikasi data master: Siswa (import Excel/tambah manual), Guru, Kelas/Rombel (7A, 8B, dsb.), dan Mata Pelajaran. |
| **5** | **Bank Soal** | Admin meninjau kesiapan bank soal: memeriksa kelengkapan butir soal, pilihan jawaban A/B/C/D, upload media gambar jika ada, dan memastikan kunci jawaban tersimpan aman di server (**Aturan 11**). |
| **6** | **Membuat Ujian** | Admin membuat paket sesi ujian baru dengan memilih bank soal yang relevan serta menentukan judul penilaian (misal: "PAS Ganjil Matematika 9"). |
| **7** | **Menentukan Peserta** | Admin mengalokasikan peserta yang berhak mengikuti ujian tersebut (berdasarkan tingkat, rombongan belajar kelas, atau siswa tertentu). |
| **8** | **Menentukan Jadwal** | Admin mengonfigurasi aturan ujian: tanggal pelaksanaan, jam buka ujian (*start window*), durasi pengerjaan dalam menit, pengacakan nomor butir soal, pengacakan urutan opsi jawaban, serta syarat token ujian. |
| **9** | **Aktifkan Ujian** | Admin mengubah status ujian menjadi `ACTIVE` dan merilis token ujian kepada pengawas ruang kelas. Pada tahap ini, ujian telah siap diakses oleh siswa di aplikasi Android. |
| **10** | **Monitoring** | Admin/Proktor memantau jalannya ujian secara *real-time* di jaringan LAN: melihat siapa saja siswa yang sedang mengerjakan, sisa waktu masing-masing siswa, serta melakukan **Reset Login** jika ada siswa yang mengalami kendala teknis (HP mati/restart). Jika waktu server habis, sistem melakukan *auto force submit* (**Aturan 12**). |
| **11** | **Hasil** | Setelah sesi berakhir, Admin memeriksa rekaman hasil: verifikasi perolehan skor auto-grading pilihan ganda dan memeriksa apakah seluruh esai sudah dinilai oleh guru mata pelajaran. |
| **12** | **Laporan** | Admin mencetak dan mengekspor rekapitulasi nilai resmi dalam format **Excel (.xlsx)** atau **PDF** untuk keperluan arsip akademik dan penginputan nilai rapor sekolah. |
