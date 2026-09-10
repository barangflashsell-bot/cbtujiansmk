# STRUKTUR MENU APLIKASI CBT
TAHAP 1 — BLUEPRINT NAVIGASI & STRUKTUR MENU

Dokumen ini merinci rancangan navigasi dan fungsi setiap menu pada antarmuka Web Dashboard (Admin & Guru) serta aplikasi Android APK (Peserta).

---

## 1. STRUKTUR MENU ADMIN (WEB DASHBOARD)

Admin memiliki akses penuh terhadap manajemen data, ujian, dan pemeliharaan server.

| Menu | Submenu / Aksi Utama | Fungsi & Kegunaan |
| :--- | :--- | :--- |
| **1. Dashboard** | Ringkasan Sistem | Menampilkan ringkasan statistik (total siswa, total guru, bank soal, jadwal ujian hari ini, status konektivitas server & database). |
| **2. Peserta** | Daftar Siswa, Tambah, Import Excel, Reset Password | Manajemen data master siswa, kartu peserta ujian, filter per kelas/tingkat, dan pemulihan password akun siswa. |
| **3. Guru** | Daftar Guru, Tambah Akun Guru | Manajemen data guru, penetapan mata pelajaran yang diampu, dan pembuatan akun login guru. |
| **4. Kelas** | Daftar Kelas & Rombel, Kenaikan Kelas | Manajemen nama kelas (misal: 7A, 8B, 9C), penentuan jenjang tingkat, dan relasi rombongan belajar. |
| **5. Mata Pelajaran** | Daftar Mapel, Kode Mapel | Master data mata pelajaran sekolah (contoh: Matematika, Bahasa Indonesia, IPA, IPS). |
| **6. Bank Soal** | Daftar Seluruh Bank Soal, Otorisasi | Meninjau seluruh paket soal dari semua guru, kontrol kelengkapan butir soal, dan pengelolaan media pendukung. |
| **7. Ujian** | Jadwal Sesi Ujian, Pembuatan Paket | Pengaturan jadwal pelaksanaan ujian (tanggal, jam mulai, durasi menit, sistem pengacakan soal/opsi, dan toleransi keterlambatan). |
| **8. Peserta Ujian** | Alokasi Kelas/Siswa ke Sesi Ujian | Menentukan dan menugaskan siswa/kelas mana saja yang berhak mengikuti sesi ujian tertentu. |
| **9. Monitoring** | Live Proctoring, Reset Login Siswa | Pemantauan langsung status pengerjaan siswa di jaringan LAN secara real-time (aktif, putus koneksi, selesai), serta fungsi *reset login* jika HP siswa mati/restart. |
| **10. Hasil** | Hasil per Ujian, Koreksi Esai, Nilai Akhir | Meninjau rekaman attempt siswa, status penilaian otomatis (pilihan ganda), dan input skor akhir. |
| **11. Laporan** | Rekapitulasi Nilai, Cetak Nilai, Analisis Butir | Ekspor data nilai per kelas/sesi dalam format Excel/PDF dan analisis tingkat kesulitan butir soal. |
| **12. Backup** | Backup Database, Restore Database | Pembuatan berkas cadangan database MySQL lokal secara berkala dan pemulihan data sesuai **Aturan 36 AI_RULES.md**. |
| **13. Pengaturan** | Identitas Sekolah, Sesi Aktif, Token Ujian | Pengaturan nama sekolah, logo, tahun ajaran aktif, dan pengaturan waktu rilis token proktor. |

---

## 2. STRUKTUR MENU GURU (WEB DASHBOARD)

Guru berfokus pada konten pedagogis dan evaluasi kelas binaannya.

