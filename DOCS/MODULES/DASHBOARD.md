# MODUL: WEB DASHBOARD OVERVIEW (TAHAP 4B)
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT)

## 1. Tanggung Jawab Modul
- Menyajikan ringkasan metrik operasional dan data analitik aktual untuk antarmuka Web Admin dan Web Guru.
- Menghubungkan tampilan web dashboard dengan backend existing tanpa membuat query buatan/angka fiktif (*Zero Hardcoded Statistics*).
- Menegakkan pemisahan otorisasi ketat (*Role-Scoped Dashboard*):
  - **Admin Dashboard**: Menampilkan metrik global institusi sekolah.
  - **Guru Dashboard**: Menampilkan metrik yang dibatasi khusus pada kepemilikan data guru login (`created_by`).
- Menangani kondisi pemuatan data, status kosong (*empty states*), dan penanganan error secara aman (*graceful error handling* tanpa kebocoran stack trace).
- Mempertahankan strategi aset mandiri lokal (*Zero CDN / Offline LAN*).

---

## 2. Admin Dashboard Overview
Diakses via: `GET /admin/dashboard` (Hak akses: `role:admin`).

### A. Metrik Statistik (8 Kartu Metrik)
1. **Total Peserta**: Total siswa terdaftar di database (`Student::count()`).
2. **Total Guru**: Total pengajar dan pengawas terdaftar (`Teacher::count()`).
3. **Total Kelas**: Total rombel/kelas aktif sekolah (`Classes::count()`).
4. **Mata Pelajaran**: Total mata pelajaran terdaftar (`Subject::count()`).
5. **Bank Soal**: Total butir soal ujian (`Question::count()`).
6. **Total Paket Ujian**: Total sesi ujian dibuat (`Exam::count()`).
7. **Ujian Aktif**: Paket ujian berstatus `published` atau `active` (`Exam::whereIn('status', ['published', 'active'])->count()`).
8. **Sedang Mengerjakan**: Jumlah attempt pengerjaan siswa yang sedang berlangsung di LAN (`ExamAttempt::where('status', 'in_progress')->count()`).

### B. Bagian Ringkasan Data Aktual
1. **Hasil Ujian Terbaru**: 5 rekaman hasil pengerjaan siswa terbaru beserta nama peserta, judul ujian/mapel, nilai akhir, dan waktu selesai (`Result::with(['student.user', 'exam.subject'])->latest('id')->take(5)`).
2. **Aktivitas Sistem Terbaru**: 5 jejak audit aktivitas server terbaru (`ActivityLog::with('user')->latest('id')->take(5)`).

---

## 3. Guru Dashboard Overview
Diakses via: `GET /guru/dashboard` (Hak akses: `role:teacher,guru`).

### A. Metrik Statistik Khusus Guru (4 Kartu Metrik)
1. **Bank Soal Saya**: Jumlah butir soal yang dibuat oleh guru login (`Question::where('created_by', $user->id)->count()`).
2. **Paket Ujian Saya**: Jumlah paket ujian yang disusun oleh guru login (`Exam::where('created_by', $user->id)->count()`).
3. **Ujian Aktif**: Jumlah sesi ujian aktif/siap dikerjakan milik guru login (`Exam::where('created_by', $user->id)->whereIn('status', ['published', 'active'])->count()`).
4. **Peserta Terdaftar**: Jumlah siswa terdaftar pada paket ujian milik guru login (`ExamParticipant::whereHas('exam', fn($q) => $q->where('created_by', $user->id))->distinct('student_id')->count('student_id')`).

### B. Ringkasan Data Guru
1. **Hasil Ujian Terbaru (Ujian Saya)**: 5 nilai siswa terbaru yang mengerjakan paket ujian milik guru login.

---

## 4. Keamanan & Penanganan Error
- **Role Isolation**: Guru diblokir total dari `/admin/dashboard` (`HTTP 403 Forbidden`). Siswa diblokir dari seluruh rute web dashboard.
- **Graceful Failure**: Jika terjadi galat pemanggilan data atau gangguan koneksi database, controller menangkap exception melalui blok `try/catch`, mencatat detail error ke log server internal, dan mengembalikan pesan yang aman dan ramah pengguna (`$errorMessage`) dengan metrik fallback default tanpa membocorkan trace query SQL atau kredensial.
- **Empty State**: Apabila tabel belum memiliki rekaman nilai atau aktivitas, disajikan tampilan placeholder informatif ("Belum ada rekaman...") menggantikan tabel kosong.
- **Kerahasiaan Data**: Password hash, API tokens, `APP_KEY`, dan environment variables tidak pernah diteruskan ke view HTML.

---

## 5. Strategi Aset Offline LAN
- Seluruh antarmuka metrik, grid, card, badge status, dan data table menggunakan styling dari `public/css/cbt-offline.css`.
- Tidak ada panggilan ke layanan CDN online eksternal, memastikan operasional 100% stabil di laboratorium komputer tanpa akses internet.
