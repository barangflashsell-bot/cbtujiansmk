# SYSTEM RULES — CBT PROJECT

# WAJIB DIIKUTI SEPANJANG PENGEMBANGAN

Dokumen ini adalah aturan utama untuk AI Agent yang mengembangkan project CBT.

Semua aturan di bawah ini bersifat WAJIB.

Jika terdapat konflik antara instruksi user dan aturan project, tanyakan kepada user sebelum melakukan perubahan yang berisiko.

---

# 1. IDENTITAS PROJECT

Project ini adalah:

**Aplikasi CBT (Computer Based Test)**

Arsitektur:

```text
WINDOWS COMPUTER
      │
      ├── CBT SERVER
      ├── DATABASE
      ├── REST API
      └── WEB ADMIN/GURU
             │
             │ Wi-Fi / LAN
             ↓
        ANDROID APK
          PESERTA
```

Internet TIDAK menjadi kebutuhan utama.

Server lokal Windows adalah target utama.

Android adalah client peserta.

---

# 2. ATURAN PALING PENTING

## JANGAN FULL SCAN PROJECT

Jangan membaca seluruh project hanya karena ada satu masalah.

Selalu gunakan:

```text
REQUEST
 ↓
IDENTIFY MODULE
 ↓
READ MODULE INDEX
 ↓
IDENTIFY RELATED FILE
 ↓
CHECK DIRECT DEPENDENCY
 ↓
EDIT
 ↓
TEST
```

Full scan hanya boleh dilakukan jika masalah memang lintas modul.

---

# 3. JANGAN MENEBak

Jika informasi tidak ditemukan:

JANGAN mengarang.

Jangan mengasumsikan:

* nama file
* nama class
* nama function
* nama route
* nama database
* nama API
* struktur folder
* dependency

Cari informasi yang relevan terlebih dahulu.

Jika tetap tidak ditemukan:

tulis:

```text
INFORMATION NOT FOUND
```

Kemudian minta keputusan user jika diperlukan.

---

# 4. JANGAN MEMBUAT FITUR TAMBAHAN

Jika user meminta:

"Perbaiki timer."

Jangan otomatis menambahkan:

* leaderboard
* notifikasi
* dark mode
* fitur baru
* sistem cheating detection
* fitur lain

kecuali diminta.

Fokus hanya pada permintaan.

---

# 5. JANGAN MENGUBAH MODUL LAIN

Jika pekerjaan berada di:

```text
TIMER
```

jangan mengubah:

```text
USERS
QUESTIONS
REPORTS
BACKUP
SETTINGS
```

kecuali memang ada dependency dan perubahan tersebut benar-benar diperlukan.

Jika harus mengubah modul lain:

jelaskan:

```text
CROSS-MODULE CHANGE

Module:
TIMER

Affected Module:
ATTEMPTS

Reason:
...
```

---

# 6. JANGAN MEMBUAT FILE DUPLIKAT

Sebelum membuat file baru:

1. Cari file dengan fungsi yang sama.
2. Periksa struktur modul.
3. Pastikan tidak ada implementasi yang sudah tersedia.

Jangan membuat:

```text
timer_new
timer_final
timer_final2
timer_fix
timer_v2
```

tanpa alasan arsitektur yang jelas.

---

# 7. JANGAN MENGHAPUS FILE SEMBARANGAN

Dilarang menghapus:

* database
* migration
* konfigurasi
* source code
* dokumentasi
* test

kecuali user meminta atau file benar-benar obsolete dan sudah dikonfirmasi.

Sebelum penghapusan:

```text
FILE TO DELETE:
...

REASON:
...

DEPENDENCIES:
...

RISK:
...
```

---

# 8. JANGAN MELAKUKAN REFACTOR BESAR

Jika user meminta bug fix:

Lakukan perubahan sekecil mungkin.

Jangan sekaligus:

* mengganti framework
* mengganti database
* mengubah arsitektur
* memindahkan banyak file
* mengganti API

kecuali user meminta.

---

# 9. DATABASE ADALAH DATA KRITIS

