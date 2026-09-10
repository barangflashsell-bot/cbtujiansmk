# ANALISIS KEBUTUHAN SISTEM CBT
TAHAP 1 — BLUEPRINT & ANALISIS KEBUTUHAN

Dokumen ini memaparkan analisis kebutuhan sistem Computer Based Test (CBT) secara komprehensif, mencakup tujuan, profil pengguna, alur operasional, pelaksanaan ujian, model penyimpanan data, serta protokol komunikasi client-server.

---

## 1. TUJUAN APLIKASI

Aplikasi CBT ini dibangun dengan tujuan utama:
1. **Penyelenggaraan Ujian Mandiri & Hemat Biaya**: Menyelenggarakan ujian sekolah (Penilaian Harian, PTS, PAS, PAT, Asesmen Sekolah) secara digital tanpa ketergantungan pada kuota/koneksi internet komersial atau biaya cloud pihak ketiga.
2. **Optimalisasi Perangkat Peserta (BYOD - Bring Your Own Device)**: Memungkinkan siswa menggunakan smartphone Android masing-masing tanpa memerlukan laboratorium komputer yang besar, dengan aplikasi yang ringan dan mudah dipasang langsung (sideloading APK) tanpa Google Play Store.
3. **Pemberdayaan Komputer Windows Sekolah**: Menjadikan komputer/laptop standar Windows yang dimiliki sekolah sebagai server lokal terpusat yang tangguh melayani puluhan hingga ratusan peserta secara bersamaan melalui Access Point / Wi-Fi lokal.
4. **Efisiensi Kerja Guru & Integritas Ujian**: Mempercepat proses koreksi nilai secara otomatis, mencegah kebocoran kunci jawaban dengan isolasi penuh di server, serta memberikan pemantauan peserta secara *real-time* kepada guru dan pengawas.

---

## 2. SIAPA PENGGUNA SISTEM (USER PERSONA & ROLES)

Sistem melayani 3 tingkatan pengguna:

### A. Peserta Ujian (Siswa)
- **Akses**: Aplikasi Android APK.
- **Kebutuhan**:
  - Login dengan nomor peserta / NIS dan password.
  - Memasukkan alamat IP Server lokal sekolah secara mudah.
  - Membaca butir soal, navigasi antar nomor, menandai ragu-ragu, dan memilih opsi jawaban.
  - Mendapatkan kepastian bahwa jawaban tersimpan aman meski koneksi Wi-Fi sempat terputus.
  - Mengetahui sisa waktu ujian yang akurat.

### B. Guru / Pembuat Soal
- **Akses**: Web Dashboard (Browser di Komputer/Laptop).
- **Kebutuhan**:
  - Mengelola bank soal sesuai mata pelajaran dan tingkat kelas (teks, gambar, opsi).
  - Menentukan kunci jawaban dan bobot nilai butir soal.
  - Membuat jadwal dan paket ujian.
  - Mengoreksi soal esai (jika ada) dan merekapitulasi perolehan nilai siswa.

### C. Administrator / Proktor (Pengawas)
- **Akses**: Web Dashboard (Browser di Komputer/Laptop).
- **Kebutuhan**:
  - Mengelola data master (data siswa, kelas, guru, akun login).
  - Membuka dan merilis sesi ujian (termasuk token ujian jika diaktifkan).
  - Melakukan *Live Monitoring* kehadiran dan progres siswa saat ujian berjalan di jaringan LAN.
  - Melakukan *reset login* peserta jika ada siswa yang HP-nya kehabisan baterai, restart, atau berganti perangkat.
  - Melakukan backup database lokal secara berkala.

---

## 3. BAGAIMANA APLIKASI DIGUNAKAN (ALUR OPERASIONAL SEKOLAH)

1. **Persiapan di Komputer Windows (Server)**:
   - Komputer Windows dihidupkan dan terhubung ke kabel LAN / Router Wi-Fi ruang ujian.
   - Layanan Database MySQL dan CBT Server dinyalakan.
   - Proktor membuka Web Dashboard di browser komputer tersebut (misal: `http://localhost:8000` atau IP LAN server `http://192.168.1.50:8000`).
   - Proktor mengonfirmasi jadwal ujian aktif dan merilis token sesi.
2. **Persiapan di Perangkat Android Siswa**:
   - HP siswa terhubung ke Wi-Fi lokal ujian (SSID lokal sekolah tanpa akses internet).
   - Siswa membuka aplikasi Android CBT.
   - Jika belum terkonfigurasi, siswa menginput IP Server yang diumumkan pengawas di papan tulis (misal: `192.168.1.50:8000`).
   - Siswa login menggunakan kredensial masing-masing.
3. **Pelaksanaan & Pengawasan**:
   - Siswa memulai ujian, soal tampil, timer countdown berjalan.
   - Guru/Proktor memantau layar *Live Monitoring* di Web Dashboard.
4. **Penutupan Sesi**:
   - Siswa menekan tombol "Selesai" atau waktu otomatis habis.
   - Guru mengunduh rekap nilai ujian dalam format Excel/PDF langsung dari Web Dashboard.

