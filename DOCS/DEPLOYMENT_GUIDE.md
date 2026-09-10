# PANDUAN DEPLOYMENT CBT V1 (ON-PREMISE WINDOWS & LAN)

Dokumen ini adalah Standard Operating Procedure (SOP) resmi untuk deployment dan pemeliharaan CBT V1 pada infrastruktur sekolah berbasis **Windows PC Server** dan jaringan **Local Area Network (LAN / Wi-Fi)**.

---

## 1. ARSITEKTUR INFRASTRUKTUR

```
                       [ PC SERVER (WINDOWS 10/11) ]
                                     |
               +---------------------+---------------------+
               |                     |                     |
     [ XAMPP MariaDB 3306 ]   [ Laravel Backend ]   [ Web Admin / Guru ]
               |               Port 8000 (CLI/Web)   http://192.168.1.100:8000
               +---------------------+---------------------+
                                     |
                          [ ROUTER / ACCESS POINT ]
                           (Subnet 192.168.1.0/24)
                                     |
        +----------------------------+----------------------------+
        |                            |                            |
  [ Tablet Siswa 1 ]           [ HP Siswa 2 ]             [ HP Siswa 100+ ]
   (CBT Android App)            (CBT Android App)          (CBT Android App)
```

- **Server Source of Truth**: Seluruh validasi, timer, attempt status, dan scoring diproses secara sentral di server.
- **Client Android**: Offline-first autosave (SQLite lokal), background queue sync, deteksi kecurangan (anti-cheat listener), dan tidak pernah menerima kunci jawaban.

---

## 2. PRASYARAT SERVER & PERANGKAT LUNAK

1. **Sistem Operasi**: Windows 10 / 11 64-bit (Minimal RAM 8 GB, Disarankan 16 GB untuk >100 siswa).
2. **PHP**: Versi 8.2 atau lebih baru.
   - Ekstensi wajib: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `curl`.
3. **Database**: MariaDB 10.4+ / MySQL 8.0+ (tersedia via XAMPP).
4. **Composer**: Versi 2.x.
5. **Node.js**: Versi 18+ (untuk kompilasi asset frontend jika diperlukan).
6. **Jaringan**: Router Wi-Fi / Switch Gigabit dengan kapasitas DHCP minimal 150 alamat IP aktif.

---

## 3. INSTALASI & KONFIGURASI DATABASE

### A. Konfigurasi MariaDB (`my.ini`)
Buka `C:\xampp\mysql\bin\my.ini`, sesuaikan konfigurasi agar siap menangani 100+ koneksi simultan:
```ini
max_connections = 300
innodb_buffer_pool_size = 512M
innodb_log_file_size = 128M
wait_timeout = 300
interactive_timeout = 300
```
Restart service MariaDB dari XAMPP Control Panel.

### B. Buat Basis Data Produksi
Melalui phpMyAdmin atau MySQL CLI:
```sql
CREATE DATABASE cbt_v1_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## 4. KONFIGURASI APLIKASI SERVER (LARAVEL)

1. Masuk ke direktori `SERVER`.
2. Salin berkas konfigurasi produksi:
   ```powershell
   copy .env.production.example .env
   ```
3. Generate application encryption key:
   ```powershell
   php artisan key:generate
   ```
4. Sesuaikan kredensial `.env`:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=http://<IP_LAN_SERVER>:8000` (contoh: `http://192.168.1.100:8000`)
   - `DB_DATABASE=cbt_v1_prod`
   - `PHP_CLI_SERVER_WORKERS=16`
5. Jalankan migrasi database resmi:
   ```powershell
   php artisan migrate --force
   ```