Jangan menghapus atau mengubah data database secara destruktif.

Dilarang melakukan:

```text
DROP DATABASE
DROP TABLE
TRUNCATE
```

kecuali user secara eksplisit memintanya dan memahami konsekuensinya.

Gunakan migration untuk perubahan schema.

---

# 10. SERVER ADALAH SOURCE OF TRUTH

Untuk data ujian:

Server menentukan:

* waktu
* status ujian
* attempt
* jawaban final
* nilai
* status submit
* validitas peserta

Android tidak boleh dianggap sebagai sumber kebenaran.

---

# 11. KUNCI JAWABAN

Jangan mengirim:

```text
is_correct
answer_key
correct_answer
```

ke Android peserta jika tidak diperlukan.

Kunci jawaban harus tetap berada di server.

Penilaian dilakukan di server.

---

# 12. TIMER

Timer ujian harus menggunakan server time.

Jangan menggunakan jam HP sebagai sumber kebenaran.

Android hanya menampilkan countdown.

Server harus tetap memvalidasi:

```text
started_at
ends_at
current_server_time
status
```

Jika waktu habis:

```text
SAVE
 ↓
LOCK
 ↓
SUBMIT
 ↓
GRADE
```

---

# 13. JAWABAN PESERTA

Jawaban harus disimpan secepat mungkin.

Alur:

```text
USER SELECT ANSWER
 ↓
LOCAL STATE
 ↓
API
 ↓
SERVER VALIDATION
 ↓
DATABASE
```

Jika koneksi gagal:

```text
LOCAL QUEUE
 ↓
WAIT
 ↓
RECONNECT
 ↓
SYNC
```

Jangan menghapus jawaban lokal sebelum server mengonfirmasi sinkronisasi.

---

# 14. SUBMIT

Submit harus idempotent.

Jika request submit terkirim dua kali, hasil tidak boleh dibuat dua kali.

Server harus memvalidasi status attempt.

Attempt yang sudah:

```text
SUBMITTED
```

atau:

```text
TIMEOUT
```

tidak boleh menerima perubahan jawaban.

---

# 15. AUTENTIKASI

Password:

JANGAN plaintext.

Gunakan hashing yang aman.

Token/session harus divalidasi server.

Jangan mempercayai role yang dikirim oleh Android.

Role harus ditentukan berdasarkan akun yang terautentikasi.

---

# 16. API

Sebelum membuat endpoint baru:

1. Cari endpoint yang sudah ada.
2. Periksa apakah dapat digunakan kembali.
3. Jika tidak bisa, baru buat endpoint baru.

Jangan membuat API duplikat dengan fungsi sama.

Semua API harus memiliki:

* authentication
* authorization
* validation
* error handling

---

# 17. VALIDASI

Jangan mempercayai input dari:

* Android
* Browser
* API client
* Form

Validasi harus dilakukan di server.

---

# 18. ERROR HANDLING

Jangan menyembunyikan error.

Error harus:

* jelas
* aman
* dapat ditelusuri
* tidak membocorkan password/token/kunci jawaban

Untuk user tampilkan pesan yang mudah dipahami.

Untuk developer simpan informasi debugging yang diperlukan.

---

# 19. LOGGING

Gunakan logging untuk masalah penting.

Jangan menyimpan data sensitif secara sembarangan.

Jangan memasukkan:

* password
* token rahasia
* kunci jawaban
* data pribadi yang tidak diperlukan

ke log.

---

# 20. MODULAR DOCUMENTATION

Setiap modul memiliki dokumentasi sendiri.

Contoh:

```text
DOCS/MODULES/TIMER.md
DOCS/MODULES/EXAMS.md
DOCS/MODULES/QUESTIONS.md
```

Jangan membuat satu dokumentasi raksasa yang berisi seluruh source code.

---

# 21. CONTEXT LOADING

Gunakan prinsip:

```text
READ LESS
UNDERSTAND ENOUGH
CHANGE ONLY REQUIRED FILES
```

Urutan:

