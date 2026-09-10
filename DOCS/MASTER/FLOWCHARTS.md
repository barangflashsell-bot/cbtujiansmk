# MASTER FLOWCHARTS SISTEM CBT
TAHAP 1 — BLUEPRINT ALUR PROSES UTAMA (POIN 6 - 11)

Dokumen ini memuat detail teknis dari 6 alur utama sistem CBT: Alur Peserta, Alur Jaringan & Penanganan Kegagalan, Alur Autosave, Alur Timer Server-Authoritative, Alur Submit Idempotent, dan Alur Penilaian.

---

## 6. FLOWCHART PESERTA (STUDENT FLOW)

```text
       ┌────────────────────────────────────────────────────────┐
       │                       1. Login                         │
       │           (Input NIS/Username & Password)              │
       └───────────────────────────┬────────────────────────────┘
                                   │
                                   ▼
       ┌────────────────────────────────────────────────────────┐
       │             2. Server Configuration                    │
       │    (Input/Verifikasi IP & Port Server Windows lokal)   │
       └───────────────────────────┬────────────────────────────┘
                                   │
                                   ▼
       ┌────────────────────────────────────────────────────────┐
       │                3. Test Connection                      │
       │        (Ping HTTP endpoint /api/health-check)          │
       └───────────────────────────┬────────────────────────────┘
                                   │ (Koneksi Sukses)
                                   ▼
       ┌────────────────────────────────────────────────────────┐
       │                     4. Dashboard                       │
       │      (Profil Siswa, Status Server, Info Ujian)         │
       └───────────────────────────┬────────────────────────────┘
                                   │
                                   ▼
       ┌────────────────────────────────────────────────────────┐
       │                  5. Ujian Tersedia                     │
       │      (Daftar Jadwal Ujian Aktif Sesuai Kelas)          │
       └───────────────────────────┬────────────────────────────┘
                                   │
                                   ▼
       ┌────────────────────────────────────────────────────────┐
       │                  6. Detail Ujian                       │
       │    (Mata Pelajaran, Durasi Menit, Syarat Token)        │
       └───────────────────────────┬────────────────────────────┘
                                   │
                                   ▼
       ┌────────────────────────────────────────────────────────┐
       │                    7. Petunjuk                         │
       │     (Tata Tertib Ujian, Skema Ragu-ragu & Waktu)       │
       └───────────────────────────┬────────────────────────────┘
                                   │
                                   ▼
       ┌────────────────────────────────────────────────────────┐
       │             8. Mulai (Start Button)                    │
       │          (Input Token Ujian dari Pengawas)             │
       └───────────────────────────┬────────────────────────────┘
                                   │
                                   ▼
       ┌────────────────────────────────────────────────────────┐
       │                 9. Exam Attempt                        │
       │ (Server buat record attempt, kunci waktu started_at)   │
       └───────────────────────────┬────────────────────────────┘
                                   │
                                   ▼
       ┌────────────────────────────────────────────────────────┐
       │            10. Download / Prepare Soal                 │
       │ (Android unduh paket butir soal TANPA kunci jawaban)   │
       └───────────────────────────┬────────────────────────────┘
                                   │
                                   ▼
       ┌────────────────────────────────────────────────────────┐
       │                 11. Mengerjakan                        │
       │  (Navigasi Nomor Soal, Baca Konten, Pilih Jawaban)     │
       └───────────────────────────┬────────────────────────────┘
                                   │
                                   ▼
       ┌────────────────────────────────────────────────────────┐
       │                    12. Autosave                        │
       │ (Simpan instan ke Local State HP -> Kirim Background)  │
       └───────────────────────────┬────────────────────────────┘
                                   │
                                   ▼
       ┌────────────────────────────────────────────────────────┐
       │                     13. Submit                         │
       │ (Siswa klik Selesai ATAU Server Time Habis -> Lock)    │
       └───────────────────────────┬────────────────────────────┘
                                   │
                                   ▼
       ┌────────────────────────────────────────────────────────┐
       │                   14. Penilaian                        │
       │ (Server Auto-Grading PG & Rekap Nilai Esai Guru)       │
       └───────────────────────────┬────────────────────────────┘
                                   │
                                   ▼
       ┌────────────────────────────────────────────────────────┐
       │                     15. Hasil                          │
       │ (Tampilan Selesai / Nilai jika diizinkan sekolah)      │
       └────────────────────────────────────────────────────────┘
```

