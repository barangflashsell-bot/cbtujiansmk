# FLOWCHART GURU SISTEM CBT
TAHAP 1 — BLUEPRINT ALUR KERJA LENGKAP GURU

Dokumen ini memetakan alur kerja Guru secara lengkap mulai dari login ke sistem, penyusunan bank soal, pengaturan jadwal ujian mata pelajaran, pemantauan pelaksanaan, koreksi soal esai, hingga rekapitulasi dan peninjauan hasil akhir ujian.

---

## 1. DIAGRAM ALUR UTAMA (FLOWCHART MERMAID)

```mermaid
flowchart TD
    A([Mulai]) --> B[1. Halaman Login Web Guru]
    B --> C{2. Autentikasi Server}
    C -- Kredensial Tidak Valid --> B1[Tampilkan Error Autentikasi]
    B1 --> B
    C -- Valid & Role: Guru --> D[3. Dashboard Utama Guru]
    
    D --> E[4. Menu Bank Soal]
    E --> E1[Pilih / Buat Bank Soal Mapel yang Diampu]
    E1 --> E2[Input Butir Soal: Pilihan Ganda / Esai]
    E2 --> E3[Input Opsi Jawaban & Kunci Jawaban Server-Side]
    E3 --> E4[Upload Media Pendukung: Gambar / Audio jika ada]
    E4 --> F{5. Verifikasi Kelengkapan Soal}
    F -- Belum Lengkap / Kurang Butir --> E2
    F -- Siap & Valid --> G[6. Pengaturan / Pembuatan Ujian]
    
    G --> G1[Pilih Bank Soal & Tentukan Kelas Sasaran]
    G1 --> G2[Tentukan Tanggal, Durasi Menit, & Acak Soal/Opsi]
    G2 --> H[7. Verifikasi Daftar Peserta Kelas]
    
    H --> I[8. Pelaksanaan Ujian & Pemantauan Progres]
    I --> I1{Ujian Berlangsung di LAN}
    I1 -- Siswa Mengerjakan --> I1
    I1 -- Waktu Habis / Siswa Submit --> J[9. Ujian Selesai]
    
    J --> K[10. Penilaian & Koreksi Hasil]
    K --> K1[Auto-Grading Pilihan Ganda Selesai Otomatis di Server]
    K --> K2{Ada Soal Esai?}
    K2 -- Ya --> K3[Buka Lembar Koreksi Esai & Input Nilai Esai]
    K3 --> L[11. Rekapitulasi Skor Akhir]
    K2 -- Tidak Ada Esai --> L
    
    L --> M[12. Melihat Hasil & Analisis Nilai]
    M --> M1[Lihat Distribusi Nilai Kelas & Analisis Butir Soal]
    M --> M2[Export Rekap Nilai ke Excel / PDF]
    M2 --> N([Selesai / Logout])
```

---

## 2. PENJELASAN SETIAP TAHAPAN ALUR GURU

### 1. Login
- Guru membuka antarmuka Web melalui browser di laptop/PC yang terhubung ke jaringan sekolah (`http://localhost:8000` atau IP server lokal).
- Memasukkan `Username / NIP` dan `Password`.

### 2. Authentication
- Server memverifikasi kecocokan kredensial hash di database MySQL (**Aturan 15 AI_RULES.md**).
- Server memastikan pengguna memiliki `role = 'teacher'` (Guru) dan membatasi akses hanya ke mata pelajaran serta kelas yang diampunya.

### 3. Dashboard Guru
- Menyajikan ringkasan informasi yang relevan untuk guru:
  - Jumlah bank soal aktif yang telah dibuat.
  - Jadwal ujian mapel guru yang akan datang atau sedang aktif hari ini.
  - Notifikasi jumlah lembar jawaban esai siswa yang belum dikoreksi.

### 4. Kelola Bank Soal
- Guru membuka menu **Bank Soal**:
  - Membuat wadah paket soal (misal: "Bank Soal Matematika Kelas 9 PAS").
  - Menambahkan butir-butir soal:
    - **Pilihan Ganda**: Mengetik pertanyaan, memasukkan pilihan opsi (A, B, C, D, E), menentukan bobot skor, dan menandai opsi jawaban yang benar (kunci jawaban disimpan eksklusif di server sesuai **Aturan 11**).
    - **Esai**: Mengetik pertanyaan esai dan menentukan pedoman penskoran serta bobot maksimal.
  - Mengunggah berkas gambar/media pendukung soal (misal: grafik, peta, diagram, rumus matematika).
  - Atau melakukan *Import Bank Soal* secara massal via template Excel yang disediakan sistem.

### 5. Verifikasi Kelengkapan Soal
- Guru meninjau pratampil (*preview*) butir-butir soal untuk memastikan tidak ada kesalahan ketik, gambar tampil sempurna, bobot poin pas, dan seluruh butir telah memiliki kunci jawaban yang valid.

### 6. Pengaturan / Pembuatan Ujian (Ujian)
- Guru membuat jadwal penilaian untuk mata pelajarannya:
  - Menghubungkan paket ujian dengan bank soal yang telah diverifikasi.
  - Menentukan nama ujian (misal: "Asesmen Sumatif Tengah Semester IPA").
  - Mengatur durasi pengerjaan dalam menit (misal: 60 menit atau 90 menit).
  - Mengaktifkan opsi pengacakan urutan soal dan pengacakan susunan pilihan ganda.

### 7. Verifikasi Peserta (Peserta)
- Guru memeriksa daftar peserta dari rombongan belajar / kelas yang diajarnya (mode *read-only*) untuk memastikan seluruh siswa aktif terdaftar di sistem.

### 8. Pelaksanaan Ujian & Pemantauan Progres
- Saat jadwal ujian dibuka oleh proktor/admin, guru dapat melihat progres pengerjaan siswa untuk mata pelajarannya:
  - Berapa banyak siswa yang sudah mulai mengerjakan.
  - Siapa saja siswa yang sedang aktif atau sudah menyelesaikan ujian.

### 9. Ujian Selesai
- Sesi pengerjaan siswa berakhir baik melalui penekanan tombol "Selesai" di Android (idempotent submit) maupun melalui penutupan otomatis batas waktu server (*timeout force submit* sesuai **Aturan 12**).

### 10. Penilaian & Koreksi Hasil
- **Pilihan Ganda**: Server secara otomatis menghitung nilai (*auto-grading*) dalam hitungan detik setelah submit berhasil.
- **Soal Esai**: Jika ujian memiliki tipe soal esai, guru membuka menu koreksi untuk membaca jawaban esai siswa per nomor dan memberikan skor secara manual.

### 11. Rekapitulasi Skor Akhir
- Server secara otomatis menggabungkan skor pilihan ganda dengan skor esai yang telah diinput guru, lalu mengalkulasi nilai akhir berdasarkan bobot total (skala 0 - 100).

### 12. Melihat Hasil & Analisis Nilai (Hasil)
- Guru meninjau rekapitulasi nilai lengkap seluruh siswa di kelas yang diampunya:
  - Nilai tertinggi, terendah, dan rata-rata kelas.
  - Analisis butir soal (mengetahui nomor soal mana yang paling banyak dijawab salah oleh siswa).
  - Mengunduh rekapitulasi nilai ke dalam format **Excel (.xlsx)** untuk keperluan pengolahan nilai rapor.
- Guru mengakhiri sesi kerja dengan aman (*Logout*).