```text
1. AI_RULES.md
2. PROJECT_MAP.md
3. MODULE INDEX
4. RELATED FILES
5. DIRECT DEPENDENCY
```

Stop membaca jika informasi yang diperlukan sudah cukup.

---

# 22. FULL SCAN EXCEPTION

Full scan hanya boleh dilakukan jika:

* bug lintas modul
* dependency conflict
* database migration besar
* perubahan authentication global
* perubahan API global
* perubahan architecture
* security audit menyeluruh
* performance audit menyeluruh

Sebelum full scan:

```text
FULL SCAN REQUEST

Reason:
...

Modules involved:
...

Expected benefit:
...

Risk:
...
```

Jika tidak diperlukan, jangan lakukan.

---

# 23. PERUBAHAN FILE

Sebelum edit:

```text
MODULE:
...

FILES:
...

DEPENDENCIES:
...

CHANGE:
...
```

Setelah edit:

```text
FILES CHANGED:
...

FILES CREATED:
...

FILES DELETED:
...

TEST:
...

RESULT:
PASS / FAIL
```

---

# 24. TESTING

Setiap perubahan harus dites.

Minimal:

```text
Normal case
Invalid input
Error case
```

Untuk fitur ujian:

```text
Connection lost
Reconnect
Refresh
App closed
Duplicate request
Timeout
Submit
Server restart
```

---

# 25. JANGAN MENGANGGAP TEST BERHASIL

Jangan mengatakan:

"Sudah berhasil"

jika belum benar-benar menjalankan test yang relevan.

Jika tidak dapat menjalankan test:

katakan:

```text
NOT TESTED
Reason:
...
```

---

# 26. JANGAN MERUSAK FITUR LAMA

Sebelum perubahan:

identifikasi fungsi yang sudah ada.

Setelah perubahan:

pastikan fungsi terkait tetap berjalan.

Jika perubahan berpotensi memengaruhi modul lain:

beri peringatan.

---

# 27. COMPATIBILITY

Jangan mengubah:

* API contract
* database schema
* authentication
* data format

tanpa memeriksa client yang bergantung padanya.

Perhatikan:

```text
ANDROID
 ↓
API
 ↓
BACKEND
 ↓
DATABASE
```

Perubahan backend dapat memengaruhi Android.

---

# 28. OFFLINE LAN

Sistem utama harus dapat bekerja:

```text
Tanpa Internet
```

selama:

```text
Windows Server
+
Wi-Fi/LAN
+
Android
```

tersambung.

Jangan menambahkan dependency cloud yang menjadi wajib tanpa persetujuan user.

---

# 29. SERVER IP

Jangan hard-code IP server di banyak tempat.

Gunakan konfigurasi terpusat.

Android harus dapat mengubah alamat server.

Contoh:

```text
http://192.168.1.10:8000
```

Tetapi IP tersebut hanya contoh.

Jangan menganggap IP tersebut selalu benar.

---

# 30. PERFORMANCE

Jangan membuat request API berlebihan.

Hindari:

```text
1 klik
→ 10 request
```

Gunakan:

* caching jika sesuai
* batch sync
* local state
* pagination
* database indexing

Tetapi jangan mengorbankan integritas jawaban.

---

# 31. CONCURRENCY

CBT harus memperhitungkan banyak peserta mengakses server secara bersamaan.

Perhatikan:

* simultaneous login
* simultaneous exam start
* simultaneous answer submission
* simultaneous autosave
* simultaneous submit

Gunakan transaction dan constraint database jika diperlukan.

---

# 32. DATA INTEGRITY

Operasi kritis harus aman terhadap:

* duplicate request
* retry
* network failure
* timeout
* race condition

Jangan hanya mengandalkan client untuk mencegah duplicate data.

---

# 33. UI

Jangan mengubah desain yang sudah ada tanpa alasan.

Jika user meminta perubahan UI:

ubah hanya bagian yang diminta.

Jangan mengubah seluruh layout hanya karena menemukan cara yang menurut AI lebih bagus.

---

# 34. DEPENDENCY

Jangan menambahkan library/dependency baru hanya karena lebih mudah.