---

## 7. FLOWCHART JARINGAN & SKENARIO KEGAGALAN (NETWORK FLOW)

### A. Topologi Alur Komunikasi Normal
```text
ANDROID (Peserta)
     │ (Request HTTP/JSON)
     ▼
WI-FI LOKAL (Access Point / Router Sekolah)
     │ (Ethernet LAN kabel)
     ▼
WINDOWS SERVER (Host Komputer Ruang Ujian)
     │
     ▼
REST API (Port 8000 / Reverse Proxy)
     │ (Middleware Auth, CORS, Rate Limit)
     ▼
BACKEND LOGIC (Controllers, Services, Timer, Scoring Engine)
     │ (Connection Pool)
     ▼
DATABASE (MySQL / MariaDB Storage)
```

### B. Penanganan Skenario Gangguan Lapangan:
1. **Wi-Fi Terputus**:
   - *Client State*: Layar ujian tetap aktif dan tidak freeze. Jawaban yang dipilih siswa disimpan langsung ke SQLite/Local Storage HP, lalu ditampung ke *Local Queue*.
   - *Indikator*: Tampil ikon status *"Offline - Jawaban tersimpan di memori HP"*.
2. **Server Tidak Aktif (Mati / Down)**:
   - Android mendeteksi *Connection Refused / Timeout*.
   - Android beralih ke mode retrying (mencoba ulang tiap 10–15 detik di latar belakang).
   - Layar siswa tidak dikeluarkan dari ujian. Data pengerjaan tetap utuh di HP.
3. **Server Restart**:
   - Setelah server Windows boot & service database kembali nyala, state ujian siswa tidak hilang karena `started_at` dan `ends_at` tersimpan permanen di database.
   - Sisa durasi tetap akurat dihitung dari `ends_at` semula.
4. **Android Kehilangan Koneksi (Airplane Mode / Error Radio)**:
   - Aplikasi memberi peringatan modal: *"Koneksi terputus. Harap tetap di halaman ini dan hubungi pengawas jika Wi-Fi tidak terhubung kembali."*
5. **Koneksi Kembali Normal**:
   - Begitu ping ke server berhasil, *Background Queue Worker* di Android secara otomatis melakukan *batch sync* menguras seluruh antrean jawaban yang tertunda ke server.
   - Server merespon konfirmasi sukses (`ACK`), dan Android menandai status jawaban: *"Tersinkronisasi"*.

---

## 8. FLOWCHART AUTOSAVE & OFFLINE QUEUE (AUTOSAVE FLOW)

Sesuai **Aturan 13 & 30 AI_RULES.md**:

```text
[ALUR NORMAL]
Peserta Memilih Jawaban
         │
         ▼
Simpan ke Local State HP (Instan 0ms)
         │
         ▼
Kirim ke Server (Debounce 300ms, POST /api/attempts/{id}/answers)
         │
         ▼
Server Validasi (Validasi Token, Batas Waktu, & Status Attempt)
         │
         ▼
Database Upsert (INSERT ... ON DUPLICATE KEY UPDATE)
         │
         ▼
Server Mengirim Respon Sukses (ACK HTTP 200)
         │
         ▼
Android Menandai Jawaban: "Tersinkronisasi"

────────────────────────────────────────────────────────────────────────

[ALUR GAGAL / KONEKSI TIMEOUT]
Jawaban Gagal Terkirim
         │
         ▼
Masukkan Jawaban ke "Local Offline Queue" (Penyimpanan Lokal HP)
         │
         ▼
Tampilkan Indikator Kuning: "Menunggu Jaringan..."
         │
         ▼
Retry Loop di Background (Interval berkala saat jaringan pulih)
         │
         ▼
Kirim Batch ke Server (POST /api/attempts/{id}/sync)
         │
         ▼
Server Validasi & Batch Upsert
         │
         ▼
Server Mengirim Respon Sukses (ACK)
         │
         ▼
Android Menghapus Antrean & Tandai "Tersinkronisasi"
```

---

## 9. FLOWCHART TIMER (SERVER-AUTHORITATIVE TIMER)

Sesuai **Aturan 10 & 12 AI_RULES.md** (Jam HP Peserta BUKAN sumber kebenaran):