| Menu | Submenu / Aksi Utama | Fungsi & Kegunaan |
| :--- | :--- | :--- |
| **1. Dashboard** | Ringkasan Guru | Menampilkan total bank soal buatan sendiri, jadwal ujian terdekat untuk mapelnya, dan notifikasi soal esai yang perlu dikoreksi. |
| **2. Bank Soal** | Buat Bank Soal, Input Butir Soal, Opsi & Kunci | Pembuatan butir soal (Pilihan Ganda, Esai), upload gambar pendukung, penentuan bobot skor, dan kunci jawaban. |
| **3. Ujian** | Jadwal Ujian Mapel | Mengajukan atau melihat paket ujian yang menggunakan bank soal miliknya. |
| **4. Peserta** | Daftar Siswa (Read-Only) | Melihat daftar siswa di kelas yang diajarnya untuk pengecekan kelengkapan data peserta. |
| **5. Hasil** | Koreksi Esai, Rekap Nilai Siswa | Mengoreksi jawaban esai siswa, memberikan nilai esai, dan melihat rekapitulasi nilai akhir mata pelajarannya. |

---

## 3. STRUKTUR MENU PESERTA (ANDROID APK)

Aplikasi peserta didesain ringkas, fokus ujian, dan tahan terhadap gangguan koneksi Wi-Fi lokal.

| Menu | Komponen Utama | Fungsi & Kegunaan |
| :--- | :--- | :--- |
| **1. Dashboard** | Beranda, Kartu Identitas Siswa | Menampilkan salam pembuka, data siswa (Nama, NIS, Kelas), status koneksi ke server, dan banner jadwal ujian aktif hari ini. |
| **2. Ujian** | Daftar Ujian Aktif, Input Token, Lembar Soal | Menampilkan ujian yang sedang berlangsung dan berhak diikuti siswa. Menyediakan input token ujian, tombol "Mulai Ujian", lembar soal interaktif, dan tombol "Selesai". |
| **3. Riwayat** | Riwayat Ujian Terdahulu | Menampilkan daftar ujian yang telah diselesaikan beserta status penyelesaiannya (dan nilai akhir jika sekolah mengizinkan publikasi nilai ke siswa). |
| **4. Profil** | Informasi Akun, Ganti Password | Menampilkan identitas peserta dan fitur perubahan password mandiri (jika diizinkan oleh sistem). |
| **5. Pengaturan Server** | Input Server IP, Port, Tes Koneksi (*Ping*) | Fitur krusial untuk lingkungan LAN Offline (**Aturan 29 AI_RULES.md**). Siswa/proktor dapat mengubah alamat IP server (contoh: `http://192.168.1.100:8000`) dan menguji koneksi (*Ping*) langsung dari HP. |
| **6. Logout** | Keluar Akun | Mengakhiri sesi login pada perangkat HP dengan aman (hanya diizinkan jika sedang tidak berada di tengah ujian aktif). |

---

## 4. EVALUASI KEBERADAAN MENU (APAKAH ADA YANG PERLU DIHAPUS?)

**Kesimpulan Evaluasi**:
**TIDAK ADA MENU YANG PERLU DIHAPUS.**

Seluruh menu yang Anda rancang sudah sangat tepat, memiliki tujuan spesifik, dan saling melengkapi:
1. **Pemisahan `Ujian` dan `Peserta Ujian` pada Admin**:
   - `Ujian`: Mengatur *kapan*, *mata pelajaran apa*, *durasi berapa menit*, dan *token apa*.
   - `Peserta Ujian`: Mengatur *siapa saja siswa/kelas yang berhak ikut* pada ujian tersebut. Pemisahan ini adalah standar terbaik dalam sistem CBT agar paket ujian dapat digunakan kembali untuk sesi/kelas yang berbeda tanpa menduplikasi paket ujian.
2. **Keberadaan `Pengaturan Server` pada Android**:
   - Menu ini sangat penting karena pada jaringan Wi-Fi lokal, IP server Windows dapat berubah (jika menggunakan DHCP) atau berbeda antar ruang laboratorium. Fitur ini menjamin fleksibilitas konektivitas tanpa perlu kompilasi ulang file APK.
3. **Pemisahan `Hasil` dan `Laporan` pada Admin**:
   - `Hasil`: Bersifat operasional teknis (status attempt per siswa, submit, auto-grading, koreksi esai).
   - `Laporan`: Bersifat administratif resmi (rekapitulasi nilai per kelas, ekspor format Dapodik/Rapor, analisis butir soal).
