# MODUL: ANDROID CLIENT (FLUTTER) & ANTI-CHEAT
DOKUMENTASI KHUSUS MODUL (AI_RULES: MODULAR CONTEXT)

## 1. Tanggung Jawab Modul
- Dibangun menggunakan **Flutter (Dart)** untuk menghasilkan single standalone APK tanpa Google Play Store.
- Konfigurasi IP & Port Server Windows lokal secara dinamis (disimpan di `shared_preferences`).
- **Mekanisme Anti-Cheat & Lock Exam Mode**:
  - Mengaktifkan mode ujian / *Lock Task Mode* / *Screen Pinning* semaksimal mungkin sesuai kapabilitas Android.
  - Membatasi peserta keluar dari aplikasi atau membuka browser/aplikasi lain selama ujian berlangsung.
  - Mendeteksi dan mencatat event saat aplikasi kehilangan fokus atau berpindah ke background (`WidgetsBindingObserver` / `didChangeAppLifecycleState` -> `APP_BACKGROUNDED`, `WINDOW_FOCUS_LOST`).
  - **Prinsip Integritas Jawaban**: Kehilangan fokus **TIDAK MENGHAPUS** jawaban yang sudah tersimpan. Saat peserta kembali, attempt yang sama langsung dilanjutkan.
  - Mengirimkan event pelanggaran/hilang fokus ke server Laravel untuk dicatat ke `activity_logs`.
- **Keterbatasan Teknis Nyata (Technical Limitations)**:
  - Tidak mengklaim keamanan 100% pada semua perangkat Android.
  - Perilaku dipengaruhi oleh versi Android (Android 7 s.d 14+) dan kustomisasi vendor (MIUI/HyperOS, ColorOS, OneUI) seperti gesture bar, floating window, split screen, dan edge panel yang memerlukan penyesuaian khusus.
- **Connection & Device Recovery**:
  - Jika Wi-Fi putus: menyimpan jawaban di memori lokal & *Local Offline Queue* (SQLite via `sqflite`).
  - Melakukan background retry asinkron saat koneksi kembali.
  - Tidak menghapus jawaban dari antrean lokal sebelum server Laravel mengirimkan konfirmasi sukses (`ACK / 200 OK`).
  - Jika HP mati / restart / ganti HP: login kembali akan memulihkan attempt aktif yang masih berlaku (`NOW() < ends_at`).

## 2. Penyimpanan Lokal di Flutter Android
- `shared_preferences`: IP Server, Port, Token Sanctum.
- `sqflite`: Cache paket soal, state jawaban saat ini, tabel *Local Offline Queue*.

## 3. Direct Dependencies
- REST API Server (Endpoint `/api/v1/health`, `/api/v1/auth/login`, `/api/v1/exams`, `/api/v1/attempts`)

## 4. Tahap 5A: Pondasi Proyek & Health Check
- **Lokasi Project Android**: `ANDROID/`
- **Environment Requirements**:
  - Flutter SDK: 3.47.2
  - Dart SDK: 3.13.2
  - Android SDK: `C:\Android\sdk` (API 36, build-tools 36.0.0, cmdline-tools latest)
  - Java: OpenJDK 25 (Android Studio JBR)
- **Cara Menjalankan Flutter**:
  ```bash
  cd ANDROID
  flutter run
  ```
- **Pondasi Base URL & Penggunaan LAN**:
  - Konfigurasi terpusat pada `lib/core/config/api_config.dart` (`ApiConfig`).
  - Nilai default development: `http://10.0.2.2:8000` (Android emulator) atau `http://127.0.0.1:8000` (Windows/testing).
  - Untuk jaringan LAN sekolah/lab, URL dapat diubah terpusat via `ApiConfig.setBaseUrl('http://<IP_SERVER_LAN>:8000')` atau melalui antarmuka layar diagnosa `HealthCheckScreen`.
  - Android Network Security Config (`android/app/src/main/res/xml/network_security_config.xml`) mengizinkan HTTP cleartext terbatas pada subnet private/lokal.
- **Health Check**:
  - Endpoint: `GET /api/v1/health`
  - Kontrak respon: `{"success": true, "message": "...", "data": {"status": "healthy", "api_version": "v1.0.0", "timestamp": "..."}}`
  - Status koneksi terkelola: `UNKNOWN`, `CHECKING`, `ONLINE`, `OFFLINE`, `ERROR`.
  - Network Client menggunakan standard library `dart:io` dan `dart:convert`.

## 5. Tahap 5B s.d 5H: Implementasi Lengkap Klien Siswa (Flutter)
- **Arsitektur Direktori**:
  - `lib/core/config/`: `api_config.dart` (Server LAN URL & Health Check), `app_preferences.dart` (Token Sanctum & Sesi).
  - `lib/core/network/`: `network_client.dart` & `http_client_adapter.dart` (GET, POST, PATCH, Bearer tokens, HTTP exception hierarchy).
  - `lib/core/database/`: `local_database.dart` (SQLite storage: `cached_attempts`, `cached_questions`, `local_answers`, `sync_queue`), `sync_queue_manager.dart` (Offline queue auto-sync ke `/api/v1/attempts/{id}/sync`).
  - `lib/features/auth/`: `auth_model.dart`, `auth_repository.dart`, `login_screen.dart` (Form login siswa, server url config, validasi).
  - `lib/features/exam/`:
    - `exam_model.dart`: `ExamListItem`, `QuestionOption`, `ExamQuestion`, `ExamAttemptSession`, `ExamSummary`.
    - `exam_repository.dart`: Fetch published exams, start attempt dengan token, submit single answer, batch sync answers, submit exam final.
    - `exam_list_screen.dart`: List ujian aktif/diterbitkan, modal konfirmasi token ujian, card countdown/status.
    - `exam_screen.dart`: Layar pengerjaan ujian interaktif dengan navigasi nomor soal (grid sheet), checkbox Ragu-ragu, auto-save asinkron, fallback offline SQLite.
    - `exam_finish_screen.dart`: Layar konfirmasi ringkasan jawaban dan serah terima ujian.
  - `lib/features/timer/`: `exam_timer_controller.dart` (Countdown real-time, format HH:MM:SS / MM:SS, warning state < 5 menit, auto-submit callback).
  - `lib/features/anti_cheat/`: `anti_cheat_service.dart`, `anti_cheat_observer.dart` (Lifecycle observer via `WidgetsBindingObserver` mendeteksi `APP_BACKGROUNDED` & `WINDOW_FOCUS_LOST` yang dikirim ke `POST /api/v1/activity-logs/event`).