6. Buat tautan storage dan optimasi cache:
   ```powershell
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

---

## 5. KONFIGURASI JARINGAN & FIREWALL WINDOWS

1. **Atur IP Statis pada PC Server**:
   - Buka `Network & Internet Settings` -> `Adapter Options`.
   - Pilih koneksi Ethernet/Wi-Fi -> Properties -> Internet Protocol Version 4 (TCP/IPv4).
   - Atur:
     - IP Address: `192.168.1.100` (sesuaikan konfigurasi router)
     - Subnet Mask: `255.255.255.0`
     - Default Gateway: `192.168.1.1`
2. **Buka Port 8000 pada Windows Defender Firewall**:
   Jalankan PowerShell sebagai Administrator:
   ```powershell
   New-NetFirewallRule -DisplayName "CBT Server HTTP 8000" -Direction Inbound -Protocol TCP -LocalPort 8000 -Action Allow
   ```

---

## 6. MENJALANKAN SERVER CBT

Untuk melayani siswa dalam jaringan LAN dengan konkurensi tinggi, jalankan server dengan multi-worker:
```powershell
$env:PHP_CLI_SERVER_WORKERS="16"
php artisan serve --host=0.0.0.0 --port=8000
```
Server kini dapat diakses oleh browser Admin/Guru di `http://localhost:8000` dan oleh tablet/HP siswa di `http://192.168.1.100:8000`.

---

## 7. DISTRIBUSI & KONFIGURASI APLIKASI SISWA (ANDROID)

1. **Konfigurasi URL Server**:
   Pada aplikasi Android (`ANDROID/lib/core/network/api_client.dart` atau konfigurasi environment), pastikan base URL mengarah ke IP LAN server:
   ```dart
   static const String baseUrl = 'http://192.168.1.100:8000/api/v1';
   ```
2. **Build APK Rilis**:
   ```powershell
   cd ANDROID
   flutter build apk --release
   ```
   File APK akan dihasilkan di: `ANDROID/build/app/outputs/flutter-apk/app-release.apk`.
3. **Distribusi ke Siswa**:
   - Salin APK ke flashdisk atau buat link unduhan lokal di server (misal: diletakkan di `public/download/cbt.apk`).
   - Pastikan perangkat Android terhubung ke Wi-Fi sekolah yang sama.
   - Buka APK dan instal pada perangkat siswa.

---

## 8. PROSEDUR BACKUP & DISASTER RECOVERY

Sistem CBT V1 dilengkapi artisan command khusus untuk snapshot berkala dan pemulihan cepat tanpa ketergantungan utility pihak ketiga.

### A. Pembuatan Snapshot Cadangan
Jalankan perintah berikut sebelum ujian dimulai, di sela sesi, atau setelah ujian:
```powershell
# Snapshot sebelum ujian
php artisan cbt:backup --type=pre_exam

# Snapshot setelah sesi ujian selesai
php artisan cbt:backup --type=post_exam

# Snapshot harian/otomatis
php artisan cbt:backup --type=daily
```
Berkas tersimpan di direktori `storage/app/backups/` lengkap dengan checksum SHA-256 dan log metadata.

### B. Pemulihan Basis Data (Disaster Recovery)
Jika terjadi insiden (misal: kesalahan konfigurasi atau kerusakan data operasional), lakukan pemulihan:
```powershell
# Memulihkan dari snapshot terbaru secara interaktif
php artisan cbt:restore

# Memulihkan dari file snapshot spesifik dengan bypass konfirmasi
php artisan cbt:restore cbt_backup_pre_exam_20260910_194958_e104121a.sql --force
```

### C. Penanganan Insiden Jaringan / Listrik Padam
1. **Listrik Server Padam Mendadak**:
   - Nyalakan kembali PC Server.
   - Start service MariaDB di XAMPP.
   - Jalankan `php artisan serve --host=0.0.0.0 --port=8000`.
   - Siswa dapat membuka kembali aplikasi Android mereka. Mekanisme resume token dan SQLite offline akan memulihkan ujian ke nomor soal terakhir tanpa kehilangan jawaban yang tersimpan.
2. **Wi-Fi Terputus di Tengah Ujian**:
   - Siswa tetap dapat melanjutkan pengerjaan soal di aplikasi Android karena jawaban tersimpan secara instan di database lokal (SQLite).
   - Antrean jawaban (`sync_queue`) akan otomatis tersinkronisasi ke server segera setelah koneksi Wi-Fi pulih kembali.
