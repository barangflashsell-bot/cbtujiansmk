# MATRIKS ROLE & PERMISSION SISTEM CBT
TAHAP 1 — BLUEPRINT OTORISASI & HAK AKSES PENGGUNA

Dokumen ini mendefinisikan matriks izin (permissions) untuk ketiga peran utama dalam sistem: **ADMIN**, **GURU**, dan **PESERTA (SISWA)** sesuai prinsip keamanan **Aturan 15 AI_RULES.md** (Role ditentukan di server dari akun yang terautentikasi, bukan dari client).

---

## 1. DEFINISI PERAN (ROLE DEFINITION)

| Role | Platform Akses | Deskripsi Kewenangan |
| :--- | :--- | :--- |
| **ADMIN** | Web Dashboard | Memiliki kendali penuh (*Superuser*) atas seluruh data master, konfigurasi server, manajemen pengguna, jadwal ujian, reset sesi ujian siswa, rekapitulasi nilai, serta backup/restore database. |
| **GURU** | Web Dashboard | Bertanggung jawab atas konten akademik: pembuatan dan pengelolaan bank soal (mata pelajaran terkait), pembuatan paket ujian, verifikasi jawaban & penilaian esai, serta melihat rekap nilai kelas yang diampu. |
| **PESERTA** | Android APK | Peserta ujian yang hanya memiliki hak untuk login, mengunduh paket soal aktif miliknya, mengerjakan ujian, mengirim lembar jawaban, dan melihat hasil/nilai kelulusan jika diizinkan oleh kebijakan ujian sekolah. |

---

## 2. TABEL MATRIKS PERMISSION LENGKAP

Keterangan simbol:
- ✅ : Diizinkan penuh (*Allowed*)
- ⚠️ : Diizinkan terbatas sesuai kepemilikan data / konfigurasi ujian (*Conditional / Owned Data Only*)
- ❌ : Dilarang (*Denied*)

| Kategori Modul | Fitur / Tindakan (*Action*) | ADMIN | GURU | PESERTA |
| :--- | :--- | :---: | :---: | :---: |
| **Autentikasi & Profil** | Login ke Web Dashboard | ✅ | ✅ | ❌ |
| | Login ke Android APK | ❌ | ❌ | ✅ |
| | Mengubah Password Sendiri | ✅ | ✅ | ⚠️ (Jika dibuka) |
| | Reset Password Pengguna Lain | ✅ | ❌ | ❌ |
| **Master Data** | Kelola Data Sekolah & Tahun Ajaran | ✅ | ❌ | ❌ |
| | Kelola Data Kelas & Rombel | ✅ | ❌ | ❌ |
| | Kelola Data Siswa / Akun Peserta | ✅ | ❌ | ❌ |
| | Kelola Data Guru & Akun Guru | ✅ | ❌ | ❌ |
| | Kelola Data Mata Pelajaran | ✅ | ❌ | ❌ |
| **Bank Soal** | Buat / Edit / Hapus Bank Soal | ✅ | ⚠️ (Mata pelajaran sendiri) | ❌ |
| | Input Butir Soal & Kunci Jawaban | ✅ | ⚠️ (Mata pelajaran sendiri) | ❌ |
| | Upload Media Soal (Gambar/Audio) | ✅ | ⚠️ (Mata pelajaran sendiri) | ❌ |
| | Import / Export Bank Soal (Excel) | ✅ | ⚠️ (Mata pelajaran sendiri) | ❌ |
| **Ujian (Exams)** | Buat / Edit Paket & Jadwal Ujian | ✅ | ⚠️ (Ujian yang dibuatnya) | ❌ |
| | Terbitkan / Rilis Token Ujian | ✅ | ⚠️ (Sebagai pengawas/proktor) | ❌ |
| | Mulai Ujian (Start Exam) | ❌ | ❌ | ✅ (Sesuai jadwal) |
| | Autosave Jawaban Siswa | ❌ | ❌ | ✅ (Attempt miliknya) |
| | Submit Ujian (Selesai Pengerjaan) | ❌ | ❌ | ✅ (Attempt miliknya) |
| | Force Submit Siswa yang Habis Waktu | ✅ (Otomatis Server) | ❌ | ❌ |
| **Pengawasan (Monitoring)**| Pantau Live Status Siswa di LAN | ✅ | ⚠️ (Hanya kelas/mapelnya) | ❌ |
| | Reset Sesi Ujian Siswa (Ganti HP) | ✅ | ⚠️ (Jika diberi wewenang) | ❌ |
| | Buka Kunci Siswa Terblokir | ✅ | ❌ | ❌ |
| **Penilaian (Scoring)** | Lihat Kunci Jawaban | ✅ | ⚠️ (Soal miliknya) | ❌ (DILARANG KERAS) |
| | Auto-Grading Pilihan Ganda | ✅ (Sistem) | ✅ (Sistem) | ❌ |
| | Koreksi & Input Nilai Esai | ✅ | ⚠️ (Mata pelajaran sendiri) | ❌ |
| | Publikasi Nilai ke Peserta | ✅ | ⚠️ (Persetujuan Admin) | ❌ |
| | Melihat Nilai Akhir / Skor | ✅ | ✅ | ⚠️ (Jika disetujui tampil) |
| **Laporan (Reports)** | Cetak / Export Rekap Nilai (Excel/PDF)| ✅ | ⚠️ (Kelas yang diampu) | ❌ |
| | Lihat Analisis Butir Soal | ✅ | ⚠️ (Soal miliknya) | ❌ |
| **Sistem & Server** | Konfigurasi IP & Port Server Lokal | ✅ | ❌ | ❌ |
| | Backup & Restore Database Lokal | ✅ | ❌ | ❌ |
| | Audit Log Aktivitas Sistem | ✅ | ❌ | ❌ |

---

## 3. ATURAN PENEGAKAN KEAMANAN PERMISSION (SECURITY RULES)

Sesuai **Aturan 10, 11, 15, dan 17 AI_RULES.md**:

1. **Server-Side Enforcement**:
   Semua pemeriksaan hak akses (*permission check*) dieksekusi secara wajib pada middleware REST API di server. Sistem **TIDAK PERNAH** mempercayai klaim peran (*role claim*) yang dikirim langsung dari request client Android atau Browser tanpa validasi token autentikasi kriptografis.
2. **Isolasi Penuh Kunci Jawaban**:
   Endpoint API yang diakses oleh Android peserta (`/api/student/*`) secara ketat memblokir atribut kunci jawaban (`is_correct`, `answer_key`). Hanya endpoint Admin/Guru terautentikasi yang dapat mengakses informasi kunci jawaban.
3. **Validasi Kepemilikan Sesi (Attempt Ownership)**:
   Saat peserta melakukan *autosave* atau *submit*, server memverifikasi bahwa `attempt_id` yang dikirim benar-benar milik `student_id` yang sedang terautentikasi pada token login tersebut, mencegah manipulasi jawaban antar peserta.