```text
1. Siswa Klik "Mulai Ujian" (Start Exam)
         │
         ▼
2. Server Membuat Attempt Baru di Database
         │
         ▼
3. Server Menentukan:
   - started_at = NOW()
   - ends_at    = NOW() + INTERVAL duration_minutes MINUTE
         │
         ▼
4. Android Menerima:
   - server_current_time
   - ends_at
   - duration_seconds
         │
         ▼
5. Android Menghitung Sisa Waktu & Menjalankan Countdown di UI
   (Sisa Waktu = ends_at - server_current_time)
         │
         ▼
6. Setiap Request (Autosave / Heartbeat):
   Server Tetap Memvalidasi: APAKAH NOW() > ends_at ?
         │
         ├─── Jika BELUM: Sesi Ujian Berjalan Normal
         │
         └─── Jika WAKTU HABIS (NOW() >= ends_at):
                   │
                   ▼
              7. Lock Attempt (Ubah status = 'timeout')
                   │
                   ▼
              8. Sinkronisasi Seluruh Jawaban Terakhir
                   │
                   ▼
              9. Auto-Submit oleh Server (Tolak modifikasi baru)
                   │
                   ▼
             10. Auto-Grade Pilihan Ganda & Selesaikan Ujian
```

---

## 10. FLOWCHART SUBMIT (IDEMPOTENT SUBMIT)

Sesuai **Aturan 14 & 32 AI_RULES.md**:

```text
Siswa Klik "Selesai" di Android
         │
         ▼
Android Memastikan Seluruh Antrean Lokal Telah Terkirim (Sync Queue)
         │
         ▼
Kirim Request POST /api/attempts/{id}/submit
         │
         ▼
Server Melakukan Validasi:
         │
         ▼
Check Attempt Status:
         │
         ├─── KASUS A: Status SUDAH 'submitted' atau 'timeout' (Request Duplikat)
         │         │
         │         └─── Kembalikan Hasil yang Sudah Ada (Idempotent, NO RE-GRADE)
         │
         └─── KASUS B: Status Masih 'in_progress'
                   │
                   ▼
              Lock Attempt (status = 'submitted')
                   │
                   ▼
              Simpan submitted_at = NOW()
                   │
                   ▼
              Hitung Skor (Auto-Grading Pilihan Ganda)
                   │
                   ▼
              Simpan Hasil Akhir ke Tabel Results
                   │
                   ▼
              Kembalikan Respon Sukses ke Android
```

---

## 11. FLOWCHART PENILAIAN & SISTEM PEMBOBOTAN (SCORING FLOW)

```text
HASIL JAWABAN PESERTA
         │
         ├─────────────────────────────────────────┐
         ▼                                         ▼
   PILIHAN GANDA                                 ESAI
         │                                         │
         ▼                                         ▼
Server Auto-Grading                     Koreksi Manual oleh Guru
(Bandingkan selected_option_id           (Guru membaca teks jawaban esai
dengan is_correct = 1)                   dan memberi skor 0 s.d Bobot Maks)
         │                                         │
         ▼                                         ▼
Hitung Skor Mentah PG                   Hitung Skor Mentah Esai
         │                                         │
         └────────────────────┬────────────────────┘
                              │
                              ▼
                FORMULA PEMBOBOTAN NILAI AKHIR
                              │
                              ▼
                   SKOR AKHIR (Skala 0 - 100)
```

### Sistem Pembobotan Matematis:
$$\text{Nilai PG} = \left( \frac{\text{Total Poin PG Benar}}{\text{Total Bobot Maksimal PG}} \right) \times \text{Persentase Bobot PG}$$

$$\text{Nilai Esai} = \left( \frac{\text{Total Poin Esai Diperoleh}}{\text{Total Bobot Maksimal Esai}} \right) \times \text{Persentase Bobot Esai}$$

$$\text{Nilai Akhir} = \text{Nilai PG} + \text{Nilai Esai}$$

*Contoh Standar Sekolah*:
- Porsi Pilihan Ganda: 70%
- Porsi Esai: 30%
- Jika ujian hanya berisi Pilihan Ganda, maka Bobot PG = 100%.
- Jika jawaban esai belum dinilai guru, status nilai ditampilkan *"Menunggu Penilaian Esai"*.