---

## 4. BAGAIMANA UJIAN BERLANGSUNG (EXAM LIFECYCLE)

Pelaksanaan ujian mengikuti siklus hidup yang ketat dan aman:

```text
[1. PRA-UJIAN]
Siswa Login ──► Masukkan Token Sesi (jika ada) ──► Konfirmasi Identitas & Durasi
                                                          │
                                                          ▼
[2. MEMULAI UJIAN]
Klik "Mulai" ──► Server catat started_at & ends_at ──► Kirim Soal (KUNCI DIISOLASI)
                                                          │
                                                          ▼
[3. PENGERJAAN & AUTOSAVE]
Pilih Opsi ──► Simpan Local State HP ──► Kirim ke API ──► Validasi & Upsert ke DB
(Jika putus: Masuk antrean lokal ──► Kirim saat koneksi pulih)
                                                          │
                                                          ▼
[4. KONTROL WAKTU (TIMER)]
Server Time sebagai acuan ──► Jika NOW() >= ends_at ──► Lock Attempt & Force Submit
                                                          │
                                                          ▼
[5. PENYELESAIAN (SUBMIT)]
Klik "Selesai" (Idempotent) ──► Server Lock Attempt ──► Auto-Grading (Pilihan Ganda)
                                                          │
                                                          ▼
[6. PASCA-UJIAN]
Layar Android Terkunci (Selesai) ──► Nilai/Hasil dicatat di Server
```

---

## 5. BAGAIMANA DATA DISIMPAN (DATA STORAGE ARCHITECTURE)

Sistem menerapkan model penyimpanan data bertingkat (*tiered storage*) untuk menjamin integritas dan keamanan:

1. **Penyimpanan Permanen (Server-Side MySQL/MariaDB)**:
   - Menjadi **Satu-satunya Sumber Kebenaran (Single Source of Truth)**.
   - Menyimpan kredensial ter-hash (Bcrypt/Argon2), data master siswa/guru, bank soal lengkap beserta kunci jawaban (`question_options.is_correct`), jadwal ujian, serta log audit.
   - Merekam sesi pengerjaan pada tabel `exam_attempts` (`started_at`, `ends_at`, `status`, `total_score`).
   - Merekam jawaban aktual per butir soal pada tabel `exam_answers` dengan constraint unik `(attempt_id, question_id)` yang mendukung operasi upsert atomik aman tanpa duplikasi baris.
2. **Penyimpanan Sementara di Android (Client-Side Local Storage)**:
   - Menggunakan local storage perangkat (SQLite lokal / SharedPreferences terenkripsi).
   - Menyimpan:
     - Konfigurasi IP & Port Server.
     - Token otentikasi sesi (JWT/Session token).
     - State paket soal yang sedang dikerjakan.
     - State jawaban peserta saat ini (*instant local cache* agar pergantian nomor soal bebas jeda).
     - *Local Offline Queue*: Antrean penampung jawaban yang belum berhasil terkirim ke server akibat gangguan sinyal Wi-Fi. Jawaban tidak akan dihapus dari antrean ini sebelum server merespon konfirmasi sukses (`200 OK / sync confirmed`).

---

## 6. BAGAIMANA ANDROID BERKOMUNIKASI DENGAN SERVER (COMMUNICATION PROTOCOL)

1. **Protokol Standar REST API (HTTP/JSON)**:
   - Komunikasi berbasis arsitektur stateless RESTful API melalui jaringan Wi-Fi lokal menggunakan format payload JSON yang ringkas (*lightweight*).
   - Penggunaan header standar: `Authorization: Bearer <token>` dan `Content-Type: application/json`.
2. **Strategi Efisiensi Jaringan (Anti-Overhead)**:
   - **Download Sekali di Awal**: Paket soal diunduh secara utuh saat tombol "Mulai" ditekan, sehingga siswa tidak perlu melakukan request unduh soal berulang kali setiap berganti nomor soal.
   - **Asynchronous Throttled Autosave**: Saat siswa memilih opsi jawaban, request penyimpanan dikirim secara asinkron di latar belakang tanpa membekukan layar (*non-blocking UI*). Terdapat jeda debounce (300ms) untuk mencegah banjir request jika siswa mengeklik opsi secara cepat berkali-kali.
   - **Heartbeat Ringan**: Android mengirim sinyal status berkala (misal tiap 30-60 detik) dengan payload minimal untuk menyinkronkan jam sisa dan memperbarui status kehadiran siswa pada dashboard pengawas.
3. **Ketahanan Terhadap Fluktuasi Jaringan Wi-Fi (Fault Tolerance)**:
   - Jika router Wi-Fi mengalami overload atau sinyal sementara putus, aplikasi Android tidak menampilkan pesan *error crash*, melainkan mengaktifkan indikator lokal "Menyimpan di memori HP...".
   - Begitu koneksi Wi-Fi tersambung kembali, worker latar belakang langsung menguras antrean dan melakukan sinkronisasi otomatis (*auto-flush queue*) ke endpoint server.