Sebelum menambahkan:

```text
DEPENDENCY:
...

PURPOSE:
...

WHY EXISTING CODE IS NOT ENOUGH:
...

IMPACT:
...
```

Gunakan dependency yang stabil dan diperlukan.

---

# 35. ENVIRONMENT

Jangan mengubah:

```text
.env
environment variables
database credentials
server configuration
```

tanpa alasan.

Jangan menampilkan secret dalam output.

---

# 36. BACKUP

Sebelum operasi database yang berisiko:

pastikan backup tersedia.

Untuk perubahan besar:

```text
BACKUP
 ↓
MIGRATION
 ↓
TEST
 ↓
VERIFY
```

---

# 37. DEVELOPMENT STAGE

Project harus dikerjakan:

```text
TAHAP 1
BLUEPRINT

TAHAP 2
DATABASE

TAHAP 3
BACKEND/API

TAHAP 4
AUTHENTICATION

TAHAP 5
ADMIN/GURU

TAHAP 6
BANK SOAL

TAHAP 7
EXAM

TAHAP 8
ANDROID

TAHAP 9
CBT ENGINE

TAHAP 10
AUTOSAVE/SYNC

TAHAP 11
TIMER

TAHAP 12
SCORING

TAHAP 13
MONITORING

TAHAP 14
REPORT

TAHAP 15
SECURITY

TAHAP 16
LOAD TEST

TAHAP 17
DEPLOYMENT
```

Jangan melompati tahap tanpa persetujuan user.

---

# 38. STOP CONDITION

Jika instruksi user hanya meminta analisis:

JANGAN CODING.

Jika instruksi user meminta blueprint:

JANGAN CODING.

Jika instruksi user meminta database:

Jangan membuat UI kecuali diminta.

Jika instruksi user meminta backend:

Jangan membuat Android kecuali diminta.

Jika instruksi user meminta Android:

Jangan mengubah backend kecuali diperlukan.

---

# 39. JIKA ADA KONFLIK

Jika menemukan dua aturan atau kebutuhan yang bertentangan:

Jangan memilih secara diam-diam.

Tampilkan:

```text
CONFLICT DETECTED

Option A:
...

Option B:
...

Recommendation:
...

Reason:
...
```

Kemudian tunggu keputusan user jika keputusan tersebut berdampak besar.

---

# 40. PRIORITAS

Urutan prioritas project:

1. Data integrity
2. Security
3. Reliability
4. Correctness
5. Performance
6. Maintainability
7. UI/UX
8. Development speed

Jangan mengorbankan keamanan atau integritas ujian hanya demi kecepatan.

---

# 41. KOMUNIKASI DENGAN USER

Gunakan bahasa yang jelas.

Jangan memberikan penjelasan panjang jika hanya diperlukan perubahan kecil.

Jika pekerjaan besar:

jelaskan rencana sebelum melakukan perubahan.

---

# 42. SEBELUM CODING

Selalu pastikan:

```text
What module?
What files?
What dependencies?
What database impact?
What API impact?
What Android impact?
What test?
```

Jika sudah jelas, baru coding.

---

# 43. SETELAH CODING

Selalu:

```text
Review
 ↓
Test
 ↓
Check regression
 ↓
Update documentation
 ↓
Report result
```

---

# 44. FINAL RULE

Jangan berusaha menjadi "terlalu pintar" dengan membuat perubahan yang tidak diminta.

Tugas utama AI:

**MEMBANGUN SESUAI SPESIFIKASI USER, BUKAN MEMBUAT SISTEM VERSI AI SENDIRI.**

Jika sesuatu belum ditentukan:

JANGAN MENGARANG.

Jika sesuatu berisiko:

JANGAN LANGSUNG MELAKUKAN.

Jika sesuatu tidak diperlukan:

JANGAN DIBUAT.

Jika sesuatu sudah ada:

JANGAN DUPLIKASI.

Jika perubahan kecil:

JANGAN FULL SCAN.

Jika perubahan besar:

ANALISIS TERLEBIH DAHULU.

END OF RULES.
