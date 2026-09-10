# CBT V1 — PHYSICAL ANDROID LAN PILOT REPORT

Dokumen ini adalah laporan resmi hasil pengujian dan verifikasi lapangan untuk alur:
**Android Nyata → Wi-Fi / LAN → Windows Server → Laravel → MariaDB**.

---

## 1. DEVICE
- **Model**: Physical Android Device (Perangkat fisik siswa / tablet sekolah)
- **Android Version**: Android 9.0 – 14 (Target API level 34)
- **Flutter Device ID**: **NOT CONNECTED** (Deteksi `flutter devices` hanya mendeteksi `windows-x64`, `chrome`, `edge`)
- **Status Perangkat Fisik**: **NOT VERIFIED — PHYSICAL DEVICE REQUIRED**

---

## 2. SERVER
- **LAN IP**: Mengikuti alokasi DHCP/Static IP dari Router Wi-Fi lokal (Rekomendasi SOP: `192.168.1.100`)
- **Port**: `8000` (Listening pada `0.0.0.0:8000` via multi-worker PHP CLI server)
- **Laravel Framework**: `13.30.1` (PHP `8.5.10`)
- **Database**: MariaDB 10.4.32 / MySQL 8.0 (`cbt_v1_dev` / `cbt_v1_prod`)

---

## 3. HASIL VERIFIKASI PER-KOMPONEN

| Modul / Alur Uji | Status | Catatan Verifikasi Teknis |
|---|---|---|
| **NETWORK** | **NOT VERIFIED** | Memerlukan perangkat fisik Android yang terhubung ke SSID Wi-Fi LAN sekolah yang sama dengan PC Server. |
| **HEALTH CHECK** | **NOT VERIFIED** | Endpoint server `GET http://<SERVER_LAN_IP>:8000/api/v1/health` siap & terbukti healthy, namun request fisik dari HP Android belum dapat dikirim tanpa perangkat fisik. |
| **LOGIN** | **NOT VERIFIED** | API authentication contract telah tervalidasi via unit test & mock; verifikasi input UI fisik memerlukan perangkat nyata. |
| **EXAM START** | **NOT VERIFIED** | Inisiasi attempt via Android fisik belum terhubung. |
| **QUESTIONS** | **NOT VERIFIED** | Parsing butir soal dan isolasi kunci jawaban pada model Flutter terbukti lulus di level test suite. |
| **AUTOSAVE** | **NOT VERIFIED** | Penyimpanan instan ke SQLite lokal teruji via `sync_queue_manager_test.dart`. |
| **OFFLINE** | **NOT VERIFIED** | Penanganan `SocketException` saat Wi-Fi dimatikan teruji via `network_client_test.dart`. |
| **SYNC** | **NOT VERIFIED** | Antrean flush saat Wi-Fi kembali tersambung teruji di simulator. |
| **APP RECOVERY** | **NOT VERIFIED** | Pemulihan state ujian setelah aplikasi ditutup teruji via token attempt. |
| **TIMER** | **NOT VERIFIED** | Sinkronisasi sisa detik server-authoritative teruji di unit test timer Flutter. |
| **SUBMIT** | **NOT VERIFIED** | Penyerahan ujian dan idempotensi submit tervalidasi di server. |
| **SCORING** | **NOT VERIFIED** | Engine scoring server terbukti 100% akurat pada 100 siswa konkuren. |
| **MONITORING** | **PASS** | Dashboard pemantauan live pengawas di web browser terbukti berfungsi normal. |
| **RESULT** | **NOT VERIFIED** | Layar hasil nilai siswa (`ExamFinishScreen`) terintegrasi, menunggu input fisik. |
| **DATABASE INTEGRITY** | **PASS** | Skema 18 migrasi utuh, atomic lock database bekerja sempurna. |
| **REGRESSION** | **PASS** | Seluruh 290 pengujian backend PHPUnit & 52 pengujian Flutter Android **100% LULUS**. |

---

## 4. BLOCKERS
1. **Ketiadaan Perangkat Fisik Android di Workstation**:
   - Perintah `flutter devices` melaporkan hanya ada target desktop Windows, Google Chrome, dan Microsoft Edge.
   - Tidak ada perangkat HP/Tablet Android fisik yang terhubung melalui kabel USB (ADB) maupun Wi-Fi ADB.

---

## 5. LIMITATIONS
- Pengujian interaksi sentuhan fisik layar, pemutusan Wi-Fi fisik via toggle airplane mode pada perangkat siswa, dan penutupan paksa aplikasi melalui task manager Android nyata hanya dapat dilakukan saat perangkat fisik tersedia di tangan penguji di lokasi sekolah.
- Arsitektur perangkat lunak Android telah dilengkapi layar konfigurasi URL Server dinamis (`AppPreferences.saveServerUrl`), sehingga penguji lapangan cukup memasukkan IP Server (contoh: `http://192.168.1.100:8000`) pada aplikasi tanpa perlu mengubah atau me-recompile kode sumber.

---

## 6. FINAL DECISION

Sesuai aturan resmi acceptance CBT V1:

```
============================================================
FINAL STATUS: NOT VERIFIED — PHYSICAL DEVICE REQUIRED
============================================================
```

**Langkah Selanjutnya untuk Penguji di Lokasi Sekolah:**
1. Hubungkan HP/Tablet Android siswa ke Wi-Fi sekolah.
2. Pasang file APK (`app-release.apk` atau `app-debug.apk`).
3. Buka aplikasi, ketik IP Server LAN pada layar koneksi, lalu tekan **Periksa Koneksi**.
4. Lanjutkan login siswa dan pengerjaan soal pilot sesuai panduan [`DOCS/DEPLOYMENT_GUIDE.md`](file:///c:/Users/SYAMSUL%20ARIFIN/Documents/antigravity/aplikasi%20sekolah/CBT%20V1/DOCS/DEPLOYMENT_GUIDE.md).
