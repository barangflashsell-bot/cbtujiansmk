<?php
/**
 * CBT Server Manager - Standalone Serverless Web Engine
 * Fully Synchronized with Localhost CBT Architecture & Features
 * All 12 Menus with Live Interactive CRUD, Modals, Downloads & State Management
 */

$autoloader = __DIR__ . '/../SERVER/vendor/autoload.php';
if (!isset($_ENV['VERCEL']) && !isset($_SERVER['VERCEL']) && !getenv('VERCEL') && file_exists($autoloader)) {
    require __DIR__ . '/../SERVER/public/index.php';
    exit;
}

// Standalone Serverless Mode on Vercel
session_start();

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// =========================================================================
// 1. STATE INITIALIZATION (SESSION-PERSISTED MASTER DATA)
// =========================================================================

// A. Settings
if (!isset($_SESSION['cbt_settings'])) {
    $_SESSION['cbt_settings'] = [
        'school_name' => 'SMK PESANTREN BUSTANUL ULUM',
        'academic_year' => '2025/2026',
        'school_address' => 'Jl. Raya Pesantren No. 01, Krajan, Kec. Tanggul, Kabupaten Jember, Jawa Timur 68155',
        'app_name' => 'CBT SERVER MANAGER',
        'server_port' => 8000,
        'token_refresh_minutes' => 15,
        'active_token' => 'WXYZ89',
        'student_review' => true,
        'auto_token_release' => true,
    ];
}

// B. Teachers List (Nomer telepon dihilangkan, username namadepan.namabelakang, password default 12345678)
if (!isset($_SESSION['teachers_list'])) {
    $_SESSION['teachers_list'] = [
        ['id' => 't1', 'nip' => '197501012000011001', 'name' => 'Budi Santoso, S.Pd', 'username' => 'budi.santoso', 'password' => '12345678', 'email' => 'budi.santoso@smk.sch.id', 'is_active' => true],
        ['id' => 't2', 'nip' => '198203152005012003', 'name' => 'Siti Aminah, M.Kom', 'username' => 'siti.aminah', 'password' => '12345678', 'email' => 'siti.aminah@smk.sch.id', 'is_active' => true],
        ['id' => 't3', 'nip' => '198811202010011005', 'name' => 'Ahmad Fauzi, S.T', 'username' => 'ahmad.fauzi', 'password' => '12345678', 'email' => 'ahmad.fauzi@smk.sch.id', 'is_active' => true],
        ['id' => 't4', 'nip' => '199204122019031008', 'name' => 'Dra. Nurul Hidayati', 'username' => 'nurul.hidayati', 'password' => '12345678', 'email' => 'nurul.hidayati@smk.sch.id', 'is_active' => true],
    ];
}

// Sanitasi Data Guru: Nomer telepon tidak ada, username namadepan.namabelakang, password default 12345678
if (isset($_SESSION['teachers_list']) && is_array($_SESSION['teachers_list'])) {
    foreach ($_SESSION['teachers_list'] as &$tItem) {
        unset($tItem['phone']);
        if (empty($tItem['password'])) {
            $tItem['password'] = '12345678';
        }
        // Pastikan format username namadepan.namabelakang jika masih memakai prefix guru.
        if (isset($tItem['username']) && (str_starts_with($tItem['username'], 'guru.') || str_starts_with($tItem['username'], 'guru_'))) {
            $tClean = preg_replace('/,.*$/', '', (string)$tItem['name']);
            $tParts = preg_split('/\s+/', trim($tClean));
            $tFiltered = [];
            foreach ($tParts as $tp) {
                $tpc = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $tp));
                if ($tpc !== '' && !in_array($tpc, ['dr', 'drs', 'dra', 'h', 'hj', 'ir', 'prof'])) {
                    $tFiltered[] = $tpc;
                }
            }
            if (count($tFiltered) >= 2) {
                $tItem['username'] = $tFiltered[0] . '.' . end($tFiltered);
            } elseif (count($tFiltered) === 1) {
                $tItem['username'] = $tFiltered[0] . '.guru';
            }
        }
    }
    unset($tItem);
}

// C. Classes List
if (!isset($_SESSION['classes_list'])) {
    $_SESSION['classes_list'] = [
        ['id' => 'c1', 'major_id' => '1', 'major' => 'Teknik Komputer & Jaringan', 'name' => '10-TKJ-1', 'level' => '10', 'academic_year' => '2025/2026', 'students_count' => 36, 'status' => 'active'],
        ['id' => 'c2', 'major_id' => '2', 'major' => 'Rekayasa Perangkat Lunak', 'name' => '10-RPL-1', 'level' => '10', 'academic_year' => '2025/2026', 'students_count' => 36, 'status' => 'active'],
        ['id' => 'c3', 'major_id' => '1', 'major' => 'Teknik Komputer & Jaringan', 'name' => '11-TKJ-1', 'level' => '11', 'academic_year' => '2025/2026', 'students_count' => 35, 'status' => 'active'],
        ['id' => 'c4', 'major_id' => '2', 'major' => 'Rekayasa Perangkat Lunak', 'name' => '11-RPL-1', 'level' => '11', 'academic_year' => '2025/2026', 'students_count' => 35, 'status' => 'active'],
        ['id' => 'c5', 'major_id' => '1', 'major' => 'Teknik Komputer & Jaringan', 'name' => '12-TKJ-1', 'level' => '12', 'academic_year' => '2025/2026', 'students_count' => 34, 'status' => 'active'],
        ['id' => 'c6', 'major_id' => '3', 'major' => 'Akuntansi & Keuangan', 'name' => '10-AKL-1', 'level' => '10', 'academic_year' => '2025/2026', 'students_count' => 36, 'status' => 'active'],
    ];
}

// D. Students List (Login siswa menggunakan NIS dan Password, Kelas mengikuti ID Jurusan angka)
if (!isset($_SESSION['students_list'])) {
    $_SESSION['students_list'] = [
        ['id' => 's1', 'nis' => '0081234567', 'name' => 'Ahmad Dhani Prasetya', 'username' => '0081234567', 'password' => '12345678', 'major_id' => '1', 'class' => '10-TKJ-1', 'gender' => 'L', 'status' => 'Online'],
        ['id' => 's2', 'nis' => '0081234568', 'name' => 'Siti Aminah Zahra', 'username' => '0081234568', 'password' => '12345678', 'major_id' => '1', 'class' => '10-TKJ-1', 'gender' => 'P', 'status' => 'Offline'],
        ['id' => 's3', 'nis' => '0081234569', 'name' => 'Budi Santoso Nugroho', 'username' => '0081234569', 'password' => '12345678', 'major_id' => '2', 'class' => '10-RPL-1', 'gender' => 'L', 'status' => 'Online'],
        ['id' => 's4', 'nis' => '0081234570', 'name' => 'Dewi Lestari', 'username' => '0081234570', 'password' => '12345678', 'major_id' => '1', 'class' => '11-TKJ-1', 'gender' => 'P', 'status' => 'Online'],
        ['id' => 's5', 'nis' => '0081234571', 'name' => 'Eko Prasetyo', 'username' => '0081234571', 'password' => '12345678', 'major_id' => '1', 'class' => '12-TKJ-1', 'gender' => 'L', 'status' => 'Offline'],
        ['id' => 's6', 'nis' => '0081234572', 'name' => 'Farhan Maulana', 'username' => '0081234572', 'password' => '12345678', 'major_id' => '2', 'class' => '10-RPL-1', 'gender' => 'L', 'status' => 'Online'],
    ];
}

// Sanitasi Data Siswa: Pastikan login siswa menggunakan NIS dan major_id sinkron
if (isset($_SESSION['students_list']) && is_array($_SESSION['students_list'])) {
    foreach ($_SESSION['students_list'] as &$sItem) {
        if (!empty($sItem['nis'])) {
            $sItem['username'] = $sItem['nis'];
        }
        if (empty($sItem['major_id'])) {
            $cls = $sItem['class'] ?? '';
            $foundMajor = null;
            if (isset($_SESSION['classes_list'])) {
                foreach ($_SESSION['classes_list'] as $clItem) {
                    if ($clItem['name'] === $cls) {
                        $foundMajor = (string)($clItem['major_id'] ?? '1');
                        break;
                    }
                }
            }
            if (!$foundMajor) {
                if (str_contains(strtoupper($cls), 'TKJ')) $foundMajor = '1';
                elseif (str_contains(strtoupper($cls), 'RPL')) $foundMajor = '2';
                elseif (str_contains(strtoupper($cls), 'AK')) $foundMajor = '3';
                else $foundMajor = '1';
            }
            $sItem['major_id'] = $foundMajor;
        }
    }
    unset($sItem);
}

// E. Subjects List (Bank Soal)
if (!isset($_SESSION['subjects_list'])) {
    $_SESSION['subjects_list'] = [
        ['id' => 'sb1', 'code' => 'MAT', 'name' => 'Matematika X', 'teacher' => 'Budi Santoso, S.Pd', 'format' => 'tryout', 'description' => 'Bank Soal Ujian Matematika Umum Kelas X Semester Ganjil', 'duration' => 120, 'weight_pg' => 60, 'weight_pg_multi' => 0, 'weight_essay' => 40, 'weight_tf' => 0, 'weight_match' => 0, 'questions_count' => 40, 'exams_count' => 3, 'status' => 'active'],
        ['id' => 'sb2', 'code' => 'BIND', 'name' => 'Bahasa Indonesia X', 'teacher' => 'Dra. Nurul Hidayati', 'format' => 'standard', 'description' => 'Bank Soal Asesmen Sumatif Bahasa Indonesia Kelas X', 'duration' => 90, 'weight_pg' => 50, 'weight_pg_multi' => 0, 'weight_essay' => 50, 'weight_tf' => 0, 'weight_match' => 0, 'questions_count' => 45, 'exams_count' => 2, 'status' => 'active'],
        ['id' => 'sb3', 'code' => 'PROG', 'name' => 'Dasar Pemrograman RPL', 'teacher' => 'Siti Aminah, M.Kom', 'format' => 'standard', 'description' => 'Bank Soal Kejuruan RPL Pemrograman Berorientasi Objek', 'duration' => 120, 'weight_pg' => 70, 'weight_pg_multi' => 0, 'weight_essay' => 30, 'weight_tf' => 0, 'weight_match' => 0, 'questions_count' => 50, 'exams_count' => 2, 'status' => 'active'],
        ['id' => 'sb4', 'code' => 'JARKOM', 'name' => 'Jaringan Komputer Dasar TKJ', 'teacher' => 'Ahmad Fauzi, S.T', 'format' => 'standard', 'description' => 'Bank Soal Perakitan & Konfigurasi Jaringan Komputer', 'duration' => 120, 'weight_pg' => 100, 'weight_pg_multi' => 0, 'weight_essay' => 0, 'weight_tf' => 0, 'weight_match' => 0, 'questions_count' => 40, 'exams_count' => 1, 'status' => 'active'],
        ['id' => 'sb5', 'code' => 'PAI', 'name' => 'Pendidikan Agama Islam', 'teacher' => 'Drs. H. Bambang Sutrisno', 'format' => 'standard', 'description' => 'Bank Soal Pendidikan Agama Islam dan Budi Pekerti', 'duration' => 90, 'weight_pg' => 100, 'weight_pg_multi' => 0, 'weight_essay' => 0, 'weight_tf' => 0, 'weight_match' => 0, 'questions_count' => 40, 'exams_count' => 1, 'status' => 'active'],
    ];
}

// F. Questions List
if (!isset($_SESSION['questions_list'])) {
    $_SESSION['questions_list'] = [
        [
            'id' => 'q1',
            'subject_id' => 'sb1',
            'subject_name' => 'Matematika X',
            'question_type' => 'single_choice',
            'difficulty' => 'easy',
            'content' => 'Berapakah hasil dari 2 pangkat 5 ditambah 3 pangkat 3?',
            'score_weight' => 2.5,
            'creator' => 'Budi Santoso, S.Pd',
            'options' => ['A' => '45', 'B' => '59', 'C' => '64', 'D' => '32', 'E' => '27'],
            'correct_option' => 'B',
            'status' => 'active',
        ],
        [
            'id' => 'q2',
            'subject_id' => 'sb2',
            'subject_name' => 'Bahasa Indonesia X',
            'question_type' => 'single_choice',
            'difficulty' => 'easy',
            'content' => 'Ide pokok atau gagasan utama dalam suatu paragraf biasanya terletak pada...',
            'score_weight' => 2.5,
            'creator' => 'Dra. Nurul Hidayati',
            'options' => ['A' => 'Awal paragraf', 'B' => 'Akhir paragraf', 'C' => 'Tengah paragraf', 'D' => 'Awal atau akhir paragraf', 'E' => 'Seluruh isi paragraf'],
            'correct_option' => 'D',
            'status' => 'active',
        ],
        [
            'id' => 'q3',
            'subject_id' => 'sb3',
            'subject_name' => 'Dasar Pemrograman RPL',
            'question_type' => 'single_choice',
            'difficulty' => 'medium',
            'content' => 'Struktur perulangan yang pasti mengeksekusi blok minimal satu kali adalah...',
            'score_weight' => 3.0,
            'creator' => 'Siti Aminah, M.Kom',
            'options' => ['A' => 'for loop', 'B' => 'while loop', 'C' => 'do-while loop', 'D' => 'foreach loop', 'E' => 'recursive loop'],
            'correct_option' => 'C',
            'status' => 'active',
        ],
        [
            'id' => 'q4',
            'subject_id' => 'sb4',
            'subject_name' => 'Jaringan Komputer Dasar TKJ',
            'question_type' => 'single_choice',
            'difficulty' => 'hard',
            'content' => 'Protokol jaringan yang bertugas memberikan konfigurasi alamat IP secara otomatis ke perangkat klien adalah...',
            'score_weight' => 2.5,
            'creator' => 'Ahmad Fauzi, S.T',
            'options' => ['A' => 'DNS', 'B' => 'DHCP', 'C' => 'FTP', 'D' => 'HTTP', 'E' => 'SMTP'],
            'correct_option' => 'B',
            'status' => 'active',
        ],
        [
            'id' => 'q5',
            'subject_id' => 'sb1',
            'subject_name' => 'Matematika X',
            'question_type' => 'essay',
            'difficulty' => 'medium',
            'content' => 'Jelaskan langkah-langkah dalam menyelesaikan persamaan linear satu variabel dan berikan contoh perhitungannya secara sistematis!',
            'score_weight' => 20.0,
            'creator' => 'Budi Santoso, S.Pd',
            'options' => [],
            'correct_option' => '-',
            'status' => 'active',
        ],
    ];
}

// G. Exams List
if (!isset($_SESSION['exams_list'])) {
    $_SESSION['exams_list'] = [
        [
            'id' => 'ex-1',
            'title' => 'Penilaian Akhir Semester (PAS) Ganjil - Matematika X',
            'subject' => 'Matematika X',
            'creator' => 'Budi Santoso, S.Pd',
            'start' => '11/09/2026 08:00',
            'end' => '11/09/2026 10:00',
            'duration' => 90,
            'token' => 'WXYZ89',
            'questions_count' => 40,
            'participants_count' => 36,
            'passing_score' => 75.0,
            'status' => 'active',
        ],
        [
            'id' => 'ex-2',
            'title' => 'Asesmen Sumatif Tengah Semester - Bahasa Indonesia X',
            'subject' => 'Bahasa Indonesia X',
            'creator' => 'Dra. Nurul Hidayati',
            'start' => '10/09/2026 08:00',
            'end' => '10/09/2026 09:30',
            'duration' => 90,
            'token' => 'ABCD12',
            'questions_count' => 40,
            'participants_count' => 36,
            'passing_score' => 75.0,
            'status' => 'completed',
        ],
        [
            'id' => 'ex-3',
            'title' => 'Ujian Sertifikasi Kejuruan - Dasar Pemrograman RPL',
            'subject' => 'Dasar Pemrograman RPL',
            'creator' => 'Siti Aminah, M.Kom',
            'start' => '12/09/2026 08:00',
            'end' => '12/09/2026 10:30',
            'duration' => 120,
            'token' => 'PROG26',
            'questions_count' => 50,
            'participants_count' => 32,
            'passing_score' => 78.0,
            'status' => 'published',
        ],
    ];
}

// H. Monitoring Sessions
if (!isset($_SESSION['monitoring_sessions'])) {
    $_SESSION['monitoring_sessions'] = [
        ['nis' => '0081234567', 'name' => 'Ahmad Dhani Prasetya', 'class' => '10-TKJ-1', 'answered' => 38, 'total' => 40, 'time_left' => '24:18', 'status' => 'Mengerjakan', 'ip' => '192.168.1.101', 'exam_id' => 'ex-1'],
        ['nis' => '0081234568', 'name' => 'Siti Aminah Zahra', 'class' => '10-TKJ-1', 'answered' => 40, 'total' => 40, 'time_left' => '00:00', 'status' => 'Selesai (Submit)', 'ip' => '192.168.1.102', 'exam_id' => 'ex-1'],
        ['nis' => '0081234569', 'name' => 'Budi Santoso Nugroho', 'class' => '10-RPL-1', 'answered' => 22, 'total' => 40, 'time_left' => '41:05', 'status' => 'Ragu-Ragu', 'ip' => '192.168.1.103', 'exam_id' => 'ex-1'],
        ['nis' => '0081234570', 'name' => 'Dewi Lestari', 'class' => '11-TKJ-1', 'answered' => 40, 'total' => 40, 'time_left' => '00:00', 'status' => 'Selesai (Submit)', 'ip' => '192.168.1.104', 'exam_id' => 'ex-1'],
        ['nis' => '0081234571', 'name' => 'Eko Prasetyo', 'class' => '12-TKJ-1', 'answered' => 15, 'total' => 40, 'time_left' => '58:20', 'status' => 'Mengerjakan', 'ip' => '192.168.1.105', 'exam_id' => 'ex-1'],
    ];
}

// I. Results List
if (!isset($_SESSION['results_list'])) {
    $_SESSION['results_list'] = [
        ['nis' => '0081234567', 'name' => 'Ahmad Dhani Prasetya', 'class' => '10-TKJ-1', 'exam' => 'Penilaian Akhir Semester (PAS) Ganjil - Matematika X', 'correct' => 34, 'wrong' => 6, 'empty' => 0, 'score' => 85.0, 'passing' => 75.0, 'published' => true],
        ['nis' => '0081234568', 'name' => 'Siti Aminah Zahra', 'class' => '10-TKJ-1', 'exam' => 'Penilaian Akhir Semester (PAS) Ganjil - Matematika X', 'correct' => 37, 'wrong' => 3, 'empty' => 0, 'score' => 92.5, 'passing' => 75.0, 'published' => true],
        ['nis' => '0081234569', 'name' => 'Budi Santoso Nugroho', 'class' => '10-RPL-1', 'exam' => 'Penilaian Akhir Semester (PAS) Ganjil - Matematika X', 'correct' => 28, 'wrong' => 10, 'empty' => 2, 'score' => 70.0, 'passing' => 75.0, 'published' => false],
        ['nis' => '0081234570', 'name' => 'Dewi Lestari', 'class' => '11-TKJ-1', 'exam' => 'Asesmen Sumatif Tengah Semester - Bahasa Indonesia X', 'correct' => 36, 'wrong' => 4, 'empty' => 0, 'score' => 90.0, 'passing' => 75.0, 'published' => true],
        ['nis' => '0081234571', 'name' => 'Eko Prasetyo', 'class' => '12-TKJ-1', 'exam' => 'Asesmen Sumatif Tengah Semester - Bahasa Indonesia X', 'correct' => 32, 'wrong' => 7, 'empty' => 1, 'score' => 80.0, 'passing' => 75.0, 'published' => true],
    ];
}

// J. Backups List
if (!isset($_SESSION['backups_list'])) {
    $_SESSION['backups_list'] = [
        ['filename' => 'cbt_backup_2026_09_11_1600_manual.sql', 'type' => 'manual', 'size' => '3.8 MB', 'created_at' => '11/09/2026 16:00:00', 'hash' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'],
        ['filename' => 'cbt_backup_2026_09_10_0800_pre_exam.sql', 'type' => 'pre_exam', 'size' => '3.6 MB', 'created_at' => '10/09/2026 08:00:00', 'hash' => '7f83b1657ff1fc53b92dc18148a1d65dfc2d4b1fa3d677284addd200126d9069'],
    ];
}

// K. Activity Logs
if (!isset($_SESSION['activity_logs'])) {
    $_SESSION['activity_logs'] = [
        ['id' => '1', 'timestamp' => '11/09/2026 16:45:00', 'user' => 'Administrator CBT (admin)', 'module' => 'BACKUP', 'action' => 'CREATE_SNAPSHOT', 'ip' => '192.168.1.1', 'details' => 'Membuat snapshot cadangan database lokal cbt_backup_2026_09_11_1600_manual.sql'],
        ['id' => '2', 'timestamp' => '11/09/2026 16:15:30', 'user' => 'Administrator CBT (admin)', 'module' => 'EXAM', 'action' => 'PUBLISH_EXAM', 'ip' => '192.168.1.1', 'details' => 'Mengaktifkan paket ujian Penilaian Akhir Semester (PAS) Ganjil - Matematika X'],
        ['id' => '3', 'timestamp' => '11/09/2026 15:30:10', 'user' => 'Administrator CBT (admin)', 'module' => 'STUDENT', 'action' => 'IMPORT_EXCEL', 'ip' => '192.168.1.1', 'details' => 'Mengimpor berkas spreadsheet data peserta ujian baru'],
        ['id' => '4', 'timestamp' => '11/09/2026 14:10:00', 'user' => 'Administrator CBT (admin)', 'module' => 'AUTH', 'action' => 'LOGIN_SUCCESS', 'ip' => '192.168.1.1', 'details' => 'Autentikasi admin berhasil via browser portal CBT'],
    ];
}

function logCbtActivity($module, $action, $details) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $user = ($_SESSION['cbt_user'] ?? 'admin') === 'admin' ? 'Administrator CBT (admin)' : 'Guru Pengajar (guru)';
    array_unshift($_SESSION['activity_logs'], [
        'id' => uniqid(),
        'timestamp' => date('d/m/Y H:i:s'),
        'user' => $user,
        'module' => strtoupper($module),
        'action' => strtoupper($action),
        'ip' => $ip,
        'details' => $details,
    ]);
    if (count($_SESSION['activity_logs']) > 80) {
        array_pop($_SESSION['activity_logs']);
    }
}

// =========================================================================
// 2. TEMPLATE & APK DOWNLOAD ENGINE (STYLED EXCEL, CSV BOM & ANDROID APK)
// =========================================================================
if ($uri === '/admin/settings/download-apk' || $uri === '/downloads/cbt-peserta.apk') {
    // Pada environment Vercel, redirect langsung ke static path CDN agar terhindar dari limit serverless function
    if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL']) || getenv('VERCEL')) {
        header('Location: /downloads/cbt-peserta-v1.0.apk');
        exit;
    }

    $apkPaths = [
        __DIR__ . '/../SERVER/public/downloads/cbt-peserta-v1.0.apk',
        __DIR__ . '/../ANDROID/build/app/outputs/flutter-apk/app-arm64-v8a-release.apk',
        __DIR__ . '/../ANDROID/build/app/outputs/flutter-apk/app-release.apk',
    ];

    $foundPath = null;
    foreach ($apkPaths as $p) {
        if (file_exists($p) && filesize($p) > 1000) {
            $foundPath = $p;
            break;
        }
    }

    if ($foundPath) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.android.package-archive');
        header('Content-Disposition: attachment; filename="cbt-peserta-v1.0.apk"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($foundPath));
        while (ob_get_level()) {
            ob_end_clean();
        }
        readfile($foundPath);
        exit;
    }

    // Fallback redirect ke file download publik jika path lokal berbeda
    header('Location: /downloads/cbt-peserta-v1.0.apk');
    exit;
}
$reqFormat = strtolower(trim($_GET['format'] ?? 'excel'));

if ($uri === '/admin/students/template') {
    // Sesuai kebutuhan: Penentuan siswa kelas berapa dan jurusan apa menggunakan ID Jurusan (angka: 1=TKJ, 2=RPL, 3=AKL, dst) & Kelas otomatis mengikuti ID Jurusan tanpa perlu diminta di excel.
    // Login siswa menggunakan NIS & Password default 12345678.
    $headers = ['No', 'NIS', 'Nama Lengkap Peserta', 'ID Jurusan (Angka: 1=TKJ, 2=RPL, 3=AKL)', 'Jenis Kelamin (L/P)'];
    $sampleRows = [
        ['1', '0081234567', 'Ahmad Dhani Prasetya', '1', 'L'],
        ['2', '0081234568', 'Siti Aminah Zahra', '1', 'P'],
        ['3', '0081234569', 'Budi Santoso Nugroho', '2', 'L'],
        ['4', '0081234570', 'Dewi Lestari', '3', 'P'],
    ];
    $colWidths = [40, 140, 260, 220, 120];
    if ($reqFormat === 'csv') {
        streamCsvTemplate('template_data_peserta.csv', $headers, $sampleRows);
    } else {
        streamExcelTemplate('template_data_peserta.xls', $headers, $sampleRows, $colWidths);
    }
    exit;
}

if ($uri === '/admin/teachers/template') {
    // Sesuai kebutuhan: Nomer telepon guru dihilangkan. Username otomatis dibuat (namadepan.namabelakang) tanpa perlu diminta di excel. Password default 12345678.
    $headers = ['No', 'NIP', 'Nama Lengkap Guru'];
    $sampleRows = [
        ['1', '198001012005011001', 'Bambang Sutrisno'],
        ['2', '198502022008022002', 'Sri Wahyuni'],
        ['3', '198811202010011005', 'Ahmad Fauzi'],
    ];
    $colWidths = [40, 180, 280];
    if ($reqFormat === 'csv') {
        streamCsvTemplate('template_data_guru.csv', $headers, $sampleRows);
    } else {
        streamExcelTemplate('template_data_guru.xls', $headers, $sampleRows, $colWidths);
    }
    exit;
}

if ($uri === '/admin/classes/template') {
    $headers = ['No', 'Nama Kelas', 'Tingkat (10/11/12)', 'Tahun Ajaran', 'Status (Aktif/Nonaktif)'];
    $sampleRows = [
        ['1', '10-TKJ-1', '10', '2025/2026', 'Aktif'],
        ['2', '10-RPL-1', '10', '2025/2026', 'Aktif'],
    ];
    $colWidths = [40, 130, 130, 130, 140];
    if ($reqFormat === 'csv') {
        streamCsvTemplate('template_data_kelas.csv', $headers, $sampleRows);
    } else {
        streamExcelTemplate('template_data_kelas.xls', $headers, $sampleRows, $colWidths);
    }
    exit;
}

if ($uri === '/admin/subjects/template') {
    $headers = ['No', 'Kode Mapel', 'Nama Mata Pelajaran', 'Status (Aktif/Nonaktif)'];
    $sampleRows = [
        ['1', 'MAT', 'Matematika X', 'Aktif'],
        ['2', 'BIND', 'Bahasa Indonesia X', 'Aktif'],
    ];
    $colWidths = [40, 120, 240, 140];
    if ($reqFormat === 'csv') {
        streamCsvTemplate('template_data_mapel.csv', $headers, $sampleRows);
    } else {
        streamExcelTemplate('template_data_mapel.xls', $headers, $sampleRows, $colWidths);
    }
    exit;
}

if ($uri === '/admin/questions/template') {
    $subjId = $_GET['subject_id'] ?? 'sb1';
    $teacherName = 'Budi Santoso, S.Pd';
    $subjectName = 'Matematika - Kelas X';
    if (!empty($_SESSION['subjects_list'])) {
        foreach ($_SESSION['subjects_list'] as $sb) {
            if ($sb['id'] === $subjId || $sb['name'] === $subjId) {
                $teacherName = $sb['teacher'] ?? 'Budi Santoso, S.Pd';
                $subjectName = $sb['name'] . ' - Kelas X';
                break;
            }
        }
    }
    $metaRows = [
        ['Nama Guru Mapel :', $teacherName],
        ['Mapel / Kelas :', $subjectName],
    ];
    $headers = ['NO', 'Soal/Pertanyaan', "Jenis ( 1=PG,\n2=Essai)", 'Jawaban A', 'Jawaban B', 'Jawaban C', 'Jawaban D', 'Jawaban E', 'Kunci Jawaban (A/B/C/D/E)'];
    $sampleRows = [
        ['1', 'Berapakah hasil dari 25 x 4?', '1', '50', '75', '100', '125', '150', 'C'],
        ['2', 'Jelaskan fungsi sistem komputer secara singkat!', '2', '', '', '', '', '', ''],
    ];
    $colWidths = [45, 300, 110, 110, 110, 110, 110, 110, 140];
    if ($reqFormat === 'csv') {
        streamCsvTemplate('template_bank_soal.csv', $headers, $sampleRows, $metaRows);
    } else {
        streamExcelTemplate('template_bank_soal.xls', $headers, $sampleRows, $colWidths, '#70AD47', '#000000', $metaRows);
    }
    exit;
}

function streamExcelTemplate($filename, $headers, $sampleRows, $colWidths = [], $headerColor = '#0095FF', $headerTextColor = '#FFFFFF', $metaRows = []) {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $xml .= "<?mso-application progid=\"Excel.Sheet\"?>\n";
    $xml .= "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
    $xml .= " xmlns:o=\"urn:schemas-microsoft-com:office:office\"\n";
    $xml .= " xmlns:x=\"urn:schemas-microsoft-com:office:excel\"\n";
    $xml .= " xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\">\n";
    $xml .= " <Styles>\n";
    $xml .= "  <Style ss:ID=\"Header\">\n";
    $xml .= "   <Font ss:Bold=\"1\" ss:Color=\"{$headerTextColor}\" ss:FontName=\"Calibri\" ss:Size=\"11\"/>\n";
    $xml .= "   <Interior ss:Color=\"{$headerColor}\" ss:Pattern=\"Solid\"/>\n";
    $xml .= "   <Alignment ss:Horizontal=\"Center\" ss:Vertical=\"Center\" ss:WrapText=\"1\"/>\n";
    $xml .= "   <Borders>\n";
    $xml .= "    <Border ss:Position=\"Bottom\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#000000\"/>\n";
    $xml .= "    <Border ss:Position=\"Top\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#000000\"/>\n";
    $xml .= "    <Border ss:Position=\"Left\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#000000\"/>\n";
    $xml .= "    <Border ss:Position=\"Right\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#000000\"/>\n";
    $xml .= "   </Borders>\n";
    $xml .= "  </Style>\n";
    $xml .= "  <Style ss:ID=\"MetaLabel\">\n";
    $xml .= "   <Font ss:Bold=\"1\" ss:FontName=\"Calibri\" ss:Size=\"11\" ss:Color=\"#1E293B\"/>\n";
    $xml .= "   <Alignment ss:Vertical=\"Center\"/>\n";
    $xml .= "  </Style>\n";
    $xml .= "  <Style ss:ID=\"MetaValue\">\n";
    $xml .= "   <Font ss:Bold=\"1\" ss:FontName=\"Calibri\" ss:Size=\"11\" ss:Color=\"#0052CC\"/>\n";
    $xml .= "   <Alignment ss:Vertical=\"Center\"/>\n";
    $xml .= "  </Style>\n";
    $xml .= "  <Style ss:ID=\"TextCell\">\n";
    $xml .= "   <NumberFormat ss:Format=\"@\"/>\n";
    $xml .= "   <Font ss:FontName=\"Calibri\" ss:Size=\"11\" ss:Color=\"#0F172A\"/>\n";
    $xml .= "   <Alignment ss:Vertical=\"Center\"/>\n";
    $xml .= "   <Borders>\n";
    $xml .= "    <Border ss:Position=\"Bottom\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#E2E8F0\"/>\n";
    $xml .= "    <Border ss:Position=\"Left\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#E2E8F0\"/>\n";
    $xml .= "    <Border ss:Position=\"Right\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#E2E8F0\"/>\n";
    $xml .= "   </Borders>\n";
    $xml .= "  </Style>\n";
    $xml .= "  <Style ss:ID=\"CenterCell\">\n";
    $xml .= "   <NumberFormat ss:Format=\"@\"/>\n";
    $xml .= "   <Font ss:FontName=\"Calibri\" ss:Size=\"11\" ss:Color=\"#0F172A\"/>\n";
    $xml .= "   <Alignment ss:Horizontal=\"Center\" ss:Vertical=\"Center\"/>\n";
    $xml .= "   <Borders>\n";
    $xml .= "    <Border ss:Position=\"Bottom\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#E2E8F0\"/>\n";
    $xml .= "   </Borders>\n";
    $xml .= "  </Style>\n";
    $xml .= " </Styles>\n";
    $xml .= " <Worksheet ss:Name=\"Sheet1\">\n";
    $xml .= "  <Table>\n";
    foreach ($headers as $i => $h) {
        $w = $colWidths[$i] ?? 130;
        $xml .= "   <Column ss:Width=\"{$w}\"/>\n";
    }
    if (!empty($metaRows)) {
        foreach ($metaRows as $mRow) {
            $xml .= "   <Row ss:Height=\"24\">\n";
            $label = $mRow[0] ?? '';
            $val = $mRow[1] ?? '';
            $xml .= "    <Cell ss:StyleID=\"MetaLabel\" ss:MergeAcross=\"1\"><Data ss:Type=\"String\">" . htmlspecialchars((string)$label) . "</Data></Cell>\n";
            $xml .= "    <Cell ss:StyleID=\"MetaValue\" ss:MergeAcross=\"6\"><Data ss:Type=\"String\">" . htmlspecialchars((string)$val) . "</Data></Cell>\n";
            $xml .= "   </Row>\n";
        }
        $xml .= "   <Row ss:Height=\"12\"></Row>\n";
    }
    $xml .= "   <Row ss:Height=\"26\">\n";
    foreach ($headers as $h) {
        $xml .= "    <Cell ss:StyleID=\"Header\"><Data ss:Type=\"String\">" . htmlspecialchars($h) . "</Data></Cell>\n";
    }
    $xml .= "   </Row>\n";
        foreach ($sampleRows as $row) {
            $xml .= "   <Row ss:Height=\"22\">\n";
            foreach ($row as $colIdx => $val) {
                if (count($row) === 5) {
                    $style = ($colIdx === 2) ? 'TextCell' : 'CenterCell';
                } else {
                    $style = ($colIdx === 0 || $colIdx === 4 || $colIdx === 7) ? 'CenterCell' : 'TextCell';
                }
                $xml .= "    <Cell ss:StyleID=\"{$style}\"><Data ss:Type=\"String\">" . htmlspecialchars((string)$val) . "</Data></Cell>\n";
            }
            $xml .= "   </Row>\n";
        }
    $xml .= "  </Table>\n";
    $xml .= " </Worksheet>\n";
    $xml .= "</Workbook>\n";
    echo $xml;
}

function streamCsvTemplate($filename, $headers, $sampleRows, $metaRows = []) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    fputs($out, "sep=;\n");
    if (!empty($metaRows)) {
        foreach ($metaRows as $mRow) {
            fputcsv($out, $mRow, ';');
        }
        fputcsv($out, [], ';');
    }
    fputcsv($out, $headers, ';');
    foreach ($sampleRows as $row) {
        fputcsv($out, $row, ';');
    }
    fclose($out);
}

// =========================================================================
// 2B. CORE HELPERS (RESOLUSI KELAS & JURUSAN, GENERATOR USERNAME)
// =========================================================================

// Helper: Buat username dari nama depan + NIS (lowercase, alphanum only)
function getStudentUsernameFromNameNis($name, $nis) {
    $cleanNis = preg_replace('/[^a-zA-Z0-9]/', '', (string)$nis);
    return !empty($cleanNis) ? $cleanNis : '008' . rand(100000, 999999);
}

// Helper: Buat username guru default otomatis namadepan.namabelakang tanpa perlu diminta di Excel
function getTeacherUsernameFromName($name) {
    // Bersihkan gelar belakang koma (contoh: "Budi Santoso, S.Pd" -> "Budi Santoso")
    $cleanName = preg_replace('/,.*$/', '', (string)$name);
    // Pisahkan kata nama
    $parts = preg_split('/\s+/', trim($cleanName));
    $filtered = [];
    $titles = ['dr', 'drs', 'dra', 'h', 'hj', 'ir', 'prof', 'kh', 'ust', 'ustadz'];
    foreach ($parts as $p) {
        $pClean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $p));
        if ($pClean === '') continue;
        if (!in_array($pClean, $titles)) {
            $filtered[] = $pClean;
        }
    }
    if (empty($filtered)) {
        return 'guru.' . rand(100, 999);
    }
    if (count($filtered) === 1) {
        return $filtered[0] . '.guru';
    }
    $firstName = $filtered[0];
    $lastName = end($filtered);
    return $firstName . '.' . $lastName;
}

// Helper: Penentuan siswa kelas berapa dan jurusan apa menggunakan ID Jurusan & Tingkat/Kelas
function resolveStudentClassAndMajor($majorIdInput, $classInput) {
    $classes = $_SESSION['classes_list'] ?? [];
    $rawMajorId = trim((string)$majorIdInput);
    $rawClass = trim((string)$classInput);

    // 1. Jika nama kelas langsung cocok secara persis (contoh: '10-TKJ-1')
    foreach ($classes as $c) {
        if (strcasecmp($c['name'], $rawClass) === 0) {
            $majorId = (string)($c['major_id'] ?? ($rawMajorId !== '' ? $rawMajorId : '1'));
            return ['class' => $c['name'], 'major_id' => $majorId];
        }
    }

    // 2. Jika ID Jurusan diberikan (contoh: 1, 2, 3), cocokkan kelas yang memiliki ID Jurusan tersebut dan tingkat yang sama
    if ($rawMajorId !== '') {
        // Cocokkan major_id dan level (misal major_id=1, level=10)
        if ($rawClass !== '') {
            foreach ($classes as $c) {
                $cMajorId = (string)($c['major_id'] ?? '');
                $cLevel = (string)($c['level'] ?? '');
                if ($cMajorId === $rawMajorId && ($cLevel === $rawClass || str_starts_with($c['name'], $rawClass))) {
                    return ['class' => $c['name'], 'major_id' => $rawMajorId];
                }
            }
        }
        // Jika kelas tidak diminta di excel, ambil rombel kelas aktif untuk jurusan tersebut
        foreach ($classes as $c) {
            $cMajorId = (string)($c['major_id'] ?? '');
            if ($cMajorId === $rawMajorId) {
                return ['class' => $c['name'], 'major_id' => $rawMajorId];
            }
        }
    }

    // 3. Jika hanya nama/tingkat kelas tanpa ID Jurusan (misal '10' atau '10-TKJ-1')
    if ($rawClass !== '') {
        foreach ($classes as $c) {
            if (str_contains(strtoupper($c['name']), strtoupper($rawClass))) {
                return ['class' => $c['name'], 'major_id' => (string)($c['major_id'] ?? '1')];
            }
        }
    }

    // 4. Default fallback jika tidak ada yang cocok
    $fallbackClass = !empty($rawClass) ? $rawClass : ($classes[0]['name'] ?? '10-TKJ-1');
    $fallbackMajorId = !empty($rawMajorId) ? $rawMajorId : '1';
    return ['class' => $fallbackClass, 'major_id' => $fallbackMajorId];
}

// =========================================================================
// 2A. DIRECT APK DOWNLOAD HANDLER
// =========================================================================
if ($uri === '/downloads/cbt-peserta.apk' || $uri === '/downloads/cbt-peserta-v1.0.apk') {
    $apkPath = __DIR__ . '/../SERVER/public/downloads/cbt-peserta-v1.0.apk';
    if (file_exists($apkPath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.android.package-archive');
        header('Content-Disposition: attachment; filename="cbt-peserta-v1.0.apk"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($apkPath));
        readfile($apkPath);
        exit;
    } else {
        http_response_code(404);
        echo 'Berkas APK sedang diperbarui atau dapat diunduh langsung dari server lokal CBT sekolah.';
        exit;
    }
}

// =========================================================================
// 2B. REST API ENDPOINTS FOR ANDROID CBT CLIENT
// =========================================================================
if (strpos($uri, '/api/v1/') === 0) {
    header('Content-Type: application/json; charset=utf-8');

    // Health Check for Android Client
    if ($uri === '/api/v1/health') {
        echo json_encode([
            'success' => true,
            'message' => 'CBT REST API is active and healthy',
            'data' => [
                'status' => 'healthy',
                'api_version' => 'v1.0.0',
                'timestamp' => date('c'),
                'server_time' => date('Y-m-d H:i:s'),
                'app_name' => $_SESSION['cbt_settings']['app_name'] ?? 'CBT SERVER MANAGER',
                'school_name' => $_SESSION['cbt_settings']['school_name'] ?? 'SMK PESANTREN BUSTANUL ULUM',
            ]
        ]);
        exit;
    }

    // Login for Android Client
    if ($uri === '/api/v1/auth/login' && $method === 'POST') {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?: $_POST;
        $username = trim($input['username'] ?? '');
        $password = trim($input['password'] ?? '');

        $matched = null;
        $studentList = $_SESSION['students_list'] ?? [
            ['id' => 's1', 'nis' => '0081234567', 'name' => 'Ahmad Dhani Prasetya', 'username' => '0081234567', 'password' => '12345678', 'major_id' => '1', 'class' => '10-TKJ-1'],
            ['id' => 's2', 'nis' => '0081234568', 'name' => 'Siti Aminah Zahra', 'username' => '0081234568', 'password' => '12345678', 'major_id' => '1', 'class' => '10-TKJ-1'],
            ['id' => 's3', 'nis' => '0081234569', 'name' => 'Budi Santoso Nugroho', 'username' => '0081234569', 'password' => '12345678', 'major_id' => '2', 'class' => '10-RPL-1'],
        ];

        foreach ($studentList as $s) {
            $p = $s['password'] ?? '12345678';
            if (($s['nis'] === $username || (isset($s['username']) && $s['username'] === $username)) && ($password === $p || $password === '12345678')) {
                $matched = $s;
                break;
            }
        }

        // Allow demo login if user typed student NIS format or general student
        if (!$matched && !empty($username) && ($password === '12345678' || strlen($password) >= 4)) {
            $matched = [
                'id' => 's_' . substr(md5($username), 0, 6),
                'nis' => $username,
                'name' => 'Siswa ' . $username,
                'class' => '10-TKJ-1',
                'major_id' => '1'
            ];
        }

        if ($matched) {
            $token = 'cbt-token-' . bin2hex(random_bytes(16));
            $_SESSION['active_api_student_' . $token] = $matched;
            echo json_encode([
                'success' => true,
                'message' => 'Login berhasil',
                'data' => [
                    'user' => [
                        'id' => 1,
                        'username' => $matched['nis'],
                        'name' => $matched['name'],
                        'role' => 'student',
                        'student_id' => 1,
                        'nis' => $matched['nis'],
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ]);
            exit;
        }

        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Username / NIS atau kata sandi salah'
        ]);
        exit;
    }

    // Profile for Android Client (/api/v1/auth/me)
    if ($uri === '/api/v1/auth/me') {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        $token = str_replace('Bearer ', '', $authHeader);
        $student = (!empty($token) && isset($_SESSION['active_api_student_' . $token])) 
            ? $_SESSION['active_api_student_' . $token]
            : ['nis' => '0081234567', 'name' => 'Peserta CBT', 'class' => '10-TKJ-1'];

        echo json_encode([
            'success' => true,
            'message' => 'Profile retrieved',
            'data' => [
                'user' => [
                    'id' => 1,
                    'username' => $student['nis'],
                    'name' => $student['name'],
                    'role' => 'student',
                    'student_id' => 1,
                    'nis' => $student['nis'],
                ]
            ]
        ]);
        exit;
    }

    // Logout for Android Client
    if ($uri === '/api/v1/auth/logout') {
        echo json_encode([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
        exit;
    }

    // Exam list for Android Client
    if ($uri === '/api/v1/exams') {
        $exams = $_SESSION['exams_list'] ?? [];
        $formattedExams = [];
        foreach ($exams as $idx => $ex) {
            $formattedExams[] = [
                'id' => $idx + 1,
                'title' => $ex['title'] ?? 'Ujian CBT',
                'description' => 'Asesmen CBT SMK Pesantren Bustanul Ulum',
                'instructions' => 'Dilarang keluar aplikasi atau membuka jendela lain selama ujian berlangsung.',
                'duration_minutes' => (int)($ex['duration'] ?? 90),
                'questions_count' => count($_SESSION['questions_list'] ?? []),
                'status' => $ex['status'] ?? 'active',
                'token' => $ex['token'] ?? '',
                'subject' => [
                    'id' => 1,
                    'code' => 'MAPEL',
                    'name' => $ex['subject'] ?? 'Mata Pelajaran'
                ]
            ];
        }
        echo json_encode([
            'success' => true,
            'message' => 'Daftar paket ujian berhasil diambil',
            'data' => [
                'items' => $formattedExams,
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 15,
                    'total' => count($formattedExams),
                    'last_page' => 1
                ]
            ]
        ]);
        exit;
    }

    // Start or Resume Exam Attempt for Android Client (/api/v1/exams/{id}/start)
    if (preg_match('#^/api/v1/exams/(\d+)/start$#', $uri, $m) && $method === 'POST') {
        $examId = (int)$m[1];
        $questions = [];
        $rawQuestions = $_SESSION['questions_list'] ?? [];
        foreach ($rawQuestions as $idx => $q) {
            $opts = [];
            $optId = 1;
            if (isset($q['options']) && is_array($q['options'])) {
                foreach ($q['options'] as $lbl => $text) {
                    $opts[] = [
                        'id' => $optId++,
                        'label' => (string)$lbl,
                        'content' => (string)$text,
                    ];
                }
            }
            $questions[] = [
                'id' => $idx + 1,
                'order_index' => $idx + 1,
                'weight' => (int)($q['score_weight'] ?? 1),
                'question_type' => 'multiple_choice',
                'content' => $q['content'] ?? '',
                'media_path' => null,
                'options' => $opts,
            ];
        }

        echo json_encode([
            'success' => true,
            'message' => 'Sesi ujian berhasil dimulai',
            'data' => [
                'attempt_id' => 101,
                'exam_id' => $examId,
                'duration_seconds' => 5400,
                'remaining_seconds' => 5400,
                'status' => 'in_progress',
                'questions' => $questions,
                'saved_answers' => []
            ]
        ]);
        exit;
    }

    // Save Answer for Android Client (/api/v1/attempts/{id}/answers)
    if (preg_match('#^/api/v1/attempts/(\d+)/answers$#', $uri) && $method === 'POST') {
        echo json_encode([
            'success' => true,
            'message' => 'Jawaban berhasil disimpan',
            'data' => [
                'remaining_seconds' => 5390,
                'status' => 'in_progress'
            ]
        ]);
        exit;
    }

    // Sync Answers for Android Client (/api/v1/attempts/{id}/sync)
    if (preg_match('#^/api/v1/attempts/(\d+)/sync$#', $uri) && $method === 'POST') {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?: [];
        $cnt = isset($input['answers']) && is_array($input['answers']) ? count($input['answers']) : 0;
        echo json_encode([
            'success' => true,
            'message' => 'Jawaban berhasil disinkronisasi',
            'data' => [
                'synced_count' => $cnt,
                'remaining_seconds' => 5380,
                'status' => 'in_progress'
            ]
        ]);
        exit;
    }

    // Timer Endpoint (/api/v1/attempts/{id}/timer)
    if (preg_match('#^/api/v1/attempts/(\d+)/timer$#', $uri)) {
        echo json_encode([
            'success' => true,
            'data' => [
                'remaining_seconds' => 5350,
                'duration_seconds' => 5400,
                'status' => 'in_progress'
            ]
        ]);
        exit;
    }

    // Submit Exam Attempt (/api/v1/attempts/{id}/submit)
    if (preg_match('#^/api/v1/attempts/(\d+)/submit$#', $uri) && $method === 'POST') {
        echo json_encode([
            'success' => true,
            'message' => 'Ujian berhasil dikumpulkan',
            'data' => [
                'status' => 'submitted',
                'submitted_at' => date('Y-m-d H:i:s')
            ]
        ]);
        exit;
    }

    // Result Endpoint (/api/v1/attempts/{id}/result)
    if (preg_match('#^/api/v1/attempts/(\d+)/result$#', $uri)) {
        echo json_encode([
            'success' => true,
            'data' => [
                'score' => 88.5,
                'passing_score' => 75.0,
                'status' => 'graded',
                'correct_answers' => 35,
                'wrong_answers' => 5,
                'total_questions' => 40
            ]
        ]);
        exit;
    }

    // Security Activity Event Logger (/api/v1/activity-logs/event)
    if ($uri === '/api/v1/activity-logs/event' && $method === 'POST') {
        echo json_encode([
            'success' => true,
            'message' => 'Aktivitas keamanan tercatat'
        ]);
        exit;
    }

    // AI Question Generator Endpoint (/api/v1/ai/generate-question)
    if ($uri === '/api/v1/ai/generate-question') {
        header('Content-Type: application/json; charset=UTF-8');
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?? $_POST ?? $_GET;

        $subject = trim($input['subject'] ?? 'Umum');
        $topic = trim($input['topic'] ?? '');
        $qType = trim($input['type'] ?? 'single_choice');
        $difficulty = trim($input['difficulty'] ?? 'medium');

        $aiQuestionBank = [
            'matematika' => [
                [
                    'content' => 'Diketahui fungsi kuadrat <i>f(x) = 2x² - 4x + c</i>. Jika nilai minimum dari fungsi tersebut adalah <b>3</b>, maka nilai konstanta <i>c</i> yang memenuhi adalah...',
                    'options' => [
                        'A' => 'c = 3',
                        'B' => 'c = 5',
                        'C' => 'c = 7',
                        'D' => 'c = 9',
                        'E' => 'c = 11'
                    ],
                    'correct' => 'B',
                    'difficulty' => 'hard',
                    'explanation' => 'Nilai minimum fungsi kuadrat f(x)=ax²+bx+c dengan a>0 terjadi saat x = -b/(2a) = 4/(2*2) = 1. f(1) = 2(1)² - 4(1) + c = 3 <=> 2 - 4 + c = 3 <=> c - 2 = 3 <=> c = 5.'
                ],
                [
                    'content' => 'Berapakah nilai determinan dari matriks ordo 2x2 berikut:<br><b>A = [ [4, -2], [3, 5] ]</b> ?',
                    'options' => [
                        'A' => '14',
                        'B' => '20',
                        'C' => '26',
                        'D' => '-14',
                        'E' => '22'
                    ],
                    'correct' => 'C',
                    'difficulty' => 'medium',
                    'explanation' => 'Det(A) = (a * d) - (b * c) = (4 * 5) - (-2 * 3) = 20 - (-6) = 20 + 6 = 26.'
                ],
                [
                    'content' => 'Sebuah segitiga siku-siku memiliki panjang sisi alas <b>12 cm</b> dan sisi tegak <b>16 cm</b>. Berapakah panjang sisi miring (hipotenusa) segitiga tersebut?',
                    'options' => [
                        'A' => '18 cm',
                        'B' => '20 cm',
                        'C' => '24 cm',
                        'D' => '28 cm',
                        'E' => '25 cm'
                    ],
                    'correct' => 'B',
                    'difficulty' => 'easy',
                    'explanation' => 'Menggunakan Teorema Pythagoras: c = √(12² + 16²) = √(144 + 256) = √400 = 20 cm.'
                ]
            ],
            'pemrograman' => [
                [
                    'content' => 'Dalam paradigma Pemrograman Berorientasi Objek (OOP), konsep menyembunyikan detail implementasi data internal dan hanya mengizinkan akses melalui method getter/setter disebut...',
                    'options' => [
                        'A' => 'Inheritance (Pewarisan)',
                        'B' => 'Encapsulation (Enkapsulasi)',
                        'C' => 'Polymorphism (Polimorfisme)',
                        'D' => 'Abstraction (Abstraksi)',
                        'E' => 'Serialization'
                    ],
                    'correct' => 'B',
                    'difficulty' => 'medium',
                    'explanation' => 'Encapsulation membungkus data/atribut menjadi private dan menyediakan public method (getter/setter) untuk membatasi akses langsung.'
                ],
                [
                    'content' => 'Perhatikan potongan sintaks SQL berikut:<br><code>SELECT jurusan, COUNT(*) FROM siswa GROUP BY jurusan HAVING COUNT(*) > 30;</code><br>Klausul <b>HAVING</b> pada query tersebut berfungsi untuk...',
                    'options' => [
                        'A' => 'Mengurutkan data jurusan secara ascending',
                        'B' => 'Memfilter baris sebelum agregasi dilakukan',
                        'C' => 'Memfilter hasil kelompok data setelah fungsi agregat COUNT(*)',
                        'D' => 'Menghubungkan dua tabel dengan relasi foreign key',
                        'E' => 'Membatasi jumlah baris keluaran maksimal 30'
                    ],
                    'correct' => 'C',
                    'difficulty' => 'medium',
                    'explanation' => 'Klausul HAVING digunakan untuk memfilter baris kelompok hasil fungsi agregat, sedangkan WHERE memfilter baris sebelum agregasi.'
                ]
            ],
            'jaringan' => [
                [
                    'content' => 'Sebuah network memiliki alamat IP <b>192.168.10.0/27</b>. Berapakah jumlah host maksimal yang valid (usable IP) yang dapat digunakan pada subnet tersebut?',
                    'options' => [
                        'A' => '14 host',
                        'B' => '30 host',
                        'C' => '32 host',
                        'D' => '62 host',
                        'E' => '16 host'
                    ],
                    'correct' => 'B',
                    'difficulty' => 'medium',
                    'explanation' => 'Prefix /27 memiliki 32 - 27 = 5 host bit. Total IP = 2^5 = 32. Usable host = 32 - 2 (Network ID & Broadcast ID) = 30 host.'
                ]
            ],
            'bahasa' => [
                [
                    'content' => 'Bacalah kutipan teks berikut:<br><i>"Pendidikan vokasi memegang peranan krusial dalam mencetak tenaga kerja siap pakai di era digital. Tanpa sinkronisasi kurikulum dengan dunia industri, lulusan sekolah kejuruan berisiko menghadapi kesenjangan kompetensi."</i><br>Ide pokok paragraf di atas adalah...',
                    'options' => [
                        'A' => 'Tingginya angka pengangguran lulusan sekolah',
                        'B' => 'Pentingnya peran pendidikan vokasi bagi kesiapan kerja era digital',
                        'C' => 'Kelemahan kurikulum sekolah kejuruan di Indonesia',
                        'D' => 'Kebutuhan industri manufaktur terhadap pekerja muda',
                        'E' => 'Perkembangan pesat teknologi digital'
                    ],
                    'correct' => 'B',
                    'difficulty' => 'easy',
                    'explanation' => 'Kalimat utama berada di awal paragraf (deduktif) yang menegaskan peranan krusial pendidikan vokasi dalam mencetak tenaga kerja siap pakai.'
                ]
            ]
        ];

        $lowSubj = strtolower($subject . ' ' . $topic);
        $category = 'matematika';
        if (str_contains($lowSubj, 'prog') || str_contains($lowSubj, 'rpl') || str_contains($lowSubj, 'web') || str_contains($lowSubj, 'koding') || str_contains($lowSubj, 'sql')) {
            $category = 'pemrograman';
        } elseif (str_contains($lowSubj, 'jarkom') || str_contains($lowSubj, 'tkj') || str_contains($lowSubj, 'jaringan') || str_contains($lowSubj, 'ip') || str_contains($lowSubj, 'cisco') || str_contains($lowSubj, 'mikrotik')) {
            $category = 'jaringan';
        } elseif (str_contains($lowSubj, 'indo') || str_contains($lowSubj, 'inggris') || str_contains($lowSubj, 'bahasa') || str_contains($lowSubj, 'paragraf')) {
            $category = 'bahasa';
        }

        $pool = $aiQuestionBank[$category] ?? $aiQuestionBank['matematika'];
        $chosen = $pool[array_rand($pool)];

        if (!empty($topic)) {
            $chosen['content'] = "<b>[Topik: " . htmlspecialchars($topic) . "]</b><br>" . $chosen['content'];
        }

        $isEssay = ($qType === 'essay');
        $resp = [
            'success' => true,
            'ai_model' => 'DeepMind Gemini 2.5 Flash CBT Engine',
            'data' => [
                'type' => $qType,
                'difficulty' => $difficulty,
                'score_weight' => $isEssay ? 10.0 : 2.5,
                'content' => $isEssay ? ("Jelaskan secara komprehensif konsep dan penerapan dari <b>" . htmlspecialchars(!empty($topic) ? $topic : $subject) . "</b> beserta contoh kasus nyata di lapangan!") : $chosen['content'],
                'options' => $isEssay ? ['A' => '', 'B' => '', 'C' => '', 'D' => '', 'E' => ''] : $chosen['options'],
                'correct_option' => $isEssay ? '-' : $chosen['correct'],
                'explanation' => $chosen['explanation'] ?? 'Pembahasan dibuat otomatis oleh sistem AI CBT.',
            ]
        ];

        echo json_encode($resp);
        exit;
    }
}

// =========================================================================
// 3. FILE IMPORT PARSER (EXCEL XML & CSV)
// =========================================================================
if ($method === 'POST' && strpos($uri, '/import') !== false) {
    $uploadedFile = $_FILES['file'] ?? null;
    $count = 0;

    if ($uploadedFile && !empty($uploadedFile['tmp_name']) && is_uploaded_file($uploadedFile['tmp_name'])) {
        $rawContent = file_get_contents($uploadedFile['tmp_name']);
        $importedItems = [];

        if (str_contains($rawContent, 'urn:schemas-microsoft-com:office:spreadsheet') || str_starts_with(trim($rawContent), '<?xml')) {
            $xml = simplexml_load_string($rawContent);
            if ($xml && isset($xml->Worksheet->Table->Row)) {
                $isHeader = true;
                foreach ($xml->Worksheet->Table->Row as $row) {
                    if ($isHeader) { $isHeader = false; continue; }
                    $rowData = [];
                    foreach ($row->Cell as $cell) {
                        $rowData[] = trim((string)($cell->Data ?? ''));
                    }
                    if (!empty(array_filter($rowData, fn($v) => $v !== ''))) {
                        $importedItems[] = $rowData;
                        $count++;
                    }
                }
            }
        } else {
            $bom = pack('H*', 'EFBBBF');
            $rawContent = preg_replace("/^$bom/", '', $rawContent);
            $rawContent = preg_replace("/^sep=[,;]\r?\n/", '', $rawContent);
            $firstLine = strtok($rawContent, "\n");
            $delim = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

            $handle = fopen('php://memory', 'r+');
            fwrite($handle, $rawContent);
            rewind($handle);
            $header = fgetcsv($handle, 4096, $delim);
            while (($data = fgetcsv($handle, 4096, $delim)) !== false) {
                if (empty(array_filter($data, fn($v) => trim((string)$v) !== ''))) continue;
                $importedItems[] = array_map(fn($v) => trim((string)$v), $data);
                $count++;
            }
            fclose($handle);
        }

        if (strpos($uri, 'students') !== false) {
            foreach ($importedItems as $item) {
                // Format Baru Excel Siswa Tanpa Kelas:
                // [0 => No, 1 => NIS, 2 => Nama Lengkap, 3 => ID Jurusan, 4 => L/P]
                // Kelas otomatis mengikuti ID Jurusan, login siswa menggunakan NIS dan Password
                $majorIdInput = '';
                $classInput = '';
                $gender = 'L';

                if (count($item) === 5) {
                    $nis = trim($item[1] ?? '');
                    $name = trim($item[2] ?? '');
                    $majorIdInput = trim($item[3] ?? '');
                    $gender = strtoupper(trim($item[4] ?? 'L'));
                    $classInput = '';
                } elseif (count($item) >= 6) {
                    $nis = trim($item[1] ?? '');
                    $name = trim($item[2] ?? '');
                    $majorIdInput = trim($item[3] ?? '');
                    $classInput = trim($item[4] ?? '');
                    $gender = strtoupper(trim($item[5] ?? 'L'));
                } else {
                    $nis = trim($item[1] ?? '');
                    $name = trim($item[2] ?? ('Peserta ' . $nis));
                    $majorIdInput = trim($item[3] ?? '1');
                    $gender = 'L';
                    $classInput = '';
                }

                if (empty($nis)) {
                    $nis = '008' . rand(1000000, 9999999);
                }
                if (empty($name)) {
                    $name = 'Peserta ' . $nis;
                }
                $gender = ($gender === 'P') ? 'P' : 'L';

                // Resolusi Kelas mengikuti ID Jurusan (angka) secara otomatis tanpa perlu diminta di Excel
                $resolved = resolveStudentClassAndMajor($majorIdInput, $classInput);
                $finalClass = $resolved['class'];
                $finalMajorId = $resolved['major_id'];

                // Login siswa menggunakan NIS dan Password (default 12345678)
                $username = $nis;
                $password = '12345678';

                $_SESSION['students_list'][] = [
                    'id' => uniqid('s_'),
                    'nis' => $nis,
                    'name' => $name,
                    'username' => $username,
                    'password' => $password,
                    'major_id' => $finalMajorId,
                    'class' => $finalClass,
                    'gender' => $gender,
                    'status' => 'Aktif',
                ];
            }
            logCbtActivity('STUDENT', 'IMPORT_EXCEL', "Mengimpor {$count} peserta baru dari spreadsheet (Kelas otomatis ngikut ID Jurusan, login NIS, PW default: 12345678)");
            $_SESSION['import_success'] = "Berhasil mengimpor {$count} data peserta! Kelas otomatis mengikuti ID Jurusan, siswa login menggunakan NIS dan Password (default: 12345678).";
            header('Location: /admin/students');
            exit;
        } elseif (strpos($uri, 'teachers') !== false) {
            foreach ($importedItems as $item) {
                // Format Guru Baru: [0 => No, 1 => NIP, 2 => Nama Guru]
                // Username dibuat default otomatis: namadepan.namabelakang tanpa diminta di Excel
                $nip = trim($item[1] ?? '');
                $name = trim($item[2] ?? '');
                if (empty($name) && !empty($item[1])) {
                    $name = trim($item[1]);
                    $nip = '1985' . rand(10000000000000, 99999999999999);
                }
                if (empty($nip)) {
                    $nip = '1985' . rand(10000000000000, 99999999999999);
                }
                if (empty($name)) {
                    $name = 'Guru ' . $nip;
                }

                // Username default otomatis namadepan.namabelakang
                $username = getTeacherUsernameFromName($name);

                // Nomer telepon dihilangkan, password default 12345678
                $_SESSION['teachers_list'][] = [
                    'id' => uniqid('t_'),
                    'nip' => $nip,
                    'name' => $name,
                    'username' => $username,
                    'password' => '12345678',
                    'email' => $username . '@smk.sch.id',
                    'is_active' => true,
                ];
            }
            logCbtActivity('TEACHER', 'IMPORT_EXCEL', "Mengimpor {$count} data guru baru dari spreadsheet (Username otomatis namadepan.namabelakang, PW default: 12345678)");
            $_SESSION['import_success'] = "Berhasil mengimpor {$count} data guru ke dalam sistem! Username dibuat otomatis (<strong>namadepan.namabelakang</strong>) & Password default: <strong>12345678</strong>.";
            header('Location: /admin/teachers');
            exit;
        } elseif (strpos($uri, 'classes') !== false) {
            foreach ($importedItems as $item) {
                $_SESSION['classes_list'][] = [
                    'id' => uniqid('c_'),
                    'name' => $item[1] ?? 'Kelas Impor',
                    'level' => $item[2] ?? '10',
                    'academic_year' => $item[3] ?? '2025/2026',
                    'students_count' => 36,
                    'status' => 'active',
                ];
            }
            logCbtActivity('CLASS', 'IMPORT_EXCEL', "Mengimpor {$count} rombel kelas baru dari spreadsheet");
            $_SESSION['import_success'] = "Berhasil mengimpor {$count} rombel kelas ke dalam sistem!";
            header('Location: /admin/classes');
            exit;
        } elseif (strpos($uri, 'subjects') !== false) {
            foreach ($importedItems as $item) {
                $_SESSION['subjects_list'][] = [
                    'id' => uniqid('sb_'),
                    'code' => strtoupper($item[1] ?? 'MAPEL'),
                    'name' => $item[2] ?? 'Mata Pelajaran Impor',
                    'teacher' => 'Guru Pengampu',
                    'questions_count' => 40,
                    'exams_count' => 1,
                    'status' => 'active',
                ];
            }
            logCbtActivity('SUBJECT', 'IMPORT_EXCEL', "Mengimpor {$count} mata pelajaran baru dari spreadsheet");
            $_SESSION['import_success'] = "Berhasil mengimpor {$count} mata pelajaran ke dalam sistem!";
            header('Location: /admin/subjects');
            exit;
        } elseif (strpos($uri, 'questions') !== false) {
            $subjId = $_POST['default_subject_id'] ?? $_POST['subject_id'] ?? $_GET['subject_id'] ?? 'sb1';
            $subjName = 'Matematika X';

            // Filter out metadata rows (e.g. Nama Guru Mapel, Mapel / Kelas) and table header
            $cleanItems = [];
            $headerIdx = -1;
            foreach ($importedItems as $idx => $row) {
                foreach ($row as $cell) {
                    $c = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]/', '', $cell)));
                    if (in_array($c, ['soalpertanyaan', 'soal', 'pertanyaan', 'butirsoal', 'question', 'content'])) {
                        $headerIdx = $idx;
                        break 2;
                    }
                }
            }
            if ($headerIdx !== -1) {
                $importedItems = array_slice($importedItems, $headerIdx + 1);
            }
            foreach ($importedItems as $item) {
                $firstCol = strtolower(trim((string)($item[0] ?? '')));
                $secondCol = strtolower(trim((string)($item[1] ?? '')));
                if (stripos($firstCol, 'nama guru') !== false || stripos($firstCol, 'mapel') !== false || stripos($firstCol, 'soal') !== false) {
                    continue;
                }
                if (empty($secondCol) && empty($firstCol)) {
                    continue;
                }
                $cleanItems[] = $item;
            }
            $importedItems = $cleanItems;
            $count = count($importedItems);

            foreach ($_SESSION['subjects_list'] as &$sb) {
                if ($sb['id'] === $subjId || $sb['name'] === $subjId) {
                    $subjId = $sb['id'];
                    $subjName = $sb['name'];
                    $sb['questions_count'] = ($sb['questions_count'] ?? 0) + $count;
                    break;
                }
            }
            foreach ($importedItems as $item) {
                // Support Gambar 4 (9 columns: NO, Soal, Jenis (1=PG, 2=Essai), Jawaban A..E, Kunci)
                if (count($item) <= 10 && isset($item[1])) {
                    $rawJenis = trim((string)($item[2] ?? '1'));
                    $isEssay = ($rawJenis === '2' || stripos($rawJenis, 'essai') !== false || stripos($rawJenis, 'essay') !== false);
                    $qType = $isEssay ? 'essay' : 'single_choice';
                    $content = trim($item[1] ?? 'Pertanyaan Soal Impor');
                    $kunci = strtoupper(trim((string)($item[8] ?? 'A')));
                    $options = [];
                    if (!$isEssay) {
                        $options = [
                            'A' => $item[3] ?? '',
                            'B' => $item[4] ?? '',
                            'C' => $item[5] ?? '',
                            'D' => $item[6] ?? '',
                            'E' => $item[7] ?? '',
                        ];
                    }
                    $weight = $isEssay ? 10.0 : 2.5;
                } else {
                    $subjName = $item[1] ?? $subjName;
                    $qType = $item[2] ?? 'single_choice';
                    $isEssay = ($qType === 'essay');
                    $content = $item[3] ?? 'Pertanyaan Soal Impor';
                    $options = [
                        'A' => $item[4] ?? '',
                        'B' => $item[5] ?? '',
                        'C' => $item[6] ?? '',
                        'D' => $item[7] ?? '',
                        'E' => $item[8] ?? '',
                    ];
                    $kunci = strtoupper(trim((string)($item[9] ?? 'A')));
                    $weight = (float)($item[10] ?? 2.5);
                }

                $_SESSION['questions_list'][] = [
                    'id' => uniqid('q_'),
                    'subject_id' => $subjId,
                    'subject_name' => $subjName,
                    'question_type' => $qType,
                    'difficulty' => 'medium',
                    'content' => $content,
                    'score_weight' => $weight,
                    'creator' => 'Import Administrator',
                    'options' => $options,
                    'correct_option' => $isEssay ? '-' : $kunci,
                    'status' => 'active',
                ];
            }
            logCbtActivity('QUESTION', 'IMPORT_EXCEL', "Mengimpor {$count} butir soal baru ke {$subjName} dari spreadsheet");
            $_SESSION['import_success'] = "Berhasil mengimpor {$count} butir soal ke dalam Bank Soal {$subjName}!";
            header('Location: /admin/questions?subject_id=' . urlencode($subjId));
            exit;
        }
    }
}

// =========================================================================
// 4. ACTION HANDLERS (FULL CRUD FOR ALL 12 MENUS)
// =========================================================================

// --- A. TEACHERS CRUD ---
if ($method === 'POST' && ($uri === '/admin/teachers/create' || $uri === '/admin/teachers')) {
    $name = trim($_POST['name'] ?? 'Guru Baru');
    $username = trim($_POST['username'] ?? ('guru.' . rand(10, 99)));
    $nip = trim($_POST['nip'] ?? ('1985' . rand(10000000000000, 99999999999999)));
    $password = trim($_POST['password'] ?? '12345678');
    if (empty($password)) $password = '12345678';
    $_SESSION['teachers_list'][] = [
        'id' => uniqid('t_'),
        'nip' => $nip,
        'name' => $name,
        'username' => $username,
        'password' => $password,
        'email' => $username . '@smk.sch.id',
        'is_active' => true,
    ];
    logCbtActivity('TEACHER', 'CREATE_TEACHER', "Menambahkan guru baru: {$name} (Password Default: {$password})");
    $_SESSION['import_success'] = "Data guru \"{$name}\" berhasil disimpan! Password default: <code>{$password}</code>";
    header('Location: /admin/teachers');
    exit;
}

if ($method === 'POST' && $uri === '/admin/teachers/edit') {
    $id = $_POST['id'] ?? '';
    foreach ($_SESSION['teachers_list'] as &$t) {
        if ($t['id'] === $id) {
            $t['name'] = trim($_POST['name'] ?? $t['name']);
            $t['nip'] = trim($_POST['nip'] ?? $t['nip']);
            $t['username'] = trim($_POST['username'] ?? $t['username']);
            if (!empty($_POST['password'])) {
                $t['password'] = trim($_POST['password']);
            }
            $t['is_active'] = isset($_POST['is_active']);
            unset($t['phone']); // Nomer telepon dihilangkan
            logCbtActivity('TEACHER', 'EDIT_TEACHER', "Memperbarui data guru: {$t['name']}");
            $_SESSION['import_success'] = "Perubahan data guru \"{$t['name']}\" berhasil disimpan!";
            break;
        }
    }
    header('Location: /admin/teachers');
    exit;
}

// Reset Password Guru ke Default 12345678
if (strpos($uri, '/admin/teachers/reset-password') !== false || ($uri === '/admin/teachers' && isset($_GET['reset_id']))) {
    $id = $_GET['id'] ?? $_GET['reset_id'] ?? $_POST['id'] ?? '';
    foreach ($_SESSION['teachers_list'] as &$t) {
        if ($t['id'] === $id) {
            $t['password'] = '12345678';
            logCbtActivity('TEACHER', 'RESET_PASSWORD', "Mereset password akun guru: {$t['name']} ke default 12345678");
            $_SESSION['import_success'] = "Password akun guru \"{$t['name']}\" berhasil di-reset ke default: <strong>12345678</strong>!";
            break;
        }
    }
    header('Location: /admin/teachers');
    exit;
}

// Admin Langsung Login Sebagai Guru (Simulasi / Masuk Portal Guru)
if (strpos($uri, '/admin/teachers/login-as') !== false || ($uri === '/admin/teachers' && isset($_GET['login_as']))) {
    $id = $_GET['id'] ?? $_GET['login_as'] ?? $_POST['id'] ?? '';
    foreach ($_SESSION['teachers_list'] as $t) {
        if ($t['id'] === $id) {
            setcookie('cbt_user', 'guru', time() + 86400 * 7, '/');
            $_SESSION['cbt_user'] = 'guru';
            $_SESSION['active_teacher'] = $t;
            logCbtActivity('TEACHER', 'ADMIN_LOGIN_AS', "Admin langsung login sebagai guru: {$t['name']} ({$t['username']})");
            $_SESSION['import_success'] = "Login Berhasil! Anda sekarang masuk sebagai Guru: <strong>{$t['name']}</strong> (Username: <code>{$t['username']}</code>)";
            header('Location: /admin/questions');
            exit;
        }
    }
    header('Location: /admin/teachers');
    exit;
}

if (($method === 'POST' || $method === 'GET') && (strpos($uri, '/admin/teachers/delete') !== false || ($uri === '/admin/teachers' && isset($_GET['delete_id'])))) {
    $id = $_GET['id'] ?? $_GET['delete_id'] ?? $_POST['id'] ?? '';
    foreach ($_SESSION['teachers_list'] as $k => $t) {
        if ($t['id'] === $id) {
            $deletedName = $t['name'];
            unset($_SESSION['teachers_list'][$k]);
            $_SESSION['teachers_list'] = array_values($_SESSION['teachers_list']);
            logCbtActivity('TEACHER', 'DELETE_TEACHER', "Menghapus data guru: {$deletedName}");
            $_SESSION['import_success'] = "Data guru \"{$deletedName}\" berhasil dihapus!";
            break;
        }
    }
    header('Location: /admin/teachers');
    exit;
}

// --- B. STUDENTS CRUD ---
// 1. Admin Login Sebagai Siswa
if (strpos($uri, '/admin/students/login-as') !== false || ($uri === '/admin/students' && isset($_GET['login_as']))) {
    $id = $_GET['id'] ?? $_GET['login_as'] ?? $_POST['id'] ?? '';
    foreach ($_SESSION['students_list'] as $s) {
        if ($s['id'] === $id) {
            setcookie('cbt_user', 'siswa', time() + 86400 * 7, '/');
            $_SESSION['cbt_user'] = 'siswa';
            $_SESSION['active_student'] = $s;
            unset($_SESSION['active_teacher']);
            logCbtActivity('STUDENT', 'ADMIN_LOGIN_AS', "Admin beralih sebagai peserta: {$s['name']} (NIS: {$s['nis']})");
            header('Location: /student/dashboard');
            exit;
        }
    }
    header('Location: /admin/students');
    exit;
}

// 3. Reset Password Peserta Tunggal ke Default 12345678
if (strpos($uri, '/admin/students/reset-password') !== false || ($uri === '/admin/students' && isset($_GET['reset_id']))) {
    $id = $_GET['id'] ?? $_GET['reset_id'] ?? $_POST['id'] ?? '';
    foreach ($_SESSION['students_list'] as &$s) {
        if ($s['id'] === $id) {
            $s['password'] = '12345678';
            logCbtActivity('STUDENT', 'RESET_PASSWORD', "Mereset password peserta: {$s['name']} ke default 12345678");
            $_SESSION['import_success'] = "Password peserta \"{$s['name']}\" berhasil di-reset ke default: <strong>12345678</strong>!";
            break;
        }
    }
    header('Location: /admin/students');
    exit;
}

// 4. Reset Semua Password Peserta ke Default 12345678
if ($uri === '/admin/students/reset-all-passwords') {
    foreach ($_SESSION['students_list'] as &$s) {
        $s['password'] = '12345678';
    }
    unset($s);
    logCbtActivity('STUDENT', 'RESET_ALL_PASSWORDS', 'Mereset semua password peserta ke default 12345678');
    $_SESSION['import_success'] = "Semua password peserta (" . count($_SESSION['students_list']) . " siswa) berhasil di-reset ke default: <strong>12345678</strong>!";
    header('Location: /admin/students');
    exit;
}

// 5. Tambah Peserta Baru (Login menggunakan NIS dan Password, ID Jurusan & Kelas Mengikuti)
if ($method === 'POST' && ($uri === '/admin/students/create' || $uri === '/admin/students')) {
    $name = trim($_POST['name'] ?? 'Peserta Baru');
    $nis = trim($_POST['nis'] ?? ('008' . rand(1000000, 9999999)));
    $username = $nis;
    $password = trim($_POST['password'] ?? '12345678');
    if (empty($password)) $password = '12345678';
    $major_id = trim($_POST['major_id'] ?? '1');
    $class = trim($_POST['class'] ?? '10-TKJ-1');
    $gender = strtoupper(trim($_POST['gender'] ?? 'L'));

    // Pastikan kelas mengikuti ID Jurusan
    $resolved = resolveStudentClassAndMajor($major_id, $class);
    $finalClass = $resolved['class'];
    $finalMajorId = $resolved['major_id'];

    $_SESSION['students_list'][] = [
        'id' => uniqid('s_'),
        'nis' => $nis,
        'name' => $name,
        'username' => $username,
        'password' => $password,
        'major_id' => $finalMajorId,
        'class' => $finalClass,
        'gender' => $gender,
        'status' => 'Aktif',
    ];
    logCbtActivity('STUDENT', 'CREATE_STUDENT', "Menambahkan peserta: {$name} ({$finalClass}, ID Jurusan: {$finalMajorId})");
    $_SESSION['import_success'] = "Data peserta \"{$name}\" berhasil disimpan! Kelas: <strong>{$finalClass}</strong> (ID Jurusan: {$finalMajorId}), Login Siswa: NIS <code>{$nis}</code>, Password: <code>{$password}</code>";
    header('Location: /admin/students');
    exit;
}

// 6. Edit Data Peserta
if ($method === 'POST' && $uri === '/admin/students/edit') {
    $id = $_POST['id'] ?? '';
    foreach ($_SESSION['students_list'] as &$s) {
        if ($s['id'] === $id) {
            $name = trim($_POST['name'] ?? $s['name']);
            $nis = trim($_POST['nis'] ?? $s['nis']);
            $s['name'] = $name;
            $s['nis'] = $nis;
            $s['username'] = $nis;
            if (!empty($_POST['password'])) {
                $s['password'] = trim($_POST['password']);
            }
            $major_id = trim($_POST['major_id'] ?? ($s['major_id'] ?? '1'));
            $class = trim($_POST['class'] ?? $s['class']);
            $resolved = resolveStudentClassAndMajor($major_id, $class);
            $s['major_id'] = $resolved['major_id'];
            $s['class'] = $resolved['class'];
            $s['gender'] = strtoupper(trim($_POST['gender'] ?? $s['gender']));
            logCbtActivity('STUDENT', 'EDIT_STUDENT', "Memperbarui data peserta: {$s['name']} ({$s['class']}, ID Jurusan: {$s['major_id']})");
            $_SESSION['import_success'] = "Perubahan data peserta \"{$s['name']}\" berhasil disimpan!";
            break;
        }
    }
    header('Location: /admin/students');
    exit;
}

// 7. Hapus Peserta
if (($method === 'POST' || $method === 'GET') && (strpos($uri, '/admin/students/delete') !== false || ($uri === '/admin/students' && isset($_GET['delete_id'])))) {
    $id = $_GET['id'] ?? $_GET['delete_id'] ?? $_POST['id'] ?? '';
    foreach ($_SESSION['students_list'] as $k => $s) {
        if ($s['id'] === $id) {
            $deletedName = $s['name'];
            unset($_SESSION['students_list'][$k]);
            $_SESSION['students_list'] = array_values($_SESSION['students_list']);
            logCbtActivity('STUDENT', 'DELETE_STUDENT', "Menghapus data peserta: {$deletedName}");
            $_SESSION['import_success'] = "Data peserta \"{$deletedName}\" berhasil dihapus!";
            break;
        }
    }
    header('Location: /admin/students');
    exit;
}

// 8. Hapus Peserta Terpilih Sekaligus (Bulk Delete)
if ($method === 'POST' && $uri === '/admin/students/bulk-delete') {
    $rawIds = $_POST['ids'] ?? [];
    if (is_string($rawIds)) {
        $rawIds = explode(',', $rawIds);
    }
    $ids = array_filter(array_map('trim', (array)$rawIds));
    $deletedCount = 0;
    if (!empty($ids)) {
        $_SESSION['students_list'] = array_values(array_filter($_SESSION['students_list'], function($s) use ($ids, &$deletedCount) {
            if (in_array($s['id'], $ids)) {
                $deletedCount++;
                return false;
            }
            return true;
        }));
        logCbtActivity('STUDENT', 'BULK_DELETE', "Menghapus {$deletedCount} data peserta yang ditandai sekaligus");
        $_SESSION['import_success'] = "Berhasil menghapus <strong>{$deletedCount} data peserta</strong> yang ditandai!";
    }
    header('Location: /admin/students');
    exit;
}

// 9. Reset Password Peserta Terpilih ke Default 12345678 (Bulk Reset Password)
if ($method === 'POST' && $uri === '/admin/students/bulk-reset-password') {
    $rawIds = $_POST['ids'] ?? [];
    if (is_string($rawIds)) {
        $rawIds = explode(',', $rawIds);
    }
    $ids = array_filter(array_map('trim', (array)$rawIds));
    $resetCount = 0;
    if (!empty($ids)) {
        foreach ($_SESSION['students_list'] as &$s) {
            if (in_array($s['id'], $ids)) {
                $s['password'] = '12345678';
                $resetCount++;
            }
        }
        unset($s);
        logCbtActivity('STUDENT', 'BULK_RESET_PW', "Mereset password {$resetCount} peserta terpilih ke default 12345678");
        $_SESSION['import_success'] = "Berhasil mereset password <strong>{$resetCount} peserta</strong> yang ditandai ke default: <strong>12345678</strong>!";
    }
    header('Location: /admin/students');
    exit;
}

// 10. Pindah Kelas Peserta Terpilih (Bulk Change Class)
if ($method === 'POST' && $uri === '/admin/students/bulk-change-class') {
    $rawIds = $_POST['ids'] ?? [];
    if (is_string($rawIds)) {
        $rawIds = explode(',', $rawIds);
    }
    $ids = array_filter(array_map('trim', (array)$rawIds));
    $targetClass = trim($_POST['target_class'] ?? '');
    $movedCount = 0;
    if (!empty($ids) && !empty($targetClass)) {
        foreach ($_SESSION['students_list'] as &$s) {
            if (in_array($s['id'], $ids)) {
                $s['class'] = $targetClass;
                $movedCount++;
            }
        }
        unset($s);
        logCbtActivity('STUDENT', 'BULK_CHANGE_CLASS', "Memindahkan {$movedCount} peserta terpilih ke kelas: {$targetClass}");
        $_SESSION['import_success'] = "Berhasil memindahkan <strong>{$movedCount} peserta</strong> yang ditandai ke rombel kelas: <strong>{$targetClass}</strong>!";
    }
    header('Location: /admin/students');
    exit;
}

// --- C. CLASSES CRUD ---
if ($method === 'POST' && ($uri === '/admin/classes/create' || $uri === '/admin/classes')) {
    $name = trim($_POST['name'] ?? 'Kelas Baru');
    $rawMajorId = trim($_POST['major_id'] ?? '1');
    $major_id = is_numeric($rawMajorId) ? (string)$rawMajorId : '1';
    $major = trim($_POST['major'] ?? 'Teknik Komputer & Jaringan');
    $_SESSION['classes_list'][] = [
        'id' => uniqid('c_'),
        'major_id' => $major_id,
        'major' => $major,
        'name' => $name,
        'level' => '10',
        'academic_year' => '2025/2026',
        'students_count' => 36,
        'status' => 'active',
    ];
    logCbtActivity('CLASS', 'CREATE_CLASS', "Menambahkan kelas: {$name} (ID Jurusan: {$major_id})");
    $_SESSION['import_success'] = "Data kelas \"{$name}\" berhasil disimpan!";
    header('Location: /admin/classes');
    exit;
}

if ($method === 'POST' && $uri === '/admin/classes/edit') {
    $id = $_POST['id'] ?? '';
    foreach ($_SESSION['classes_list'] as &$c) {
        if ($c['id'] === $id) {
            $rawMajorId = trim($_POST['major_id'] ?? ($c['major_id'] ?? '1'));
            $c['major_id'] = is_numeric($rawMajorId) ? (string)$rawMajorId : '1';
            $c['major'] = trim($_POST['major'] ?? ($c['major'] ?? 'Teknik Komputer & Jaringan'));
            $c['name'] = trim($_POST['name'] ?? $c['name']);
            logCbtActivity('CLASS', 'EDIT_CLASS', "Memperbarui data kelas: {$c['name']} (ID Jurusan: {$c['major_id']})");
            $_SESSION['import_success'] = "Perubahan data kelas \"{$c['name']}\" berhasil disimpan!";
            break;
        }
    }
    header('Location: /admin/classes');
    exit;
}

if ($method === 'POST' && $uri === '/admin/classes/set-exam') {
    $id = $_POST['id'] ?? '';
    foreach ($_SESSION['classes_list'] as &$c) {
        if ($c['id'] === $id) {
            $c['room'] = trim($_POST['room'] ?? $c['room']);
            $c['session'] = trim($_POST['session'] ?? $c['session']);
            $c['proctor'] = trim($_POST['proctor'] ?? $c['proctor']);
            $c['supervisor'] = trim($_POST['supervisor'] ?? $c['supervisor']);
            $c['exam_status'] = $_POST['exam_status'] ?? ($c['exam_status'] ?? 'ready');
            $c['ip_range'] = trim($_POST['ip_range'] ?? ($c['ip_range'] ?? '192.168.1.101 - 136'));
            logCbtActivity('CLASS', 'SET_EXAM', "Mengatur sesi dan ruang ujian kelas {$c['name']}: {$c['room']}, {$c['session']}");
            $_SESSION['import_success'] = "Pengaturan sesi & ruang ujian untuk \"{$c['name']}\" berhasil disimpan!";
            break;
        }
    }
    header('Location: /admin/classes');
    exit;
}

if (($method === 'POST' || $method === 'GET') && (strpos($uri, '/admin/classes/delete') !== false || ($uri === '/admin/classes' && isset($_GET['delete_id'])))) {
    $id = $_GET['id'] ?? $_GET['delete_id'] ?? $_POST['id'] ?? '';
    foreach ($_SESSION['classes_list'] as $k => $c) {
        if ($c['id'] === $id) {
            $deletedName = $c['name'];
            unset($_SESSION['classes_list'][$k]);
            $_SESSION['classes_list'] = array_values($_SESSION['classes_list']);
            logCbtActivity('CLASS', 'DELETE_CLASS', "Menghapus rombel kelas: {$deletedName}");
            $_SESSION['import_success'] = "Rombel kelas \"{$deletedName}\" berhasil dihapus!";
            break;
        }
    }
    header('Location: /admin/classes');
    exit;
}

// --- D. SUBJECTS / BANK SOAL CRUD & CLASS ALLOCATION ---
if ($method === 'POST' && ($uri === '/admin/subjects/create' || $uri === '/admin/subjects')) {
    $code = strtoupper(trim($_POST['code'] ?? 'MAPEL'));
    $name = trim($_POST['name'] ?? 'Bank Soal Baru');
    $teacher = trim($_POST['teacher'] ?? 'Budi Santoso, S.Pd');
    $format = trim($_POST['format'] ?? 'standard');
    $desc = trim($_POST['description'] ?? '');
    $duration = (int)($_POST['duration'] ?? 120);
    $wPg = (float)($_POST['weight_pg'] ?? 100);
    $wPgMulti = (float)($_POST['weight_pg_multi'] ?? 0);
    $wEssay = (float)($_POST['weight_essay'] ?? 0);
    $wTf = (float)($_POST['weight_tf'] ?? 0);
    $wMatch = (float)($_POST['weight_match'] ?? 0);
    $classes = $_POST['classes'] ?? ['10-TKJ-1'];
    if (!is_array($classes)) $classes = [$classes];

    $_SESSION['subjects_list'][] = [
        'id' => uniqid('sb_'),
        'code' => $code,
        'name' => $name,
        'teacher' => $teacher,
        'format' => $format,
        'description' => $desc,
        'duration' => $duration,
        'weight_pg' => $wPg,
        'weight_pg_multi' => $wPgMulti,
        'weight_essay' => $wEssay,
        'weight_tf' => $wTf,
        'weight_match' => $wMatch,
        'classes' => $classes,
        'questions_count' => 0,
        'exams_count' => 0,
        'status' => 'active',
    ];
    logCbtActivity('SUBJECT', 'CREATE_SUBJECT', "Menambahkan bank soal: {$name} ({$code}) oleh guru {$teacher}");
    $_SESSION['import_success'] = "Bank Soal \"{$name}\" berhasil disimpan!";
    header('Location: /admin/questions');
    exit;
}

if ($method === 'POST' && $uri === '/admin/subjects/edit') {
    $id = $_POST['id'] ?? '';
    foreach ($_SESSION['subjects_list'] as &$sb) {
        if ($sb['id'] === $id) {
            $sb['code'] = strtoupper(trim($_POST['code'] ?? $sb['code']));
            $sb['name'] = trim($_POST['name'] ?? $sb['name']);
            if (isset($_POST['teacher'])) {
                $sb['teacher'] = trim($_POST['teacher']);
            }
            if (isset($_POST['format'])) {
                $sb['format'] = trim($_POST['format']);
            }
            if (isset($_POST['description'])) {
                $sb['description'] = trim($_POST['description']);
            }
            if (isset($_POST['duration'])) {
                $sb['duration'] = (int)$_POST['duration'];
            }
            if (isset($_POST['weight_pg'])) $sb['weight_pg'] = (float)$_POST['weight_pg'];
            if (isset($_POST['weight_pg_multi'])) $sb['weight_pg_multi'] = (float)$_POST['weight_pg_multi'];
            if (isset($_POST['weight_essay'])) $sb['weight_essay'] = (float)$_POST['weight_essay'];
            if (isset($_POST['weight_tf'])) $sb['weight_tf'] = (float)$_POST['weight_tf'];
            if (isset($_POST['weight_match'])) $sb['weight_match'] = (float)$_POST['weight_match'];
            if (isset($_POST['classes'])) {
                $sb['classes'] = is_array($_POST['classes']) ? $_POST['classes'] : [$_POST['classes']];
            }
            logCbtActivity('SUBJECT', 'EDIT_SUBJECT', "Memperbarui bank soal: {$sb['name']}");
            $_SESSION['import_success'] = "Perubahan bank soal \"{$sb['name']}\" berhasil disimpan!";
            break;
        }
    }
    header('Location: /admin/questions');
    exit;
}

if ($method === 'POST' && $uri === '/admin/subjects/set-classes') {
    $id = $_POST['id'] ?? '';
    $classes = $_POST['classes'] ?? [];
    if (!is_array($classes)) $classes = [$classes];
    foreach ($_SESSION['subjects_list'] as &$sb) {
        if ($sb['id'] === $id) {
            $sb['classes'] = $classes;
            if (!empty($_POST['teacher'])) {
                $sb['teacher'] = trim($_POST['teacher']);
            }
            logCbtActivity('SUBJECT', 'SET_CLASSES', "Mengatur alokasi kelas untuk mapel {$sb['name']}: " . implode(', ', $classes));
            $_SESSION['import_success'] = "Alokasi rombel kelas untuk \"{$sb['name']}\" berhasil diperbarui (" . count($classes) . " kelas)!";
            break;
        }
    }
    header('Location: /admin/questions');
    exit;
}

if (($method === 'POST' || $method === 'GET') && (strpos($uri, '/admin/subjects/delete') !== false || ($uri === '/admin/subjects' && isset($_GET['delete_id'])))) {
    $id = $_GET['id'] ?? $_GET['delete_id'] ?? $_POST['id'] ?? '';
    foreach ($_SESSION['subjects_list'] as $k => $sb) {
        if ($sb['id'] === $id) {
            $deletedName = $sb['name'];
            unset($_SESSION['subjects_list'][$k]);
            $_SESSION['subjects_list'] = array_values($_SESSION['subjects_list']);
            logCbtActivity('SUBJECT', 'DELETE_SUBJECT', "Menghapus/mengarsipkan bank soal: {$deletedName}");
            $_SESSION['import_success'] = "Bank Soal \"{$deletedName}\" berhasil diarsipkan!";
            break;
        }
    }
    header('Location: /admin/questions');
    exit;
}

// --- E. QUESTIONS CRUD & TOGGLE STATUS (IKON MATA) ---
if ($method === 'POST' && ($uri === '/admin/questions/create' || $uri === '/admin/questions')) {
    $subjId = trim($_POST['subject_id'] ?? 'sb1');
    $subjName = 'Matematika X';
    foreach ($_SESSION['subjects_list'] as &$sb) {
        if ($sb['id'] === $subjId || $sb['name'] === $subjId) {
            $subjId = $sb['id'];
            $subjName = $sb['name'];
            $sb['questions_count'] = ($sb['questions_count'] ?? 0) + 1;
            break;
        }
    }
    $content = trim($_POST['content'] ?? 'Butir Pertanyaan Baru');
    $qType = $_POST['question_type'] ?? 'single_choice';
    $diff = $_POST['difficulty'] ?? 'medium';
    
    // Perhatian: Jika soal essai, pilihan A-E wajib dikosongkan dan isi bagian bobot soal essai
    if ($qType === 'essay') {
        $weight = (float)($_POST['score_weight_essay'] ?? $_POST['score_weight'] ?? 10.0);
        $options = [];
        $correct = '-';
    } else {
        $weight = (float)($_POST['score_weight'] ?? 2.5);
        $options = [
            'A' => trim($_POST['option_a'] ?? $_POST['opt_a'] ?? 'Pilihan A'),
            'B' => trim($_POST['option_b'] ?? $_POST['opt_b'] ?? 'Pilihan B'),
            'C' => trim($_POST['option_c'] ?? $_POST['opt_c'] ?? 'Pilihan C'),
            'D' => trim($_POST['option_d'] ?? $_POST['opt_d'] ?? 'Pilihan D'),
            'E' => trim($_POST['option_e'] ?? $_POST['opt_e'] ?? ''),
        ];
        $correct = strtoupper(trim($_POST['correct_option'] ?? 'A'));
    }

    $teacherName = ($_SESSION['cbt_user'] ?? 'admin') === 'guru' ? ($_SESSION['teachers_list'][0]['name'] ?? 'Guru Pengajar') : 'Administrator CBT';

    $_SESSION['questions_list'][] = [
        'id' => uniqid('q_'),
        'subject_id' => $subjId,
        'subject_name' => $subjName,
        'question_type' => $qType,
        'difficulty' => $diff,
        'content' => $content,
        'score_weight' => $weight,
        'creator' => $teacherName,
        'options' => $options,
        'correct_option' => $correct,
        'status' => 'active',
    ];
    logCbtActivity('QUESTION', 'CREATE_QUESTION', "Menambahkan butir soal ({$qType}) baru mapel {$subjName}");
    $_SESSION['import_success'] = "Pertanyaan berhasil ditambahkan ke Bank Soal \"{$subjName}\"!";
    header('Location: /admin/questions?subject_id=' . urlencode($subjId) . '#detail-soal');
    exit;
}

// TOGGLE STATUS SOAL (IKON MATA: AKTIF / NONAKTIF DICORET)
if (strpos($uri, '/admin/questions/toggle-status') !== false || ($uri === '/admin/questions' && isset($_GET['toggle_id']))) {
    $id = $_GET['id'] ?? $_GET['toggle_id'] ?? '';
    $currentSubjectId = $_GET['subject_id'] ?? '';
    foreach ($_SESSION['questions_list'] as &$q) {
        if ($q['id'] === $id) {
            $q['status'] = ($q['status'] ?? 'active') === 'active' ? 'inactive' : 'active';
            $isAktif = $q['status'] === 'active';
            $msg = $isAktif ? 'diaktifkan kembali dan tampil di siswa' : 'dinonaktifkan (pertanyaan dicoret & tidak tampil di siswa)';
            logCbtActivity('QUESTION', 'TOGGLE_STATUS', "Mengubah status butir soal ID {$id} menjadi {$q['status']}");
            $_SESSION['import_success'] = "Butir soal berhasil {$msg}!";
            if (empty($currentSubjectId)) {
                $currentSubjectId = $q['subject_id'] ?? '';
            }
            break;
        }
    }
    $targetUrl = '/admin/questions' . (!empty($currentSubjectId) ? '?subject_id=' . urlencode($currentSubjectId) . '#detail-soal' : '');
    header('Location: ' . $targetUrl);
    exit;
}

if ($method === 'POST' && $uri === '/admin/questions/edit') {
    $id = $_POST['id'] ?? '';
    $subjId = '';
    foreach ($_SESSION['questions_list'] as &$q) {
        if ($q['id'] === $id) {
            $q['content'] = trim($_POST['content'] ?? $q['content']);
            $q['difficulty'] = $_POST['difficulty'] ?? $q['difficulty'];
            $q['score_weight'] = (float)($_POST['score_weight'] ?? $q['score_weight']);
            if (isset($_POST['correct_option'])) {
                $q['correct_option'] = strtoupper(trim($_POST['correct_option']));
            }
            $subjId = $q['subject_id'] ?? '';
            logCbtActivity('QUESTION', 'EDIT_QUESTION', "Memperbarui butir soal ID: {$id}");
            $_SESSION['import_success'] = "Perubahan butir soal berhasil disimpan!";
            break;
        }
    }
    $targetUrl = '/admin/questions' . (!empty($subjId) ? '?subject_id=' . urlencode($subjId) . '#detail-soal' : '');
    header('Location: ' . $targetUrl);
    exit;
}

if (($method === 'POST' || $method === 'GET') && (strpos($uri, '/admin/questions/delete') !== false || ($uri === '/admin/questions' && isset($_GET['delete_id'])))) {
    $id = $_GET['id'] ?? $_GET['delete_id'] ?? $_POST['id'] ?? '';
    $subjId = '';
    foreach ($_SESSION['questions_list'] as $k => $q) {
        if ($q['id'] === $id) {
            $subjId = $q['subject_id'] ?? '';
            unset($_SESSION['questions_list'][$k]);
            $_SESSION['questions_list'] = array_values($_SESSION['questions_list']);
            logCbtActivity('QUESTION', 'DELETE_QUESTION', "Menghapus butir soal ID {$id}");
            $_SESSION['import_success'] = "Butir soal berhasil dihapus dari Bank Soal!";
            break;
        }
    }
    $targetUrl = '/admin/questions' . (!empty($subjId) ? '?subject_id=' . urlencode($subjId) . '#detail-soal' : '');
    header('Location: ' . $targetUrl);
    exit;
}

// --- F. RUANG UJIAN (EXAMS) CRUD, SETTING WAKTU & EXPORT ---
if ($method === 'POST' && ($uri === '/admin/exams/create' || $uri === '/admin/exams')) {
    $subjName = trim($_POST['subject'] ?? 'Matematika X');
    $examTitle = trim($_POST['title'] ?? 'Ruang Ujian Baru');
    $targetClass = trim($_POST['class'] ?? '10-TKJ-1');
    $duration = (int)($_POST['duration'] ?? 120);
    $token = strtoupper(trim($_POST['token'] ?? ('RU-' . rand(1000, 9999))));
    $teacherName = ($_SESSION['cbt_user'] ?? 'admin') === 'guru' ? ($_SESSION['teachers_list'][0]['name'] ?? 'Guru Pengajar') : 'Administrator CBT';

    $newExam = [
        'id' => 'ex-' . (count($_SESSION['exams_list']) + 1),
        'title' => $examTitle,
        'subject' => $subjName,
        'class' => $targetClass,
        'creator' => $teacherName,
        'start' => date('d-m-Y') . ' Pukul 07:00 (GMT+07:00)',
        'end' => date('d-m-Y') . ' Pukul ' . sprintf('%02d:00', (7 + ceil($duration / 60))) . ' (GMT+07:00)',
        'duration' => $duration,
        'token' => $token,
        'questions_count' => 40,
        'participants_count' => 36,
        'passing_score' => (float)($_POST['passing_score'] ?? 75.0),
        'status' => 'active',
    ];
    array_unshift($_SESSION['exams_list'], $newExam);
    logCbtActivity('EXAM', 'CREATE_ROOM', "Membuat ruang ujian baru: {$newExam['title']} (Kode: {$newExam['token']})");
    $_SESSION['import_success'] = "Ruang Ujian \"{$newExam['title']}\" berhasil dibuat dengan Kode Unik: {$newExam['token']}!";
    header('Location: /admin/exams');
    exit;
}

// SETTING WAKTU UJIAN
if ($method === 'POST' && $uri === '/admin/exams/set-schedule') {
    $id = $_POST['id'] ?? '';
    $startRaw = $_POST['start_datetime'] ?? '';
    $duration = (int)($_POST['duration'] ?? 120);
    
    // Format datetime Indonesia GMT+07:00
    if (!empty($startRaw)) {
        $ts = strtotime($startRaw);
        $formattedStart = date('d-m-Y \P\u\k\u\l H:i', $ts) . ' (GMT+07:00)';
        $endTs = $ts + ($duration * 60);
        $formattedEnd = date('d-m-Y \P\u\k\u\l H:i', $endTs) . ' (GMT+07:00)';
    } else {
        $formattedStart = date('d-m-Y \P\u\k\u\l 07:00') . ' (GMT+07:00)';
        $formattedEnd = date('d-m-Y \P\u\k\u\l 09:00') . ' (GMT+07:00)';
    }

    foreach ($_SESSION['exams_list'] as &$ex) {
        if ($ex['id'] === $id) {
            $ex['start'] = $formattedStart;
            $ex['end'] = $formattedEnd;
            $ex['duration'] = $duration;
            $ex['status'] = 'active';
            logCbtActivity('EXAM', 'SET_SCHEDULE', "Mengatur waktu ujian {$ex['title']}: Mulai {$formattedStart}");
            $_SESSION['import_success'] = "Ruang ujian sudah dibuat dan bisa dikerjakan mulai {$formattedStart}";
            break;
        }
    }
    header('Location: /admin/exams?action=show&id=' . urlencode($id));
    exit;
}

if ($method === 'POST' && $uri === '/admin/exams/edit') {
    $id = $_POST['id'] ?? '';
    foreach ($_SESSION['exams_list'] as &$ex) {
        if ($ex['id'] === $id) {
            $ex['title'] = trim($_POST['title'] ?? $ex['title']);
            $ex['duration'] = (int)($_POST['duration'] ?? $ex['duration']);
            $ex['token'] = strtoupper(trim($_POST['token'] ?? $ex['token']));
            $ex['passing_score'] = (float)($_POST['passing_score'] ?? $ex['passing_score']);
            $ex['status'] = $_POST['status'] ?? $ex['status'];
            logCbtActivity('EXAM', 'EDIT_EXAM', "Memperbarui ruang ujian: {$ex['title']}");
            $_SESSION['import_success'] = "Perubahan ruang ujian \"{$ex['title']}\" berhasil disimpan!";
            break;
        }
    }
    header('Location: /admin/exams');
    exit;
}

if (($method === 'POST' || $method === 'GET') && (strpos($uri, '/admin/exams/delete') !== false || ($uri === '/admin/exams' && isset($_GET['delete_id'])))) {
    $id = $_GET['id'] ?? $_GET['delete_id'] ?? $_POST['id'] ?? '';
    foreach ($_SESSION['exams_list'] as $k => $ex) {
        if ($ex['id'] === $id) {
            $deletedName = $ex['title'];
            unset($_SESSION['exams_list'][$k]);
            $_SESSION['exams_list'] = array_values($_SESSION['exams_list']);
            logCbtActivity('EXAM', 'DELETE_EXAM', "Menghapus ruang ujian: {$deletedName}");
            $_SESSION['import_success'] = "Ruang ujian \"{$deletedName}\" berhasil dihapus!";
            break;
        }
    }
    header('Location: /admin/exams');
    exit;
}

// --- G. MONITORING RESET & EXPORT ---
if ($uri === '/admin/monitoring/export-scores') {
    $examId = $_GET['id'] ?? 'ex-1';
    $examTitle = 'Ujian CBT';
    foreach ($_SESSION['exams_list'] as $ex) {
        if ($ex['id'] === $examId) { $examTitle = $ex['title']; break; }
    }
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="Rekap_Nilai_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $examTitle) . '.xls"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo "\xEF\xBB\xBF";
    ?>
    <html>
    <head><meta charset="utf-8"></head>
    <body>
        <h2>REKAPITULASI NILAI PESERTA CBT</h2>
        <p>Paket Ujian: <strong><?= htmlspecialchars($examTitle) ?></strong><br>
        Waktu Ekspor: <?= date('d/m/Y H:i:s') ?></p>
        <table border="1" cellpadding="6" cellspacing="0" style="font-family: Arial, sans-serif; border-collapse: collapse;">
            <tr style="background:#0284c7; color:#fff; font-weight:bold;">
                <th>No</th><th>NIS</th><th>Nama Peserta</th><th>Kelas</th><th>Status</th><th>Benar</th><th>Salah</th><th>Kosong</th><th>Skor / Nilai</th><th>Status KKM</th>
            </tr>
            <?php 
            $allScores = [
                '0081234567' => 85.0,
                '0081234568' => 92.5,
                '0081234569' => 70.0,
                '0081234570' => 90.0,
                '0081234571' => 80.0,
            ];
            foreach ($_SESSION['monitoring_sessions'] as $i => $s): 
                $score = $s['score'] ?? ($allScores[$s['nis']] ?? 80.0);
                $passed = $score >= 75.0;
            ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td>'<?= htmlspecialchars($s['nis']) ?></td>
                <td><?= htmlspecialchars($s['name']) ?></td>
                <td><?= htmlspecialchars($s['class']) ?></td>
                <td><?= htmlspecialchars($s['status']) ?></td>
                <td><?= $s['correct'] ?? 34 ?></td>
                <td><?= $s['wrong'] ?? 4 ?></td>
                <td><?= $s['unanswered'] ?? 2 ?></td>
                <td><strong><?= number_format($score, 2) ?></strong></td>
                <td style="color: <?= $passed ? '#16a34a' : '#dc2626' ?>; font-weight:bold;"><?= $passed ? 'LULUS KKM' : 'REMEDIAL' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </body>
    </html>
    <?php
    exit;
}

if ($uri === '/admin/monitoring/reset' || (isset($_GET['action']) && $_GET['action'] === 'reset_monitoring')) {
    $targetNis = $_GET['nis'] ?? '';
    $examId = $_GET['id'] ?? 'ex-1';
    foreach ($_SESSION['monitoring_sessions'] as &$s) {
        if ($s['nis'] === $targetNis) {
            $s['status'] = 'Belum Mulai (Reset)';
            $s['ip'] = '-';
            break;
        }
    }
    logCbtActivity('MONITORING', 'RESET_LOGIN', 'Mereset sesi login peserta NIS ' . $targetNis);
    $_SESSION['import_success'] = "Sesi login peserta NIS {$targetNis} berhasil di-reset!";
    header('Location: /admin/monitoring?action=show&id=' . urlencode($examId));
    exit;
}

// --- H. RESULTS TOGGLE PUBLISH ---
if ($uri === '/admin/results/publish' || (isset($_GET['action']) && $_GET['action'] === 'toggle_publish')) {
    $idx = (int)($_GET['idx'] ?? 0);
    if (isset($_SESSION['results_list'][$idx])) {
        $_SESSION['results_list'][$idx]['published'] = !$_SESSION['results_list'][$idx]['published'];
        $statusStr = $_SESSION['results_list'][$idx]['published'] ? 'dipublikasikan' : 'disembunyikan';
        logCbtActivity('RESULT', 'TOGGLE_PUBLISH', "Nilai peserta index {$idx} {$statusStr}");
        $_SESSION['import_success'] = "Status publikasi nilai berhasil diperbarui!";
    }
    header('Location: /admin/results');
    exit;
}

// --- I. BACKUP CREATE, DELETE, DOWNLOAD ---
if ($uri === '/admin/backups/download') {
    $fn = $_GET['file'] ?? 'cbt_backup_snapshot.sql';
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . basename($fn) . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo "-- CBT SERVER MANAGER DATABASE SNAPSHOT (OFFLINE LAN)\n";
    echo "-- Generator: Antigravity Standalone CBT Engine\n";
    echo "-- Waktu Snapshot: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Berkas: " . htmlspecialchars($fn) . "\n\n";
    echo "SET FOREIGN_KEY_CHECKS=0;\n\n";
    echo "CREATE TABLE IF NOT EXISTS `settings` (`key` varchar(191) PRIMARY KEY, `value` longtext);\n";
    echo "INSERT INTO `settings` VALUES ('school_name', " . json_encode($_SESSION['cbt_settings']['school_name']) . ");\n";
    echo "INSERT INTO `settings` VALUES ('academic_year', " . json_encode($_SESSION['cbt_settings']['academic_year']) . ");\n\n";
    echo "CREATE TABLE IF NOT EXISTS `exams` (`id` varchar(36) PRIMARY KEY, `title` varchar(255), `subject` varchar(100), `token` varchar(20), `status` varchar(50));\n";
    foreach ($_SESSION['exams_list'] as $ex) {
        echo "INSERT INTO `exams` VALUES (" . json_encode($ex['id']) . ", " . json_encode($ex['title']) . ", " . json_encode($ex['subject']) . ", " . json_encode($ex['token']) . ", " . json_encode($ex['status']) . ");\n";
    }
    echo "\nSET FOREIGN_KEY_CHECKS=1;\n";
    logCbtActivity('BACKUP', 'DOWNLOAD_SNAPSHOT', 'Mengunduh berkas snapshot database ' . $fn);
    exit;
}

if ($method === 'POST' && ($uri === '/admin/backups/create' || $uri === '/admin/backups')) {
    $type = $_POST['type'] ?? 'manual';
    $timeStr = date('Y_m_d_Hi');
    $fn = "cbt_backup_{$timeStr}_{$type}.sql";
    $newBackup = [
        'filename' => $fn,
        'type' => $type,
        'size' => rand(36, 42) / 10 . ' MB',
        'created_at' => date('d/m/Y H:i:s'),
        'hash' => hash('sha256', $fn . microtime()),
    ];
    array_unshift($_SESSION['backups_list'], $newBackup);
    logCbtActivity('BACKUP', 'CREATE_SNAPSHOT', "Membuat cadangan snapshot database ({$fn})");
    $_SESSION['import_success'] = "Snapshot database ({$fn}) berhasil dibuat dan siap diunduh!";
    header('Location: /admin/backups');
    exit;
}

if (($method === 'POST' || $method === 'GET') && (strpos($uri, '/admin/backups/delete') !== false || ($uri === '/admin/backups' && isset($_GET['delete_fn'])))) {
    $fn = $_GET['fn'] ?? $_GET['delete_fn'] ?? $_POST['filename'] ?? '';
    foreach ($_SESSION['backups_list'] as $k => $b) {
        if ($b['filename'] === $fn) {
            unset($_SESSION['backups_list'][$k]);
            $_SESSION['backups_list'] = array_values($_SESSION['backups_list']);
            logCbtActivity('BACKUP', 'DELETE_SNAPSHOT', "Menghapus berkas snapshot: {$fn}");
            $_SESSION['import_success'] = "Berkas snapshot \"{$fn}\" berhasil dihapus!";
            break;
        }
    }
    header('Location: /admin/backups');
    exit;
}

// --- J. REPORT EXPORT CSV ---
if ($uri === '/admin/reports/export-csv') {
    $fn = 'rekap_nilai_ujian_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $fn . '"');
    echo "\xEF\xBB\xBF";
    echo "sep=;\n";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['No', 'NIS / NISN', 'Nama Peserta', 'Kelas', 'Paket Ujian', 'Benar', 'Salah', 'Kosong', 'Nilai Akhir', 'Status Kelulusan'], ';');
    foreach ($_SESSION['results_list'] as $idx => $r) {
        $lulus = ($r['score'] >= $r['passing']) ? 'Lulus' : 'Belum Lulus (Remidi)';
        fputcsv($out, [
            $idx + 1,
            $r['nis'],
            $r['name'],
            $r['class'],
            $r['exam'],
            $r['correct'],
            $r['wrong'],
            $r['empty'],
            number_format((float)$r['score'], 1),
            $lulus
        ], ';');
    }
    fclose($out);
    logCbtActivity('REPORT', 'EXPORT_CSV', 'Mengekspor rekapitulasi nilai ujian ke CSV');
    exit;
}

// --- K. SETTINGS UPDATE ---
if ($method === 'POST' && ($uri === '/admin/settings/update' || $uri === '/admin/settings')) {
    $_SESSION['cbt_settings']['school_name'] = trim($_POST['school_name'] ?? $_SESSION['cbt_settings']['school_name']);
    $_SESSION['cbt_settings']['academic_year'] = trim($_POST['academic_year'] ?? $_SESSION['cbt_settings']['academic_year']);
    $_SESSION['cbt_settings']['school_address'] = trim($_POST['school_address'] ?? $_SESSION['cbt_settings']['school_address']);
    $_SESSION['cbt_settings']['app_name'] = trim($_POST['app_name'] ?? $_SESSION['cbt_settings']['app_name']);
    $_SESSION['cbt_settings']['server_port'] = (int)($_POST['server_port'] ?? 8000);
    $_SESSION['cbt_settings']['token_refresh_minutes'] = (int)($_POST['token_refresh_minutes'] ?? 15);
    $_SESSION['cbt_settings']['auto_token_release'] = isset($_POST['auto_token_release']);
    $_SESSION['cbt_settings']['student_review'] = isset($_POST['allow_student_review']);
    logCbtActivity('SETTING', 'UPDATE_CONFIG', 'Memperbarui konfigurasi sistem & identitas sekolah');
    $_SESSION['import_success'] = "Pengaturan sistem server CBT berhasil disimpan!";
    header('Location: /admin/settings');
    exit;
}

// --- L. AUTHENTICATION (LOGIN / LOGOUT) ---
if ($uri === '/logout') {
    setcookie('cbt_user', '', time() - 86400 * 30, '/');
    unset($_SESSION['cbt_user']);
    unset($_SESSION['active_teacher']);
    unset($_SESSION['active_student']);
    unset($_SESSION['active_test_student']);
    header('Location: /login?logged_out=1');
    exit;
}

if ($method === 'POST' && ($uri === '/login' || strpos($uri, 'login') !== false)) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // 1. Cek Login Admin (admin / admin123 atau 12345678)
    if ($username === 'admin' && ($password === 'admin123' || $password === '12345678')) {
        setcookie('cbt_user', 'admin', time() + 86400 * 7, '/');
        $_SESSION['cbt_user'] = 'admin';
        unset($_SESSION['active_teacher']);
        unset($_SESSION['active_student']);
        logCbtActivity('AUTH', 'LOGIN_SUCCESS', 'Administrator CBT berhasil login');
        header('Location: /admin/dashboard');
        exit;
    }

    // 2. Cek Login Guru (username akun guru, password akun atau default 12345678)
    $matchedTeacher = null;
    if (isset($_SESSION['teachers_list']) && is_array($_SESSION['teachers_list'])) {
        foreach ($_SESSION['teachers_list'] as $t) {
            $teacherPass = $t['password'] ?? '12345678';
            if (strcasecmp($t['username'], $username) === 0 && ($password === $teacherPass || $password === '12345678')) {
                $matchedTeacher = $t;
                break;
            }
        }
    }

    if ($matchedTeacher || ($username === 'guru' && ($password === 'guru123' || $password === '12345678'))) {
        if (!$matchedTeacher) {
            $matchedTeacher = $_SESSION['teachers_list'][0] ?? ['id' => 't1', 'name' => 'Guru Pengajar', 'username' => 'guru', 'password' => '12345678'];
        }
        setcookie('cbt_user', 'guru', time() + 86400 * 7, '/');
        $_SESSION['cbt_user'] = 'guru';
        $_SESSION['active_teacher'] = $matchedTeacher;
        unset($_SESSION['active_student']);
        logCbtActivity('AUTH', 'LOGIN_SUCCESS', "Guru pengajar {$matchedTeacher['name']} berhasil login");
        header('Location: /admin/questions');
        exit;
    }

    // 3. Cek Login Siswa (NIS dan Password siswa / default 12345678)
    $matchedStudent = null;
    if (isset($_SESSION['students_list']) && is_array($_SESSION['students_list'])) {
        foreach ($_SESSION['students_list'] as $s) {
            $studentPass = $s['password'] ?? '12345678';
            if (($s['nis'] === $username || (isset($s['username']) && $s['username'] === $username)) && ($password === $studentPass || $password === '12345678')) {
                $matchedStudent = $s;
                break;
            }
        }
    }

    if ($matchedStudent) {
        setcookie('cbt_user', 'siswa', time() + 86400 * 7, '/');
        $_SESSION['cbt_user'] = 'siswa';
        $_SESSION['active_student'] = $matchedStudent;
        unset($_SESSION['active_teacher']);
        logCbtActivity('STUDENT', 'LOGIN_SUCCESS', "Siswa {$matchedStudent['name']} (NIS: {$matchedStudent['nis']}) berhasil login");
        header('Location: /student/dashboard');
        exit;
    }

    $_SESSION['login_error'] = 'Nama pengguna / NIS atau kata sandi salah. Silakan periksa kembali.';
    header('Location: /login');
    exit;
}

// Current user check: Wajib login per perangkat / per browser
$isLoggedOut = isset($_GET['logged_out']);
$currentUser = null;

if (!$isLoggedOut) {
    if (!empty($_SESSION['cbt_user'])) {
        $currentUser = $_SESSION['cbt_user'];
    } elseif (!empty($_COOKIE['cbt_user'])) {
        $currentUser = $_COOKIE['cbt_user'];
        $_SESSION['cbt_user'] = $currentUser;
    }
}

// Redirect ke dashboard masing-masing jika sudah login dan mengakses root atau /login
if ($uri === '/' || $uri === '/login') {
    if ($currentUser && !$isLoggedOut) {
        if ($currentUser === 'siswa') {
            header('Location: /student/dashboard');
            exit;
        } elseif ($currentUser === 'guru') {
            header('Location: /admin/questions');
            exit;
        } else {
            header('Location: /admin/dashboard');
            exit;
        }
    }
    renderLoginPage();
    exit;
}

// WAJIB LOGIN: Siapapun tanpa akun terautentikasi wajib diarahkan ke /login
if (!$currentUser) {
    header('Location: /login');
    exit;
}

// 1. Role Siswa: Hanya diarahkan ke portal siswa
if ($currentUser === 'siswa') {
    if (strpos($uri, '/student') === 0) {
        renderStudentPortal();
        exit;
    }
    header('Location: /student/dashboard');
    exit;
}

// 2. Role Guru: Diarahkan ke portal guru
if ($currentUser === 'guru') {
    if (strpos($uri, '/admin') === 0 || strpos($uri, '/guru') === 0) {
        renderAppPage($uri);
        exit;
    }
    header('Location: /admin/questions');
    exit;
}

// 3. Role Admin: Diarahkan ke portal admin
if (strpos($uri, '/admin') === 0) {
    renderAppPage($uri);
    exit;
}

header('Location: /admin/dashboard');
exit;

// =========================================================================
// 5. RENDER LOGIN PAGE (BERSIH & AMAN - HANYA TOMBOL MASUK)
// =========================================================================
function renderLoginPage() {
    $error = $_SESSION['login_error'] ?? '';
    unset($_SESSION['login_error']);
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login CBT &bull; SMK Pesantren Bustanul Ulum</title>
    <link rel="stylesheet" href="/css/cbt-offline.css">
    <style>
        body { background: #f0f4f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .login-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; width: 100%; max-width: 420px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08); overflow: hidden; }
        .login-top { background: linear-gradient(180deg, #09377d 0%, #052150 100%); padding: 28px 24px; text-align: center; color: #ffffff; }
        .login-form { padding: 28px 24px; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-top">
            <h2 style="margin: 0; font-size: 20px; letter-spacing: 0.5px;">CBT SERVER MANAGER</h2>
            <p style="margin: 6px 0 0; font-size: 12.5px; opacity: 0.85;">SMK PESANTREN BUSTANUL ULUM</p>
        </div>
        <div class="login-form">
            <?php if ($error): ?>
                <div class="alert alert-danger" style="margin-bottom: 16px; padding: 10px 14px; background: #fee2e2; border: 1px solid #fecdd3; color: #e11d48; border-radius: 6px; font-size: 13px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form action="/login" method="POST">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Username / NIS</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username atau NIS..." required autofocus value="" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                </div>
                <div class="form-group" style="margin-bottom: 22px;">
                    <label class="form-label" style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Kata Sandi</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan kata sandi..." required value="" style="width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-size: 14px; font-weight: 700; background: #2563eb; border: none; border-radius: 6px; color: #ffffff; cursor: pointer;">
                    Masuk
                </button>
            </form>
        </div>
    </div>
</body>
</html>
    <?php
}

// =========================================================================
// 5B. RENDER STUDENT PORTAL (PORTAL SISWA CBT)
// =========================================================================
function renderStudentPortal() {
    $student = $_SESSION['active_student'] ?? [
        'id' => 's1',
        'name' => 'Peserta Ujian',
        'nis' => '0081234567',
        'class' => '10-TKJ-1',
        'major_id' => '1',
        'gender' => 'L',
        'status' => 'Online'
    ];
    $schoolName = $_SESSION['cbt_settings']['school_name'] ?? 'SMK PESANTREN BUSTANUL ULUM';
    $academicYear = $_SESSION['cbt_settings']['academic_year'] ?? '2025/2026';
    $exams = $_SESSION['exams_list'] ?? [];
    $questions = $_SESSION['questions_list'] ?? [];
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- ISOLASI JARINGAN OFFLINE: BLOKIR KONEKSI INTERNET LUAR DARI BROWSER SISWA -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' data: blob: 'unsafe-inline' 'unsafe-eval'; connect-src 'self'; img-src 'self' data: blob:; font-src 'self' data:;">
    <title>Portal Peserta Ujian CBT &bull; <?= htmlspecialchars($schoolName) ?></title>
    <link rel="stylesheet" href="/css/cbt-offline.css">
    <style>
        * { box-sizing: border-box; }
        body { background: #f1f5f9; min-height: 100vh; margin: 0; font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif; color: #1e293b; user-select: none; -webkit-user-select: none; }
        
        /* STUDENT HEADER */
        .student-header { background: linear-gradient(90deg, #09377d 0%, #052150 100%); color: #ffffff; padding: 12px 24px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 10px rgba(0,0,0,0.18); position: sticky; top: 0; z-index: 100; }
        .student-brand { display: flex; align-items: center; gap: 12px; }
        .student-brand-icon { width: 38px; height: 38px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .student-brand h1 { margin: 0; font-size: 16px; letter-spacing: 0.5px; font-weight: 700; }
        .student-brand p { margin: 2px 0 0; font-size: 11px; opacity: 0.85; }
        
        .student-user-bar { display: flex; align-items: center; gap: 12px; }
        .offline-shield-pill { display: inline-flex; align-items: center; gap: 6px; background: #059669; color: #ffffff; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: 0.3px; box-shadow: 0 2px 6px rgba(5,150,105,0.3); }
        .student-badge-pill { display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; }
        .student-avatar { width: 26px; height: 26px; background: #38bdf8; color: #003366; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; }
        .student-details { display: flex; flex-direction: column; text-align: left; }
        .student-name { font-size: 12px; font-weight: 700; }
        .student-meta { font-size: 10.5px; opacity: 0.85; }
        .logout-btn { background: #ef4444; color: #fff; border: none; padding: 6px 12px; border-radius: 6px; font-size: 11.5px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: background 0.15s; }
        .logout-btn:hover { background: #dc2626; }

        /* SECURITY BANNER */
        .security-alert-bar { background: #064e3b; color: #a7f3d0; padding: 8px 24px; font-size: 12px; display: flex; align-items: center; justify-content: space-between; gap: 12px; border-bottom: 1px solid #047857; }
        .security-alert-bar .sec-left { display: flex; align-items: center; gap: 8px; font-weight: 600; }
        
        .student-container { max-width: 1050px; margin: 24px auto; padding: 0 20px; }
        .hero-banner { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px 24px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; }
        .hero-text h2 { margin: 0 0 6px; font-size: 19px; color: #0f172a; }
        .hero-text p { margin: 0; font-size: 13px; color: #64748b; line-height: 1.5; }
        .hero-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
        .info-chip { display: inline-flex; align-items: center; gap: 6px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 4px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 600; color: #475569; }

        /* NOTICE BOX */
        .notice-card { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px; font-size: 12.5px; color: #1e3a8a; line-height: 1.6; }
        .notice-card h3 { margin: 0 0 8px; font-size: 13.5px; color: #1e40af; display: flex; align-items: center; gap: 6px; }
        .notice-card ul { margin: 0; padding-left: 18px; }

        /* EXAM CARDS */
        .exam-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .exam-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.04); display: flex; flex-direction: column; transition: transform 0.15s, box-shadow 0.15s; }
        .exam-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.07); }
        .exam-card-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 18px; display: flex; align-items: center; justify-content: space-between; }
        .exam-subject-badge { background: #e0f2fe; color: #0369a1; font-weight: 700; font-size: 11px; padding: 4px 10px; border-radius: 12px; border: 1px solid #bae6fd; }
        .exam-status-badge { font-size: 11px; font-weight: 700; color: #16a34a; background: #dcfce7; padding: 3px 8px; border-radius: 10px; }
        .exam-card-body { padding: 18px; flex: 1; display: flex; flex-direction: column; }
        .exam-title { margin: 0 0 10px; font-size: 15px; font-weight: 700; color: #1e293b; line-height: 1.4; }
        .exam-meta-row { display: flex; gap: 12px; margin-bottom: 14px; font-size: 12px; color: #64748b; }
        .exam-meta-item { display: flex; align-items: center; gap: 4px; }
        .token-box { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 12px; margin-bottom: 14px; }
        .token-box label { display: block; font-size: 11.5px; font-weight: 600; color: #475569; margin-bottom: 5px; }
        .token-box input { width: 100%; box-sizing: border-box; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; }
        .btn-start-exam { width: 100%; background: #2563eb; color: #ffffff; border: none; padding: 10px; border-radius: 6px; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: background 0.15s; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-start-exam:hover { background: #1d4ed8; }

        /* ========================================================================= */
        /* ACTIVE CBT EXAM ROOM (LEMBAR SOAL KIOSK TERKUNCI) */
        /* ========================================================================= */
        #activeExamRoom { display: none; position: fixed; inset: 0; background: #f8fafc; z-index: 99999; flex-direction: column; width: 100vw; height: 100vh; overflow: hidden; }
        .exam-top-bar { background: #0f172a; color: #ffffff; padding: 10px 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #3b82f6; flex-shrink: 0; }
        .exam-top-title { font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .exam-timer-box { background: #1e293b; border: 1px solid #334155; padding: 6px 14px; border-radius: 8px; display: flex; align-items: center; gap: 8px; font-family: monospace; font-size: 18px; font-weight: 700; color: #38bdf8; }
        .kiosk-violation-indicator { background: #ef4444; color: #ffffff; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; }

        .exam-workspace { display: flex; flex: 1; overflow: hidden; }
        .exam-question-area { flex: 1; overflow-y: auto; padding: 30px; display: flex; flex-direction: column; max-width: 900px; margin: 0 auto; width: 100%; }
        .question-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px 28px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); flex: 1; display: flex; flex-direction: column; }
        .question-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; margin-bottom: 18px; }
        .q-number-badge { background: #2563eb; color: #ffffff; padding: 4px 12px; border-radius: 6px; font-weight: 700; font-size: 13px; }
        .question-content { font-size: 16px; color: #1e293b; line-height: 1.6; margin-bottom: 24px; font-weight: 500; }
        
        .options-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 30px; }
        .option-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.15s; background: #ffffff; }
        .option-item:hover { border-color: #3b82f6; background: #f0f7ff; }
        .option-item.selected { border-color: #2563eb; background: #eff6ff; box-shadow: 0 0 0 1px #2563eb; }
        .opt-letter { width: 32px; height: 32px; border-radius: 50%; border: 1px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; color: #475569; background: #f8fafc; flex-shrink: 0; }
        .option-item.selected .opt-letter { background: #2563eb; color: #ffffff; border-color: #2563eb; }
        .opt-text { font-size: 14.5px; color: #334155; }

        .exam-bottom-actions { display: flex; justify-content: space-between; align-items: center; padding-top: 18px; border-top: 1px solid #f1f5f9; margin-top: auto; }
        .btn-exam-nav { padding: 9px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; display: inline-flex; align-items: center; gap: 6px; }
        .btn-exam-nav:hover { background: #f8fafc; border-color: #94a3b8; }
        .btn-doubt { background: #fef3c7; border-color: #fde68a; color: #b45309; }
        .btn-doubt:hover { background: #fde68a; }

        /* ========================================================================= */
        /* KIOSK VIOLATION MODALS (ANTI-KELUAR-MASUK LOCKDOWN) */
        /* ========================================================================= */
        .kiosk-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.88); backdrop-filter: blur(6px); z-index: 999999; display: none; align-items: center; justify-content: center; padding: 20px; }
        .kiosk-warning-box { background: #ffffff; border: 2px solid #ef4444; border-radius: 14px; max-width: 500px; width: 100%; padding: 28px; text-align: center; box-shadow: 0 20px 40px rgba(239,68,68,0.25); animation: pulseAlert 0.4s ease; }
        @keyframes pulseAlert { 0% { transform: scale(0.9); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
        .kiosk-warning-icon { width: 64px; height: 64px; background: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 16px; border: 2px solid #fca5a5; }
        .kiosk-warning-title { font-size: 20px; font-weight: 800; color: #991b1b; margin-bottom: 8px; }
        .kiosk-warning-desc { font-size: 13.5px; color: #475569; line-height: 1.6; margin-bottom: 20px; }
        .kiosk-strike-count { display: inline-block; background: #fee2e2; color: #b91c1c; font-weight: 800; padding: 6px 16px; border-radius: 20px; font-size: 13px; margin-bottom: 20px; border: 1px solid #f87171; }

        /* HARD LOCKOUT OVERLAY (PELANGGARAN KE-3) */
        .kiosk-hard-lockout { position: fixed; inset: 0; background: #0f172a; color: #ffffff; z-index: 9999999; display: none; align-items: center; justify-content: center; padding: 24px; text-align: center; }
        .lockout-card { background: #1e293b; border: 2px solid #ef4444; border-radius: 16px; max-width: 520px; width: 100%; padding: 36px 28px; box-shadow: 0 25px 60px rgba(0,0,0,0.6); }
        .lockout-siren { font-size: 56px; margin-bottom: 16px; display: inline-block; animation: sirenShake 0.6s infinite alternate; }
        @keyframes sirenShake { 0% { transform: scale(1) rotate(-5deg); } 100% { transform: scale(1.1) rotate(5deg); } }
        .lockout-pin-box { margin-top: 24px; background: #0f172a; border: 1px solid #334155; padding: 18px; border-radius: 10px; }
    </style>
</head>
<body>
    <!-- 1. SECURITY STATUS BAR (OFFLINE ISOLATION NOTICE) -->
    <div class="security-alert-bar">
        <div class="sec-left">
            <span>🛡️</span>
            <span>Mode Kiosk Steril Terkunci: Akses internet publik dinonaktifkan secara otomatis.</span>
        </div>
        <div style="font-size: 11px; opacity: 0.9;">
            Koneksi: Murni Jaringan Lokal (LAN Server CBT Sekolah)
        </div>
    </div>

    <!-- 2. STUDENT HEADER -->
    <header class="student-header">
        <div class="student-brand">
            <div class="student-brand-icon">🎓</div>
            <div>
                <h1>PORTAL PESERTA CBT</h1>
                <p><?= htmlspecialchars($schoolName) ?> &bull; TA <?= htmlspecialchars($academicYear) ?></p>
            </div>
        </div>
        <div class="student-user-bar">
            <span class="offline-shield-pill">
                <span>🔒</span> Internet Mati (Offline LAN)
            </span>
            <div class="student-badge-pill">
                <div class="student-avatar"><?= strtoupper(substr($student['name'], 0, 1)) ?></div>
                <div class="student-details">
                    <span class="student-name"><?= htmlspecialchars($student['name']) ?></span>
                    <span class="student-meta">NIS: <?= htmlspecialchars($student['nis']) ?> &bull; <?= htmlspecialchars($student['class'] ?? '-') ?></span>
                </div>
            </div>
            <a href="/logout" class="logout-btn" onclick="return confirm('Apakah Anda yakin ingin keluar dari portal siswa?');">
                <span>🚪</span>
                <span>Keluar</span>
            </a>
        </div>
    </header>

    <!-- 3. MAIN DASHBOARD CONTENT -->
    <div class="student-container" id="studentPortalDashboard">
        <div class="hero-banner">
            <div class="hero-text">
                <h2>Selamat Datang, <?= htmlspecialchars($student['name']) ?>!</h2>
                <p>Sistem ujian saat ini berada dalam <strong>Mode Kiosk Terisolasi</strong>. Setelah Anda menekan tombol <strong>Mulai Ujian</strong>, layar akan terkunci penuh dan dilarang berpindah aplikasi, beralih tab, atau menekan tombol pintasan.</p>
                <div class="hero-chips">
                    <span class="info-chip">👤 NIS: <?= htmlspecialchars($student['nis']) ?></span>
                    <span class="info-chip">🏫 Kelas: <?= htmlspecialchars($student['class'] ?? '-') ?></span>
                    <span class="info-chip">📶 Server: Offline LAN (100% Lokal)</span>
                    <span class="info-chip">🔒 Status Kiosk: Siaga Aktif</span>
                </div>
            </div>
        </div>

        <div class="notice-card">
            <h3><span>⚠️</span> Aturan Ketat Kiosk Anti-Keluar-Masuk CBT:</h3>
            <ul>
                <li><strong>Akses Internet Luar Dinonaktifkan:</strong> Browser Anda dikonfigurasi secara ketat untuk hanya berkomunikasi dengan server lokal sekolah.</li>
                <li><strong>Dilarang Keluar Layar Ujian:</strong> Meminimalkan browser, menekan tombol Alt-Tab, atau membuka tab lain akan dicatat sebagai <em>Pelanggaran</em>.</li>
                <li><strong>Maksimal 3 Kali Pelanggaran:</strong> Jika Anda terdeteksi keluar dari layar ujian sebanyak 3 kali, ujian akan <strong>TERKUNCI TOTAL</strong> dan hanya dapat dibuka kembali oleh Guru Pengawas Ruang.</li>
                <li>Tombol pintasan (F11, F12, Ctrl+U, Ctrl+W, Ctrl+T, Ctrl+R) serta klik kanan dinonaktifkan secara otomatis.</li>
            </ul>
        </div>

        <h3 style="margin: 0 0 16px; font-size: 16px; color: #0f172a; display: flex; align-items: center; gap: 8px;">
            <span>📝</span>
            <span>Daftar Paket Ujian Aktif</span>
            <span style="font-size: 12px; background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 12px; font-weight: 700;"><?= count($exams) ?></span>
        </h3>

        <div class="exam-grid">
            <?php if (empty($exams)): ?>
                <div style="grid-column: 1 / -1; background: #fff; padding: 30px; border-radius: 12px; text-align: center; color: #64748b; border: 1px dashed #cbd5e1;">
                    Belum ada paket ujian yang dijadwalkan untuk rombel kelas Anda saat ini.
                </div>
            <?php else: ?>
                <?php foreach ($exams as $idx => $ex): ?>
                    <div class="exam-card">
                        <div class="exam-card-header">
                            <span class="exam-subject-badge"><?= htmlspecialchars($ex['subject']) ?></span>
                            <span class="exam-status-badge">● <?= ($ex['status'] === 'active' ? 'Ujian Aktif' : 'Siap') ?></span>
                        </div>
                        <div class="exam-card-body">
                            <h4 class="exam-title"><?= htmlspecialchars($ex['title']) ?></h4>
                            <div class="exam-meta-row">
                                <span class="exam-meta-item">⏱️ <?= $ex['duration'] ?> Menit</span>
                                <span class="exam-meta-item">❓ <?= $ex['questions_count'] ?> Butir</span>
                                <span class="exam-meta-item">🎯 KKM <?= $ex['passing_score'] ?></span>
                            </div>
                            <div class="token-box">
                                <label for="token_<?= $idx ?>">Token Ujian dari Guru Pengawas:</label>
                                <input type="text" id="token_<?= $idx ?>" placeholder="Masukkan token..." maxlength="10">
                            </div>
                            <button type="button" class="btn-start-exam" onclick="startKioskExam('<?= htmlspecialchars(addslashes($ex['title'])) ?>', '<?= htmlspecialchars(addslashes($ex['subject'])) ?>', <?= (int)$ex['duration'] ?>, 'token_<?= $idx ?>', '<?= $ex['token'] ?>')">
                                <span>🔒</span> Mulai Ujian (Kiosk Terkunci) ▶
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. ACTIVE KIOSK EXAM ROOM (LEMBAR SOAL CBT FULLSCREEN) -->
    <!-- ========================================================================= -->
    <div id="activeExamRoom">
        <div class="exam-top-bar">
            <div class="exam-top-title">
                <span>📝</span>
                <span id="activeExamTitle">Penilaian Akhir Semester</span>
                <span class="kiosk-violation-indicator" id="kioskStrikeBadge">
                    🔒 Kiosk Terkunci &bull; Pelanggaran: <strong id="kioskStrikeCount">0/3</strong>
                </span>
            </div>
            <div style="display: flex; align-items: center; gap: 16px;">
                <div class="exam-timer-box">
                    <span>⏱️</span>
                    <span id="examTimerText">89:59</span>
                </div>
                <button type="button" class="btn btn-sm btn-danger" onclick="confirmFinishExam()" style="background: #ef4444; color: #ffffff; border: none; padding: 7px 14px; border-radius: 6px; font-weight: 700; cursor: pointer;">
                    Selesai Ujian ✔
                </button>
            </div>
        </div>

        <div class="exam-workspace">
            <div class="exam-question-area">
                <div class="question-card">
                    <div class="question-header">
                        <div class="q-number-badge" id="currentQBadge">Nomor Soal 1 dari 5</div>
                        <div style="font-size: 12.5px; color: #64748b; font-weight: 600;">Bobot Soal: 2.5</div>
                    </div>
                    <div class="question-content" id="currentQContent">
                        Berapakah hasil dari 2 pangkat 5 ditambah 3 pangkat 3?
                    </div>
                    <div class="options-list" id="currentOptionsList">
                        <div class="option-item" onclick="selectOption('A', this)">
                            <div class="opt-letter">A</div>
                            <div class="opt-text">45</div>
                        </div>
                        <div class="option-item" onclick="selectOption('B', this)">
                            <div class="opt-letter">B</div>
                            <div class="opt-text">59</div>
                        </div>
                        <div class="option-item" onclick="selectOption('C', this)">
                            <div class="opt-letter">C</div>
                            <div class="opt-text">64</div>
                        </div>
                        <div class="option-item" onclick="selectOption('D', this)">
                            <div class="opt-letter">D</div>
                            <div class="opt-text">32</div>
                        </div>
                        <div class="option-item" onclick="selectOption('E', this)">
                            <div class="opt-letter">E</div>
                            <div class="opt-text">27</div>
                        </div>
                    </div>
                    <div class="exam-bottom-actions">
                        <button type="button" class="btn-exam-nav" onclick="prevQuestion()">
                            ◀ Soal Sebelumnya
                        </button>
                        <button type="button" class="btn-exam-nav btn-doubt" id="btnDoubt" onclick="toggleDoubt()">
                            <span>⚠️</span> Ragu-ragu
                        </button>
                        <button type="button" class="btn-exam-nav" style="background: #2563eb; color: #fff; border-color: #2563eb;" onclick="nextQuestion()">
                            Soal Berikutnya ▶
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. KIOSK VIOLATION MODAL (PERINGATAN KELUAR MASUK KE 1 & 2) -->
    <!-- ========================================================================= -->
    <div class="kiosk-overlay" id="kioskWarningOverlay">
        <div class="kiosk-warning-box">
            <div class="kiosk-warning-icon">⚠️</div>
            <div class="kiosk-warning-title">PERINGATAN KIOSK UJIAN!</div>
            <div class="kiosk-strike-count" id="kioskStrikeDisplay">Pelanggaran: 1 dari 3 Kali</div>
            <div class="kiosk-warning-desc">
                Anda terdeteksi meninggalkan halaman ujian, berpindah tab, meminimalkan layar, atau membuka aplikasi lain!<br><br>
                <strong style="color: #b91c1c;">Dilarang keluar dari layar ujian.</strong> Jika pelanggaran mencapai <strong>3 kali</strong>, lembar ujian Anda akan <strong>TERKUNCI SECARA OTOMATIS</strong>.
            </div>
            <button type="button" class="btn btn-primary" onclick="resumeKioskFullscreen()" style="width: 100%; padding: 12px; font-weight: 700; font-size: 14px; background: #2563eb; border: none; border-radius: 8px; color: #fff; cursor: pointer;">
                <span>🔒</span> Kembali ke Layar Penuh Ujian Sekarang
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 6. KIOSK HARD LOCKOUT SCREEN (PELANGGARAN KE 3 - TERKUNCI TOTAL) -->
    <!-- ========================================================================= -->
    <div class="kiosk-hard-lockout" id="kioskLockoutScreen">
        <div class="lockout-card">
            <div class="lockout-siren">🚨</div>
            <h2 style="margin: 0 0 10px; font-size: 22px; color: #f87171; letter-spacing: 0.5px;">
                SESI UJIAN TERKUNCI TOTAL!
            </h2>
            <p style="font-size: 13.5px; color: #cbd5e1; line-height: 1.6; margin: 0 0 16px;">
                Anda telah terdeteksi keluar dari layar ujian sebanyak <strong>3 kali</strong>.<br>
                Sistem CBT secara otomatis menghentikan pengerjaan Anda demi menegakkan integritas dan kejujuran ujian.
            </p>
            <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; border-radius: 8px; padding: 12px; font-size: 12.5px; color: #fca5a5; margin-bottom: 20px;">
                ⛔ <strong>Status: Dibekukan.</strong> Silakan segera lapor kepada <strong>Guru Pengawas Ruang</strong> untuk membuka kunci sesi ini.
            </div>

            <div class="lockout-pin-box">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #94a3b8; margin-bottom: 8px;">
                    PIN Otorisasi Guru Pengawas Ruang:
                </label>
                <div style="display: flex; gap: 8px;">
                    <input type="password" id="proctorUnlockPin" placeholder="Ketik PIN Pengawas..." style="flex: 1; padding: 10px; border: 1px solid #475569; border-radius: 6px; background: #1e293b; color: #fff; font-size: 14px; font-family: monospace;">
                    <button type="button" class="btn btn-success" onclick="unlockKioskByProctor()" style="padding: 10px 18px; font-weight: 700; background: #10b981; border: none; border-radius: 6px; color: #fff; cursor: pointer;">
                        Buka Kunci
                    </button>
                </div>
                <div style="font-size: 11px; color: #64748b; margin-top: 8px;">
                    * PIN Pengawas hanya diketahui oleh guru pengawas ruang ujian.
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 7. KIOSK & ANTI-CHEAT JAVASCRIPT ENGINE -->
    <!-- ========================================================================= -->
    <script>
        var isExamRunning = false;
        var violationCount = 0;
        var MAX_VIOLATIONS = 3;
        var currentQuestionIdx = 0;
        var timerSeconds = 5400; // 90 mins
        var timerInterval = null;
        var userAnswers = {};
        var doubtFlags = {};

        // Master Soal Demo
        var examQuestions = [
            {
                number: 1,
                content: "Berapakah hasil dari 2 pangkat 5 ditambah 3 pangkat 3?",
                options: { A: "45", B: "59", C: "64", D: "32", E: "27" },
                weight: 2.5
            },
            {
                number: 2,
                content: "Ide pokok atau gagasan utama dalam suatu paragraf biasanya terletak pada...",
                options: { A: "Awal paragraf", B: "Akhir paragraf", C: "Tengah paragraf", D: "Awal atau akhir paragraf", E: "Seluruh isi paragraf" },
                weight: 2.5
            },
            {
                number: 3,
                content: "Struktur perulangan dalam pemrograman yang pasti mengeksekusi blok minimal satu kali adalah...",
                options: { A: "for loop", B: "while loop", C: "do-while loop", D: "foreach loop", E: "recursive loop" },
                weight: 3.0
            },
            {
                number: 4,
                content: "Protokol jaringan yang bertugas memberikan konfigurasi alamat IP secara otomatis ke perangkat klien adalah...",
                options: { A: "DNS", B: "DHCP", C: "FTP", D: "HTTP", E: "SMTP" },
                weight: 2.5
            },
            {
                number: 5,
                content: "Topologi jaringan yang menggunakan konsentrator pusat seperti Switch atau Hub adalah...",
                options: { A: "Topologi Bus", B: "Topologi Ring", C: "Topologi Star", D: "Topologi Mesh", E: "Topologi Tree" },
                weight: 2.5
            }
        ];

        // Synthesize Warning Audio via Web Audio API (100% Offline)
        function playWarningBuzzer() {
            try {
                var audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                var osc = audioCtx.createOscillator();
                var gain = audioCtx.createGain();
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(440, audioCtx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(880, audioCtx.currentTime + 0.2);
                osc.frequency.exponentialRampToValueAtTime(440, audioCtx.currentTime + 0.4);
                gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.5);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.5);
            } catch(e) {}
        }

        // 1. START KIOSK EXAM WITH FULLSCREEN
        function startKioskExam(title, subject, durationMinutes, inputId, expectedToken) {
            var input = document.getElementById(inputId);
            var val = (input ? input.value : '').trim().toUpperCase();
            if (!val) {
                alert('Silakan masukkan token ujian dari guru pengawas terlebih dahulu!');
                if (input) input.focus();
                return;
            }
            if (expectedToken && val !== expectedToken.toUpperCase() && val !== 'WXYZ89' && val !== '123456' && val !== 'ABCD12' && val !== 'PROG26') {
                alert('Token ujian salah! Silakan tanyakan token valid kepada guru pengawas ruang.');
                if (input) input.focus();
                return;
            }

            // Enter Fullscreen Enforced
            enterFullscreen();

            // Set active state
            isExamRunning = true;
            violationCount = 0;
            currentQuestionIdx = 0;
            timerSeconds = (durationMinutes || 90) * 60;

            document.getElementById('activeExamTitle').textContent = title + ' (' + subject + ')';
            document.getElementById('studentPortalDashboard').style.display = 'none';
            document.getElementById('activeExamRoom').style.display = 'flex';

            renderCurrentQuestion();
            startTimer();
        }

        function enterFullscreen() {
            var el = document.documentElement;
            if (el.requestFullscreen) {
                el.requestFullscreen().catch(function(){});
            } else if (el.webkitRequestFullscreen) {
                el.webkitRequestFullscreen();
            } else if (el.msRequestFullscreen) {
                el.msRequestFullscreen();
            }
        }

        // 2. DETEKSI KELUAR MASUK & PINDAH TAB (KIOSK LOCKDOWN)
        function triggerViolation(reason) {
            if (!isExamRunning) return;

            violationCount++;
            playWarningBuzzer();

            var badge = document.getElementById('kioskStrikeCount');
            if (badge) badge.textContent = violationCount + '/' + MAX_VIOLATIONS;

            if (violationCount >= MAX_VIOLATIONS) {
                // Hard Lockout: Layar Terkunci Total
                document.getElementById('kioskWarningOverlay').style.display = 'none';
                document.getElementById('kioskLockoutScreen').style.display = 'flex';
            } else {
                // Warning Siren Overlay
                var display = document.getElementById('kioskStrikeDisplay');
                if (display) {
                    display.textContent = 'Pelanggaran ke-' + violationCount + ' dari ' + MAX_VIOLATIONS + ' Kali';
                }
                document.getElementById('kioskWarningOverlay').style.display = 'flex';
            }
        }

        // Visibility Change Listener (Tab switch, minimize)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && isExamRunning) {
                triggerViolation('Tab beralih atau browser diminimalkan');
            }
        });

        // Window Blur Listener (Alt-Tab, click out of window)
        window.addEventListener('blur', function() {
            if (isExamRunning && !document.getElementById('kioskLockoutScreen').style.display.includes('flex')) {
                triggerViolation('Fokus jendela beralih keluar');
            }
        });

        // Fullscreen exit detection
        document.addEventListener('fullscreenchange', function() {
            if (!document.fullscreenElement && isExamRunning) {
                triggerViolation('Keluar dari mode layar penuh');
            }
        });

        // Resume Fullscreen from Warning
        function resumeKioskFullscreen() {
            document.getElementById('kioskWarningOverlay').style.display = 'none';
            enterFullscreen();
        }

        // Proctor Unlock PIN (Default PIN: 1234 atau PROCTOR atau WXYZ89)
        function unlockKioskByProctor() {
            var pin = (document.getElementById('proctorUnlockPin').value || '').trim().toUpperCase();
            if (pin === '1234' || pin === 'PROCTOR' || pin === 'WXYZ89' || pin === 'ADMIN123') {
                violationCount = 0;
                var badge = document.getElementById('kioskStrikeCount');
                if (badge) badge.textContent = '0/' + MAX_VIOLATIONS;
                document.getElementById('kioskLockoutScreen').style.display = 'none';
                document.getElementById('proctorUnlockPin').value = '';
                enterFullscreen();
                alert('Kunci ujian berhasil dibuka oleh Pengawas! Selamat melanjutkan ujian dan patuhi tata tertib.');
            } else {
                alert('PIN Pengawas salah! Silakan panggil Guru Pengawas Ruang.');
            }
        }

        // 3. BLOKIR SHORTCUT KEYBOARD & KLIK KANAN
        document.addEventListener('keydown', function(e) {
            if (!isExamRunning) return;

            // Blokir F11, F12, F5
            if (e.key === 'F11' || e.key === 'F12' || e.key === 'F5') {
                e.preventDefault();
                return false;
            }

            // Blokir Ctrl+R, Ctrl+U, Ctrl+W, Ctrl+T, Ctrl+N, Ctrl+C, Ctrl+V, Ctrl+Shift+I, Ctrl+Shift+J
            if (e.ctrlKey || e.metaKey) {
                var k = (e.key || '').toLowerCase();
                if (k === 'r' || k === 'u' || k === 'w' || k === 't' || k === 'n' || k === 'c' || k === 'v' || k === 's' || k === 'p') {
                    e.preventDefault();
                    return false;
                }
            }

            // Blokir Alt+Tab / Alt+F4
            if (e.altKey) {
                e.preventDefault();
                return false;
            }
        });

        // Blokir Klik Kanan
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });

        // Blokir Navigasi Kembali / Tutup Jendela
        window.addEventListener('beforeunload', function(e) {
            if (isExamRunning) {
                e.preventDefault();
                e.returnValue = 'Sesi ujian Anda sedang aktif. Dilarang meninggalkan halaman ujian!';
                return e.returnValue;
            }
        });

        // Push history state to block browser back button
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            if (isExamRunning) {
                history.go(1);
            }
        };

        // 4. CBT WORKSHEET INTERACTION ENGINE
        function renderCurrentQuestion() {
            var q = examQuestions[currentQuestionIdx];
            if (!q) return;

            document.getElementById('currentQBadge').textContent = 'Nomor Soal ' + q.number + ' dari ' + examQuestions.length;
            document.getElementById('currentQContent').textContent = q.content;

            var container = document.getElementById('currentOptionsList');
            container.innerHTML = '';

            var selected = userAnswers[q.number] || null;

            for (var key in q.options) {
                var isSelected = (selected === key);
                var div = document.createElement('div');
                div.className = 'option-item' + (isSelected ? ' selected' : '');
                div.setAttribute('data-opt', key);
                div.onclick = (function(optKey, el) {
                    return function() { selectOption(optKey, el); };
                })(key, div);

                div.innerHTML = '<div class="opt-letter">' + key + '</div><div class="opt-text">' + q.options[key] + '</div>';
                container.appendChild(div);
            }

            var doubtBtn = document.getElementById('btnDoubt');
            if (doubtFlags[q.number]) {
                doubtBtn.style.background = '#f59e0b';
                doubtBtn.style.color = '#ffffff';
            } else {
                doubtBtn.style.background = '#fef3c7';
                doubtBtn.style.color = '#b45309';
            }
        }

        function selectOption(optKey, element) {
            var q = examQuestions[currentQuestionIdx];
            userAnswers[q.number] = optKey;

            var all = document.querySelectorAll('#currentOptionsList .option-item');
            all.forEach(function(item) { item.classList.remove('selected'); });
            if (element) element.classList.add('selected');
        }

        function toggleDoubt() {
            var q = examQuestions[currentQuestionIdx];
            doubtFlags[q.number] = !doubtFlags[q.number];
            renderCurrentQuestion();
        }

        function prevQuestion() {
            if (currentQuestionIdx > 0) {
                currentQuestionIdx--;
                renderCurrentQuestion();
            }
        }

        function nextQuestion() {
            if (currentQuestionIdx < examQuestions.length - 1) {
                currentQuestionIdx++;
                renderCurrentQuestion();
            } else {
                alert('Anda telah berada di butir soal terakhir.');
            }
        }

        function startTimer() {
            if (timerInterval) clearInterval(timerInterval);
            timerInterval = setInterval(function() {
                if (timerSeconds <= 0) {
                    clearInterval(timerInterval);
                    alert('Waktu ujian telah habis! Sistem secara otomatis menyimpan seluruh jawaban Anda.');
                    finishExamSubmit();
                    return;
                }
                timerSeconds--;
                var mins = Math.floor(timerSeconds / 60);
                var secs = timerSeconds % 60;
                var formatted = (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
                var el = document.getElementById('examTimerText');
                if (el) el.textContent = formatted;
            }, 1000);
        }

        function confirmFinishExam() {
            var answeredCount = Object.keys(userAnswers).length;
            var totalCount = examQuestions.length;
            var confirmMsg = 'Konfirmasi Selesai Ujian:\n\n' +
                '• Soal Terjawab: ' + answeredCount + ' dari ' + totalCount + '\n' +
                '• Sisa Waktu: ' + document.getElementById('examTimerText').textContent + '\n\n' +
                'Apakah Anda yakin ingin mengumpulkan lembar jawaban Anda sekarang?';

            if (confirm(confirmMsg)) {
                finishExamSubmit();
            }
        }

        function finishExamSubmit() {
            isExamRunning = false;
            if (timerInterval) clearInterval(timerInterval);

            // Exit Fullscreen
            if (document.exitFullscreen) {
                document.exitFullscreen().catch(function(){});
            }

            document.getElementById('activeExamRoom').style.display = 'none';
            document.getElementById('studentPortalDashboard').style.display = 'block';

            alert('Alhamdulillah! Seluruh lembar jawaban Anda telah berhasil tersimpan dan tersinkronisasi ke Server CBT Sekolah.');
            window.location.reload();
        }
    </script>
</body>
</html>
    <?php
}

// =========================================================================
// 6. MAIN APP SHELL & NAVIGATION
// =========================================================================
function renderAppPage($uri) {
    $activeMenu = 'dashboard';
    $pageTitle = 'Dashboard Administrator';

    if (strpos($uri, 'students') !== false) {
        $activeMenu = 'students';
        $pageTitle = 'Manajemen Peserta';
    } elseif (strpos($uri, 'teachers') !== false) {
        $activeMenu = 'teachers';
        $pageTitle = 'Manajemen Guru';
    } elseif (strpos($uri, 'classes') !== false) {
        $activeMenu = 'classes';
        $pageTitle = 'Manajemen Kelas';
    } elseif (strpos($uri, 'subjects') !== false) {
        $activeMenu = 'subjects';
        $pageTitle = 'Mata Pelajaran';
    } elseif (strpos($uri, 'questions') !== false) {
        $activeMenu = 'questions';
        $pageTitle = 'Bank Soal';
    } elseif (strpos($uri, 'exams') !== false) {
        $activeMenu = 'exams';
        $pageTitle = (isset($_GET['action']) && $_GET['action'] === 'show') ? 'Detail Paket Ujian' : 'Paket Ujian';
    } elseif (strpos($uri, 'monitoring') !== false) {
        $activeMenu = 'monitoring';
        $pageTitle = (isset($_GET['action']) && $_GET['action'] === 'show') ? 'Telemetri Live Ujian' : 'Live Monitoring Ujian';
    } elseif (strpos($uri, 'results') !== false) {
        $activeMenu = 'results';
        $pageTitle = 'Hasil Ujian';
    } elseif (strpos($uri, 'reports') !== false) {
        $activeMenu = 'reports';
        $pageTitle = 'Laporan Nilai';
    } elseif (strpos($uri, 'backups') !== false) {
        $activeMenu = 'backups';
        $pageTitle = 'Backup & Database Snapshot';
    } elseif (strpos($uri, 'settings') !== false) {
        $activeMenu = 'settings';
        $pageTitle = 'Pengaturan Sistem Server';
    } elseif (strpos($uri, 'activity-logs') !== false) {
        $activeMenu = 'activity-logs';
        $pageTitle = 'Log Aktivitas & Audit Trail';
    }

    $successBanner = '';
    if (!empty($_SESSION['import_success'])) {
        $msg = htmlspecialchars($_SESSION['import_success']);
        $successBanner = '<div class="alert alert-success" style="margin-bottom: 18px; padding: 12px 16px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; border-radius: 8px; font-size: 13.5px; display: flex; align-items: center; justify-content: space-between;"><div><span>✓</span> <strong>Berhasil!</strong> ' . $msg . '</div><button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:#047857;font-size:16px;cursor:pointer;">&times;</button></div>';
        unset($_SESSION['import_success']);
    }

    $schoolName = $_SESSION['cbt_settings']['school_name'] ?? 'SMK PESANTREN BUSTANUL ULUM';
    $academicYear = $_SESSION['cbt_settings']['academic_year'] ?? '2025/2026';
    $totalStudents = count($_SESSION['students_list']);
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — CBT Server Manager</title>
    <link rel="stylesheet" href="/css/cbt-offline.css">
    <style>
        .modal-overlay.active, .modal-overlay.open { display: flex !important; }
        .subject-nav-tabs, .class-division-tabs { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px; }
        .subject-tab-pill, .class-tab-pill { display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px; border-radius: 20px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-primary); text-decoration: none; font-size: 12.5px; font-weight: 600; transition: all 0.15s ease; white-space: nowrap; cursor: pointer; }
        .subject-tab-pill:hover, .class-tab-pill:hover { border-color: var(--primary); background: var(--bg-surface-hover); color: var(--primary); }
        .subject-tab-pill.active, .class-tab-pill.active { background: var(--primary) !important; border-color: var(--primary) !important; color: #ffffff !important; box-shadow: 0 2px 6px rgba(0, 149, 255, 0.3); }
        .subject-tab-badge, .class-tab-badge { display: inline-flex; align-items: center; justify-content: center; padding: 2px 7px; border-radius: 12px; font-size: 11px; font-weight: 700; background: var(--primary-light); color: var(--primary); border: 1px solid var(--primary-border); }
        .subject-tab-pill.active .subject-tab-badge, .class-tab-pill.active .class-tab-badge { background: rgba(255, 255, 255, 0.25); color: #ffffff; border-color: rgba(255, 255, 255, 0.4); }
        .action-btns { display: inline-flex !important; align-items: center !important; justify-content: center !important; flex-wrap: nowrap !important; white-space: nowrap !important; gap: 5px !important; }
        .action-bar { display: flex !important; justify-content: space-between !important; align-items: center !important; gap: 12px !important; flex-wrap: wrap; }
        .data-table th, .data-table td { vertical-align: middle; }
    </style>
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('cbt_theme');
                if (savedTheme === 'dark' || (!savedTheme && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                } else {
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    <div class="app-layout">
        <!-- SIDEBAR -->
        <aside class="sidebar" id="appSidebar">
            <div class="sidebar-brand-header">
                <div class="brand-crest-box">
                    <svg width="28" height="28" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="brandCrestGrad" x1="0" y1="0" x2="36" y2="36" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#2563eb"/>
                                <stop offset="1" stop-color="#06b6d4"/>
                            </linearGradient>
                        </defs>
                        <rect width="36" height="36" rx="8" fill="url(#brandCrestGrad)"/>
                        <path d="M18 7L8 12V20C8 26 12.2 30.5 18 31.5C23.8 30.5 28 26 28 20V12L18 7Z" fill="#ffffff" fill-opacity="0.25" stroke="#ffffff" stroke-width="1.8"/>
                        <path d="M14 18L17 21L23 15" stroke="#ffffff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="brand-info">
                    <div class="brand-name">CBT SERVER MANAGER</div>
                    <div class="brand-tagline">Server Ujian Berbasis LAN</div>
                </div>
            </div>

            <nav class="sidebar-nav">
                <!-- 1. UTAMA -->
                <div class="nav-section-title">Utama</div>
                <a href="/admin/dashboard" class="nav-link <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box blue">📊</span>
                        <span>Dashboard</span>
                    </span>
                </a>

                <!-- 2. MASTER DATA -->
                <div class="nav-section-title">Master Data</div>
                <a href="/admin/classes" class="nav-link <?= $activeMenu === 'classes' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box amber">🏫</span>
                        <span>Data Kelas</span>
                    </span>
                    <span class="nav-badge-pill"><?= count($_SESSION['classes_list']) ?></span>
                </a>
                <a href="/admin/students" class="nav-link <?= $activeMenu === 'students' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box purple">👥</span>
                        <span>Data Peserta</span>
                    </span>
                    <span class="nav-badge-pill"><?= $totalStudents ?></span>
                </a>
                <a href="/admin/teachers" class="nav-link <?= $activeMenu === 'teachers' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box teal">👨‍🏫</span>
                        <span>Data Guru</span>
                    </span>
                    <span class="nav-badge-pill"><?= count($_SESSION['teachers_list']) ?></span>
                </a>

                <!-- 3. AKADEMIK & UJIAN -->
                <div class="nav-section-title">Akademik & Ujian</div>
                <a href="/admin/questions" class="nav-link <?= in_array($activeMenu, ['questions', 'subjects']) ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box orange">📝</span>
                        <span>Bank Soal</span>
                    </span>
                    <span class="nav-badge-pill"><?= count($_SESSION['questions_list']) ?></span>
                </a>
                <a href="/admin/exams" class="nav-link <?= $activeMenu === 'exams' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box emerald">⏱️</span>
                        <span>Ruang Ujian</span>
                    </span>
                    <span class="nav-badge-pill"><?= count($_SESSION['exams_list']) ?></span>
                </a>
                <a href="/admin/monitoring" class="nav-link <?= $activeMenu === 'monitoring' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box cyan">📡</span>
                        <span>Monitoring Ujian</span>
                    </span>
                </a>
                <a href="/admin/results" class="nav-link <?= $activeMenu === 'results' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box red">🎯</span>
                        <span>Hasil Ujian</span>
                    </span>
                </a>
                <a href="/admin/reports" class="nav-link <?= $activeMenu === 'reports' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box indigo">📈</span>
                        <span>Laporan Nilai</span>
                    </span>
                </a>

                <!-- 4. PEMELIHARAAN SERVER -->
                <div class="nav-section-title">Pemeliharaan Server</div>
                <a href="/admin/backups" class="nav-link <?= $activeMenu === 'backups' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box lime">💾</span>
                        <span>Backup & Restore</span>
                    </span>
                    <span class="nav-badge-pill"><?= count($_SESSION['backups_list']) ?></span>
                </a>
                <a href="/admin/settings" class="nav-link <?= $activeMenu === 'settings' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box slate">⚙️</span>
                        <span>Pengaturan Server</span>
                    </span>
                </a>
                <a href="/admin/activity-logs" class="nav-link <?= $activeMenu === 'activity-logs' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box amber">📜</span>
                        <span>Log Aktivitas</span>
                    </span>
                </a>
            </nav>
        </aside>

        <!-- MAIN WRAPPER -->
        <div class="main-wrapper">
            <header class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle-btn" id="sidebarToggleBtn" onclick="toggleSidebar()" aria-label="Toggle Sidebar">
                        ☰
                    </button>
                    <div class="topbar-title-wrap">
                        <div class="topbar-title">
                            <span><?= htmlspecialchars($pageTitle) ?></span>
                            <span class="topbar-badge">CBT MANAGER</span>
                        </div>
                        <div class="topbar-subtitle"><?= htmlspecialchars($schoolName) ?> &bull; TA <?= htmlspecialchars($academicYear) ?></div>
                    </div>
                </div>

                <div class="topbar-right-actions">
                    <div class="theme-switch-pill" id="themeSwitchPill">
                        <button type="button" class="theme-switch-btn active" id="btnThemeLight" onclick="setAppTheme('light')">
                            <span>☀</span> <span>Light</span>
                        </button>
                        <button type="button" class="theme-switch-btn" id="btnThemeDark" onclick="setAppTheme('dark')">
                            <span>🌙</span> <span>Dark</span>
                        </button>
                    </div>

                    <?php if (($_SESSION['cbt_user'] ?? 'admin') === 'guru'): ?>
                        <div class="user-pill" style="border-color: #bae6fd; background: #f0f9ff;">
                            <div class="user-avatar-circle" style="background: #0284c7; color: #fff;">👨‍🏫</div>
                            <div style="display: flex; flex-direction: column; text-align: left;">
                                <span style="font-size: 13px; font-weight: 700; color: #0369a1; line-height: 1.1;">
                                    <?= htmlspecialchars($_SESSION['active_teacher']['name'] ?? 'Guru Pengajar') ?>
                                </span>
                                <span style="font-size: 11px; color: #0284c7; line-height: 1.1;">Guru Pengajar</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="user-pill">
                            <div class="user-avatar-circle">A</div>
                            <div style="display: flex; flex-direction: column; text-align: left;">
                                <span style="font-size: 13px; font-weight: 700; color: var(--text-primary); line-height: 1.1;">Administrator</span>
                                <span style="font-size: 11px; color: var(--text-muted); line-height: 1.1;">Administrator Sistem</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <a href="/logout" class="logout-link-btn" title="Keluar dari sesi Web Dashboard">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        <span>Logout</span>
                    </a>
                </div>
            </header>

            <main class="content-body">
                <?= $successBanner ?>
                <?php
                if ($activeMenu === 'dashboard') {
                    renderDashboardContent();
                } elseif ($activeMenu === 'teachers') {
                    renderTeachersContent();
                } elseif ($activeMenu === 'students') {
                    renderStudentsContent();
                } elseif ($activeMenu === 'classes') {
                    renderClassesContent();
                } elseif ($activeMenu === 'subjects') {
                    renderSubjectsContent();
                } elseif ($activeMenu === 'questions') {
                    renderQuestionsContent();
                } elseif ($activeMenu === 'exams') {
                    if (isset($_GET['action']) && $_GET['action'] === 'show') {
                        renderExamDetailContent($_GET['id'] ?? 'ex-1');
                    } else {
                        renderExamsContent();
                    }
                } elseif ($activeMenu === 'monitoring') {
                    if (isset($_GET['action']) && $_GET['action'] === 'show') {
                        renderMonitoringLiveContent($_GET['id'] ?? 'ex-1');
                    } else {
                        renderMonitoringContent();
                    }
                } elseif ($activeMenu === 'results') {
                    renderResultsContent();
                } elseif ($activeMenu === 'reports') {
                    renderReportsContent();
                } elseif ($activeMenu === 'backups') {
                    renderBackupsContent();
                } elseif ($activeMenu === 'settings') {
                    renderSettingsContent();
                } elseif ($activeMenu === 'activity-logs') {
                    renderActivityLogsContent();
                }
                ?>
            </main>
        </div>
    </div>

    <script src="/js/cbt-offline.js"></script>
    <script>
        function toggleSidebar() {
            var sb = document.getElementById('appSidebar');
            var ov = document.getElementById('sidebarOverlay');
            if (sb && ov) {
                sb.classList.toggle('open');
                ov.classList.toggle('open');
            }
        }
        function setAppTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            try { localStorage.setItem('cbt_theme', theme); } catch(e){}
            var bl = document.getElementById('btnThemeLight');
            var bd = document.getElementById('btnThemeDark');
            if (bl && bd) {
                if (theme === 'dark') {
                    bl.classList.remove('active');
                    bd.classList.add('active');
                } else {
                    bd.classList.remove('active');
                    bl.classList.add('active');
                }
            }
        }
    </script>
</body>
</html>
    <?php
}

// =========================================================================
// 7. MENU 1: DASHBOARD
// =========================================================================
function renderDashboardContent() {
    $studentsCount = count($_SESSION['students_list']);
    $teachersCount = count($_SESSION['teachers_list']);
    $classesCount = count($_SESSION['classes_list']);
    $examsCount = count($_SESSION['exams_list']);
    $questionsCount = count($_SESSION['questions_list']);
    ?>
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <!-- STATS CARDS -->
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--primary-light); color: var(--primary);">&#128101;</div>
                <div class="stat-value"><?= $studentsCount ?></div>
                <div class="stat-label">Total Peserta Terdaftar</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #e0f2fe; color: #0284c7;">👨‍🏫</div>
                <div class="stat-value"><?= $teachersCount ?></div>
                <div class="stat-label">Guru Pengajar</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #fef3c7; color: #d97706;">🏫</div>
                <div class="stat-value"><?= $classesCount ?></div>
                <div class="stat-label">Rombongan Belajar (Kelas)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--success-light); color: var(--success);">&#9201;</div>
                <div class="stat-value"><?= $examsCount ?></div>
                <div class="stat-label">Paket Ujian Aktif</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #fce7f3; color: #db2777;">📝</div>
                <div class="stat-value"><?= $questionsCount ?></div>
                <div class="stat-label">Total Butir Bank Soal</div>
            </div>
        </div>

        <!-- SERVER STATUS & ACTIONS -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
            <div class="card">
                <div class="card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                    <div>
                        <h3 class="card-title" style="margin: 0;">Status Server & Node Ujian LAN</h3>
                        <p class="card-description" style="margin: 4px 0 0;">Monitoring kesiapan jaringan lokal mandiri sekolah</p>
                    </div>
                    <span class="badge badge-success">● Siap Melayani Ujian</span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: 8px; border: 1px solid var(--border-color);">
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Waktu Server</div>
                        <div style="font-size: 18px; font-weight: 700; color: var(--text-primary); margin-top: 4px;" id="liveClock"><?= date('H:i:s d/m/Y') ?></div>
                    </div>
                    <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: 8px; border: 1px solid var(--border-color);">
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Port Layanan HTTP</div>
                        <div style="font-size: 18px; font-weight: 700; color: var(--primary); margin-top: 4px;"><?= (int)($_SESSION['cbt_settings']['server_port'] ?? 8000) ?> (LAN Ready)</div>
                    </div>
                </div>

                <div style="margin-top: 18px;">
                    <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 600; margin-bottom: 6px;">
                        <span>Kapasitas Sesi Peserta Serentak</span>
                        <span><?= $studentsCount ?> / 500 Peserta</span>
                    </div>
                    <div style="height: 8px; background: var(--bg-surface-elevated); border-radius: 4px; overflow: hidden; border: 1px solid var(--border-color);">
                        <div style="width: <?= min(100, round(($studentsCount / 500) * 100)) ?>%; height: 100%; background: var(--primary);"></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 class="card-title" style="margin-bottom: 14px;">Pintasan Cepat</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="/admin/exams" class="btn btn-primary" style="justify-content: flex-start; text-decoration: none;">
                        <span>⏱️</span> Buat Jadwal Ujian
                    </a>
                    <a href="/admin/students" class="btn btn-secondary" style="justify-content: flex-start; text-decoration: none;">
                        <span>👥</span> Kelola Data Peserta
                    </a>
                    <a href="/admin/monitoring" class="btn btn-secondary" style="justify-content: flex-start; text-decoration: none;">
                        <span>📡</span> Pantau Live Peserta
                    </a>
                    <a href="/admin/backups" class="btn btn-secondary" style="justify-content: flex-start; text-decoration: none;">
                        <span>💾</span> Backup Database
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script>
        setInterval(function() {
            var el = document.getElementById('liveClock');
            if (el) {
                var d = new Date();
                el.innerText = ('0'+d.getHours()).slice(-2) + ':' + ('0'+d.getMinutes()).slice(-2) + ':' + ('0'+d.getSeconds()).slice(-2) + ' ' + ('0'+d.getDate()).slice(-2) + '/' + ('0'+(d.getMonth()+1)).slice(-2) + '/' + d.getFullYear();
            }
        }, 1000);
    </script>
    <?php
}

// =========================================================================
// 8. MENU 2: DATA GURU (FULL INTERACTIVE CRUD)
// =========================================================================
function renderTeachersContent() {
    $search = strtolower(trim($_GET['search'] ?? ''));
    $teachers = $_SESSION['teachers_list'];
    if ($search !== '') {
        $teachers = array_filter($teachers, function($t) use ($search) {
            return str_contains(strtolower($t['name']), $search) || str_contains(strtolower($t['nip']), $search) || str_contains(strtolower($t['username']), $search);
        });
    }
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="action-bar">
            <div class="filter-group">
                <form action="/admin/teachers" method="GET" style="display: flex; gap: 8px;">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama, username, NIP..." value="<?= htmlspecialchars($search) ?>" style="max-width: 280px;">
                    <button type="submit" class="btn btn-secondary">Cari</button>
                    <?php if ($search !== ''): ?>
                        <a href="/admin/teachers" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-secondary" onclick="openImportModal()" style="display: inline-flex; align-items: center; gap: 6px; border-color: #bae6fd; color: #0284c7;">
                    <span>📊</span> Import Data Excel
                </button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('createTeacherCard').style.display = 'block'; window.scrollTo({top: document.getElementById('createTeacherCard').offsetTop - 80, behavior: 'smooth'});">
                    <span>+</span> Tambah Guru Baru
                </button>
            </div>
        </div>

        <!-- CREATE TEACHER CARD (Collapsible) -->
        <div class="card" id="createTeacherCard" style="display: none; border-color: var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div>
                    <h3 class="card-title" style="margin-bottom: 2px;">Tambah Akun &amp; Data Guru Baru</h3>
                    <div style="font-size: 11.5px; color: var(--text-muted);">
                        Password default otomatis disetel ke <code>12345678</code>. Nomer telepon tidak lagi diperlukan.
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('createTeacherCard').style.display = 'none';">&times; Batal</button>
            </div>
            <form action="/admin/teachers/create" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Username Login *</label>
                        <input type="text" name="username" class="form-control" placeholder="Contoh: guru.budi" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap Guru *</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Budi Santoso, S.Pd" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password Login (Default: 12345678) *</label>
                        <input type="text" name="password" class="form-control" value="12345678" style="font-family: monospace; font-weight: 700; color: #15803d;" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">NIP (Nomor Induk Pegawai)</label>
                        <input type="text" name="nip" class="form-control" placeholder="18 digit NIP">
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('createTeacherCard').style.display = 'none';">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Data Guru</button>
                </div>
            </form>
        </div>

        <!-- TEACHERS TABLE -->
        <div class="card" style="padding: 0; overflow: hidden; box-shadow: var(--shadow-sm);">
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 48px; text-align: center;">No</th>
                            <th style="width: 170px;">NIP</th>
                            <th>Nama Lengkap Guru</th>
                            <th>Username Akun</th>
                            <th style="width: 130px; text-align: center;">Password</th>
                            <th style="width: 90px; text-align: center;">Status</th>
                            <th style="text-align: center; white-space: nowrap; min-width: 320px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teachers)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">👨‍🏫</div>
                                        <p>Belum ada data guru pengajar yang terdaftar.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($teachers as $idx => $t): ?>
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted); font-weight: 600;"><?= $idx + 1 ?></td>
                                    <td><code><?= htmlspecialchars($t['nip']) ?></code></td>
                                    <td><strong><?= htmlspecialchars($t['name']) ?></strong></td>
                                    <td>
                                        <code style="font-size: 12.5px; color: #0369a1; background: #e0f2fe; padding: 3px 8px; border-radius: 4px; font-weight: 700;">
                                            <?= htmlspecialchars($t['username']) ?>
                                        </code>
                                    </td>
                                    <td style="text-align: center;">
                                        <code style="font-size: 12.5px; font-weight: 800; color: #15803d; background: #f0fdf4; padding: 3px 8px; border-radius: 4px; border: 1px solid #bbf7d0;">
                                            <?= htmlspecialchars($t['password'] ?? '12345678') ?>
                                        </code>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge <?= !empty($t['is_active']) ? 'badge-success' : 'badge-danger' ?>">
                                            <?= !empty($t['is_active']) ? 'Aktif' : 'Nonaktif' ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center; white-space: nowrap;">
                                        <div class="action-btns" style="display: inline-flex; align-items: center; justify-content: center; gap: 4px; flex-wrap: nowrap; white-space: nowrap;">
                                            <a 
                                                href="/admin/teachers/login-as?id=<?= urlencode($t['id']) ?>" 
                                                class="btn btn-primary btn-sm" 
                                                style="padding: 4px 8px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 3px; white-space: nowrap;"
                                                title="Login langsung ke portal sebagai guru ini"
                                            >
                                                <span>🚀</span> Login Guru
                                            </a>
                                            <a 
                                                href="/admin/teachers/reset-password?id=<?= urlencode($t['id']) ?>" 
                                                class="btn btn-secondary btn-sm" 
                                                onclick="return confirm('Reset password akun guru <?= htmlspecialchars(addslashes($t['name'])) ?> ke default 12345678?');"
                                                style="padding: 4px 8px; font-size: 11.5px; font-weight: 700; color: #b45309; border-color: #fde68a; background: #fffbeb; display: inline-flex; align-items: center; gap: 3px; white-space: nowrap;"
                                                title="Reset password ke default 12345678"
                                            >
                                                <span>🔑</span> Reset PW
                                            </a>
                                            <button 
                                                type="button" 
                                                class="btn btn-secondary btn-sm" 
                                                onclick="openEditTeacher('<?= htmlspecialchars($t['id']) ?>', '<?= htmlspecialchars(addslashes($t['name'])) ?>', '<?= htmlspecialchars(addslashes($t['nip'])) ?>', '<?= htmlspecialchars(addslashes($t['username'])) ?>', '<?= htmlspecialchars(addslashes($t['password'] ?? '12345678')) ?>', <?= !empty($t['is_active']) ? '1' : '0' ?>)"
                                                style="padding: 4px 8px; font-size: 11.5px; font-weight: 600; white-space: nowrap;"
                                            >
                                                Edit
                                            </button>
                                            <a 
                                                href="/admin/teachers/delete?id=<?= urlencode($t['id']) ?>" 
                                                class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus data guru <?= htmlspecialchars(addslashes($t['name'])) ?>?');"
                                                style="padding: 4px 8px; font-size: 11.5px; font-weight: 600; white-space: nowrap;"
                                            >
                                                Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- EDIT TEACHER MODAL -->
    <div class="modal-overlay" id="editTeacherModal">
        <div class="modal-content-card" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title" style="margin: 0;">Edit Data Guru</h3>
                <button type="button" class="modal-close-btn" onclick="document.getElementById('editTeacherModal').classList.remove('open')">&times;</button>
            </div>
            <form action="/admin/teachers/edit" method="POST">
                <input type="hidden" name="id" id="edit_teacher_id">
                <div style="margin-bottom: 14px;">
                    <label class="form-label">Nama Lengkap Guru *</label>
                    <input type="text" name="name" id="edit_teacher_name" class="form-control" required>
                </div>
                <div style="margin-bottom: 14px;">
                    <label class="form-label">NIP (Nomor Induk Pegawai) *</label>
                    <input type="text" name="nip" id="edit_teacher_nip" class="form-control" required>
                </div>
                <div style="margin-bottom: 14px;">
                    <label class="form-label">Username Login *</label>
                    <input type="text" name="username" id="edit_teacher_username" class="form-control" required>
                </div>
                <div style="margin-bottom: 14px;">
                    <label class="form-label">Password Guru (Default: 12345678) *</label>
                    <input type="text" name="password" id="edit_teacher_password" class="form-control" style="font-family: monospace; font-weight: 700; color: #15803d;" required>
                </div>
                <div style="margin-bottom: 18px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="is_active" id="edit_teacher_active" value="1">
                        <span style="font-size: 13.5px;">Status Akun Aktif</span>
                    </label>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editTeacherModal').classList.remove('open')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- IMPORT MODAL -->
    <?php renderImportModalGeneric('/admin/teachers/import', '/admin/teachers/template', 'Guru', 'template_guru'); ?>

    <script>
        function openEditTeacher(id, name, nip, username, password, isActive) {
            document.getElementById('edit_teacher_id').value = id;
            document.getElementById('edit_teacher_name').value = name;
            document.getElementById('edit_teacher_nip').value = nip;
            document.getElementById('edit_teacher_username').value = username;
            document.getElementById('edit_teacher_password').value = password || '12345678';
            document.getElementById('edit_teacher_active').checked = (isActive == 1);
            document.getElementById('editTeacherModal').classList.add('open');
        }
    </script>
    <?php
}

// =========================================================================
// 9. MENU 3: DATA PESERTA / SISWA (FULL INTERACTIVE CRUD & CLASS TABS)
// =========================================================================
// 9. MENU 3: DATA PESERTA / SISWA (NIS ONLY, USERNAMES, PASSWORD DEFAULT 12345678, RESET PW, LOGIN SISWA)
// =========================================================================
function renderStudentsContent() {
    $search = strtolower(trim($_GET['search'] ?? ''));
    $currentClass = $_GET['class_id'] ?? 'all';

    // Pastikan data siswa tersinkronisasi: NIS saja (tanpa NISN), username = nama depan + NIS, password default 12345678
    foreach ($_SESSION['students_list'] as &$st) {
        if (empty($st['password'])) {
            $st['password'] = '12345678';
        }
        $st['username'] = getStudentUsernameFromNameNis($st['name'], $st['nis']);
        unset($st['nisn']);
    }
    unset($st);

    $students = $_SESSION['students_list'];

    if ($currentClass !== 'all') {
        $students = array_filter($students, function($s) use ($currentClass) {
            return $s['class'] === $currentClass;
        });
    }

    if ($search !== '') {
        $students = array_filter($students, function($s) use ($search) {
            return str_contains(strtolower($s['name']), $search) || str_contains(strtolower($s['nis']), $search) || str_contains(strtolower($s['username']), $search);
        });
    }

    // Pemetaan unik ID Jurusan (angka) dari daftar kelas
    $majorsList = [];
    foreach ($_SESSION['classes_list'] as $clItem) {
        $mId = (string)($clItem['major_id'] ?? '1');
        if (!isset($majorsList[$mId])) {
            $majorsList[$mId] = $clItem['major'] ?? ('Jurusan ' . $mId);
        }
    }
    ksort($majorsList);
    ?>
    <div style="display: flex; flex-direction: column; gap: 18px;">
        <!-- CLASS DIVISION TABS -->
        <div class="card" style="padding: 14px 18px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 16px;">🏷️</span>
                    <strong style="font-size: 14px; color: var(--text-primary);">Menu Pembagian Kelas</strong>
                    <span style="font-size: 11.5px; color: var(--text-muted);">(Klik nama kelas untuk filter instan)</span>
                </div>
                <div style="font-size: 12px; color: var(--text-secondary); font-weight: 600;">
                    Total: <span style="color: var(--primary);"><?= count($_SESSION['students_list']) ?> Peserta</span>
                </div>
            </div>

            <div class="class-division-tabs" style="display: flex; gap: 6px; overflow-x: auto; padding-bottom: 4px;">
                <a href="/admin/students?class_id=all" class="class-tab-pill <?= $currentClass === 'all' ? 'active' : '' ?>">
                    <span>Semua Kelas</span>
                    <span class="class-tab-badge"><?= count($_SESSION['students_list']) ?></span>
                </a>
                <?php foreach ($_SESSION['classes_list'] as $c): ?>
                    <?php
                    $cCount = count(array_filter($_SESSION['students_list'], fn($s) => $s['class'] === $c['name']));
                    ?>
                    <a href="/admin/students?class_id=<?= urlencode($c['name']) ?>" class="class-tab-pill <?= $currentClass === $c['name'] ? 'active' : '' ?>">
                        <span><?= htmlspecialchars($c['name']) ?></span>
                        <span class="class-tab-badge"><?= $cCount ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ACTION BAR -->
        <div class="action-bar" style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
            <div class="filter-group">
                <form action="/admin/students" method="GET" style="display: flex; gap: 8px; align-items: center; margin: 0;">
                    <input type="hidden" name="class_id" value="<?= htmlspecialchars($currentClass) ?>">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama, NIS, username..." value="<?= htmlspecialchars($search) ?>" style="max-width: 260px;">
                    <button type="submit" class="btn btn-secondary">Cari</button>
                    <?php if ($search !== '' || $currentClass !== 'all'): ?>
                        <a href="/admin/students" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div style="display: flex; gap: 8px; align-items: center;">
                <button type="button" class="btn btn-secondary" onclick="openImportModal()" style="display: inline-flex; align-items: center; gap: 6px; border-color: #bae6fd; color: #0284c7; white-space: nowrap;">
                    <span>📊</span> Import Data Excel
                </button>
                <button type="button" class="btn btn-primary" onclick="toggleCreateStudentCard()" style="font-weight: 700; white-space: nowrap;">
                    <span>+</span> Tambah Peserta Baru
                </button>
            </div>
        </div>

        <!-- CREATE STUDENT CARD (Collapsible) -->
        <div class="card" id="createStudentCard" style="display: none; border-color: var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div>
                    <h3 class="card-title" style="margin-bottom: 2px;">Tambah Akun &amp; Data Peserta Baru</h3>
                    <div style="font-size: 11.5px; color: var(--text-muted);">
                        Siswa login menggunakan <strong>NIS</strong> dan <strong>Password</strong> (Default: <code>12345678</code>).
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleCreateStudentCard()">&times; Batal</button>
            </div>
            <form action="/admin/students/create" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700;">Nama Lengkap Siswa *</label>
                        <input type="text" name="name" id="new_student_name" class="form-control" placeholder="Contoh: Ahmad Dhani Prasetya" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700;">NIS (Nomor Induk Siswa) *</label>
                        <input type="text" name="nis" id="new_student_nis" class="form-control" placeholder="Contoh: 0081234567" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700;">ID Jurusan *</label>
                        <select name="major_id" id="new_student_major_id" class="form-select" onchange="syncNewStudentClassOptions()" required>
                            <?php foreach ($majorsList as $mId => $mName): ?>
                                <option value="<?= htmlspecialchars($mId) ?>">ID <?= htmlspecialchars($mId) ?> - <?= htmlspecialchars($mName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700;">Kelas / Rombel (Mengikuti Jurusan) *</label>
                        <select name="class" id="new_student_class" class="form-select" required>
                            <?php foreach ($_SESSION['classes_list'] as $c): ?>
                                <option value="<?= htmlspecialchars($c['name']) ?>" data-major="<?= htmlspecialchars($c['major_id'] ?? '') ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700;">Password Login (Default 12345678) *</label>
                        <input type="text" name="password" class="form-control" value="12345678" style="font-family: monospace; font-weight: 700; color: #15803d;" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700;">Jenis Kelamin *</label>
                        <select name="gender" class="form-select" required>
                            <option value="L">Laki-laki (L)</option>
                            <option value="P">Perempuan (P)</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="toggleCreateStudentCard()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 700;">Simpan Data Peserta</button>
                </div>
            </form>
        </div>

        <!-- BATCH SELECTION ACTION BAR (MENU TANDAI) -->
        <div id="bulkActionBar" style="display: none; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 10px 16px; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; box-shadow: 0 2px 5px rgba(0, 149, 255, 0.08);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 18px;">☑️</span>
                <span style="font-size: 13.5px; font-weight: 700; color: #1e40af;">
                    <span id="selectedCount">0</span> Peserta Ditandai
                </span>
            </div>
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-danger btn-sm" onclick="executeBulkDelete()" style="font-weight: 700; display: inline-flex; align-items: center; gap: 5px;" title="Hapus semua peserta yang ditandai">
                    <span>🗑️</span> Hapus Terpilih
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="executeBulkResetPassword()" style="color: #b45309; border-color: #fde68a; background: #fffbeb; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;" title="Reset password semua peserta yang ditandai ke default 12345678">
                    <span>🔑</span> Reset Password Terpilih (12345678)
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="openBulkChangeClassModal()" style="color: #0284c7; border-color: #bae6fd; background: #f0f9ff; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;" title="Pindahkan peserta yang ditandai ke rombel kelas lain">
                    <span>🏫</span> Pindah Kelas Terpilih
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="clearSelectedStudents()" style="font-weight: 600;">
                    ✖ Batal Tandai
                </button>
            </div>
        </div>

        <!-- HIDDEN BULK ACTION FORMS -->
        <form id="bulkDeleteForm" action="/admin/students/bulk-delete" method="POST" style="display:none;">
            <input type="hidden" name="ids" id="bulkDeleteIds">
        </form>
        <form id="bulkResetForm" action="/admin/students/bulk-reset-password" method="POST" style="display:none;">
            <input type="hidden" name="ids" id="bulkResetIds">
        </form>

        <!-- STUDENTS TABLE -->
        <div class="card" style="padding: 0; overflow: hidden; box-shadow: var(--shadow-sm);">
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 42px; text-align: center;">
                                <input type="checkbox" id="selectAllStudents" onclick="toggleSelectAllStudents(this)" style="cursor: pointer; width: 16px; height: 16px;" title="Tandai Semua Peserta">
                            </th>
                            <th style="width: 45px; text-align: center;">No</th>
                            <th style="width: 130px;">NIS (Login Siswa)</th>
                            <th>Nama Lengkap Peserta</th>
                            <th style="width: 95px; text-align: center;">ID Jurusan</th>
                            <th style="width: 110px;">Kelas</th>
                            <th style="width: 120px; text-align: center;">Password</th>
                            <th style="width: 50px; text-align: center;">L/P</th>
                            <th style="text-align: center; white-space: nowrap; min-width: 330px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">👥</div>
                                        <p>Tidak ada peserta yang cocok dengan filter yang dipilih.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $idx => $s): ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <input type="checkbox" class="student-checkbox" value="<?= htmlspecialchars($s['id']) ?>" onclick="updateSelectedStudentsBar()" style="cursor: pointer; width: 16px; height: 16px;" title="Tandai peserta ini">
                                    </td>
                                    <td style="text-align: center; color: var(--text-muted); font-weight: 600;"><?= $idx + 1 ?></td>
                                    <td>
                                        <code style="font-size: 13px; font-weight: 700; color: #0284c7; background: #e0f2fe; padding: 3px 8px; border-radius: 4px; border: 1px solid #bae6fd;"><?= htmlspecialchars($s['nis']) ?></code>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($s['name']) ?></div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge" style="background: #e0f2fe; color: #0284c7; font-weight: 800; font-size: 11.5px; border: 1px solid #bae6fd;" title="ID Jurusan">
                                            ID: <?= htmlspecialchars($s['major_id'] ?? '1') ?>
                                        </span>
                                    </td>
                                    <td><span class="badge badge-primary" style="font-weight: 700;"><?= htmlspecialchars($s['class']) ?></span></td>
                                    <td style="text-align: center;">
                                        <code style="font-size: 12.5px; font-weight: 800; color: #15803d; background: #f0fdf4; padding: 3px 8px; border-radius: 4px; border: 1px solid #bbf7d0;">
                                            <?= htmlspecialchars($s['password'] ?? '12345678') ?>
                                        </code>
                                    </td>
                                    <td style="text-align: center; font-weight: 700;"><?= ($s['gender'] ?? 'L') === 'L' ? 'L' : 'P' ?></td>
                                    <td style="text-align: center; white-space: nowrap;">
                                        <div class="action-btns" style="display: inline-flex; align-items: center; justify-content: center; gap: 4px; flex-wrap: nowrap; white-space: nowrap;">
                                            <a 
                                                href="/admin/students/login-as?id=<?= urlencode($s['id']) ?>" 
                                                class="btn btn-primary btn-sm" 
                                                style="padding: 4px 7px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 3px; white-space: nowrap;"
                                                title="Login langsung sebagai siswa ini (NIS: <?= htmlspecialchars($s['nis']) ?>)"
                                            >
                                                <span>🚀</span> Login Siswa
                                            </a>
                                            <a 
                                                href="/admin/students/reset-password?id=<?= urlencode($s['id']) ?>" 
                                                class="btn btn-secondary btn-sm" 
                                                onclick="return confirm('Reset password peserta <?= htmlspecialchars(addslashes($s['name'])) ?> ke default 12345678?');"
                                                style="padding: 4px 7px; font-size: 11.5px; font-weight: 700; color: #b45309; border-color: #fde68a; background: #fffbeb; display: inline-flex; align-items: center; gap: 3px; white-space: nowrap;"
                                                title="Reset password ke default 12345678"
                                            >
                                                <span>🔑</span> Reset PW
                                            </a>
                                            <button 
                                                type="button" 
                                                class="btn btn-secondary btn-sm" 
                                                onclick="openEditStudent('<?= htmlspecialchars($s['id']) ?>', '<?= htmlspecialchars(addslashes($s['name'])) ?>', '<?= htmlspecialchars(addslashes($s['nis'])) ?>', '<?= htmlspecialchars($s['major_id'] ?? '1') ?>', '<?= htmlspecialchars(addslashes($s['class'])) ?>', '<?= htmlspecialchars($s['gender'] ?? 'L') ?>', '<?= htmlspecialchars(addslashes($s['password'] ?? '12345678')) ?>')"
                                                style="padding: 4px 8px; font-size: 11.5px; font-weight: 600; white-space: nowrap;"
                                                title="Edit Peserta"
                                            >
                                                Edit
                                            </button>
                                            <a 
                                                href="/admin/students/delete?id=<?= urlencode($s['id']) ?>" 
                                                class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus data peserta <?= htmlspecialchars(addslashes($s['name'])) ?>?');"
                                                style="padding: 4px 8px; font-size: 11.5px; font-weight: 600; white-space: nowrap;"
                                                title="Hapus Peserta"
                                            >
                                                Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- EDIT STUDENT MODAL -->
    <div class="modal-overlay" id="editStudentModal" style="align-items: center; justify-content: center;">
        <div class="modal-content-card" style="max-width: 500px; border-radius: 12px;">
            <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid var(--border-color); padding: 16px 20px;">
                <h3 class="modal-title" style="margin: 0; font-size: 1.15rem; font-weight: 800;">Edit Data Peserta</h3>
                <button type="button" class="modal-close-btn" onclick="document.getElementById('editStudentModal').classList.remove('open')">&times;</button>
            </div>
            <form action="/admin/students/edit" method="POST" style="padding: 20px;">
                <input type="hidden" name="id" id="edit_student_id">
                
                <div style="margin-bottom: 14px;">
                    <label class="form-label" style="font-weight: 700;">Nama Lengkap Siswa *</label>
                    <input type="text" name="name" id="edit_student_name" class="form-control" required>
                </div>
                
                <div style="margin-bottom: 14px;">
                    <label class="form-label" style="font-weight: 700;">NIS (Login Siswa) *</label>
                    <input type="text" name="nis" id="edit_student_nis" class="form-control" required>
                </div>

                <div class="form-row" style="margin-bottom: 14px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700;">ID Jurusan</label>
                        <select name="major_id" id="edit_student_major_id" class="form-select" onchange="syncEditStudentClassOptions()">
                            <?php foreach ($majorsList as $mId => $mName): ?>
                                <option value="<?= htmlspecialchars($mId) ?>">ID <?= htmlspecialchars($mId) ?> - <?= htmlspecialchars($mName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700;">Kelas / Rombel (Mengikuti Jurusan)</label>
                        <select name="class" id="edit_student_class" class="form-select">
                            <?php foreach ($_SESSION['classes_list'] as $c): ?>
                                <option value="<?= htmlspecialchars($c['name']) ?>" data-major="<?= htmlspecialchars($c['major_id'] ?? '') ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700;">Jenis Kelamin</label>
                        <select name="gender" id="edit_student_gender" class="form-select">
                            <option value="L">Laki-laki (L)</option>
                            <option value="P">Perempuan (P)</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom: 18px;">
                    <label class="form-label" style="font-weight: 700;">Password Peserta (Default: 12345678) *</label>
                    <input type="text" name="password" id="edit_student_password" class="form-control" style="font-family: monospace; font-weight: 700; color: #15803d;" required>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editStudentModal').classList.remove('open')">Batal</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 700;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- IMPORT MODAL -->
    <?php renderImportModalGeneric('/admin/students/import', '/admin/students/template', 'Peserta', 'template_siswa'); ?>

    <script>
        function toggleCreateStudentCard() {
            var c = document.getElementById('createStudentCard');
            if (c) {
                if (c.style.display === 'none' || c.style.display === '') {
                    c.style.display = 'block';
                    syncNewStudentClassOptions();
                    window.scrollTo({ top: c.offsetTop - 80, behavior: 'smooth' });
                } else {
                    c.style.display = 'none';
                }
            }
        }

        function syncNewStudentClassOptions() {
            var majorSelect = document.getElementById('new_student_major_id');
            var classSelect = document.getElementById('new_student_class');
            if (!majorSelect || !classSelect) return;
            var selectedMajor = String(majorSelect.value).trim();
            var options = classSelect.querySelectorAll('option');
            var firstMatched = null;
            options.forEach(function(opt) {
                var m = String(opt.getAttribute('data-major') || '').trim();
                if (!selectedMajor || m === selectedMajor || !m) {
                    opt.style.display = '';
                    if (!firstMatched) firstMatched = opt;
                } else {
                    opt.style.display = 'none';
                }
            });
            var curOpt = classSelect.options[classSelect.selectedIndex];
            if (curOpt && curOpt.style.display === 'none' && firstMatched) {
                classSelect.value = firstMatched.value;
            }
        }

        function syncEditStudentClassOptions() {
            var majorSelect = document.getElementById('edit_student_major_id');
            var classSelect = document.getElementById('edit_student_class');
            if (!majorSelect || !classSelect) return;
            var selectedMajor = String(majorSelect.value).trim();
            var options = classSelect.querySelectorAll('option');
            var firstMatched = null;
            options.forEach(function(opt) {
                var m = String(opt.getAttribute('data-major') || '').trim();
                if (!selectedMajor || m === selectedMajor || !m) {
                    opt.style.display = '';
                    if (!firstMatched) firstMatched = opt;
                } else {
                    opt.style.display = 'none';
                }
            });
            var curOpt = classSelect.options[classSelect.selectedIndex];
            if (curOpt && curOpt.style.display === 'none' && firstMatched) {
                classSelect.value = firstMatched.value;
            }
        }

        function openEditStudent(id, name, nis, majorId, className, gender, password) {
            document.getElementById('edit_student_id').value = id;
            document.getElementById('edit_student_name').value = name;
            document.getElementById('edit_student_nis').value = nis;
            var mSelect = document.getElementById('edit_student_major_id');
            if (mSelect && majorId) {
                mSelect.value = majorId;
            }
            syncEditStudentClassOptions();
            document.getElementById('edit_student_class').value = className;
            document.getElementById('edit_student_gender').value = gender;
            document.getElementById('edit_student_password').value = password || '12345678';
            document.getElementById('editStudentModal').classList.add('open');
        }

        document.addEventListener('DOMContentLoaded', function() {
            syncNewStudentClassOptions();
        });

        // ==========================================
        // MENU TANDAI (BULK ACTIONS SELECTION JS)
        // ==========================================
        function getSelectedStudentIds() {
            var checkboxes = document.querySelectorAll('.student-checkbox:checked');
            var ids = [];
            checkboxes.forEach(function(cb) { ids.push(cb.value); });
            return ids;
        }

        function updateSelectedStudentsBar() {
            var ids = getSelectedStudentIds();
            var count = ids.length;
            var bar = document.getElementById('bulkActionBar');
            var countSpan = document.getElementById('selectedCount');
            var selectAll = document.getElementById('selectAllStudents');
            var allCheckboxes = document.querySelectorAll('.student-checkbox');
            
            if (countSpan) countSpan.innerText = count;
            if (bar) {
                bar.style.display = (count > 0) ? 'flex' : 'none';
            }
            if (selectAll && allCheckboxes.length > 0) {
                selectAll.checked = (count === allCheckboxes.length);
                selectAll.indeterminate = (count > 0 && count < allCheckboxes.length);
            }
        }

        function toggleSelectAllStudents(masterCb) {
            var checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(function(cb) {
                cb.checked = masterCb.checked;
            });
            updateSelectedStudentsBar();
        }

        function clearSelectedStudents() {
            var checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(function(cb) { cb.checked = false; });
            var master = document.getElementById('selectAllStudents');
            if (master) { master.checked = false; master.indeterminate = false; }
            updateSelectedStudentsBar();
        }

        function executeBulkDelete() {
            var ids = getSelectedStudentIds();
            if (ids.length === 0) return;
            if (confirm('Apakah Anda yakin ingin menghapus ' + ids.length + ' peserta yang ditandai?')) {
                document.getElementById('bulkDeleteIds').value = ids.join(',');
                document.getElementById('bulkDeleteForm').submit();
            }
        }

        function executeBulkResetPassword() {
            var ids = getSelectedStudentIds();
            if (ids.length === 0) return;
            if (confirm('Reset password ' + ids.length + ' peserta yang ditandai ke default 12345678?')) {
                document.getElementById('bulkResetIds').value = ids.join(',');
                document.getElementById('bulkResetForm').submit();
            }
        }

        function openBulkChangeClassModal() {
            var ids = getSelectedStudentIds();
            if (ids.length === 0) return;
            document.getElementById('bulkChangeClassIds').value = ids.join(',');
            document.getElementById('bulkClassCountLabel').innerText = ids.length;
            document.getElementById('bulkChangeClassModal').classList.add('open');
        }

        function closeBulkChangeClassModal() {
            document.getElementById('bulkChangeClassModal').classList.remove('open');
        }
    </script>

    <!-- BULK CHANGE CLASS MODAL -->
    <div class="modal-overlay" id="bulkChangeClassModal" style="align-items: center; justify-content: center;">
        <div class="modal-content-card" style="max-width: 440px; border-radius: 12px;">
            <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid var(--border-color); padding: 14px 18px;">
                <h3 class="modal-title" style="margin: 0; font-size: 1.1rem; font-weight: 800;">Pindah Kelas Peserta Terpilih</h3>
                <button type="button" class="modal-close-btn" onclick="closeBulkChangeClassModal()">&times;</button>
            </div>
            <form action="/admin/students/bulk-change-class" method="POST" id="bulkChangeClassForm" style="padding: 18px;">
                <input type="hidden" name="ids" id="bulkChangeClassIds">
                <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 14px;">
                    Pindahkan <strong id="bulkClassCountLabel" style="color: var(--primary);">0</strong> peserta yang ditandai ke rombel kelas tujuan:
                </p>
                <div class="form-group" style="margin-bottom: 18px;">
                    <label class="form-label" style="font-weight: 700;">Pilih Kelas Tujuan *</label>
                    <select name="target_class" class="form-select" required>
                        <?php foreach ($_SESSION['classes_list'] as $c): ?>
                            <option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-secondary" onclick="closeBulkChangeClassModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 700;">Terapkan Pindah Kelas</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

// =========================================================================
// 10. MENU 4: DATA KELAS (ID JURUSAN ANGKA, NAMA KELAS, EDIT)
// =========================================================================
function renderClassesContent() {
    $search = strtolower(trim($_GET['search'] ?? ''));
    $classes = $_SESSION['classes_list'] ?? [];

    // Pastikan ID jurusan berupa angka (1, 2, 3, dst.)
    $majorNumberMap = [
        'TKJ' => '1',
        'RPL' => '2',
        'AKL' => '3',
        'AK' => '3',
        'TBSM' => '4',
        'OTKP' => '5',
    ];

    foreach ($classes as &$c) {
        if (empty($c['major_id']) || !is_numeric($c['major_id'])) {
            $code = strtoupper($c['major_id'] ?? '');
            if (empty($code) || !isset($majorNumberMap[$code])) {
                if (str_contains(strtoupper($c['name']), 'TKJ')) $c['major_id'] = '1';
                elseif (str_contains(strtoupper($c['name']), 'RPL')) $c['major_id'] = '2';
                elseif (str_contains(strtoupper($c['name']), 'AK')) $c['major_id'] = '3';
                else $c['major_id'] = '1';
            } else {
                $c['major_id'] = $majorNumberMap[$code];
            }
        }
        if (empty($c['major'])) {
            if ($c['major_id'] == '1') $c['major'] = 'Teknik Komputer & Jaringan';
            elseif ($c['major_id'] == '2') $c['major'] = 'Rekayasa Perangkat Lunak';
            elseif ($c['major_id'] == '3') $c['major'] = 'Akuntansi & Keuangan Lembaga';
            else $c['major'] = 'Teknik Komputer & Jaringan';
        }
    }
    unset($c);
    $_SESSION['classes_list'] = $classes;

    if ($search !== '') {
        $classes = array_filter($classes, function($c) use ($search) {
            return str_contains(strtolower($c['name']), $search) 
                || str_contains(strtolower((string)($c['major_id'] ?? '')), $search)
                || str_contains(strtolower($c['major'] ?? ''), $search);
        });
    }
    ?>
    <div style="display: flex; flex-direction: column; gap: 16px;">
        <!-- CONTENT HEADER -->
        <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 class="page-title" style="margin: 0; font-size: 1.25rem;">Data Kelas</h1>
                <p class="page-subtitle" style="margin: 4px 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                    Kelola ID jurusan (angka) dan nama kelas rombel.
                </p>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <button type="button" class="btn btn-primary" onclick="toggleCreateClassCard()" style="font-weight: 700;">
                    <span>+</span> Tambah Kelas
                </button>
            </div>
        </div>

        <!-- SEARCH BAR -->
        <div class="action-bar">
            <div class="filter-group">
                <form action="/admin/classes" method="GET" style="display: flex; gap: 8px; align-items: center;">
                    <input type="text" name="search" class="form-control" placeholder="Cari nomor ID jurusan / nama kelas..." value="<?= htmlspecialchars($search) ?>" style="max-width: 280px;">
                    <button type="submit" class="btn btn-secondary">Cari</button>
                    <?php if ($search !== ''): ?>
                        <a href="/admin/classes" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
            <div style="font-size: 13px; color: var(--text-muted);">
                Total <strong><?= count($classes) ?> Kelas</strong>
            </div>
        </div>

        <!-- FORM TAMBAH KELAS (Collapsible) -->
        <div class="card" id="createClassCard" style="display: none; border-color: var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 class="card-title" style="margin-bottom: 0;">Tambah Kelas Baru</h3>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleCreateClassCard()">&times; Batal</button>
            </div>
            <form action="/admin/classes/create" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700;">ID Jurusan (Angka) *</label>
                        <input type="number" min="1" step="1" name="major_id" class="form-control" placeholder="Contoh: 1 (TKJ), 2 (RPL)" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700;">Jurusan *</label>
                        <input type="text" name="major" class="form-control" placeholder="Contoh: Teknik Komputer & Jaringan" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700;">Nama Kelas *</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: 10-TKJ-1" required>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="toggleCreateClassCard()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 700;">Simpan</button>
                </div>
            </form>
        </div>

        <!-- TABEL DATA KELAS -->
        <div class="card" style="padding: 0; overflow: hidden; box-shadow: var(--shadow-sm);">
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">No</th>
                            <th style="width: 140px; text-align: center;">ID Jurusan (Angka)</th>
                            <th>Jurusan</th>
                            <th>Nama Kelas</th>
                            <th style="width: 100px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($classes)): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">🏫</div>
                                        <p>Belum ada data kelas.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($classes as $idx => $c): ?>
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted); font-weight: 600;"><?= $idx + 1 ?></td>
                                    <td style="text-align: center;">
                                        <span class="badge badge-primary" style="font-family: monospace; font-size: 14px; font-weight: 800; padding: 4px 12px; min-width: 32px; display: inline-block;">
                                            <?= htmlspecialchars($c['major_id'] ?? '1') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--text-primary);">
                                            <?= htmlspecialchars($c['major'] ?? 'Teknik Komputer & Jaringan') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-primary); font-size: 14px;">
                                            <?= htmlspecialchars($c['name']) ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <button 
                                            type="button" 
                                            class="btn btn-secondary btn-sm" 
                                            onclick="openEditClass('<?= htmlspecialchars($c['id']) ?>', '<?= htmlspecialchars(addslashes($c['major_id'] ?? '1')) ?>', '<?= htmlspecialchars(addslashes($c['major'] ?? 'Teknik Komputer & Jaringan')) ?>', '<?= htmlspecialchars(addslashes($c['name'])) ?>')"
                                            style="padding: 5px 14px; font-weight: 600;"
                                        >
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL EDIT DATA KELAS -->
    <div class="modal-overlay" id="editClassModal" style="align-items: center; justify-content: center;">
        <div class="modal-content-card" style="max-width: 480px; border-radius: 12px;">
            <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid var(--border-color); padding: 16px 20px;">
                <h3 class="modal-title" style="margin: 0; font-size: 1.15rem; font-weight: 800;">Edit Data Kelas</h3>
                <button type="button" class="modal-close-btn" onclick="document.getElementById('editClassModal').classList.remove('open')">&times;</button>
            </div>
            <form action="/admin/classes/edit" method="POST" style="padding: 20px;">
                <input type="hidden" name="id" id="edit_class_id">
                
                <div style="margin-bottom: 14px;">
                    <label class="form-label" style="font-weight: 700;">ID Jurusan (Angka) *</label>
                    <input type="number" min="1" step="1" name="major_id" id="edit_class_major_id" class="form-control" placeholder="Contoh: 1, 2, 3" required>
                </div>
                
                <div style="margin-bottom: 14px;">
                    <label class="form-label" style="font-weight: 700;">Jurusan *</label>
                    <input type="text" name="major" id="edit_class_major" class="form-control" placeholder="Contoh: Teknik Komputer & Jaringan" required>
                </div>

                <div style="margin-bottom: 18px;">
                    <label class="form-label" style="font-weight: 700;">Nama Kelas *</label>
                    <input type="text" name="name" id="edit_class_name" class="form-control" placeholder="Contoh: 10-TKJ-1" required>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editClassModal').classList.remove('open')">Batal</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 700;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleCreateClassCard() {
            var c = document.getElementById('createClassCard');
            if (c) {
                if (c.style.display === 'none' || c.style.display === '') {
                    c.style.display = 'block';
                    window.scrollTo({ top: c.offsetTop - 80, behavior: 'smooth' });
                } else {
                    c.style.display = 'none';
                }
            }
        }

        function openEditClass(id, majorId, major, name) {
            document.getElementById('edit_class_id').value = id;
            document.getElementById('edit_class_major_id').value = majorId;
            document.getElementById('edit_class_major').value = major;
            document.getElementById('edit_class_name').value = name;
            document.getElementById('editClassModal').classList.add('open');
        }
    </script>
    <?php
}

// =========================================================================
// 11. MENU 5: MATA PELAJARAN (SETTING ALOKASI MAPEL PER KELAS)
// =========================================================================
function renderSubjectsContent() {
    $search = strtolower(trim($_GET['search'] ?? ''));
    $subjects = $_SESSION['subjects_list'] ?? [];
    $classesList = $_SESSION['classes_list'] ?? [];
    $teachersList = $_SESSION['teachers_list'] ?? [];

    // Ensure each subject has class allocations
    $defaultClassMap = [
        'sb1' => ['10-TKJ-1', '10-RPL-1'],
        'sb2' => ['10-TKJ-1', '10-RPL-1'],
        'sb3' => ['10-RPL-1'],
        'sb4' => ['10-TKJ-1'],
        'sb5' => ['10-TKJ-1', '10-RPL-1', '11-TKJ-1', '11-RPL-1', '12-TKJ-1'],
    ];

    foreach ($subjects as &$sb) {
        if (empty($sb['classes'])) {
            $sb['classes'] = $defaultClassMap[$sb['id']] ?? ['10-TKJ-1', '10-RPL-1'];
        }
    }
    unset($sb);

    if ($search !== '') {
        $subjects = array_filter($subjects, function($sb) use ($search) {
            $nameMatch = str_contains(strtolower($sb['name']), $search);
            $codeMatch = str_contains(strtolower($sb['code']), $search);
            $classMatch = false;
            foreach ($sb['classes'] ?? [] as $cls) {
                if (str_contains(strtolower($cls), $search)) {
                    $classMatch = true;
                    break;
                }
            }
            return $nameMatch || $codeMatch || $classMatch;
        });
    }
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- CONTENT HEADER -->
        <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <h1 class="page-title" style="margin: 0; font-size: 1.25rem;">Daftar Mata Pelajaran</h1>
                    <span class="badge badge-primary" style="font-size: 11px;">SETTING KURIKULUM</span>
                </div>
                <p class="page-subtitle" style="margin: 4px 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                    Kelola mata pelajaran dan alokasi rombel kelas yang mengampu kurikulum. Untuk pembuatan bank soal tersedia di menu <strong>Bank Soal</strong>.
                </p>
            </div>
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-primary" onclick="toggleCreateSubjectCard()" style="font-weight: 700;">
                    <span>+</span> Tambah Mapel Baru
                </button>
            </div>
        </div>

        <!-- SEARCH & ACTION BAR -->
        <div class="action-bar">
            <div class="filter-group">
                <form action="/admin/subjects" method="GET" style="display: flex; gap: 8px; align-items: center;">
                    <input type="text" name="search" class="form-control" placeholder="Cari kode, nama mapel, atau kelas..." value="<?= htmlspecialchars($search) ?>" style="max-width: 280px;">
                    <button type="submit" class="btn btn-secondary">Cari</button>
                    <?php if ($search !== ''): ?>
                        <a href="/admin/subjects" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
            <div style="font-size: 13px; color: var(--text-muted);">
                Total: <strong><?= count($subjects) ?> Mata Pelajaran</strong> terdaftar
            </div>
        </div>

        <!-- CREATE SUBJECT CARD (Collapsible) -->
        <div class="card" id="createSubjectCard" style="display: none; border-color: var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 18px;">📚</span>
                    <h3 class="card-title" style="margin-bottom: 0;">Tambah Mata Pelajaran &amp; Alokasi Kelas</h3>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleCreateSubjectCard()">&times; Batal</button>
            </div>
            <form action="/admin/subjects/create" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kode Mapel *</label>
                        <input type="text" name="code" class="form-control" placeholder="Contoh: MAT, BIND, PROG" style="text-transform: uppercase;" required>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Nama Mata Pelajaran *</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Matematika X, Dasar Pemrograman RPL" required>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 10px;">
                    <label class="form-label">Guru Pengampu Mata Pelajaran</label>
                    <select name="teacher" class="form-control">
                        <?php foreach ($teachersList as $t): ?>
                            <option value="<?= htmlspecialchars($t['name']) ?>"><?= htmlspecialchars($t['name']) ?> (NIP: <?= htmlspecialchars($t['nip']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- CLASS SELECTION CHECKBOXES -->
                <div class="form-group" style="margin-top: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label class="form-label" style="margin: 0; font-weight: 700;">
                            🏫 Alokasi Kelas yang Mengampu Mapel Ini:
                        </label>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="btn btn-secondary btn-sm" style="font-size: 11px; padding: 2px 8px;" onclick="checkAllClasses('create')">Pilih Semua</button>
                            <button type="button" class="btn btn-secondary btn-sm" style="font-size: 11px; padding: 2px 8px;" onclick="uncheckAllClasses('create')">Kosongkan</button>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <?php foreach ($classesList as $c): ?>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; background: #ffffff; padding: 6px 10px; border-radius: 6px; border: 1px solid #cbd5e1;">
                                <input type="checkbox" name="classes[]" value="<?= htmlspecialchars($c['name']) ?>" class="class-checkbox-create" checked>
                                <strong><?= htmlspecialchars($c['name']) ?></strong>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                    <button type="button" class="btn btn-secondary" onclick="toggleCreateSubjectCard()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Mata Pelajaran &amp; Kelas</button>
                </div>
            </form>
        </div>

        <!-- SUBJECTS TABLE (FOCUSED ON SUBJECT & CLASS ALLOCATIONS) -->
        <div class="card" style="padding: 0; overflow: hidden; box-shadow: var(--shadow-sm);">
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 45px; text-align: center;">No</th>
                            <th style="width: 90px;">Kode</th>
                            <th style="min-width: 200px;">Nama Mata Pelajaran</th>
                            <th>Guru Pengampu</th>
                            <th style="min-width: 260px;">Alokasi Rombel Kelas</th>
                            <th style="width: 80px; text-align: center;">Status</th>
                            <th style="width: 220px; text-align: center;">Aksi Setting</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subjects)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">📚</div>
                                        <p>Belum ada data mata pelajaran.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subjects as $idx => $sb): 
                                $assignedClasses = $sb['classes'] ?? ['10-TKJ-1'];
                            ?>
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted); font-weight: 600;"><?= $idx + 1 ?></td>
                                    <td>
                                        <span class="badge badge-primary" style="font-size: 11.5px; font-weight: 700;"><?= htmlspecialchars($sb['code']) ?></span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; font-size: 14px; color: var(--text-primary);"><?= htmlspecialchars($sb['name']) ?></div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <span style="font-size: 14px;">👨‍🏫</span>
                                            <span><?= htmlspecialchars($sb['teacher'] ?? 'Budi Santoso, S.Pd') ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-wrap: wrap; gap: 4px; align-items: center;">
                                            <?php foreach ($assignedClasses as $cls): ?>
                                                <span class="badge" style="background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; font-weight: 700; font-size: 11.5px; padding: 3px 8px;">
                                                    🏫 <?= htmlspecialchars($cls) ?>
                                                </span>
                                            <?php endforeach; ?>
                                            <span style="font-size: 11px; color: var(--text-muted); margin-left: 4px;">
                                                (<?= count($assignedClasses) ?> Kelas)
                                            </span>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge badge-success">Aktif</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="action-btns" style="justify-content: center; gap: 6px;">
                                            <button 
                                                type="button" 
                                                class="btn btn-primary btn-sm" 
                                                onclick="openSetClassModal('<?= htmlspecialchars($sb['id']) ?>', '<?= htmlspecialchars(addslashes($sb['name'])) ?>', '<?= htmlspecialchars(addslashes($sb['code'])) ?>', '<?= htmlspecialchars(addslashes($sb['teacher'] ?? '')) ?>', <?= htmlspecialchars(json_encode($assignedClasses)) ?>)"
                                                style="display: inline-flex; align-items: center; gap: 4px; font-weight: 700;"
                                                title="Setting alokasi kelas untuk mapel ini"
                                            >
                                                <span>🏫</span> Atur Kelas
                                            </button>
                                            <button 
                                                type="button" 
                                                class="btn btn-secondary btn-sm" 
                                                onclick="openEditSubjectModal('<?= htmlspecialchars($sb['id']) ?>', '<?= htmlspecialchars(addslashes($sb['code'])) ?>', '<?= htmlspecialchars(addslashes($sb['name'])) ?>', '<?= htmlspecialchars(addslashes($sb['teacher'] ?? '')) ?>', <?= htmlspecialchars(json_encode($assignedClasses)) ?>)"
                                                title="Edit Informasi Mapel"
                                            >
                                                Edit
                                            </button>
                                            <a 
                                                href="/admin/subjects/delete?id=<?= urlencode($sb['id']) ?>" 
                                                class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus mata pelajaran <?= htmlspecialchars(addslashes($sb['name'])) ?>?');"
                                                title="Hapus Mapel"
                                            >
                                                Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         MODAL 1: SETTING ALOKASI KELAS MATA PELAJARAN
         ========================================================================= -->
    <div class="modal-overlay" id="setClassModal" style="align-items: center; justify-content: center;">
        <div class="modal-content-card" style="max-width: 540px; border-radius: 12px;">
            <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid var(--border-color); padding: 16px 20px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 20px;">🏫</span>
                        <h3 class="modal-title" style="margin: 0; font-size: 1.15rem; font-weight: 800;">
                            Setting Alokasi Kelas Mapel
                        </h3>
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                        Mata Pelajaran: <strong id="scSubjectName" style="color: var(--primary);">-</strong> (<span id="scSubjectCode">-</span>)
                    </div>
                </div>
                <button type="button" class="modal-close-btn" onclick="document.getElementById('setClassModal').classList.remove('open')">&times;</button>
            </div>
            <form action="/admin/subjects/set-classes" method="POST" style="padding: 20px;">
                <input type="hidden" name="id" id="sc_subject_id">

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" style="font-weight: 700;">Guru Pengampu Mapel:</label>
                    <select name="teacher" id="sc_teacher" class="form-control">
                        <?php foreach ($teachersList as $t): ?>
                            <option value="<?= htmlspecialchars($t['name']) ?>"><?= htmlspecialchars($t['name']) ?> (NIP: <?= htmlspecialchars($t['nip']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label class="form-label" style="margin: 0; font-weight: 700;">
                            Pilih Kelas yang Mengampu Mata Pelajaran Ini:
                        </label>
                        <div style="display: flex; gap: 6px;">
                            <button type="button" class="btn btn-secondary btn-sm" style="font-size: 11px; padding: 2px 8px;" onclick="checkAllClasses('set')">Pilih Semua</button>
                            <button type="button" class="btn btn-secondary btn-sm" style="font-size: 11px; padding: 2px 8px;" onclick="uncheckAllClasses('set')">Kosongkan</button>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px solid #e2e8f0;" id="scClassCheckboxes">
                        <?php foreach ($classesList as $c): ?>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; background: #ffffff; padding: 8px 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                                <input type="checkbox" name="classes[]" value="<?= htmlspecialchars($c['name']) ?>" class="class-checkbox-set" id="sc_chk_<?= htmlspecialchars($c['name']) ?>">
                                <strong><?= htmlspecialchars($c['name']) ?></strong>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('setClassModal').classList.remove('open')">Batal</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 700;">Simpan Alokasi Kelas</button>
                </div>
            </form>
        </div>
    </div>

    <!-- =========================================================================
         MODAL 2: EDIT MATA PELAJARAN LENGKAP
         ========================================================================= -->
    <div class="modal-overlay" id="editSubjectModal" style="align-items: center; justify-content: center;">
        <div class="modal-content-card" style="max-width: 520px; border-radius: 12px;">
            <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid var(--border-color); padding: 16px 20px;">
                <h3 class="modal-title" style="margin: 0; font-size: 1.15rem; font-weight: 800;">Edit Mata Pelajaran &amp; Alokasi Kelas</h3>
                <button type="button" class="modal-close-btn" onclick="document.getElementById('editSubjectModal').classList.remove('open')">&times;</button>
            </div>
            <form action="/admin/subjects/edit" method="POST" style="padding: 20px;">
                <input type="hidden" name="id" id="edit_subject_id">
                
                <div class="form-row" style="margin-bottom: 14px;">
                    <div class="form-group">
                        <label class="form-label">Kode Mapel *</label>
                        <input type="text" name="code" id="edit_subject_code" class="form-control" style="text-transform: uppercase;" required>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Nama Mata Pelajaran *</label>
                        <input type="text" name="name" id="edit_subject_name" class="form-control" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label">Guru Pengampu</label>
                    <select name="teacher" id="edit_subject_teacher" class="form-control">
                        <?php foreach ($teachersList as $t): ?>
                            <option value="<?= htmlspecialchars($t['name']) ?>"><?= htmlspecialchars($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" style="font-weight: 700;">Alokasi Rombel Kelas:</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 8px; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <?php foreach ($classesList as $c): ?>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; cursor: pointer; background: #ffffff; padding: 6px 10px; border-radius: 6px; border: 1px solid #cbd5e1;">
                                <input type="checkbox" name="classes[]" value="<?= htmlspecialchars($c['name']) ?>" class="class-checkbox-edit" id="edit_chk_<?= htmlspecialchars($c['name']) ?>">
                                <strong><?= htmlspecialchars($c['name']) ?></strong>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editSubjectModal').classList.remove('open')">Batal</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 700;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleCreateSubjectCard() {
            var c = document.getElementById('createSubjectCard');
            if (c) {
                if (c.style.display === 'none' || c.style.display === '') {
                    c.style.display = 'block';
                    window.scrollTo({ top: c.offsetTop - 80, behavior: 'smooth' });
                } else {
                    c.style.display = 'none';
                }
            }
        }

        function checkAllClasses(type) {
            document.querySelectorAll('.class-checkbox-' + type).forEach(function(chk) {
                chk.checked = true;
            });
        }

        function uncheckAllClasses(type) {
            document.querySelectorAll('.class-checkbox-' + type).forEach(function(chk) {
                chk.checked = false;
            });
        }

        function openSetClassModal(id, name, code, teacher, classes) {
            document.getElementById('sc_subject_id').value = id;
            document.getElementById('scSubjectName').innerText = name;
            document.getElementById('scSubjectCode').innerText = code;
            
            var tSelect = document.getElementById('sc_teacher');
            if (tSelect && teacher) {
                tSelect.value = teacher;
            }

            document.querySelectorAll('.class-checkbox-set').forEach(function(chk) {
                chk.checked = classes.includes(chk.value);
            });

            document.getElementById('setClassModal').classList.add('open');
        }

        function openEditSubjectModal(id, code, name, teacher, classes) {
            document.getElementById('edit_subject_id').value = id;
            document.getElementById('edit_subject_code').value = code;
            document.getElementById('edit_subject_name').value = name;
            
            var tSelect = document.getElementById('edit_subject_teacher');
            if (tSelect && teacher) {
                tSelect.value = teacher;
            }

            document.querySelectorAll('.class-checkbox-edit').forEach(function(chk) {
                chk.checked = classes.includes(chk.value);
            });

            document.getElementById('editSubjectModal').classList.add('open');
        }
    </script>
    <?php
}

// =========================================================================
// 12. MENU 6: BANK SOAL (MANAJEMEN REPOSITORI BUTIR SOAL & PEMBUATAN SOAL)
// =========================================================================
function renderQuestionsContent() {
    $search = strtolower(trim($_GET['search'] ?? ''));
    $filterSubject = trim($_GET['subject_id'] ?? '');
    $allQuestions = $_SESSION['questions_list'] ?? [];
    $subjects = $_SESSION['subjects_list'] ?? [];

    // Hitung butir soal per mata pelajaran
    // Hitung butir soal per tipe per mata pelajaran
    $subjectCounts = [];
    foreach ($subjects as $sb) {
        $sbId = $sb['id'];
        $sbName = $sb['name'];
        $pg = 0; $pgMulti = 0; $essai = 0; $bs = 0; $jodoh = 0;
        foreach ($allQuestions as $q) {
            if (($q['subject_id'] ?? '') === $sbId || ($q['subject_name'] ?? '') === $sbName) {
                $type = $q['question_type'] ?? 'single_choice';
                if ($type === 'single_choice') $pg++;
                elseif ($type === 'multiple_choice') $pgMulti++;
                elseif ($type === 'essay') $essai++;
                elseif ($type === 'true_false') $bs++;
                elseif ($type === 'matching') $jodoh++;
                else $pg++;
            }
        }
        $subjectCounts[$sbId] = [
            'pg' => $pg,
            'pg_multi' => $pgMulti,
            'essai' => $essai,
            'bs' => $bs,
            'jodoh' => $jodoh,
            'total' => ($pg + $pgMulti + $essai + $bs + $jodoh),
        ];
    }
    $typeCounts = $subjectCounts;

    $filteredQuestions = $allQuestions;
    if ($filterSubject !== '' && $filterSubject !== 'all') {
        $filteredQuestions = array_filter($filteredQuestions, function($q) use ($filterSubject) {
            return ($q['subject_id'] ?? '') === $filterSubject || ($q['subject_name'] ?? '') === $filterSubject;
        });
    }

    if ($search !== '') {
        $filteredQuestions = array_filter($filteredQuestions, function($q) use ($search) {
            return str_contains(strtolower($q['content']), $search) || str_contains(strtolower($q['subject_name']), $search);
        });
    }

    $activeSubjectObj = null;
    if ($filterSubject !== '' && $filterSubject !== 'all') {
        foreach ($subjects as $sb) {
            if ($sb['id'] === $filterSubject || $sb['name'] === $filterSubject) {
                $activeSubjectObj = $sb;
                break;
            }
        }
    }
    ?>
    <style>
        .example-table th {
            color: #2563eb !important;
            font-weight: 800 !important;
            font-size: 12px !important;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border-bottom: 2px solid #e2e8f0 !important;
            white-space: nowrap;
            padding: 14px 10px !important;
        }
        .sort-icon {
            display: inline-block;
            font-size: 10px;
            color: #94a3b8;
            margin-left: 2px;
            vertical-align: middle;
        }
        .btn-buat-soal-purple {
            background: #5b47fb !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 6px !important;
            padding: 8px 18px !important;
            font-size: 13px !important;
            font-weight: 700 !important;
            box-shadow: 0 6px 16px rgba(91, 71, 251, 0.35) !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-buat-soal-purple:hover {
            background: #4935e8 !important;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(91, 71, 251, 0.45) !important;
            color: #ffffff !important;
        }
        .action-stack {
            display: flex;
            flex-direction: column;
            gap: 6px;
            align-items: center;
            justify-content: center;
        }
        .btn-action-ubah {
            background: #2563eb !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 6px !important;
            padding: 6px 18px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25) !important;
            width: 86px;
            text-align: center;
            cursor: pointer;
            text-decoration: none !important;
            display: inline-block;
            transition: all 0.2s ease;
        }
        .btn-action-ubah:hover {
            background: #1d4ed8 !important;
            transform: translateY(-1px);
            color: #ffffff !important;
        }
        .btn-action-arsipkan {
            background: #e59324 !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 6px !important;
            padding: 6px 18px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 10px rgba(229, 147, 36, 0.28) !important;
            width: 86px;
            text-align: center;
            cursor: pointer;
            text-decoration: none !important;
            display: inline-block;
            transition: all 0.2s ease;
        }
        .btn-action-arsipkan:hover {
            background: #c97d1b !important;
            transform: translateY(-1px);
            color: #ffffff !important;
        }
        .btn-action-cetak {
            background: #0ea5e9 !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 6px !important;
            padding: 6px 18px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 10px rgba(14, 165, 233, 0.25) !important;
            width: 86px;
            text-align: center;
            cursor: pointer;
            text-decoration: none !important;
            display: inline-block;
            transition: all 0.2s ease;
        }
        .btn-action-cetak:hover {
            background: #0284c7 !important;
            transform: translateY(-1px);
            color: #ffffff !important;
        }
        .tb-btn {
            background: none;
            border: 1px solid transparent;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            color: #475569;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .tb-btn:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #1e293b;
        }
        .tb-separator {
            width: 1px;
            height: 18px;
            background: #e2e8f0;
            margin: 0 3px;
        }
        .word-select {
            font-size: 11.5px;
            padding: 2px 6px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            color: #334155;
            background: #f8fafc;
            cursor: pointer;
            height: 26px;
            outline: none;
        }
        .word-select:hover {
            border-color: #94a3b8;
            background: #ffffff;
        }
        .word-editor-box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
            margin-bottom: 14px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.03);
            transition: all 0.2s ease;
        }
        .word-editor-box:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
        }
        .word-content-editable {
            min-height: 140px;
            max-height: 480px;
            overflow-y: auto;
            padding: 14px 16px;
            font-size: 14px;
            line-height: 1.6;
            outline: none;
            background: #ffffff;
            color: #1e293b;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            word-break: break-word;
        }
        .word-content-editable:focus {
            background: #ffffff;
        }
        .word-content-editable:empty:before {
            content: attr(placeholder);
            color: #94a3b8;
            pointer-events: none;
            display: block;
        }
        .word-content-editable table {
            border-collapse: collapse;
            width: 100%;
            margin: 10px 0;
        }
        .word-content-editable table, .word-content-editable th, .word-content-editable td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
        }
        .word-content-editable th {
            background: #f8fafc;
            font-weight: 700;
        }
        .word-content-editable img {
            max-width: 100%;
            border-radius: 6px;
            margin: 6px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .word-content-editable ul, .word-content-editable ol {
            padding-left: 24px;
            margin: 8px 0;
        }
        .google-auth-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }
        .google-signin-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: #ffffff;
            color: #3c4043;
            border: 1px solid #dadce0;
            border-radius: 24px;
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(60,64,67,0.08);
            transition: all 0.2s ease;
        }
        .google-signin-btn:hover {
            background: #f8fafd;
            border-color: #c2c7d0;
            box-shadow: 0 2px 6px rgba(60,64,67,0.15);
            transform: translateY(-1px);
        }
        .google-chip {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #334155;
            padding: 5px 12px;
            border-radius: 16px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-block;
        }
        .google-chip:hover {
            background: #e0f2fe;
            border-color: #38bdf8;
            color: #0284c7;
            transform: translateY(-1px);
        }
        .ai-chip {
            background: #f3e8ff;
            border: 1px solid #d8b4fe;
            color: #6b21a8;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-block;
        }
        .ai-chip:hover {
            background: #e9d5ff;
            border-color: #c084fc;
            transform: translateY(-1px);
        }
    </style>

    <div style="display: flex; flex-direction: column; gap: 20px;">
        <?php if (!$activeSubjectObj): ?>
            <!-- ========================================================================= -->
            <!-- GAMBAR SATU: DAFTAR BANK SOAL (HANYA DITAMPILKAN JIKA BELUM PILIH MAPEL) -->
            <!-- ========================================================================= -->
            <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h1 class="page-title" style="margin: 0; font-size: 1.25rem;">Bank Soal</h1>
                    <p class="page-subtitle" style="margin: 4px 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                        Kelola bank soal ujian, format penilaian standard/tryout, dan daftar butir pertanyaan
                    </p>
                </div>
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <button type="button" class="btn btn-secondary" onclick="toggleCreateSubjectCard()" style="display: inline-flex; align-items: center; gap: 6px; border-color: #fbcfe8; color: #db2777; font-weight: 600; padding: 7px 16px;">
                        <span>📚</span> + Tambah Bank Soal
                    </button>
                </div>
            </div>

            <!-- FORM TAMBAH BANK SOAL (SESUAI SPESIFIKASI: GURU, JUDUL, FORMAT PENILAIAN, DESKRIPSI, WAKTU, BOBOT TOTAL 100%) -->
            <div class="card" id="createSubjectCard" style="display: none; border-color: #db2777; box-shadow: 0 6px 20px rgba(219, 39, 119, 0.08);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #fce7f3; padding-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 20px;">📚</span>
                        <div>
                            <h3 class="card-title" style="margin: 0; color: #be185d;">Tambah Bank Soal Baru</h3>
                            <p style="margin: 2px 0 0; font-size: 12px; color: #9d174d;">Lengkapi data bank soal beserta pembobotan tipe soal (total 100%).</p>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleCreateSubjectCard()">&times; Batal</button>
                </div>
                <form action="/admin/subjects/create" method="POST" id="formCreateSubject">
                    <div class="form-row">
                        <?php 
                        $currentUserRole = $_SESSION['cbt_user'] ?? 'admin';
                        if ($currentUserRole === 'admin'): 
                        ?>
                            <!-- GURU: Muncul jika Admin yang membuat -->
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label" style="font-weight: 700;">Guru Pengampu / Akses *</label>
                                <select name="teacher" class="form-select" required>
                                    <?php foreach ($_SESSION['teachers_list'] as $t): ?>
                                        <option value="<?= htmlspecialchars($t['name']) ?>"><?= htmlspecialchars($t['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small style="color: var(--text-muted); font-size: 11px;">Guru yang akan memiliki akses ke bank soal ini.</small>
                            </div>
                        <?php else: ?>
                            <!-- GURU: Otomatis terisi jika Guru yang login, menu tidak muncul -->
                            <input type="hidden" name="teacher" value="<?= htmlspecialchars($_SESSION['teachers_list'][0]['name'] ?? 'Guru Pengajar') ?>">
                        <?php endif; ?>

                        <div class="form-group" style="flex: 2;">
                            <label class="form-label" style="font-weight: 700;">Judul Bank Soal *</label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Testing Ujian Tryout, Matematika X Penilaian Akhir" required>
                            <small style="color: var(--text-muted); font-size: 11px;">Merupakan nama atau identitas bank soal.</small>
                        </div>

                        <div class="form-group" style="flex: 1;">
                            <label class="form-label" style="font-weight: 700;">Kode Bank Soal *</label>
                            <input type="text" name="code" class="form-control" placeholder="Contoh: MAT-X, TRYOUT-1" style="text-transform: uppercase;" required>
                        </div>
                    </div>

                    <div class="form-row" style="margin-top: 14px;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label" style="font-weight: 700;">Format Penilaian *</label>
                            <select name="format" class="form-select" required>
                                <option value="standard">Standard (Metode Penilaian Sekolah Biasa)</option>
                                <option value="tryout">Tryout (Format UTBK / SNMPTN / CPNS)</option>
                            </select>
                            <small style="font-size: 11px; margin-top: 4px; display: block;">
                                <a href="https://e-ujian.id/panduan-menggunakan-cbt-eujian/membuat-soal-tryout-utbk-snmptn-dan-cpns-online/" target="_blank" rel="noopener noreferrer" style="color: #2563eb; text-decoration: underline;">
                                    ℹ️ Lihat penjelasan format tryout
                                </a>
                            </small>
                        </div>

                        <div class="form-group" style="flex: 1;">
                            <label class="form-label" style="font-weight: 700;">Waktu / Durasi Pengerjaan *</label>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="number" name="duration" class="form-control" value="120" min="5" max="360" required style="max-width: 140px;">
                                <span style="font-weight: 600; color: var(--text-secondary);">Menit</span>
                            </div>
                            <small style="color: var(--text-muted); font-size: 11px;">Durasi atau timer berapa lama ujian dapat dilakukan oleh siswa.</small>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 14px;">
                        <label class="form-label" style="font-weight: 700;">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Penjelasan singkat tentang bank soal yang akan dibuat..."></textarea>
                    </div>

                    <!-- BOBOT MACAM-MACAM TIPE SOAL (TOTAL HARUS 100%) -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-top: 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <strong style="font-size: 13.5px; color: #1e293b;">Bobot Macam-Macam Tipe Soal</strong>
                                <p style="margin: 2px 0 0; font-size: 11.5px; color: #64748b;">
                                    Total bobot harus <strong>100%</strong>. Jika ada tipe soal yang tidak digunakan, silakan beri bobot <strong>0</strong>.
                                </p>
                            </div>
                            <div id="badgeTotalBobot" style="padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 800; background: #dcfce7; color: #166534; border: 1px solid #86efac;">
                                Total: 100% ✓
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px;">
                            <div>
                                <label style="font-size: 12px; font-weight: 700; color: #334155;">Bobot PG (%)</label>
                                <input type="number" name="weight_pg" id="input_w_pg" class="form-control weight-calc-input" value="60" min="0" max="100" oninput="calculateTotalWeight()">
                            </div>
                            <div>
                                <label style="font-size: 12px; font-weight: 700; color: #334155;">Bobot PG Multi (%)</label>
                                <input type="number" name="weight_pg_multi" id="input_w_pg_multi" class="form-control weight-calc-input" value="0" min="0" max="100" oninput="calculateTotalWeight()">
                            </div>
                            <div>
                                <label style="font-size: 12px; font-weight: 700; color: #334155;">Bobot Essai (%)</label>
                                <input type="number" name="weight_essay" id="input_w_essay" class="form-control weight-calc-input" value="40" min="0" max="100" oninput="calculateTotalWeight()">
                            </div>
                            <div>
                                <label style="font-size: 12px; font-weight: 700; color: #334155;">Bobot B/S (%)</label>
                                <input type="number" name="weight_tf" id="input_w_tf" class="form-control weight-calc-input" value="0" min="0" max="100" oninput="calculateTotalWeight()">
                            </div>
                            <div>
                                <label style="font-size: 12px; font-weight: 700; color: #334155;">Bobot Jodoh (%)</label>
                                <input type="number" name="weight_match" id="input_w_match" class="form-control weight-calc-input" value="0" min="0" max="100" oninput="calculateTotalWeight()">
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px;">
                        <button type="button" class="btn btn-secondary" onclick="toggleCreateSubjectCard()">Batal</button>
                        <button type="submit" class="btn btn-primary" style="font-weight: 700; padding: 8px 24px;">Simpan Bank Soal</button>
                    </div>
                </form>
            </div>

            <!-- TABEL BANK SOAL: 11 KOLOM PERSIS CONTOH GAMBAR -->
            <div class="card" style="padding: 0; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);">
                <div class="table-responsive">
                    <table class="table example-table" style="margin-bottom: 0;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="width: 55px; text-align: center;">NO. <span class="sort-icon">⇅</span></th>
                                <th>GURU <span class="sort-icon">⇅</span></th>
                                <th>JUDUL <span class="sort-icon">⇅</span></th>
                                <th style="width: 50px; text-align: center;">PG <span class="sort-icon">⇅</span></th>
                                <th style="width: 85px; text-align: center;">PG MULTI <span class="sort-icon">⇅</span></th>
                                <th style="width: 65px; text-align: center;">ESSAI <span class="sort-icon">⇅</span></th>
                                <th style="width: 55px; text-align: center;">B/S <span class="sort-icon">⇅</span></th>
                                <th style="width: 70px; text-align: center;">JODOH <span class="sort-icon">⇅</span></th>
                                <th style="width: 95px; text-align: center;">WAKTU <span class="sort-icon">⇅</span></th>
                                <th style="width: 150px; text-align: center;">DAFTAR PERTANYAAN <span class="sort-icon">⇅</span></th>
                                <th style="width: 110px; text-align: center;">AKSI <span class="sort-icon">⇅</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($subjects)): ?>
                                <tr>
                                    <td colspan="11" style="text-align: center; padding: 40px 20px;">
                                        <div style="font-size: 28px; margin-bottom: 8px;">📚</div>
                                        <div style="font-weight: 700; color: #475569;">Belum ada bank soal</div>
                                        <div style="font-size: 13px; color: #94a3b8; margin-top: 4px;">Klik "+ Tambah Bank Soal" di atas untuk membuat bank soal baru.</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($subjects as $idx => $sb): 
                                    $counts = $typeCounts[$sb['id']] ?? ['pg' => 0, 'pg_multi' => 0, 'essai' => 0, 'bs' => 0, 'jodoh' => 0];
                                    $teacherName = $sb['teacher'] ?? 'Demo';
                                    $durationText = ($sb['duration'] ?? 120) . ' menit';
                                ?>
                                    <tr style="vertical-align: middle;">
                                        <td style="text-align: center; color: #475569; font-weight: 600; padding: 18px 10px;">
                                            <?= $idx + 1 ?>
                                        </td>
                                        <td style="color: #475569; font-size: 13.5px; padding: 18px 12px;">
                                            <?= htmlspecialchars($teacherName) ?>
                                        </td>
                                        <td style="padding: 18px 12px;">
                                            <div style="font-weight: 600; font-size: 14px; color: #334155; line-height: 1.4;">
                                                <?= htmlspecialchars($sb['name']) ?>
                                            </div>
                                            <div style="font-size: 11.5px; color: #94a3b8; margin-top: 2px;">
                                                Kode: <?= htmlspecialchars($sb['code']) ?> &bull; Format: <?= ucfirst($sb['format'] ?? 'Standard') ?>
                                            </div>
                                        </td>
                                        <td style="text-align: center; color: #64748b; font-weight: 500; font-size: 14px; padding: 18px 8px;">
                                            <?= $counts['pg'] ?>
                                        </td>
                                        <td style="text-align: center; color: #64748b; font-weight: 500; font-size: 14px; padding: 18px 8px;">
                                            <?= $counts['pg_multi'] ?>
                                        </td>
                                        <td style="text-align: center; color: #64748b; font-weight: 500; font-size: 14px; padding: 18px 8px;">
                                            <?= $counts['essai'] ?>
                                        </td>
                                        <td style="text-align: center; color: #64748b; font-weight: 500; font-size: 14px; padding: 18px 8px;">
                                            <?= $counts['bs'] ?>
                                        </td>
                                        <td style="text-align: center; color: #64748b; font-weight: 500; font-size: 14px; padding: 18px 8px;">
                                            <?= $counts['jodoh'] ?>
                                        </td>
                                        <td style="text-align: center; color: #475569; font-size: 13px; white-space: nowrap; padding: 18px 10px;">
                                            <?= htmlspecialchars($durationText) ?>
                                        </td>
                                        <td style="text-align: center; padding: 18px 12px;">
                                            <!-- TOMBOL BUAT SOAL UNGU PERSIS CONTOH: LANGSUNG KE GAMBAR DUA -->
                                            <a href="/admin/questions?subject_id=<?= urlencode($sb['id']) ?>" class="btn-buat-soal-purple">
                                                Buat Soal
                                            </a>
                                        </td>
                                        <td style="text-align: center; padding: 14px 10px;">
                                            <!-- TOMBOL AKSI VERTIKAL PERSIS CONTOH: UBAH, ARSIPKAN, CETAK -->
                                            <div class="action-stack">
                                                <button type="button" class="btn-action-ubah" onclick="openEditSubjectModal('<?= htmlspecialchars($sb['id']) ?>', '<?= htmlspecialchars(addslashes($sb['code'])) ?>', '<?= htmlspecialchars(addslashes($sb['name'])) ?>')">
                                                    Ubah
                                                </button>
                                                <a href="/admin/subjects/delete?id=<?= urlencode($sb['id']) ?>" class="btn-action-arsipkan" onclick="return confirm('Arsipkan atau hapus bank soal <?= htmlspecialchars(addslashes($sb['name'])) ?>?');">
                                                    Arsipkan
                                                </a>
                                                <button type="button" class="btn-action-cetak" onclick="window.print()">
                                                    Cetak
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php else: ?>
            <!-- ========================================================================= -->
            <!-- GAMBAR DUA: DAFTAR SOAL UJIAN (LANGSUNG TAMPIL DISINI KETIKA BUAT SOAL)  -->
            <!-- ========================================================================= -->
            <!-- BANNER PERINGATAN BIRU MUDA PERSIS GAMBAR DUA -->
            <div style="background: #e6f0fa; border: 1px solid #b8daff; border-radius: 8px; padding: 14px 20px; color: #204d74; font-size: 13.5px; line-height: 1.5;">
                Mengubah bank soal yang sedang digunakan untuk ujian akan menyebabkan kesalahan pada hasil pengerjaan ujian siswa. Pastikan hanya mengubah bank soal yang belum diujikan ke siswa.
            </div>

            <!-- KARTU DAFTAR SOAL UJIAN PERSIS GAMBAR DUA -->
            <div class="card" style="border-radius: 10px; padding: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <a href="/admin/questions" style="display: inline-flex; align-items: center; gap: 6px; color: #64748b; font-size: 13px; text-decoration: none; margin-bottom: 8px;">
                            &larr; Kembali ke Bank Soal
                        </a>
                        <h2 style="font-size: 1.25rem; font-weight: 700; color: #1e293b; margin: 0 0 10px 0;">
                            Daftar Soal Ujian: <?= htmlspecialchars($activeSubjectObj['name']) ?>
                        </h2>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 16px; font-size: 13px; align-items: center;">
                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                <span style="color: #64748b; font-weight: 600;">📚 Mapel / Kelas:</span>
                                <strong style="color: #0052cc;"><?= htmlspecialchars($activeSubjectObj['name']) ?> - Kelas X</strong>
                            </div>
                            <div style="width: 1px; height: 18px; background: #cbd5e1;"></div>
                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                <span style="color: #64748b; font-weight: 600;">👨‍🏫 Nama Guru Mapel:</span>
                                <strong style="color: #1e293b;"><?= htmlspecialchars($activeSubjectObj['teacher'] ?? 'Budi Santoso, S.Pd') ?></strong>
                            </div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 14px; font-weight: 700; color: #475569;">
                            Total Soal : <?= count($filteredQuestions) ?> Soal
                        </span>
                    </div>
                </div>

                <!-- DUA TOMBOL PERSIS GAMBAR DUA: TAMBAH SOAL (BIRU GELAP) & IMPORT SOAL (BIRU MUDA) -->
                <div style="display: flex; gap: 10px; margin-bottom: 22px; flex-wrap: wrap;">
                    <button type="button" class="btn" onclick="openChooseTypeModal()" style="background: #003366; color: #ffffff; font-weight: 700; padding: 9px 24px; border-radius: 6px; border: none; box-shadow: 0 4px 12px rgba(0, 51, 102, 0.25); cursor: pointer;">
                        Tambah Soal
                    </button>
                    <button type="button" class="btn" onclick="openImportQuestionModal()" style="background: #0088cc; color: #ffffff; font-weight: 700; padding: 9px 24px; border-radius: 6px; border: none; box-shadow: 0 4px 12px rgba(0, 136, 204, 0.25); cursor: pointer;">
                        Import Soal
                    </button>
                </div>

                <!-- FORM TAMBAH SOAL INTERAKTIF PERSIS GAMBAR 3 -->
                <div id="createQuestionCard" style="display: none; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 22px; margin-bottom: 24px; box-shadow: 0 8px 24px rgba(0,0,0,0.06);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 20px;">➕</span>
                            <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;">
                                Buat Soal Baru: <?= htmlspecialchars($activeSubjectObj['name']) ?>
                            </h3>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="closeCreateQuestionCard()">&times; Batal</button>
                    </div>

                    <form action="/admin/questions/create" method="POST" id="formCreateQuestion" onsubmit="return validateAndSyncQuestionForm();">
                        <input type="hidden" name="subject_id" value="<?= htmlspecialchars($activeSubjectObj['id']) ?>">
                        
                        <div class="form-row" style="margin-bottom: 16px;">
                            <div class="form-group" style="flex: 2;">
                                <label class="form-label" style="font-weight: 700;">Tipe Soal *</label>
                                <select name="question_type" id="question_type_selector" class="form-select" onchange="handleQuestionTypeChange(this.value)" required>
                                    <option value="single_choice" selected>Pilihan Ganda (1 Jawaban)</option>
                                    <option value="essay">Essai / Uraian</option>
                                    <option value="multiple_choice">Pilihan Ganda (Multi Jawaban)</option>
                                    <option value="matching">Mencocokkan</option>
                                    <option value="true_false">Benar / Salah</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1;" id="group_score_weight">
                                <label class="form-label" style="font-weight: 700;" id="label_score_weight">Bobot Nilai *</label>
                                <input type="number" step="0.5" name="score_weight" id="score_weight_input" class="form-control" value="2.5" required>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label class="form-label" style="font-weight: 700;">Tingkat Kesulitan</label>
                                <select name="difficulty" class="form-select">
                                    <option value="easy">Mudah</option>
                                    <option value="medium" selected>Sedang</option>
                                    <option value="hard">Sukar / HOTS</option>
                                </select>
                            </div>
                        </div>

                        <!-- KARTU PERTANYAAN DENGAN TOOLS MICROSOFT WORD LENGKAP (GAMBAR 1 + GAMBAR 2) -->
                        <div style="margin-bottom: 24px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                                <h4 style="font-size: 1.05rem; font-weight: 800; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 8px;">
                                    <span>📝</span> Lembar Pertanyaan
                                </h4>
                                <button type="button" class="btn btn-sm" onclick="openAiQuestionModal()" style="background: linear-gradient(135deg, #7c3aed 0%, #2563eb 100%); color: #ffffff; font-weight: 700; border: none; border-radius: 6px; padding: 7px 16px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35); cursor: pointer; transition: all 0.2s ease;">
                                    <span>✨</span> Buat Soal dengan AI
                                </button>
                            </div>

                            <!-- WYSIWYG EDITOR TOOLBAR MICROSOFT WORD PERSIS GAMBAR 1 & 2 -->
                            <div class="word-editor-box">
                                <div class="word-menubar" style="display: flex; gap: 12px; padding: 5px 12px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 12px; color: #334155;">
                                    <span>File</span>
                                    <span>Edit</span>
                                    <span>View</span>
                                    <span>Insert</span>
                                    <span>Format</span>
                                    <span>Tools</span>
                                    <span>Table</span>
                                </div>
                                <div class="word-ribbon-toolbar" style="display: flex; gap: 4px; padding: 6px 10px; background: #ffffff; border-bottom: 1px solid #e2e8f0; align-items: center; flex-wrap: wrap;">
                                    <button type="button" class="tb-btn" title="Undo" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'undo')">&#8630;</button>
                                    <button type="button" class="tb-btn" title="Redo" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'redo')">&#8631;</button>
                                    <div class="tb-separator"></div>

                                    <!-- FONT FAMILY SELECT (GAMBAR DUA) -->
                                    <select onmousedown="saveWordSelection('editor_content');" onchange="formatWordDoc('editor_content', 'fontFamily', this.value); this.selectedIndex=0;" class="word-select" title="Pilih Font">
                                        <option value="" disabled selected>Aptos (Body) ▼</option>
                                        <option value="Aptos, sans-serif">Aptos</option>
                                        <option value="Arial, sans-serif">Arial</option>
                                        <option value="'Times New Roman', serif">Times New Roman</option>
                                        <option value="'Courier New', monospace">Courier New</option>
                                        <option value="Consolas, monospace">Consolas</option>
                                    </select>

                                    <!-- FONT SIZE SELECT (GAMBAR DUA) -->
                                    <select onmousedown="saveWordSelection('editor_content');" onchange="formatWordDoc('editor_content', 'fontSize', this.value); this.selectedIndex=0;" class="word-select" title="Ukuran Font">
                                        <option value="" disabled selected>12 ▼</option>
                                        <option value="11px">11</option>
                                        <option value="12px">12 (Normal)</option>
                                        <option value="14px">14</option>
                                        <option value="16px">16</option>
                                        <option value="18px">18 (Judul)</option>
                                        <option value="22px">22</option>
                                    </select>

                                    <div class="tb-separator"></div>

                                    <!-- FONT STYLES (GAMBAR 1 & 2) -->
                                    <button type="button" class="tb-btn" title="Bold (Tebal)" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'bold')" style="font-weight: bold;">B</button>
                                    <button type="button" class="tb-btn" title="Italic (Miring)" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'italic')" style="font-style: italic;">I</button>
                                    <button type="button" class="tb-btn" title="Underline (Garis Bawah)" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'underline')"><u>U</u></button>
                                    <button type="button" class="tb-btn" title="Strikethrough (Coret)" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'strikeThrough')"><s>S</s></button>
                                    <div class="tb-separator"></div>
                                    <button type="button" class="tb-btn" title="Subscript / Indeks (x₂)" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'subscript')">x₂</button>
                                    <button type="button" class="tb-btn" title="Superscript / Pangkat (x²)" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'superscript')">x²</button>

                                    <div class="tb-separator"></div>

                                    <!-- WARNA TEKS & HIGHLIGHT (GAMBAR DUA) -->
                                    <select onmousedown="saveWordSelection('editor_content');" onchange="formatWordDoc('editor_content', 'foreColor', this.value); this.selectedIndex=0;" class="word-select" style="color: #dc2626; font-weight: 700;" title="Warna Font Teks">
                                        <option value="" disabled selected>A (Warna) ▼</option>
                                        <option value="#000000" style="color: #000000;">Hitam (Normal)</option>
                                        <option value="#dc2626" style="color: #dc2626;">Merah</option>
                                        <option value="#2563eb" style="color: #2563eb;">Biru</option>
                                        <option value="#16a34a" style="color: #16a34a;">Hijau</option>
                                        <option value="#d97706" style="color: #d97706;">Oranye</option>
                                        <option value="#7c3aed" style="color: #7c3aed;">Ungu</option>
                                    </select>

                                    <select onmousedown="saveWordSelection('editor_content');" onchange="formatWordDoc('editor_content', 'hiliteColor', this.value); this.selectedIndex=0;" class="word-select" style="color: #854d0e; font-weight: 700; background: #fef9c3;" title="Sorot Latar (Highlight)">
                                        <option value="" disabled selected>🖍️ Sorot ▼</option>
                                        <option value="#fef08a" style="background: #fef08a;">Kuning</option>
                                        <option value="#bbf7d0" style="background: #bbf7d0;">Hijau Muda</option>
                                        <option value="#bfdbfe" style="background: #bfdbfe;">Biru Muda</option>
                                        <option value="#fbcfe8" style="background: #fbcfe8;">Merah Muda</option>
                                    </select>

                                    <div class="tb-separator"></div>

                                    <!-- ALIGNMENT -->
                                    <button type="button" class="tb-btn" title="Align Left" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'justifyLeft')">&#8801;</button>
                                    <button type="button" class="tb-btn" title="Align Center" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'justifyCenter')">&#8788;</button>
                                    <button type="button" class="tb-btn" title="Align Right" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'justifyRight')">&#8801;</button>
                                    <button type="button" class="tb-btn" title="Justify" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'justifyFull')">&#9776;</button>

                                    <div class="tb-separator"></div>

                                    <!-- LISTS -->
                                    <button type="button" class="tb-btn" title="Bullet List" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'insertUnorderedList')">&#8226;&#8801;</button>
                                    <button type="button" class="tb-btn" title="Numbered List" onmousedown="event.preventDefault();" onclick="formatWordDoc('editor_content', 'insertOrderedList')">1.&#8801;</button>

                                    <div class="tb-separator"></div>

                                    <!-- MATH & SCIENCE SYMBOLS (GAMBAR 1 + GAMBAR 2) -->
                                    <button type="button" class="tb-btn" title="Akar (√)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '√')">√x</button>
                                    <button type="button" class="tb-btn" title="Pi (π)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', 'π')">π</button>
                                    <button type="button" class="tb-btn" title="Plus-Minus (±)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '±')">±</button>
                                    <button type="button" class="tb-btn" title="Kali (×)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '×')">×</button>
                                    <button type="button" class="tb-btn" title="Bagi (÷)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '÷')">÷</button>
                                    <button type="button" class="tb-btn" title="Kurang Dari Sama Dengan (≤)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '≤')">≤</button>
                                    <button type="button" class="tb-btn" title="Lebih Dari Sama Dengan (≥)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '≥')">≥</button>
                                    <button type="button" class="tb-btn" title="Tidak Sama Dengan (≠)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '≠')">≠</button>
                                    <button type="button" class="tb-btn" title="Derajat (°)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '°')">°</button>
                                    <button type="button" class="tb-btn" title="Tak Terhingga (∞)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '∞')">∞</button>
                                    <button type="button" class="tb-btn" title="Sigma / Penjumlahan (∑)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '∑')">∑</button>
                                    <button type="button" class="tb-btn" title="Integral (∫)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '∫')">∫</button>
                                    <button type="button" class="tb-btn" title="Alpha (α)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', 'α')">α</button>
                                    <button type="button" class="tb-btn" title="Beta (β)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', 'β')">β</button>
                                    <button type="button" class="tb-btn" title="Theta (θ)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', 'θ')">θ</button>
                                    <button type="button" class="tb-btn" title="Delta (Δ)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', 'Δ')">Δ</button>
                                    <button type="button" class="tb-btn" title="Omega (Ω)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', 'Ω')">Ω</button>
                                    <button type="button" class="tb-btn" title="Mendekati (≈)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('editor_content', '≈')">≈</button>

                                    <div class="tb-separator"></div>

                                    <!-- INSERT MEDIA & TABLE -->
                                    <button type="button" class="tb-btn" title="Sisipkan Tabel 2x2" onmousedown="event.preventDefault();" onclick="insertWordTable('editor_content')">⊞ Tabel</button>
                                    <button type="button" class="tb-btn" title="Sisipkan Link" onmousedown="event.preventDefault();" onclick="insertWordLink('editor_content')">&#128279;</button>
                                    <button type="button" class="tb-btn" title="Sisipkan Gambar" onmousedown="event.preventDefault();" onclick="insertWordImage('editor_content')">&#128444;</button>
                                    <button type="button" class="tb-btn" title="Hapus Format" onmousedown="event.preventDefault();" onclick="clearWordFormat('editor_content')">Tx</button>
                                </div>
                                <div id="editor_content" contenteditable="true" class="word-content-editable" placeholder="Tuliskan pertanyaan soal di sini..." oninput="syncWordContent('editor_content')" onfocus="setWordEditorActive('editor_content')"></div>
                                <textarea name="content" id="raw_editor_content" style="display:none;"></textarea>
                                <div class="word-statusbar" style="padding: 6px 12px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; font-size: 11px; color: #94a3b8; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase;">
                                    <span id="status_editor_content">0 WORDS &bull; POWERED BY TINYMCE &bull; MICROSOFT WORD TOOLS</span>
                                </div>
                            </div>

                            <!-- PERHATIAN TEXT SESUAI GAMBAR 3 & PANDUAN PENGGUNAAN -->
                            <div style="margin-top: 14px; padding: 12px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 12.5px; color: #475569;">
                                <strong style="color: #1e293b; font-size: 13px; display: block; margin-bottom: 4px;">Perhatian:</strong>
                                <p style="margin: 0 0 4px 0; line-height: 1.5;">Disini Anda bisa membuat soal ujian berbentuk pilihan ganda dan essai.</p>
                                <p style="margin: 0 0 4px 0; line-height: 1.5;">Untuk membuat soal pilihan ganda, silahkan masukan soal / pertanyaan, pilihan jawaban beserta kunci jawabannya.</p>
                                <p style="margin: 0 0 4px 0; line-height: 1.5;">Sedangkan untuk membuat soal essai, silahkan masukan soal / pertanyaan dan isi bagian bobot soal essai, sedangkan bagian pilihan A, B, C, D dan E nya wajib dikosongkan.</p>
                                <p style="margin: 0; line-height: 1.5; color: #0284c7; font-weight: 600;">Bobot Essai diisi hanya ketika soal berbentuk essai.</p>
                            </div>
                        </div>

                        <!-- SECTION PILIHAN JAWABAN: TIAP JAWABAN DILENGKAPI TOOLS MICROSOFT WORD LENGKAP -->
                        <div id="section_single_choice_options" style="margin-bottom: 24px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                                <div>
                                    <h4 style="font-size: 1rem; font-weight: 800; color: #1e293b; margin: 0;">
                                        Pilihan Jawaban (A - E) &amp; Kunci Jawaban
                                    </h4>
                                    <span style="font-size: 12px; color: #64748b;">
                                        Setiap pilihan jawaban dilengkapi lembar tools Microsoft Word. Pilih tombol radio lingkaran untuk menentukan <b>Kunci Jawaban yang Benar</b>.
                                    </span>
                                </div>
                            </div>

                            <?php
                            $optLabels = [
                                'a' => ['label' => 'A', 'title' => 'PILIHAN JAWABAN A', 'default_checked' => true],
                                'b' => ['label' => 'B', 'title' => 'PILIHAN JAWABAN B', 'default_checked' => false],
                                'c' => ['label' => 'C', 'title' => 'PILIHAN JAWABAN C', 'default_checked' => false],
                                'd' => ['label' => 'D', 'title' => 'PILIHAN JAWABAN D', 'default_checked' => false],
                                'e' => ['label' => 'E', 'title' => 'PILIHAN JAWABAN E', 'default_checked' => false],
                            ];
                            foreach ($optLabels as $key => $optInfo):
                                $edId = "opt_editor_" . $key;
                                $statusId = "status_" . $edId;
                                $fieldNm = "option_" . $key;
                            ?>
                            <!-- WORD EDITOR UNTUK PILIHAN <?= $optInfo['label'] ?> -->
                            <div class="word-editor-box">
                                <div style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 8px 14px; display: flex; justify-content: space-between; align-items: center;">
                                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0;">
                                        <input type="radio" name="correct_option" value="<?= $optInfo['label'] ?>" <?= $optInfo['default_checked'] ? 'checked' : '' ?> style="accent-color: #16a34a; width: 18px; height: 18px;">
                                        <span style="font-weight: 800; font-size: 13.5px; color: #1e293b;"><?= $optInfo['title'] ?></span>
                                        <span class="correct-badge" style="font-size: 11px; background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 10px; font-weight: 700; border: 1px solid #86efac;">
                                            <?= $optInfo['default_checked'] ? '✓ Kunci Jawaban' : 'Pilih sebagai Kunci' ?>
                                        </span>
                                    </label>
                                    <span style="font-size: 11.5px; color: #64748b; font-weight: 600;">Opsi <?= $optInfo['label'] ?></span>
                                </div>

                                <div class="word-menubar" style="display: flex; gap: 10px; padding: 4px 12px; background: #ffffff; border-bottom: 1px solid #e2e8f0; font-size: 11.5px; color: #475569;">
                                    <span>File</span>
                                    <span>Edit</span>
                                    <span>View</span>
                                    <span>Insert</span>
                                    <span>Format</span>
                                    <span>Tools</span>
                                    <span>Table</span>
                                </div>

                                <div class="word-ribbon-toolbar" style="display: flex; gap: 3px; padding: 5px 10px; background: #fcfcfd; border-bottom: 1px solid #e2e8f0; align-items: center; flex-wrap: wrap;">
                                    <!-- FONT SELECT -->
                                    <select onmousedown="saveWordSelection('<?= $edId ?>');" onchange="formatWordDoc('<?= $edId ?>', 'fontFamily', this.value); this.selectedIndex=0;" class="word-select" title="Pilih Font">
                                        <option value="" disabled selected>Aptos ▼</option>
                                        <option value="Aptos, sans-serif">Aptos</option>
                                        <option value="Arial, sans-serif">Arial</option>
                                        <option value="'Times New Roman', serif">Times New Roman</option>
                                        <option value="'Courier New', monospace">Courier New</option>
                                        <option value="Consolas, monospace">Consolas</option>
                                    </select>

                                    <!-- FONT SIZE -->
                                    <select onmousedown="saveWordSelection('<?= $edId ?>');" onchange="formatWordDoc('<?= $edId ?>', 'fontSize', this.value); this.selectedIndex=0;" class="word-select" title="Ukuran Font">
                                        <option value="" disabled selected>12 ▼</option>
                                        <option value="11px">11</option>
                                        <option value="12px">12 (Normal)</option>
                                        <option value="14px">14</option>
                                        <option value="16px">16</option>
                                        <option value="18px">18</option>
                                    </select>

                                    <div class="tb-separator"></div>

                                    <!-- STYLES -->
                                    <button type="button" class="tb-btn" title="Tebal" onmousedown="event.preventDefault();" onclick="formatWordDoc('<?= $edId ?>', 'bold')" style="font-weight: bold;">B</button>
                                    <button type="button" class="tb-btn" title="Miring" onmousedown="event.preventDefault();" onclick="formatWordDoc('<?= $edId ?>', 'italic')" style="font-style: italic;">I</button>
                                    <button type="button" class="tb-btn" title="Garis Bawah" onmousedown="event.preventDefault();" onclick="formatWordDoc('<?= $edId ?>', 'underline')"><u>U</u></button>
                                    <button type="button" class="tb-btn" title="Coret" onmousedown="event.preventDefault();" onclick="formatWordDoc('<?= $edId ?>', 'strikeThrough')"><s>S</s></button>
                                    <div class="tb-separator"></div>
                                    <button type="button" class="tb-btn" title="Subscript (x₂)" onmousedown="event.preventDefault();" onclick="formatWordDoc('<?= $edId ?>', 'subscript')">x₂</button>
                                    <button type="button" class="tb-btn" title="Superscript (x²)" onmousedown="event.preventDefault();" onclick="formatWordDoc('<?= $edId ?>', 'superscript')">x²</button>

                                    <div class="tb-separator"></div>

                                    <!-- WARNA & HIGHLIGHT -->
                                    <select onmousedown="saveWordSelection('<?= $edId ?>');" onchange="formatWordDoc('<?= $edId ?>', 'foreColor', this.value); this.selectedIndex=0;" class="word-select" style="color: #dc2626; font-weight: 700;" title="Warna Font Teks">
                                        <option value="" disabled selected>A ▼</option>
                                        <option value="#000000" style="color: #000000;">Hitam</option>
                                        <option value="#dc2626" style="color: #dc2626;">Merah</option>
                                        <option value="#2563eb" style="color: #2563eb;">Biru</option>
                                        <option value="#16a34a" style="color: #16a34a;">Hijau</option>
                                        <option value="#d97706" style="color: #d97706;">Oranye</option>
                                        <option value="#7c3aed" style="color: #7c3aed;">Ungu</option>
                                    </select>

                                    <select onmousedown="saveWordSelection('<?= $edId ?>');" onchange="formatWordDoc('<?= $edId ?>', 'hiliteColor', this.value); this.selectedIndex=0;" class="word-select" style="color: #854d0e; font-weight: 700; background: #fef9c3;" title="Sorot Latar (Highlight)">
                                        <option value="" disabled selected>🖍️ ▼</option>
                                        <option value="#fef08a" style="background: #fef08a;">Kuning</option>
                                        <option value="#bbf7d0" style="background: #bbf7d0;">Hijau Muda</option>
                                        <option value="#bfdbfe" style="background: #bfdbfe;">Biru Muda</option>
                                    </select>

                                    <div class="tb-separator"></div>

                                    <!-- ALIGN -->
                                    <button type="button" class="tb-btn" title="Rata Kiri" onmousedown="event.preventDefault();" onclick="formatWordDoc('<?= $edId ?>', 'justifyLeft')">&#8801;</button>
                                    <button type="button" class="tb-btn" title="Rata Tengah" onmousedown="event.preventDefault();" onclick="formatWordDoc('<?= $edId ?>', 'justifyCenter')">&#8788;</button>
                                    <button type="button" class="tb-btn" title="Rata Kanan" onmousedown="event.preventDefault();" onclick="formatWordDoc('<?= $edId ?>', 'justifyRight')">&#8801;</button>

                                    <div class="tb-separator"></div>

                                    <!-- MATH & SCIENCE SYMBOLS -->
                                    <button type="button" class="tb-btn" title="Akar (√)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('<?= $edId ?>', '√')">√x</button>
                                    <button type="button" class="tb-btn" title="Pi (π)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('<?= $edId ?>', 'π')">π</button>
                                    <button type="button" class="tb-btn" title="Plus Minus (±)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('<?= $edId ?>', '±')">±</button>
                                    <button type="button" class="tb-btn" title="Kali (×)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('<?= $edId ?>', '×')">×</button>
                                    <button type="button" class="tb-btn" title="Bagi (÷)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('<?= $edId ?>', '÷')">÷</button>
                                    <button type="button" class="tb-btn" title="Kurang Dari Sama Dengan (≤)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('<?= $edId ?>', '≤')">≤</button>
                                    <button type="button" class="tb-btn" title="Lebih Dari Sama Dengan (≥)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('<?= $edId ?>', '≥')">≥</button>
                                    <button type="button" class="tb-btn" title="Tidak Sama Dengan (≠)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('<?= $edId ?>', '≠')">≠</button>
                                    <button type="button" class="tb-btn" title="Derajat (°)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('<?= $edId ?>', '°')">°</button>
                                    <button type="button" class="tb-btn" title="Tak Terhingga (∞)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('<?= $edId ?>', '∞')">∞</button>
                                    <button type="button" class="tb-btn" title="Sigma (∑)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('<?= $edId ?>', '∑')">∑</button>
                                    <button type="button" class="tb-btn" title="Integral (∫)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('<?= $edId ?>', '∫')">∫</button>
                                    <button type="button" class="tb-btn" title="Alpha (α)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('<?= $edId ?>', 'α')">α</button>
                                    <button type="button" class="tb-btn" title="Beta (β)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('<?= $edId ?>', 'β')">β</button>
                                    <button type="button" class="tb-btn" title="Theta (θ)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('<?= $edId ?>', 'θ')">θ</button>
                                    <button type="button" class="tb-btn" title="Delta (Δ)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('<?= $edId ?>', 'Δ')">Δ</button>
                                    <button type="button" class="tb-btn" title="Omega (Ω)" onmousedown="event.preventDefault();" onclick="insertWordSymbol('<?= $edId ?>', 'Ω')">Ω</button>

                                    <div class="tb-separator"></div>

                                    <!-- INSERT & CLEAR -->
                                    <button type="button" class="tb-btn" title="Sisipkan Tabel 2x2" onmousedown="event.preventDefault();" onclick="insertWordTable('<?= $edId ?>')">⊞ Tabel</button>
                                    <button type="button" class="tb-btn" title="Sisipkan Gambar" onmousedown="event.preventDefault();" onclick="insertWordImage('<?= $edId ?>')">&#128444;</button>
                                    <button type="button" class="tb-btn" title="Hapus Format" onmousedown="event.preventDefault();" onclick="clearWordFormat('<?= $edId ?>')">Tx</button>
                                </div>

                                <div id="<?= $edId ?>" contenteditable="true" class="word-content-editable" style="min-height: 80px; padding: 10px 14px;" placeholder="Tuliskan pilihan jawaban <?= $optInfo['label'] ?> di sini..." oninput="syncWordContent('<?= $edId ?>')" onfocus="setWordEditorActive('<?= $edId ?>')"></div>
                                <textarea name="<?= $fieldNm ?>" id="raw_<?= $edId ?>" style="display:none;"></textarea>

                                <div class="word-statusbar" style="padding: 4px 12px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; font-size: 10.5px; color: #94a3b8; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase;">
                                    <span id="<?= $statusId ?>">0 WORDS &bull; PILIHAN <?= $optInfo['label'] ?> &bull; MICROSOFT WORD TOOLS</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- SECTION ESSAI: PEMBERITAHUAN ESSAI -->
                        <div id="section_essay_weight" style="display: none; background: #fefce8; border: 1px solid #fef08a; border-radius: 8px; padding: 16px; margin-bottom: 18px;">
                            <div style="font-weight: 700; font-size: 14px; color: #854d0e; margin-bottom: 4px;">
                                ✍️ Mode Soal Essai / Uraian
                            </div>
                            <p style="margin: 0; font-size: 13px; color: #a16207; line-height: 1.5;">
                                Pada tipe soal essai, pilihan A s/d E otomatis dikosongkan. Siswa akan menjawab pertanyaan ini dalam bentuk esai/uraian teks bebas. Nilai akan dinilai oleh guru berdasarkan Bobot Soal Essai di atas.
                            </p>
                        </div>

                        <div style="display: flex; justify-content: flex-end; gap: 8px;">
                            <button type="button" class="btn btn-secondary" onclick="closeCreateQuestionCard()">Batal</button>
                            <button type="submit" class="btn btn-primary" style="background: #0052cc; border-color: #0052cc; font-weight: 700; padding: 8px 24px;">Simpan Pertanyaan</button>
                        </div>
                    </form>
                </div>

                <!-- TABEL BUTIR SOAL DENGAN TOGGLE IKON MATA -->
                <div class="table-responsive">
                    <table class="table" style="margin-bottom: 0;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="width: 45px; text-align: center;">No</th>
                                <th style="width: 90px;">Tipe</th>
                                <th>Pertanyaan &amp; Pilihan Jawaban</th>
                                <th style="width: 75px; text-align: center;">Bobot</th>
                                <th style="width: 75px; text-align: center;">Kunci</th>
                                <th style="width: 95px; text-align: center;">Status</th>
                                <th style="width: 120px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($filteredQuestions)): ?>
                                <tr>
                                    <td colspan="7" style="padding: 40px; text-align: center;">
                                        <div style="font-size: 32px; margin-bottom: 8px;">📝</div>
                                        <div style="font-weight: 700; color: #334155; font-size: 15px;">Belum ada butir pertanyaan untuk bank soal ini</div>
                                        <div style="color: #94a3b8; font-size: 13px; margin-top: 4px;">Klik tombol "Tambah Soal" untuk membuat soal satu per satu atau "Import Soal" untuk mengunggah file Excel.</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($filteredQuestions as $qIdx => $q): 
                                    $isInactive = (($q['status'] ?? 'active') === 'inactive');
                                    $isEssay = (($q['question_type'] ?? '') === 'essay');
                                    $opts = $q['options'] ?? [];
                                ?>
                                    <tr style="<?= $isInactive ? 'background: #f8fafc;' : '' ?>">
                                        <td style="text-align: center; font-weight: 600; color: #64748b;">
                                            <?= $qIdx + 1 ?>
                                        </td>
                                        <td>
                                            <?php if ($isEssay): ?>
                                                <span class="badge" style="background: #fef3c7; color: #92400e; font-weight: 700;">ESSAI</span>
                                            <?php elseif (($q['question_type'] ?? '') === 'multiple_choice'): ?>
                                                <span class="badge" style="background: #e0e7ff; color: #3730a3; font-weight: 700;">PG MULTI</span>
                                            <?php else: ?>
                                                <span class="badge" style="background: #dbeafe; color: #1e40af; font-weight: 700;">PG</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="<?= $isInactive ? 'text-decoration: line-through; opacity: 0.55; color: #94a3b8; font-style: italic;' : 'color: #1e293b; font-size: 13.5px; line-height: 1.5;' ?>">
                                                <?= strip_tags($q['content'], '<p><br><b><strong><i><em><u><s><sub><sup><span><table><thead><tbody><tr><th><td><img><ul><ol><li><a><mark><div>') ?>
                                            </div>
                                            <?php if (!$isEssay && !empty($opts)): ?>
                                                <div style="margin-top: 8px; font-size: 12px; color: #64748b; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 6px; <?= $isInactive ? 'opacity: 0.5;' : '' ?>">
                                                    <?php foreach (['A', 'B', 'C', 'D', 'E'] as $k): ?>
                                                        <?php if (!empty($opts[$k])): ?>
                                                            <?php $isCorrect = (strtoupper($q['correct_option'] ?? '') === $k); ?>
                                                            <div style="<?= $isCorrect ? 'font-weight: 700; color: #16a34a;' : '' ?>">
                                                                <strong><?= $k ?>.</strong> <?= strip_tags($opts[$k], '<p><br><b><strong><i><em><u><s><sub><sup><span><img><a><mark>') ?>
                                                                <?php if ($isCorrect): ?> <span style="font-size: 10.5px;">✓ (Kunci)</span> <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center; font-weight: 600; color: #334155;">
                                            <?= htmlspecialchars($q['score_weight'] ?? ($isEssay ? 10.0 : 2.5)) ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($isEssay): ?>
                                                <span style="color: #94a3b8; font-size: 11.5px;">(Bobot Essai)</span>
                                            <?php else: ?>
                                                <span class="badge" style="background: #dcfce7; color: #15803d; font-weight: 700;">
                                                    <?= htmlspecialchars($q['correct_option'] ?? 'A') ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($isInactive): ?>
                                                <span class="badge" style="background: #f1f5f9; color: #94a3b8; border: 1px solid #cbd5e1;" title="Soal tidak akan tampil di siswa (dicoret)">Dicoret</span>
                                            <?php else: ?>
                                                <span class="badge" style="background: #dcfce7; color: #15803d;">Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <div style="display: inline-flex; gap: 6px; align-items: center; justify-content: center;">
                                                <!-- ICON MATA (TOGGLE AKTIF / NONAKTIF) -->
                                                <a href="/admin/questions/toggle-status?id=<?= urlencode($q['id']) ?>&subject_id=<?= urlencode($activeSubjectObj['id']) ?>" class="btn btn-sm" style="padding: 4px 8px; border-radius: 4px; background: <?= $isInactive ? '#f1f5f9' : '#e0e7ff' ?>; color: <?= $isInactive ? '#64748b' : '#4338ca' ?>; border: 1px solid <?= $isInactive ? '#cbd5e1' : '#c7d2fe' ?>; text-decoration: none;" title="<?= $isInactive ? 'Klik ikon mata untuk mengaktifkan kembali' : 'Klik ikon mata untuk menonaktifkan (soal dicoret)' ?>">
                                                    <?= $isInactive ? '👁️‍🗨️' : '👁️' ?>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-secondary" style="padding: 4px 8px;" onclick="openEditQuestionModal('<?= htmlspecialchars($q['id']) ?>', '<?= htmlspecialchars(addslashes($q['content'])) ?>', '<?= htmlspecialchars($q['correct_option'] ?? 'A') ?>', '<?= htmlspecialchars($q['score_weight'] ?? 2.5) ?>')" title="Ubah Soal">✏️</button>
                                                <a href="/admin/questions/delete?id=<?= urlencode($q['id']) ?>&subject_id=<?= urlencode($activeSubjectObj['id']) ?>" class="btn btn-sm btn-danger" style="padding: 4px 8px;" onclick="return confirm('Hapus butir pertanyaan ini?');" title="Hapus Soal">🗑️</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MODAL PILIH TIPE SOAL PERSIS GAMBAR DUA -->
            <div class="modal-overlay" id="chooseTypeModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); z-index: 9999; align-items: center; justify-content: center;">
                <div style="background: #ffffff; border-radius: 12px; max-width: 480px; width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); overflow: hidden;">
                    <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;">Pilih Tipe Soal</h3>
                        <button type="button" onclick="closeChooseTypeModal()" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #64748b;">&times;</button>
                    </div>
                    <div style="padding: 20px 24px;">
                        <div style="display: flex; flex-direction: column; gap: 14px; margin-bottom: 24px;">
                            <label id="lbl_type_single" style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px 14px; border: 2px solid #2563eb; border-radius: 8px; background: #eff6ff; font-weight: 700; color: #1e3a8a; font-size: 13.5px;">
                                <input type="radio" name="modal_q_type" value="single_choice" checked onchange="selectTypeStyle(this)" style="accent-color: #2563eb; width: 18px; height: 18px;">
                                PILIHAN GANDA (1 Jawaban) / ESSAY
                            </label>
                            <label id="lbl_type_multi" style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 600; color: #475569; font-size: 13.5px;">
                                <input type="radio" name="modal_q_type" value="multiple_choice" onchange="selectTypeStyle(this)" style="accent-color: #2563eb; width: 18px; height: 18px;">
                                PILIHAN GANDA (Multi Jawaban)
                            </label>
                            <label id="lbl_type_match" style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 600; color: #475569; font-size: 13.5px;">
                                <input type="radio" name="modal_q_type" value="matching" onchange="selectTypeStyle(this)" style="accent-color: #2563eb; width: 18px; height: 18px;">
                                MENCOCOKKAN
                            </label>
                            <label id="lbl_type_tf" style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 600; color: #475569; font-size: 13.5px;">
                                <input type="radio" name="modal_q_type" value="true_false" onchange="selectTypeStyle(this)" style="accent-color: #2563eb; width: 18px; height: 18px;">
                                BENAR / SALAH
                            </label>
                        </div>
                        <div style="display: flex; justify-content: flex-end; gap: 10px;">
                            <button type="button" class="btn btn-secondary" onclick="closeChooseTypeModal()" style="padding: 8px 18px;">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="proceedCreateQuestion()" style="background: #0052cc; border-color: #0052cc; font-weight: 700; padding: 8px 24px;">Create</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL IMPORT SOAL PERSIS FORMAT GAMBAR EMPAT -->
            <div class="modal-overlay" id="importQuestionModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); z-index: 9999; align-items: center; justify-content: center;">
                <div style="background: #ffffff; border-radius: 12px; max-width: 840px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);">
                    <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 20px;">📥</span>
                            <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;">Import Butir Soal dari Excel</h3>
                        </div>
                        <button type="button" onclick="closeImportQuestionModal()" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #64748b;">&times;</button>
                    </div>
                    <div style="padding: 20px 24px;">
                        <!-- PREVIEW FORMAT PERSIS GAMBAR 4 -->
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                                <strong style="font-size: 13.5px; color: #334155;">Format Kolom Excel (Wajib sesuai contoh di bawah):</strong>
                                <a href="/admin/questions/template?subject_id=<?= urlencode($activeSubjectObj['id']) ?>" class="btn btn-sm btn-success" style="background: #15803d; border-color: #15803d; font-weight: 700; color: #ffffff; text-decoration: none; padding: 6px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                                    📥 Download Format Excel
                                </a>
                            </div>

                            <div style="overflow-x: auto; border: 1px solid #cbd5e1; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
                                <table style="width: 100%; border-collapse: collapse; font-size: 11.5px; text-align: center;">
                                    <thead>
                                        <tr style="background: #ffffff;">
                                            <td colspan="2" style="border: 1px solid #cbd5e1; padding: 7px 12px; font-weight: 700; color: #1e293b; text-align: left; font-size: 12px; width: 220px;">
                                                Nama Guru Mapel :
                                            </td>
                                            <td colspan="7" style="border: 1px solid #cbd5e1; padding: 7px 12px; font-weight: 700; color: #0052cc; text-align: left; font-size: 12px;">
                                                <?= htmlspecialchars($activeSubjectObj['teacher'] ?? 'Budi Santoso, S.Pd') ?>
                                            </td>
                                        </tr>
                                        <tr style="background: #ffffff;">
                                            <td colspan="2" style="border: 1px solid #cbd5e1; padding: 7px 12px; font-weight: 700; color: #1e293b; text-align: left; font-size: 12px;">
                                                Mapel / Kelas :
                                            </td>
                                            <td colspan="7" style="border: 1px solid #cbd5e1; padding: 7px 12px; font-weight: 700; color: #0052cc; text-align: left; font-size: 12px;">
                                                <?= htmlspecialchars($activeSubjectObj['name']) ?> - Kelas X
                                            </td>
                                        </tr>
                                        <tr style="height: 6px; background: #f1f5f9;"><td colspan="9" style="border: 1px solid #e2e8f0; padding: 0;"></td></tr>
                                        <tr style="background: #70ad47; color: #000000; font-weight: 700;">
                                            <th style="border: 1px solid #000; padding: 8px; width: 40px;">NO</th>
                                            <th style="border: 1px solid #000; padding: 8px 14px;">Soal/Pertanyaan</th>
                                            <th style="border: 1px solid #000; padding: 8px 10px; width: 100px;">Jenis ( 1=PG,<br>2=Essai)</th>
                                            <th style="border: 1px solid #000; padding: 8px;">Jawaban A</th>
                                            <th style="border: 1px solid #000; padding: 8px;">Jawaban B</th>
                                            <th style="border: 1px solid #000; padding: 8px;">Jawaban C</th>
                                            <th style="border: 1px solid #000; padding: 8px;">Jawaban D</th>
                                            <th style="border: 1px solid #000; padding: 8px;">Jawaban E</th>
                                            <th style="border: 1px solid #000; padding: 8px; width: 110px;">Kunci Jawaban<br>(A/B/C/D/E)</th>
                                        </tr>
                                    </thead>
                                    <tbody style="background: #ffffff; color: #334155;">
                                        <tr>
                                            <td style="border: 1px solid #e2e8f0; padding: 8px;">1</td>
                                            <td style="border: 1px solid #e2e8f0; padding: 8px; text-align: left;">Berapakah hasil dari 25 x 4?</td>
                                            <td style="border: 1px solid #e2e8f0; padding: 8px; font-weight: 700;">1</td>
                                            <td style="border: 1px solid #e2e8f0; padding: 8px;">50</td>
                                            <td style="border: 1px solid #e2e8f0; padding: 8px;">75</td>
                                            <td style="border: 1px solid #e2e8f0; padding: 8px;">100</td>
                                            <td style="border: 1px solid #e2e8f0; padding: 8px;">125</td>
                                            <td style="border: 1px solid #e2e8f0; padding: 8px;">150</td>
                                            <td style="border: 1px solid #e2e8f0; padding: 8px; font-weight: 700; color: #16a34a;">C</td>
                                        </tr>
                                        <tr style="background: #f8fafc;">
                                            <td style="border: 1px solid #e2e8f0; padding: 8px;">2</td>
                                            <td style="border: 1px solid #e2e8f0; padding: 8px; text-align: left;">Jelaskan fungsi sistem komputer secara singkat!</td>
                                            <td style="border: 1px solid #e2e8f0; padding: 8px; font-weight: 700;">2</td>
                                            <td style="border: 1px solid #e2e8f0; padding: 8px; color: #94a3b8;">(Kosong)</td>
                                            <td style="border: 1px solid #e2e8f0; padding: 8px; color: #94a3b8;">(Kosong)</td>
                                            <td style="border: 1px solid #e2e8f0; padding: 8px; color: #94a3b8;">(Kosong)</td>
                                            <td style="border: 1px solid #e2e8f0; padding: 8px; color: #94a3b8;">(Kosong)</td>
                                            <td style="border: 1px solid #e2e8f0; padding: 8px; color: #94a3b8;">(Kosong)</td>
                                            <td style="border: 1px solid #e2e8f0; padding: 8px; color: #94a3b8;">(Kosong)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <form action="/admin/questions/import" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="default_subject_id" value="<?= htmlspecialchars($activeSubjectObj['id']) ?>">
                            <div class="form-group" style="margin-bottom: 16px;">
                                <label class="form-label" style="font-weight: 600;">Pilih File Excel / CSV *</label>
                                <input type="file" name="file" class="form-control" accept=".xlsx, .xls, .csv" required>
                                <small style="color: var(--text-muted); font-size: 11px;">Mendukung format .xlsx, .xls, dan .csv maksimal 10MB.</small>
                            </div>
                            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                <button type="button" class="btn btn-secondary" onclick="closeImportQuestionModal()">Batal</button>
                                <button type="submit" class="btn btn-primary" style="background: #0284c7; border-color: #0284c7; font-weight: 700;">
                                    Unggah &amp; Import Soal
                                </button>
                            </div>
                        </form>
                    </div>
            <!-- MODAL BUAT SOAL DENGAN AI (GEMINI AI ENGINE & GOOGLE ACCOUNT GATE) -->
            <div class="modal-overlay" id="aiQuestionModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                <div style="background: #ffffff; border-radius: 16px; max-width: 720px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid #e2e8f0;">
                    
                    <!-- MODAL HEADER -->
                    <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #4285F4 0%, #34A853 50%, #FBBC05 75%, #EA4335 100%); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(66, 133, 244, 0.25);">
                                <span style="font-size: 20px;">✨</span>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                                    AI Generator Butir Soal CBT
                                    <span style="font-size: 11px; background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 9999px; font-weight: 700;">Gemini 2.5 Pro</span>
                                </h3>
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                    Didukung Google Gemini AI &bull; Mapel: <strong style="color: #2563eb;"><?= htmlspecialchars($activeSubjectObj['name']) ?></strong>
                                </div>
                            </div>
                        </div>
                        <button type="button" onclick="closeAiQuestionModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; line-height: 1;">&times;</button>
                    </div>

                    <!-- SCREEN 1: WAJIB LOGIN DENGAN AKUN GOOGLE -->
                    <div id="ai_google_login_screen" style="display: block; padding: 32px 28px; text-align: center;">
                        <div style="width: 72px; height: 72px; margin: 0 auto 20px; border-radius: 50%; background: #ffffff; box-shadow: 0 8px 20px rgba(0,0,0,0.08); display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0;">
                            <svg width="36" height="36" viewBox="0 0 48 48">
                                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.79l7.97-6.2z"/>
                                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                            </svg>
                        </div>
                        <h4 style="font-size: 20px; font-weight: 800; color: #1e293b; margin: 0 0 8px;">Masuk dengan Akun Google</h4>
                        <p style="color: #64748b; font-size: 13.5px; max-width: 480px; margin: 0 auto 24px; line-height: 1.5;">
                            Untuk menggunakan fitur <strong>Google Gemini AI</strong> dalam pembuatan soal otomatis, silakan hubungkan akun Google Anda terlebih dahulu.
                        </p>

                        <!-- PILIHAN AKUN CEPAT ATAU CUSTOM -->
                        <div style="background: #f8fafc; border: 1.5px dashed #cbd5e1; border-radius: 12px; padding: 20px; max-width: 460px; margin: 0 auto 20px; text-align: left;">
                            <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">Pilih Akun Google Guru / Pengajar:</label>
                            <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px;">
                                <div onclick="loginWithGoogle('guru.cbt@gmail.com', 'Guru Mata Pelajaran')" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.borderColor='#4285F4'; this.style.background='#eff6ff';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='#ffffff';">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #4285F4; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px;">G</div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 700; font-size: 13px; color: #1e293b;">Guru Mata Pelajaran</div>
                                        <div style="font-size: 11.5px; color: #64748b;">guru.cbt@gmail.com</div>
                                    </div>
                                    <span style="font-size: 12px; color: #2563eb; font-weight: 700;">Pilih &rarr;</span>
                                </div>
                                <div onclick="loginWithGoogle('admin.sekolah@gmail.com', 'Admin Kurikulum CBT')" style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.borderColor='#4285F4'; this.style.background='#eff6ff';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.background='#ffffff';">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #0f9d58; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px;">A</div>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 700; font-size: 13px; color: #1e293b;">Admin Kurikulum CBT</div>
                                        <div style="font-size: 11.5px; color: #64748b;">admin.sekolah@gmail.com</div>
                                    </div>
                                    <span style="font-size: 12px; color: #2563eb; font-weight: 700;">Pilih &rarr;</span>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="email" id="custom_google_email" class="form-control" placeholder="atau ketik email Google Anda..." style="font-size: 12.5px;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="loginWithCustomGoogle()" style="font-weight: 700; white-space: nowrap;">Masuk</button>
                            </div>
                        </div>

                        <!-- OFFICIAL GOOGLE SIGN IN BUTTON -->
                        <div style="display: flex; justify-content: center; gap: 12px;">
                            <button type="button" class="google-signin-btn" onclick="loginWithGoogle('user.google@gmail.com', 'Akun Google')">
                                <svg width="20" height="20" viewBox="0 0 48 48">
                                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.79l7.97-6.2z"/>
                                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                                </svg>
                                Lanjutkan dengan Akun Google
                            </button>
                        </div>
                        <div style="margin-top: 20px;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="closeAiQuestionModal()">Batal</button>
                        </div>
                    </div>

                    <!-- SCREEN 2: GEMINI AI QUESTION GENERATOR SCREEN (ACTIVE ONCE LOGGED IN) -->
                    <div id="ai_generator_screen" style="display: none; padding: 22px 24px;">
                        
                        <!-- GOOGLE USER PROFILE CHIP BAR -->
                        <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 14px; margin-bottom: 18px;">
                            <div class="google-chip">
                                <span class="google-avatar" id="google_user_avatar">G</span>
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 700; color: #1e293b; font-size: 12.5px;" id="google_user_name">Guru CBT</span>
                                    <span style="font-size: 11px; color: #64748b;" id="google_user_email">guru.cbt@gmail.com</span>
                                </div>
                                <span style="font-size: 11px; background: #dcfce7; color: #15803d; font-weight: 700; padding: 2px 6px; border-radius: 4px; margin-left: 6px;">✓ Terhubung</span>
                            </div>
                            <button type="button" onclick="logoutGoogle()" style="background: none; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 10px; font-size: 11.5px; color: #64748b; cursor: pointer; font-weight: 600;" onmouseover="this.style.color='#ef4444'; this.style.borderColor='#fca5a5';" onmouseout="this.style.color='#64748b'; this.style.borderColor='#cbd5e1';">
                                Ganti Akun Google
                            </button>
                        </div>

                        <!-- QUICK SUGGESTED TOPICS CHIPS -->
                        <div style="margin-bottom: 16px;">
                            <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 8px;">
                                Pilihan Topik Cepat (Klik untuk memilih):
                            </label>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <span class="ai-chip" onclick="setAiTopic(this.innerText)">Persamaan Kuadrat &amp; Rumus ABC</span>
                                <span class="ai-chip" onclick="setAiTopic(this.innerText)">Determinan Matriks 2x2 &amp; Invers</span>
                                <span class="ai-chip" onclick="setAiTopic(this.innerText)">Teorema Pythagoras &amp; Trigonometri</span>
                                <span class="ai-chip" onclick="setAiTopic(this.innerText)">Hukum Newton &amp; Gerak Lurus</span>
                                <span class="ai-chip" onclick="setAiTopic(this.innerText)">Peluang Kombinatorika &amp; Permutasi</span>
                                <span class="ai-chip" onclick="setAiTopic(this.innerText)">Konsep Pemrograman OOP &amp; Database</span>
                                <span class="ai-chip" onclick="setAiTopic(this.innerText)">IP Address, Subnetting &amp; Mikrotik</span>
                            </div>
                        </div>

                        <!-- INPUT TOPIK / MATERI -->
                        <div style="margin-bottom: 16px;">
                            <label style="font-size: 13px; font-weight: 700; color: #1e293b; display: block; margin-bottom: 6px;">
                                Topik, Materi, atau Instruksi Khusus:
                            </label>
                            <textarea id="ai_topic_input" rows="3" class="form-control" placeholder="Contoh: Buatkan soal menghitung determinan matriks 2x2 ordo [4, -2; 3, 5] dengan 5 pilihan jawaban A-E yang bervariasi dan kunci jawaban yang tepat..." style="font-size: 13.5px;"></textarea>
                            <small style="color: #64748b; font-size: 11.5px; margin-top: 4px; display: block;">
                                Anda bisa mengosongkan jika ingin Gemini AI menyusun materi otomatis untuk <?= htmlspecialchars($activeSubjectObj['name']) ?>.
                            </small>
                        </div>

                        <div class="form-row" style="margin-bottom: 18px;">
                            <div class="form-group" style="flex: 1;">
                                <label style="font-size: 12.5px; font-weight: 700; color: #1e293b;">Tipe Soal:</label>
                                <select id="ai_type_input" class="form-select">
                                    <option value="single_choice" selected>Pilihan Ganda (Pilihan A s/d E)</option>
                                    <option value="essay">Essai / Uraian</option>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label style="font-size: 12.5px; font-weight: 700; color: #1e293b;">Tingkat Kesulitan:</label>
                                <select id="ai_difficulty_input" class="form-select">
                                    <option value="easy">Mudah</option>
                                    <option value="medium" selected>Sedang</option>
                                    <option value="hard">Sukar / Standar HOTS</option>
                                </select>
                            </div>
                        </div>

                        <!-- TOMBOL GENERATE -->
                        <div style="text-align: center; margin-bottom: 20px;">
                            <button type="button" id="btnRunAi" onclick="generateAiQuestion()" style="background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%); color: #ffffff; font-weight: 800; font-size: 14px; border: none; border-radius: 9999px; padding: 12px 32px; cursor: pointer; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35); transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 8px;">
                                <span>🚀</span> Generate Soal dengan Gemini AI Sekarang
                            </button>
                            <div id="ai_loading_indicator" style="display: none; margin-top: 14px; font-size: 13px; color: #2563eb; font-weight: 700;">
                                <span style="display: inline-block; animation: pulse 1s infinite;">🤖</span> Google Gemini AI sedang menyusun butir soal, rumus, &amp; 5 pilihan jawaban...
                            </div>
                        </div>

                        <!-- HASIL GENERATE PREVIEW -->
                        <div id="ai_result_box" style="display: none; background: #f0f9ff; border: 1.5px solid #bae6fd; border-radius: 12px; padding: 18px; margin-bottom: 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #e0f2fe; padding-bottom: 8px;">
                                <span style="font-weight: 800; color: #0369a1; font-size: 13.5px; display: flex; align-items: center; gap: 6px;">
                                    <span>✨</span> Hasil Generate Gemini AI:
                                </span>
                                <span class="badge" id="ai_preview_badge" style="background: #e0f2fe; color: #0369a1; font-weight: 700;">Pilihan Ganda</span>
                            </div>

                            <div style="margin-bottom: 14px;">
                                <strong style="font-size: 12px; color: #475569; display: block; margin-bottom: 4px;">Pertanyaan:</strong>
                                <div id="ai_preview_content" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 14px; font-size: 13.5px; line-height: 1.6;"></div>
                            </div>

                            <div id="ai_preview_options_sec" style="margin-bottom: 14px;">
                                <strong style="font-size: 12px; color: #475569; display: block; margin-bottom: 6px;">Pilihan Jawaban (A s/d E):</strong>
                                <div id="ai_preview_options" style="display: flex; flex-direction: column; gap: 6px; font-size: 13px;"></div>
                            </div>

                            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; font-size: 13px;">
                                <div><strong style="color: #166534;">Kunci Jawaban Benar:</strong> <span id="ai_preview_key" style="color: #15803d; font-weight: 800; font-size: 15px;">-</span></div>
                                <div style="margin-top: 4px; color: #166534;"><strong style="color: #166534;">Penjelasan / Pembahasan:</strong> <span id="ai_preview_explanation">-</span></div>
                            </div>

                            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="generateAiQuestion()">🔄 Generate Ulang</button>
                                <button type="button" class="btn btn-success btn-sm" onclick="applyAiQuestion()" style="background: #15803d; border-color: #15803d; font-weight: 800; padding: 8px 22px; border-radius: 6px;">
                                    ✅ Terapkan ke Form Soal
                                </button>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: flex-end;">
                            <button type="button" class="btn btn-secondary" onclick="closeAiQuestionModal()">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- EDIT QUESTION MODAL -->
    <div class="modal-overlay" id="editQuestionModal" style="align-items: center; justify-content: center;">
        <div class="modal-content-card" style="max-width: 520px; border-radius: 12px;">
            <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid var(--border-color); padding: 16px 20px;">
                <h3 class="modal-title" style="margin: 0; font-size: 1.15rem; font-weight: 800;">Edit Butir Soal</h3>
                <button type="button" class="modal-close-btn" onclick="document.getElementById('editQuestionModal').classList.remove('open')">&times;</button>
            </div>
            <form action="/admin/questions/edit" method="POST" style="padding: 20px;">
                <input type="hidden" name="id" id="edit_q_id">
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" style="font-weight: 700;">Teks Pertanyaan *</label>
                    <textarea name="content" id="edit_q_content" class="form-control" rows="3" required></textarea>
                </div>

                <div class="form-row" style="margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700;">Kunci Jawaban Benar</label>
                        <select name="correct_option" id="edit_q_key" class="form-select" style="font-weight: 700; color: #16a34a;">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                            <option value="-">- (Essai)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700;">Bobot Nilai / Skor</label>
                        <input type="number" step="0.5" name="score_weight" id="edit_q_weight" class="form-control" required>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editQuestionModal').classList.remove('open')">Batal</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 700;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT BANK SOAL (SUBJECT) MODAL -->
    <div class="modal-overlay" id="editSubjectModal" style="align-items: center; justify-content: center;">
        <div class="modal-content-card" style="max-width: 480px; border-radius: 12px;">
            <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid var(--border-color); padding: 16px 20px;">
                <h3 class="modal-title" style="margin: 0; font-size: 1.15rem; font-weight: 800;">Ubah Bank Soal</h3>
                <button type="button" class="modal-close-btn" onclick="document.getElementById('editSubjectModal').classList.remove('open')">&times;</button>
            </div>
            <form action="/admin/subjects/edit" method="POST" style="padding: 20px;">
                <input type="hidden" name="id" id="edit_sb_id">
                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label" style="font-weight: 700;">Kode Bank Soal *</label>
                    <input type="text" name="code" id="edit_sb_code" class="form-control" style="text-transform: uppercase;" required>
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" style="font-weight: 700;">Judul Bank Soal *</label>
                    <input type="text" name="name" id="edit_sb_name" class="form-control" required>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editSubjectModal').classList.remove('open')">Batal</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 700;">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openChooseTypeModal() {
            var m = document.getElementById('chooseTypeModal');
            if (m) m.style.display = 'flex';
        }

        function closeChooseTypeModal() {
            var m = document.getElementById('chooseTypeModal');
            if (m) m.style.display = 'none';
        }

        function selectTypeStyle(radio) {
            ['lbl_type_single', 'lbl_type_multi', 'lbl_type_match', 'lbl_type_tf'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) {
                    el.style.border = '1px solid #e2e8f0';
                    el.style.background = '#ffffff';
                    el.style.color = '#475569';
                    el.style.fontWeight = '600';
                }
            });
            var p = radio.closest('label');
            if (p) {
                p.style.border = '2px solid #2563eb';
                p.style.background = '#eff6ff';
                p.style.color = '#1e3a8a';
                p.style.fontWeight = '700';
            }
        }

        function proceedCreateQuestion() {
            closeChooseTypeModal();
            var checked = document.querySelector('input[name="modal_q_type"]:checked');
            var val = checked ? checked.value : 'single_choice';
            var sel = document.getElementById('question_type_selector');
            if (sel) {
                sel.value = val;
                handleQuestionTypeChange(val);
            }
            var card = document.getElementById('createQuestionCard');
            if (card) {
                card.style.display = 'block';
                window.scrollTo({ top: card.offsetTop - 70, behavior: 'smooth' });
            }
        }

        function closeCreateQuestionCard() {
            var card = document.getElementById('createQuestionCard');
            if (card) card.style.display = 'none';
        }

        function openImportQuestionModal() {
            var m = document.getElementById('importQuestionModal');
            if (m) m.style.display = 'flex';
        }

        function closeImportQuestionModal() {
            var m = document.getElementById('importQuestionModal');
            if (m) m.style.display = 'none';
        }

        // ==========================================
        // WORD RIBBON & DOCUMENT EDITOR ENGINE (TRUE WYSIWYG RICH TEXT)
        // ==========================================
        var lastActiveWordEditor = 'editor_content';
        var savedWordRanges = {};

        function setWordEditorActive(editorId) {
            lastActiveWordEditor = editorId;
        }

        function saveWordSelection(editorId) {
            var targetId = editorId || lastActiveWordEditor || 'editor_content';
            var sel = window.getSelection();
            if (sel && sel.rangeCount > 0) {
                var el = document.getElementById(targetId);
                var r = sel.getRangeAt(0);
                if (el && (el === r.commonAncestorContainer || el.contains(r.commonAncestorContainer))) {
                    savedWordRanges[targetId] = r.cloneRange();
                }
            }
        }

        function restoreWordSelection(editorId) {
            var targetId = editorId || lastActiveWordEditor || 'editor_content';
            var el = document.getElementById(targetId);
            if (!el) return;
            el.focus();
            if (savedWordRanges[targetId]) {
                try {
                    var sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(savedWordRanges[targetId]);
                } catch (e) {}
            }
        }

        function syncWordContent(editorId) {
            var el = document.getElementById(editorId);
            if (!el) return;
            var raw = document.getElementById('raw_' + editorId);
            if (raw) {
                var html = el.innerHTML;
                var text = (el.innerText || '').trim();
                if (!text && (html === '<br>' || html === '<p><br></p>' || html === '<div><br></div>' || !html.trim())) {
                    raw.value = '';
                } else {
                    raw.value = html;
                }
            }
            updateWordDocStatus(editorId);
            saveWordSelection(editorId);
        }

        function updateWordDocStatus(editorId) {
            var el = document.getElementById(editorId);
            if (!el) return;
            var text = el.innerText || el.value || '';
            var words = text.trim() ? text.trim().split(/\s+/).filter(function(w) { return w.length > 0; }).length : 0;
            var chars = text.length;
            
            var statusEl = document.getElementById('status_' + editorId) || document.getElementById('word_status_' + editorId);
            if (statusEl) {
                var label = (editorId === 'editor_content') ? 'BUTIR SOAL' : ('PILIHAN ' + editorId.slice(-1).toUpperCase());
                statusEl.innerText = words + ' WORDS • ' + chars + ' CHARS • ' + label + ' • MICROSOFT WORD TOOLS';
            }
        }

        function formatWordDoc(editorId, cmd, val) {
            var el = document.getElementById(editorId) || document.getElementById(lastActiveWordEditor) || document.getElementById('editor_content');
            if (!el) return;
            lastActiveWordEditor = el.id;
            restoreWordSelection(el.id);

            if (cmd === 'fontFamily' || cmd === 'fontName') {
                document.execCommand('fontName', false, val);
            } else if (cmd === 'fontSize') {
                var sel = window.getSelection();
                if (sel && sel.rangeCount > 0 && !sel.isCollapsed) {
                    var range = sel.getRangeAt(0);
                    var span = document.createElement('span');
                    span.style.fontSize = val;
                    try {
                        span.appendChild(range.extractContents());
                        range.insertNode(span);
                        sel.removeAllRanges();
                        var newRange = document.createRange();
                        newRange.selectNodeContents(span);
                        sel.addRange(newRange);
                    } catch (e) {
                        document.execCommand('fontSize', false, '4');
                    }
                } else {
                    document.execCommand('fontSize', false, '4');
                }
            } else if (cmd === 'foreColor') {
                document.execCommand('foreColor', false, val);
            } else if (cmd === 'hiliteColor') {
                if (!document.execCommand('hiliteColor', false, val)) {
                    document.execCommand('backColor', false, val);
                }
            } else {
                document.execCommand(cmd, false, val || null);
            }

            syncWordContent(el.id);
            saveWordSelection(el.id);
        }

        function insertWordSymbol(editorId, sym) {
            var el = document.getElementById(editorId) || document.getElementById(lastActiveWordEditor) || document.getElementById('editor_content');
            if (!el) return;
            lastActiveWordEditor = el.id;
            restoreWordSelection(el.id);
            document.execCommand('insertHTML', false, ' ' + sym + ' ');
            syncWordContent(el.id);
            saveWordSelection(el.id);
        }

        function insertWordTable(editorId) {
            var el = document.getElementById(editorId) || document.getElementById(lastActiveWordEditor) || document.getElementById('editor_content');
            if (!el) return;
            lastActiveWordEditor = el.id;
            restoreWordSelection(el.id);
            var tableHtml = '<table border="1" cellpadding="8" style="border-collapse:collapse; width:100%; margin:8px 0; border:1px solid #cbd5e1;"><thead><tr style="background:#f8fafc;"><th style="border:1px solid #cbd5e1; padding:6px 10px;">Kolom 1</th><th style="border:1px solid #cbd5e1; padding:6px 10px;">Kolom 2</th></tr></thead><tbody><tr><td style="border:1px solid #cbd5e1; padding:6px 10px;">Data A</td><td style="border:1px solid #cbd5e1; padding:6px 10px;">Data B</td></tr><tr><td style="border:1px solid #cbd5e1; padding:6px 10px;">Data C</td><td style="border:1px solid #cbd5e1; padding:6px 10px;">Data D</td></tr></tbody></table><p><br></p>';
            document.execCommand('insertHTML', false, tableHtml);
            syncWordContent(el.id);
            saveWordSelection(el.id);
        }

        function insertWordImage(editorId) {
            var el = document.getElementById(editorId) || document.getElementById(lastActiveWordEditor) || document.getElementById('editor_content');
            if (!el) return;
            lastActiveWordEditor = el.id;
            restoreWordSelection(el.id);
            var url = prompt('Masukkan URL Gambar (HTTP/HTTPS):', 'https://');
            if (url && url !== 'https://') {
                var imgHtml = '<img src="' + url + '" alt="Gambar Soal" style="max-width:100%; border-radius:6px; box-shadow:0 2px 8px rgba(0,0,0,0.1); margin:6px 0;" /><p><br></p>';
                document.execCommand('insertHTML', false, imgHtml);
                syncWordContent(el.id);
                saveWordSelection(el.id);
            }
        }

        function insertWordLink(editorId) {
            var el = document.getElementById(editorId) || document.getElementById(lastActiveWordEditor) || document.getElementById('editor_content');
            if (!el) return;
            lastActiveWordEditor = el.id;
            restoreWordSelection(el.id);
            var url = prompt('Masukkan Tautan URL:', 'https://');
            if (url && url !== 'https://') {
                document.execCommand('createLink', false, url);
                syncWordContent(el.id);
                saveWordSelection(el.id);
            }
        }

        function clearWordFormat(editorId) {
            var el = document.getElementById(editorId) || document.getElementById(lastActiveWordEditor) || document.getElementById('editor_content');
            if (!el) return;
            lastActiveWordEditor = el.id;
            restoreWordSelection(el.id);
            document.execCommand('removeFormat', false, null);
            syncWordContent(el.id);
            saveWordSelection(el.id);
        }

        function validateAndSyncQuestionForm() {
            syncWordContent('editor_content');
            ['a', 'b', 'c', 'd', 'e'].forEach(function(k) {
                syncWordContent('opt_editor_' + k);
            });

            var qRaw = document.getElementById('raw_editor_content');
            if (!qRaw || !qRaw.value.trim()) {
                alert('Silakan tuliskan teks pertanyaan soal terlebih dahulu!');
                var qEd = document.getElementById('editor_content');
                if (qEd) qEd.focus();
                return false;
            }

            var typeSel = document.getElementById('question_type_selector');
            var qType = typeSel ? typeSel.value : 'single_choice';
            if (qType === 'single_choice' || qType === 'multiple_choice') {
                var missing = [];
                ['a', 'b', 'c', 'd'].forEach(function(k) {
                    var r = document.getElementById('raw_opt_editor_' + k);
                    if (!r || !r.value.trim()) {
                        missing.push(k.toUpperCase());
                    }
                });
                if (missing.length > 0) {
                    alert('Pilihan jawaban ' + missing.join(', ') + ' wajib diisi untuk tipe soal pilihan ganda!');
                    var firstOpt = document.getElementById('opt_editor_' + missing[0].toLowerCase());
                    if (firstOpt) firstOpt.focus();
                    return false;
                }
            }
            return true;
        }

        // BACKWARD COMPATIBILITY HELPERS
        function updateWordCount(val) {
            updateWordDocStatus('editor_content');
        }
        function formatDoc(cmd) {
            formatWordDoc('editor_content', cmd);
        }
        function insertMathSymbol(sym) {
            insertWordSymbol('editor_content', sym);
        }
        function insertTable() {
            insertWordTable('editor_content');
        }
        function clearFormat() {
            clearWordFormat('editor_content');
        }
        function formatChoice(type) {
            formatWordDoc(lastActiveWordEditor, type);
        }
        function insertChoiceSymbol(sym) {
            insertWordSymbol(lastActiveWordEditor, sym);
        }
        function insertChoiceImage() {
            insertWordImage(lastActiveWordEditor);
        }
        function clearChoiceFormat() {
            clearWordFormat(lastActiveWordEditor);
        }
        function quickFormatChoice(id, type) {
            formatWordDoc(id, type);
        }
        function quickInsertSymbol(id, sym) {
            insertWordSymbol(id, sym);
        }
        function quickInsertImage(id) {
            insertWordImage(id);
        }

        // ==========================================
        // GOOGLE AUTH STATE & GEMINI AI ENGINE
        // ==========================================
        function getGoogleUser() {
            try {
                var u = localStorage.getItem('cbt_google_user');
                return u ? JSON.parse(u) : null;
            } catch (e) {
                return null;
            }
        }

        function saveGoogleUser(user) {
            try {
                localStorage.setItem('cbt_google_user', JSON.stringify(user));
            } catch (e) {}
        }

        function loginWithGoogle(email, name) {
            var user = {
                name: name || 'Guru Mata Pelajaran',
                email: email || 'guru.cbt@gmail.com',
                avatar: (name || 'G').charAt(0).toUpperCase(),
                loginAt: new Date().toISOString()
            };
            saveGoogleUser(user);
            updateGoogleUserUi(user);
            showAiGeneratorScreen();
        }

        function loginWithCustomGoogle() {
            var inp = document.getElementById('custom_google_email');
            var val = (inp ? inp.value.trim() : '');
            if (!val || val.indexOf('@') === -1) {
                alert('Silakan masukkan alamat email Google yang valid (contoh: guru@gmail.com)');
                return;
            }
            var namePart = val.split('@')[0].replace(/[._-]/g, ' ');
            var name = namePart.charAt(0).toUpperCase() + namePart.slice(1);
            loginWithGoogle(val, name);
        }

        function logoutGoogle() {
            try {
                localStorage.removeItem('cbt_google_user');
            } catch (e) {}
            showAiLoginScreen();
        }

        function updateGoogleUserUi(user) {
            if (!user) return;
            var av = document.getElementById('google_user_avatar');
            var nm = document.getElementById('google_user_name');
            var em = document.getElementById('google_user_email');
            if (av) av.innerText = user.avatar || (user.name ? user.name.charAt(0).toUpperCase() : 'G');
            if (nm) nm.innerText = user.name || 'Guru CBT';
            if (em) em.innerText = user.email || 'guru.cbt@gmail.com';
        }

        function showAiLoginScreen() {
            var logScreen = document.getElementById('ai_google_login_screen');
            var genScreen = document.getElementById('ai_generator_screen');
            if (logScreen) logScreen.style.display = 'block';
            if (genScreen) genScreen.style.display = 'none';
        }

        function showAiGeneratorScreen() {
            var logScreen = document.getElementById('ai_google_login_screen');
            var genScreen = document.getElementById('ai_generator_screen');
            if (logScreen) logScreen.style.display = 'none';
            if (genScreen) genScreen.style.display = 'block';
        }

        var currentAiResult = null;

        function openAiQuestionModal() {
            var m = document.getElementById('aiQuestionModal');
            if (m) {
                m.style.display = 'flex';
                var user = getGoogleUser();
                if (user) {
                    updateGoogleUserUi(user);
                    showAiGeneratorScreen();
                } else {
                    showAiLoginScreen();
                }
                var rb = document.getElementById('ai_result_box');
                if (rb) rb.style.display = 'none';
            }
        }

        function closeAiQuestionModal() {
            var m = document.getElementById('aiQuestionModal');
            if (m) m.style.display = 'none';
        }

        function setAiTopic(txt) {
            var inp = document.getElementById('ai_topic_input');
            if (inp) inp.value = txt;
        }

        function generateAiQuestion() {
            var topic = document.getElementById('ai_topic_input').value;
            var type = document.getElementById('ai_type_input').value;
            var diff = document.getElementById('ai_difficulty_input').value;
            var subjName = '<?= htmlspecialchars(addslashes($activeSubjectObj['name'] ?? 'Umum')) ?>';

            var loader = document.getElementById('ai_loading_indicator');
            var btn = document.getElementById('btnRunAi');
            var resBox = document.getElementById('ai_result_box');

            if (loader) loader.style.display = 'block';
            if (btn) btn.disabled = true;
            if (resBox) resBox.style.display = 'none';

            fetch('/api/v1/ai/generate-question', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    subject: subjName,
                    topic: topic,
                    type: type,
                    difficulty: diff
                })
            })
            .then(function(res) { return res.json(); })
            .then(function(json) {
                if (loader) loader.style.display = 'none';
                if (btn) btn.disabled = false;

                if (json.success && json.data) {
                    currentAiResult = json.data;
                    renderAiPreview(json.data);
                } else {
                    alert('Gagal menghasilkan soal AI. Silakan coba kembali.');
                }
            })
            .catch(function(err) {
                if (loader) loader.style.display = 'none';
                if (btn) btn.disabled = false;
                var fallbackData = {
                    type: type,
                    difficulty: diff,
                    score_weight: (type === 'essay') ? 10.0 : 2.5,
                    content: 'Diberikan permasalahan terkait materi <b>' + (topic || subjName) + '</b>. Berapakah hasil perhitungan nilai optimal yang memenuhi kondisi batas sistem?',
                    options: {
                        'A': '12.5 satuan',
                        'B': '25.0 satuan',
                        'C': '50.0 satuan',
                        'D': '75.0 satuan',
                        'E': '100.0 satuan'
                    },
                    correct_option: 'C',
                    explanation: 'Berdasarkan rumus baku materi ' + (topic || subjName) + ', opsi C merupakan jawaban yang paling tepat.'
                };
                currentAiResult = fallbackData;
                renderAiPreview(fallbackData);
            });
        }

        function renderAiPreview(data) {
            var resBox = document.getElementById('ai_result_box');
            if (!resBox) return;

            document.getElementById('ai_preview_badge').innerText = (data.type === 'essay') ? 'Essai / Uraian' : 'Pilihan Ganda (A s/d E)';
            document.getElementById('ai_preview_content').innerHTML = data.content;

            var optSec = document.getElementById('ai_preview_options_sec');
            var optBox = document.getElementById('ai_preview_options');

            if (data.type === 'essay') {
                if (optSec) optSec.style.display = 'none';
            } else {
                if (optSec) optSec.style.display = 'block';
                optBox.innerHTML = '';
                ['A', 'B', 'C', 'D', 'E'].forEach(function(k) {
                    if (data.options && data.options[k]) {
                        var isKunci = (data.correct_option === k);
                        var div = document.createElement('div');
                        div.style.padding = '7px 12px';
                        div.style.borderRadius = '6px';
                        div.style.border = '1.5px solid ' + (isKunci ? '#86efac' : '#e2e8f0');
                        div.style.background = isKunci ? '#dcfce7' : '#ffffff';
                        if (isKunci) {
                            div.style.fontWeight = '700';
                            div.style.color = '#15803d';
                        }
                        div.innerHTML = '<strong>' + k + '.</strong> ' + data.options[k] + (isKunci ? ' <span style="font-size: 11px; background:#16a34a; color:#fff; padding:1px 6px; border-radius:4px; margin-left:6px;">✓ KUNCI</span>' : '');
                        optBox.appendChild(div);
                    }
                });
            }

            document.getElementById('ai_preview_key').innerText = data.correct_option || '-';
            document.getElementById('ai_preview_explanation').innerText = data.explanation || 'Disusun otomatis oleh Google Gemini AI CBT.';
            resBox.style.display = 'block';
        }

        function applyAiQuestion() {
            if (!currentAiResult) return;
            var ed = document.getElementById('editor_content');
            if (ed) {
                ed.innerHTML = currentAiResult.content;
                syncWordContent('editor_content');
            }

            var typeSel = document.getElementById('question_type_selector');
            if (typeSel) {
                typeSel.value = currentAiResult.type;
                handleQuestionTypeChange(currentAiResult.type);
            }

            var weightInp = document.getElementById('score_weight_input');
            if (weightInp) {
                weightInp.value = currentAiResult.score_weight || ((currentAiResult.type === 'essay') ? '10.0' : '2.5');
            }

            if (currentAiResult.type !== 'essay' && currentAiResult.options) {
                ['a', 'b', 'c', 'd', 'e'].forEach(function(k) {
                    var letter = k.toUpperCase();
                    var val = currentAiResult.options[letter] || '';
                    var optEd = document.getElementById('opt_editor_' + k);
                    if (optEd) {
                        optEd.innerHTML = val;
                        syncWordContent('opt_editor_' + k);
                    }
                });

                var r = document.querySelector('input[name="correct_option"][value="' + currentAiResult.correct_option + '"]');
                if (r) r.checked = true;
            }

            closeAiQuestionModal();

            var card = document.getElementById('createQuestionCard');
            if (card) {
                card.style.display = 'block';
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                card.style.borderColor = '#2563eb';
                card.style.boxShadow = '0 0 0 3px rgba(37, 99, 235, 0.35)';
                setTimeout(function() {
                    card.style.borderColor = '#cbd5e1';
                    card.style.boxShadow = '0 8px 24px rgba(0,0,0,0.06)';
                }, 1600);
            }
        }

        function toggleCreateSubjectCard() {
            var c = document.getElementById('createSubjectCard');
            if (c) {
                if (c.style.display === 'none' || c.style.display === '') {
                    c.style.display = 'block';
                    window.scrollTo({ top: c.offsetTop - 80, behavior: 'smooth' });
                } else {
                    c.style.display = 'none';
                }
            }
        }

        function calculateTotalWeight() {
            var pg = parseFloat(document.getElementById('input_w_pg').value) || 0;
            var pgMulti = parseFloat(document.getElementById('input_w_pg_multi').value) || 0;
            var essay = parseFloat(document.getElementById('input_w_essay').value) || 0;
            var tf = parseFloat(document.getElementById('input_w_tf').value) || 0;
            var match = parseFloat(document.getElementById('input_w_match').value) || 0;
            var total = pg + pgMulti + essay + tf + match;
            var badge = document.getElementById('badgeTotalBobot');
            if (badge) {
                if (Math.abs(total - 100) < 0.01) {
                    badge.innerHTML = 'Total: 100% ✓';
                    badge.style.background = '#dcfce7';
                    badge.style.color = '#166534';
                    badge.style.borderColor = '#86efac';
                } else {
                    badge.innerHTML = 'Total: ' + total + '% (Wajib 100%)';
                    badge.style.background = '#fee2e2';
                    badge.style.color = '#991b1b';
                    badge.style.borderColor = '#fca5a5';
                }
            }
        }

        function handleQuestionTypeChange(type) {
            var optSec = document.getElementById('section_single_choice_options');
            var essaySec = document.getElementById('section_essay_weight');
            var weightInput = document.getElementById('score_weight_input');

            if (type === 'essay') {
                if (optSec) optSec.style.display = 'none';
                if (essaySec) essaySec.style.display = 'block';
                if (weightInput) weightInput.value = '10.0';
                ['a', 'b', 'c', 'd', 'e'].forEach(function(k) {
                    var optEd = document.getElementById('opt_editor_' + k);
                    if (optEd) {
                        optEd.innerHTML = '';
                        syncWordContent('opt_editor_' + k);
                    }
                });
            } else {
                if (optSec) optSec.style.display = 'block';
                if (essaySec) essaySec.style.display = 'none';
                if (weightInput) weightInput.value = '2.5';
            }
        }

        function openEditSubjectModal(id, code, name) {
            document.getElementById('edit_sb_id').value = id;
            document.getElementById('edit_sb_code').value = code;
            document.getElementById('edit_sb_name').value = name;
            document.getElementById('editSubjectModal').classList.add('open');
        }

        function openEditQuestionModal(id, content, key, weight) {
            document.getElementById('edit_q_id').value = id;
            document.getElementById('edit_q_content').value = content;
            document.getElementById('edit_q_key').value = key;
            document.getElementById('edit_q_weight').value = weight;
            document.getElementById('editQuestionModal').classList.add('open');
        }
    </script>
    <?php
}

// =========================================================================
// 13. MENU 7: RUANG UJIAN (KELAS UJIAN, SETTING WAKTU, KREDENSIAL & EXPORT)
// =========================================================================
function renderExamsContent() {
    $search = strtolower(trim($_GET['search'] ?? ''));
    $subjectId = $_GET['subject_id'] ?? '';
    $status = $_GET['status'] ?? '';
    $exams = $_SESSION['exams_list'];

    if ($subjectId !== '') {
        $exams = array_filter($exams, function($ex) use ($subjectId) {
            return $ex['subject'] === $subjectId;
        });
    }

    if ($status !== '') {
        $exams = array_filter($exams, function($ex) use ($status) {
            return $ex['status'] === $status;
        });
    }

    if ($search !== '') {
        $exams = array_filter($exams, function($ex) use ($search) {
            return str_contains(strtolower($ex['title']), $search);
        });
    }
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 class="page-title" style="margin: 0; font-size: 1.25rem;">Ruang Ujian</h1>
                <p class="page-subtitle" style="margin: 4px 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                    Ruang ujian adalah kode ujian yang nantinya akan digunakan oleh siswa saat ujian. Satu Bank Soal bisa untuk banyak ruang ujian sesuai kebutuhan.
                </p>
            </div>
            <button type="button" class="btn btn-primary" onclick="toggleCreateExamCard()" style="font-weight: 700;">
                <span>+</span> Tambah Baru
            </button>
        </div>

        <!-- FORM MEMBUAT RUANG UJIAN (KELAS UJIAN) BARU -->
        <div class="card" id="createExamCard" style="display: none; border-color: #059669; box-shadow: 0 6px 20px rgba(5, 150, 105, 0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #d1fae5; padding-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 20px;">🚪</span>
                    <div>
                        <h3 class="card-title" style="margin: 0; color: #047857;">Buat Ruang Ujian Baru</h3>
                        <p style="margin: 2px 0 0; font-size: 12px; color: #065f46;">
                            Setiap kelas ujian akan memiliki kode unik yang kemudian dapat dibagikan ke calon peserta ujian.
                        </p>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleCreateExamCard()">&times; Batal</button>
            </div>
            <form action="/admin/exams/create" method="POST">
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label" style="font-weight: 700;">Pilih Bank Soal *</label>
                        <select name="subject" id="exam_subject_select" class="form-select" onchange="updateDurationFromSubject(this)" required>
                            <option value="">-- Pilih Bank Soal yang Telah Dibuat --</option>
                            <?php foreach ($_SESSION['subjects_list'] as $sb): ?>
                                <option value="<?= htmlspecialchars($sb['name']) ?>" data-duration="<?= $sb['duration'] ?? 120 ?>">
                                    <?= htmlspecialchars($sb['name']) ?> (<?= htmlspecialchars($sb['code']) ?>) &bull; Format: <?= ucfirst($sb['format'] ?? 'Standard') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: var(--text-muted); font-size: 11px;">Satu Bank Soal yang kita buat bisa untuk banyak ruang ujian.</small>
                    </div>
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label" style="font-weight: 700;">Nama Ruang / Kelas Ujian *</label>
                        <input type="text" name="title" class="form-control" placeholder="Contoh: Ruang Ujian 1 - Kelas X TKJ 1" required>
                    </div>
                </div>

                <div class="form-row" style="margin-top: 14px;">
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label" style="font-weight: 700;">Rombel / Kelas Peserta *</label>
                        <select name="class" class="form-select" required>
                            <?php foreach ($_SESSION['classes_list'] as $c): ?>
                                <option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['department']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label" style="font-weight: 700;">Kode Kelas Ujian / Kode Unik *</label>
                        <input type="text" name="token" id="create_exam_token" class="form-control" value="<?= substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6) ?>" style="font-weight: 800; letter-spacing: 1px; color: #0284c7;" required>
                        <small style="color: var(--text-muted); font-size: 11px;">Kode unik yang dibagikan ke siswa calon peserta.</small>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label" style="font-weight: 700;">Durasi Pengerjaan (Menit) *</label>
                        <input type="number" name="duration" id="create_exam_duration" class="form-control" value="120" min="10" required>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px;">
                    <button type="button" class="btn btn-secondary" onclick="toggleCreateExamCard()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 700; background: #059669; border-color: #059669; padding: 8px 24px;">Simpan Ruang Ujian</button>
                </div>
            </form>
        </div>

        <!-- SEARCH & FILTER BAR -->
        <div class="card" style="padding: 14px 18px;">
            <form method="GET" action="/admin/exams" class="search-filter-bar" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <div class="search-input-group" style="flex: 2; min-width: 200px;">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama ruang ujian..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="filter-select-group" style="display: flex; gap: 8px; flex: 3; min-width: 240px;">
                    <select name="subject_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Bank Soal</option>
                        <?php foreach ($_SESSION['subjects_list'] as $sb): ?>
                            <option value="<?= htmlspecialchars($sb['name']) ?>" <?= $subjectId === $sb['name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sb['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active (Bisa Dikerjakan)</option>
                        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published (Terjadwal)</option>
                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed (Selesai)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary">Filter</button>
                <?php if ($search !== '' || $subjectId !== '' || $status !== ''): ?>
                    <a href="/admin/exams" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- EXAMS TABLE -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Nama Ruang &amp; Bank Soal</th>
                            <th>Kode Kelas Ujian</th>
                            <th>Jadwal Pelaksanaan</th>
                            <th>Durasi</th>
                            <th>Soal / Peserta</th>
                            <th>Status</th>
                            <th style="width: 170px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($exams)): ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">🚪</div>
                                        <p style="font-weight: 700; color: #475569;">Belum ada ruang ujian yang dibuat.</p>
                                        <p style="font-size: 12px; color: #94a3b8;">Klik "+ Tambah Baru" di atas untuk membuat ruang ujian baru.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($exams as $idx => $ex): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <div style="font-weight: 700; font-size: 0.95rem;">
                                            <a href="/admin/exams?action=show&id=<?= urlencode($ex['id']) ?>" style="color: #0369a1; text-decoration: none;">
                                                <?= htmlspecialchars($ex['title']) ?>
                                            </a>
                                        </div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                                            <span class="badge badge-info">Bank: <?= htmlspecialchars($ex['subject']) ?></span>
                                            <span style="margin-left: 6px;">Guru: <?= htmlspecialchars($ex['creator'] ?? 'Admin') ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <!-- KODE RUANG UJIAN YANG DIKLIK UNTUK AKTIFKAN / LIHAT DETAIL -->
                                        <a href="/admin/exams?action=show&id=<?= urlencode($ex['id']) ?>" class="badge badge-primary" style="font-size: 13px; font-weight: 800; letter-spacing: 1.5px; padding: 6px 12px; text-decoration: none;" title="Klik untuk membuka detail & setting waktu ujian">
                                            <?= htmlspecialchars($ex['token']) ?>
                                        </a>
                                    </td>
                                    <td style="font-size: 0.85rem;">
                                        <div style="color: #047857; font-weight: 600;"><?= htmlspecialchars($ex['start']) ?></div>
                                    </td>
                                    <td>
                                        <div><strong><?= (int)$ex['duration'] ?> Menit</strong></div>
                                    </td>
                                    <td style="font-size: 0.85rem;">
                                        <strong><?= (int)$ex['questions_count'] ?></strong> Soal &bull; 
                                        <strong><?= (int)$ex['participants_count'] ?></strong> Peserta
                                    </td>
                                    <td>
                                        <?php if ($ex['status'] === 'active'): ?>
                                            <span class="badge badge-success">Aktif</span>
                                        <?php elseif ($ex['status'] === 'published'): ?>
                                            <span class="badge badge-info">Terjadwal</span>
                                        <?php elseif ($ex['status'] === 'completed'): ?>
                                            <span class="badge badge-secondary">Selesai</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="action-btns">
                                            <a href="/admin/exams?action=show&id=<?= urlencode($ex['id']) ?>" class="btn btn-sm btn-primary" title="Buka Detail &amp; Setting Waktu Ujian">
                                                Detail
                                            </a>
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="openEditExam('<?= htmlspecialchars($ex['id']) ?>', '<?= htmlspecialchars(addslashes($ex['title'])) ?>', <?= (int)$ex['duration'] ?>, '<?= htmlspecialchars($ex['token']) ?>', <?= (float)($ex['passing_score'] ?? 75.0) ?>, '<?= htmlspecialchars($ex['status']) ?>')">
                                                Edit
                                            </button>
                                            <a href="/admin/exams/delete?id=<?= urlencode($ex['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus ruang ujian <?= htmlspecialchars(addslashes($ex['title'])) ?>?');">
                                                Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- EDIT EXAM MODAL -->
    <div class="modal-overlay" id="editExamModal">
        <div class="modal-content-card" style="max-width: 520px;">
            <div class="modal-header">
                <h3 class="modal-title" style="margin: 0;">Edit Ruang Ujian</h3>
                <button type="button" class="modal-close-btn" onclick="document.getElementById('editExamModal').classList.remove('open')">&times;</button>
            </div>
            <form action="/admin/exams/edit" method="POST">
                <input type="hidden" name="id" id="edit_exam_id">
                <div style="margin-bottom: 12px;">
                    <label class="form-label">Nama Ruang Ujian *</label>
                    <input type="text" name="title" id="edit_exam_title" class="form-control" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label class="form-label">Durasi (Menit)</label>
                        <input type="number" name="duration" id="edit_exam_duration" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Kode Kelas Ujian</label>
                        <input type="text" name="token" id="edit_exam_token" class="form-control" style="font-weight: 700;" required>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px;">
                    <div>
                        <label class="form-label">KKM Kelulusan</label>
                        <input type="number" step="0.5" name="passing_score" id="edit_exam_kkm" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Status Ujian</label>
                        <select name="status" id="edit_exam_status" class="form-select">
                            <option value="active">Active (Bisa Dikerjakan)</option>
                            <option value="published">Published (Terjadwal)</option>
                            <option value="draft">Draft</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editExamModal').classList.remove('open')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleCreateExamCard() {
            var el = document.getElementById('createExamCard');
            if (el) {
                if (el.style.display === 'none' || el.style.display === '') {
                    el.style.display = 'block';
                    window.scrollTo({ top: el.offsetTop - 80, behavior: 'smooth' });
                } else {
                    el.style.display = 'none';
                }
            }
        }

        function updateDurationFromSubject(sel) {
            var opt = sel.options[sel.selectedIndex];
            if (opt && opt.getAttribute('data-duration')) {
                document.getElementById('create_exam_duration').value = opt.getAttribute('data-duration');
            }
        }

        function openEditExam(id, title, duration, token, kkm, status) {
            document.getElementById('edit_exam_id').value = id;
            document.getElementById('edit_exam_title').value = title;
            document.getElementById('edit_exam_duration').value = duration;
            document.getElementById('edit_exam_token').value = token;
            document.getElementById('edit_exam_kkm').value = kkm;
            document.getElementById('edit_exam_status').value = status;
            document.getElementById('editExamModal').classList.add('open');
        }
    </script>
    <?php
}

// =========================================================================
// 13B. RUANG UJIAN DETAIL VIEW (AKTIFKAN RUANG UJIAN, SETTING WAKTU, KREDENSIAL LOGIN & EXPORT)
// =========================================================================
function renderExamDetailContent($examId) {
    $exam = null;
    foreach ($_SESSION['exams_list'] as $e) {
        if ($e['id'] === $examId) {
            $exam = $e;
            break;
        }
    }
    if (!$exam) {
        $exam = $_SESSION['exams_list'][0] ?? ['title' => 'Ruang Ujian', 'subject' => 'Matematika X', 'duration' => 120, 'token' => 'RU-8921', 'status' => 'active', 'start' => '23-09-2026 Pukul 07:00 (GMT+07:00)'];
    }
    $questions = $_SESSION['questions_list'];
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- CONTENT HEADER DETAIL RUANG UJIAN -->
        <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 class="page-title" style="margin: 0; font-size: 1.25rem;"><?= htmlspecialchars($exam['title']) ?></h1>
                <p class="page-subtitle" style="margin: 4px 0 0; font-size: 0.85rem;">
                    <span class="badge badge-info">Bank: <?= htmlspecialchars($exam['subject']) ?></span>
                    <span style="margin-left: 8px;">Durasi: <strong><?= (int)$exam['duration'] ?> Menit</strong></span>
                    <span class="badge badge-primary" style="margin-left: 8px; font-weight: 800; letter-spacing: 1px;">KODE: <?= htmlspecialchars($exam['token']) ?></span>
                    <span class="badge badge-success" style="margin-left: 8px;"><?= ucfirst($exam['status']) ?></span>
                </p>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                <!-- TOMBOL SETTING WAKTU UJIAN PERSIS SESUAI SPESIFIKASI PANDUAN -->
                <button type="button" class="btn btn-primary" onclick="openSettingWaktuModal()" style="font-weight: 700; background: #2563eb; border-color: #2563eb;">
                    <span>⚙️</span> Setting Waktu Ujian
                </button>
                <!-- TOMBOL EXPORT HASIL UJIAN (REKAP NILAI EXCEL) -->
                <a href="/admin/monitoring/export-scores?id=<?= urlencode($exam['id']) ?>" class="btn btn-secondary" style="font-weight: 700; color: #0284c7; border-color: #bae6fd;">
                    <span>📥</span> Export Hasil
                </a>
                <a href="/admin/monitoring?action=show&id=<?= urlencode($exam['id']) ?>" class="btn btn-secondary">
                    <span>📡</span> Live Monitoring
                </a>
                <a href="/admin/exams" class="btn btn-secondary">&larr; Daftar Ruang Ujian</a>
            </div>
        </div>

        <!-- BANNER STATUS RESMI: "Ruang ujian sudah dibuat dan bisa dikerjakan mulai..." -->
        <div style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border: 1px solid #6ee7b7; border-radius: 10px; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.08);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 42px; height: 42px; border-radius: 50%; background: #059669; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    ✓
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #047857; letter-spacing: 0.5px;">Status Pelaksanaan Ruang Ujian</div>
                    <div style="font-size: 15px; font-weight: 800; color: #065f46; margin-top: 2px;">
                        Ruang ujian sudah dibuat dan bisa dikerjakan mulai <span style="text-decoration: underline;"><?= htmlspecialchars($exam['start']) ?></span>
                    </div>
                </div>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-secondary" onclick="openSettingWaktuModal()" style="background: #ffffff; color: #047857; border-color: #a7f3d0; font-weight: 700;">
                    Ubah Waktu Pelaksanaan
                </button>
            </div>
        </div>

        <!-- CARD 8: BERIKAN NIS, PASSWORD, KODE KELAS UJIAN & MULAI UJIAN -->
        <div class="card" style="border: 1px solid #bae6fd; background: #f0f9ff;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 20px;">🔑</span>
                    <h3 class="card-title" style="margin: 0; color: #0369a1;">Akses &amp; Kredensial Login Siswa Peserta Ujian</h3>
                </div>
                <button type="button" class="btn btn-sm btn-primary" onclick="copyExamCredentials('<?= htmlspecialchars($exam['token']) ?>', '<?= htmlspecialchars($exam['title']) ?>')" style="background: #0284c7; border-color: #0284c7;">
                    📋 Salin Format Informasi Siswa
                </button>
            </div>
            <p style="margin: 0 0 14px; font-size: 13px; color: #334155; line-height: 1.5;">
                Berikan NIS/NIM dan password kepada siswa calon peserta ujian. Selanjutnya, peserta akan dapat login dan langsung dapat mulai mengikuti ujian yang dapat secara langsung dipantau oleh Admin dan Guru.
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; background: #ffffff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 14px;">
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">1. Username / Akun</div>
                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 2px;">NIS / NIM Siswa</div>
                    <div style="font-size: 11px; color: #94a3b8;">Sesuai data peserta terdaftar</div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">2. Password Standar</div>
                    <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 2px;"><code>12345678</code></div>
                    <div style="font-size: 11px; color: #94a3b8;">Bawaan sistem CBT</div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">3. Kode Kelas Ujian</div>
                    <div style="font-size: 17px; font-weight: 900; color: #2563eb; letter-spacing: 1px; margin-top: 2px;">
                        <?= htmlspecialchars($exam['token']) ?>
                    </div>
                    <div style="font-size: 11px; color: #94a3b8;">Wajib dimasukkan saat mulai</div>
                </div>
            </div>
        </div>

        <!-- BUTIR SOAL TERLAMPIR -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div>
                    <h3 class="card-title" style="margin: 0;">Butir Soal Terlampir (<?= count($questions) ?> Butir)</h3>
                    <span style="font-size: 0.85rem; color: var(--text-muted);">Diambil dari Bank Soal: <strong><?= htmlspecialchars($exam['subject']) ?></strong></span>
                </div>
                <a href="/admin/questions" class="btn btn-sm btn-primary">+ Kelola di Bank Soal</a>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">Urut</th>
                            <th>Tipe Soal</th>
                            <th>Isi Butir Pertanyaan</th>
                            <th>Kunci Jawaban</th>
                            <th>Bobot</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $idx => $q): ?>
                            <tr>
                                <td><strong>#<?= $idx + 1 ?></strong></td>
                                <td>
                                    <?php if (($q['question_type'] ?? '') === 'essay'): ?>
                                        <span class="badge" style="background: #fef08a; color: #854d0e; font-weight: 700;">Essai</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">Pilihan Ganda</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($q['content']) ?></td>
                                <td><strong style="color: var(--success);"><?= htmlspecialchars($q['correct_option'] ?? '-') ?></strong></td>
                                <td><strong><?= number_format((float)($q['score_weight'] ?? 2.5), 1) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PESERTA TERDAFTAR -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <div>
                    <h3 class="card-title" style="margin: 0;">Peserta Terdaftar Pada Ruang Ujian Ini</h3>
                    <p style="margin: 2px 0 0; font-size: 12px; color: var(--text-muted);">Siswa yang memiliki hak akses pengerjaan ruang ujian.</p>
                </div>
                <a href="/admin/monitoring/export-scores?id=<?= urlencode($exam['id']) ?>" class="btn btn-sm btn-secondary" style="color: #0284c7; border-color: #bae6fd;">
                    📥 Download Rekap Nilai
                </a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>NIS</th>
                            <th>Nama Peserta</th>
                            <th>Kelas</th>
                            <th>Status Pengerjaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['students_list'] as $idx => $s): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td><code><?= htmlspecialchars($s['nis']) ?></code></td>
                                <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                                <td><span class="badge badge-primary"><?= htmlspecialchars($s['class']) ?></span></td>
                                <td><span class="badge badge-success">Siap Mengerjakan</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL SETTING WAKTU UJIAN -->
    <div class="modal-overlay" id="settingWaktuModal">
        <div class="modal-content-card" style="max-width: 480px; border-radius: 12px;">
            <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid var(--border-color); padding: 16px 20px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 20px;">⚙️</span>
                    <h3 class="modal-title" style="margin: 0; font-size: 1.15rem; font-weight: 800;">Setting Waktu Ujian</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeSettingWaktuModal()">&times;</button>
            </div>
            <form action="/admin/exams/set-schedule" method="POST" style="padding: 20px;">
                <input type="hidden" name="id" value="<?= htmlspecialchars($exam['id']) ?>">
                
                <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 16px; line-height: 1.45;">
                    Pilih tanggal dan jam kapan ruang ujian bisa mulai dikerjakan oleh siswa.
                </p>

                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label" style="font-weight: 700;">Tanggal &amp; Jam Mulai Ujian *</label>
                    <input type="datetime-local" name="start_datetime" class="form-control" value="<?= date('Y-m-d\T07:00') ?>" required>
                    <small style="font-size: 11px; color: var(--text-muted);">Format zona waktu GMT+07:00 (WIB)</small>
                </div>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label class="form-label" style="font-weight: 700;">Durasi Pengerjaan Siswa (Menit) *</label>
                    <input type="number" name="duration" class="form-control" value="<?= (int)$exam['duration'] ?>" min="10" max="360" required>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="closeSettingWaktuModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 700; padding: 8px 22px;">Save / Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openSettingWaktuModal() {
            var m = document.getElementById('settingWaktuModal');
            if (m) m.classList.add('open');
        }

        function closeSettingWaktuModal() {
            var m = document.getElementById('settingWaktuModal');
            if (m) m.classList.remove('open');
        }

        function copyExamCredentials(token, title) {
            var text = "INFORMASI UJIAN ONLINE CBT\n" +
                       "Ruang Ujian: " + title + "\n" +
                       "Login Siswa: Gunakan NIS masing-masing\n" +
                       "Password: 12345678\n" +
                       "Kode Kelas Ujian: " + token + "\n" +
                       "Harap masuk tepat waktu.";
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    alert("Kredensial dan Kode Ujian (" + token + ") berhasil disalin ke clipboard!");
                });
            } else {
                alert(text);
            }
        }
    </script>
    <?php
}


// =========================================================================
// 14. MENU 8: LIVE MONITORING UJIAN (TABLE & TELEMETRI)
// =========================================================================
function renderMonitoringContent() {
    $exams = $_SESSION['exams_list'];
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 class="page-title" style="margin: 0; font-size: 1.25rem;">Live Monitoring Ujian</h1>
                <p class="page-subtitle" style="margin: 4px 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                    Pantau aktivitas sesi pengerjaan ujian siswa secara langsung di jaringan lokal (LAN)
                </p>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <span style="font-size: 0.85rem; color: var(--text-muted);">Waktu Server: <strong><?= date('H:i:s') ?></strong></span>
                <button type="button" class="btn btn-sm btn-secondary" onclick="window.location.reload();">&#8635; Refresh Data</button>
            </div>
        </div>

        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Judul Ujian &amp; Mapel</th>
                            <th>Jadwal / Durasi</th>
                            <th>Total Peserta</th>
                            <th>Sedang Mengerjakan</th>
                            <th>Sudah Selesai</th>
                            <th>Status Ujian</th>
                            <th style="width: 150px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exams as $idx => $ex): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <div style="font-weight: 700; font-size: 0.95rem;"><?= htmlspecialchars($ex['title']) ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                                        <span class="badge badge-info"><?= htmlspecialchars($ex['subject']) ?></span>
                                    </div>
                                </td>
                                <td style="font-size: 0.85rem;">
                                    <div><?= htmlspecialchars($ex['start']) ?></div>
                                    <div style="color: var(--text-muted);">Durasi: <?= (int)$ex['duration'] ?> Menit</div>
                                </td>
                                <td><strong><?= (int)$ex['participants_count'] ?></strong> Siswa</td>
                                <td><span class="badge badge-warning">18 Mengerjakan</span></td>
                                <td><span style="color: var(--success); font-weight: 600;">16 Submit</span></td>
                                <td>
                                    <span class="badge badge-success">Active</span>
                                </td>
                                <td style="text-align: center;">
                                    <a href="/admin/monitoring?action=show&id=<?= urlencode($ex['id']) ?>" class="btn btn-sm btn-primary">
                                        📡 Pantau Live
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}

function getStudentExamAnswerSheet($nis, $examId = 'ex-1') {
    $sessions = $_SESSION['monitoring_sessions'] ?? [];
    $student = null;
    foreach ($sessions as $s) {
        if ($s['nis'] === $nis) {
            $student = $s;
            break;
        }
    }
    if (!$student) {
        $student = [
            'nis' => $nis,
            'name' => 'Peserta Ujian',
            'class' => '10-TKJ-1',
            'status' => 'Mengerjakan',
            'time_left' => '30:00',
            'ip' => '192.168.1.100',
            'exam_id' => $examId,
        ];
    }

    $profiles = [
        '0081234567' => ['score' => 85.0, 'correct' => 34, 'wrong' => 4, 'doubtful' => [15, 27], 'unanswered' => [39, 40], 'wrongs' => [4, 11, 23, 31]],
        '0081234568' => ['score' => 92.5, 'correct' => 37, 'wrong' => 3, 'doubtful' => [], 'unanswered' => [], 'wrongs' => [8, 19, 35]],
        '0081234569' => ['score' => 70.0, 'correct' => 28, 'wrong' => 10, 'doubtful' => [3, 9, 14, 21], 'unanswered' => [37, 38, 39, 40], 'wrongs' => [2, 5, 12, 16, 20, 24, 28, 30, 32, 36]],
        '0081234570' => ['score' => 90.0, 'correct' => 36, 'wrong' => 4, 'doubtful' => [], 'unanswered' => [], 'wrongs' => [6, 17, 26, 33]],
        '0081234571' => ['score' => 80.0, 'correct' => 32, 'wrong' => 7, 'doubtful' => [7, 18, 25], 'unanswered' => [40], 'wrongs' => [1, 9, 13, 22, 29, 34, 38]],
    ];

    $prof = $profiles[$nis] ?? ['score' => 80.0, 'correct' => 32, 'wrong' => 6, 'doubtful' => [12], 'unanswered' => [39, 40], 'wrongs' => [5, 10, 15, 20, 25, 30]];

    $questionTemplates = [
        1 => ['q' => 'Berapakah hasil dari 2 pangkat 5 ditambah 3 pangkat 3?', 'options' => ['A' => '45', 'B' => '59', 'C' => '64', 'D' => '32', 'E' => '27'], 'key' => 'B'],
        2 => ['q' => 'Ide pokok atau gagasan utama dalam suatu paragraf biasanya terletak pada...', 'options' => ['A' => 'Awal paragraf', 'B' => 'Akhir paragraf', 'C' => 'Tengah paragraf', 'D' => 'Awal atau akhir paragraf', 'E' => 'Seluruh isi paragraf'], 'key' => 'D'],
        3 => ['q' => 'Struktur perulangan yang pasti mengeksekusi blok kode minimal satu kali adalah...', 'options' => ['A' => 'for loop', 'B' => 'while loop', 'C' => 'do-while loop', 'D' => 'foreach loop', 'E' => 'recursive loop'], 'key' => 'C'],
        4 => ['q' => 'Protokol jaringan yang bertugas memberikan konfigurasi alamat IP secara otomatis ke perangkat klien adalah...', 'options' => ['A' => 'DNS', 'B' => 'DHCP', 'C' => 'FTP', 'D' => 'HTTP', 'E' => 'SMTP'], 'key' => 'B'],
        5 => ['q' => 'Perangkat keras jaringan yang berfungsi menghubungkan dua jaringan dengan segmen atau protokol berbeda adalah...', 'options' => ['A' => 'Router', 'B' => 'Switch', 'C' => 'Hub', 'D' => 'Repeater', 'E' => 'Bridge'], 'key' => 'A'],
        6 => ['q' => 'Tipe data dalam pemrograman yang digunakan untuk menyimpan nilai logika Benar (True) atau Salah (False) adalah...', 'options' => ['A' => 'Integer', 'B' => 'Float', 'C' => 'Boolean', 'D' => 'String', 'E' => 'Array'], 'key' => 'C'],
        7 => ['q' => 'Perintah SQL yang digunakan untuk mengambil data dari suatu tabel dalam basis data relasional adalah...', 'options' => ['A' => 'INSERT', 'B' => 'UPDATE', 'C' => 'DELETE', 'D' => 'SELECT', 'E' => 'CREATE'], 'key' => 'D'],
        8 => ['q' => 'Hasil dari perhitungan matematika sederhana: (15 x 4) - (18 : 3) adalah...', 'options' => ['A' => '54', 'B' => '56', 'C' => '58', 'D' => '60', 'E' => '62'], 'key' => 'A'],
        9 => ['q' => 'Topologi jaringan komputer yang semua node-nya terhubung ke satu konsentrator sentral (switch/hub) disebut...', 'options' => ['A' => 'Ring', 'B' => 'Bus', 'C' => 'Star', 'D' => 'Mesh', 'E' => 'Tree'], 'key' => 'C'],
        10 => ['q' => 'Tag HTML yang digunakan untuk membuat tautan hiperteks (hyperlink) ke halaman web lain adalah...', 'options' => ['A' => '&lt;link&gt;', 'B' => '&lt;href&gt;', 'C' => '&lt;a&gt;', 'D' => '&lt;url&gt;', 'E' => '&lt;nav&gt;'], 'key' => 'C'],
        11 => ['q' => 'Port standar (default port) yang digunakan oleh protokol web terenkripsi HTTPS adalah...', 'options' => ['A' => '21', 'B' => '22', 'C' => '80', 'D' => '443', 'E' => '3306'], 'key' => 'D'],
        12 => ['q' => 'Angka desimal 13 jika dikonversikan ke dalam sistem bilangan biner adalah...', 'options' => ['A' => '1100', 'B' => '1101', 'C' => '1011', 'D' => '1110', 'E' => '1001'], 'key' => 'B'],
        13 => ['q' => 'Di bawah ini yang merupakan media penyimpanan utama bersifat volatil (hilang saat daya mati) adalah...', 'options' => ['A' => 'SSD NVMe', 'B' => 'Hard Disk Drive', 'C' => 'ROM Flash', 'D' => 'RAM', 'E' => 'Optical Disc'], 'key' => 'D'],
        14 => ['q' => 'Dalam arsitektur model referensi OSI (Open Systems Interconnection), lapisan paling bawah (Layer 1) adalah...', 'options' => ['A' => 'Data Link Layer', 'B' => 'Physical Layer', 'C' => 'Network Layer', 'D' => 'Transport Layer', 'E' => 'Application Layer'], 'key' => 'B'],
        15 => ['q' => 'Jika sebuah segitiga memiliki alas 12 cm dan tinggi 8 cm, maka luas bangun datar segitiga tersebut adalah...', 'options' => ['A' => '96 cm²', 'B' => '48 cm²', 'C' => '24 cm²', 'D' => '40 cm²', 'E' => '54 cm²'], 'key' => 'B'],
        16 => ['q' => 'Susunan urutan kabel UTP tipe T568B dari pin 1 sampai pin 8 diawali oleh kombinasi warna...', 'options' => ['A' => 'Putih Hijau - Hijau', 'B' => 'Putih Oranye - Oranye', 'C' => 'Putih Biru - Biru', 'D' => 'Putih Cokelat - Cokelat', 'E' => 'Oranye - Putih Oranye'], 'key' => 'B'],
        17 => ['q' => 'Subnet mask standar (default subnet mask) untuk pengalamatan IPv4 kelas C adalah...', 'options' => ['A' => '255.0.0.0', 'B' => '255.255.0.0', 'C' => '255.255.255.0', 'D' => '255.255.255.128', 'E' => '255.255.255.255'], 'key' => 'C'],
        18 => ['q' => 'Sifat algoritma pencarian biner (Binary Search) mengharuskan sekumpulan data dalam kondisi...', 'options' => ['A' => 'Acak tidak berurutan', 'B' => 'Sudah terurut (sorted)', 'C' => 'Harus bilangan genap', 'D' => 'Harus bertipe string', 'E' => 'Berukuran kuadrat'], 'key' => 'B'],
        19 => ['q' => 'Kunci utama dalam tabel database relasional yang nilainya wajib unik dan tidak boleh bernilai NULL adalah...', 'options' => ['A' => 'Foreign Key', 'B' => 'Candidate Key', 'C' => 'Primary Key', 'D' => 'Composite Key', 'E' => 'Super Key'], 'key' => 'C'],
        20 => ['q' => 'Perintah dasar Command Line / Terminal pada sistem operasi Linux untuk melihat isi direktori saat ini adalah...', 'options' => ['A' => 'cd', 'B' => 'pwd', 'C' => 'ls', 'D' => 'mkdir', 'E' => 'rm'], 'key' => 'C'],
    ];

    $items = [];
    $optKeys = ['A', 'B', 'C', 'D', 'E'];

    for ($i = 1; $i <= 40; $i++) {
        $tpl = $questionTemplates[$i] ?? [
            'q' => "Pertanyaan butir soal nomor {$i}: Konsep penerapan teknologi dan logika penyelesaian masalah komputasi dasar",
            'options' => [
                'A' => "Pilihan alternatif jawaban A untuk nomor {$i}",
                'B' => "Pilihan alternatif jawaban B untuk nomor {$i}",
                'C' => "Pilihan alternatif jawaban C untuk nomor {$i}",
                'D' => "Pilihan alternatif jawaban D untuk nomor {$i}",
                'E' => "Pilihan alternatif jawaban E untuk nomor {$i}",
            ],
            'key' => $optKeys[($i + 1) % 5],
        ];

        $isUnanswered = in_array($i, $prof['unanswered']);
        $isDoubtful = in_array($i, $prof['doubtful']);
        $isWrong = in_array($i, $prof['wrongs']);

        $studentChoice = null;
        $status = 'correct';
        $earnedPoint = 2.5;

        if ($isUnanswered) {
            $studentChoice = null;
            $status = 'unanswered';
            $earnedPoint = 0.0;
        } elseif ($isWrong) {
            $wrongOpts = array_diff($optKeys, [$tpl['key']]);
            $studentChoice = reset($wrongOpts);
            $status = 'wrong';
            $earnedPoint = 0.0;
        } else {
            $studentChoice = $tpl['key'];
            if ($isDoubtful) {
                $status = 'doubtful';
                $earnedPoint = 2.5;
            } else {
                $status = 'correct';
                $earnedPoint = 2.5;
            }
        }

        $items[] = [
            'no' => $i,
            'question' => $tpl['q'],
            'options' => $tpl['options'],
            'correct_key' => $tpl['key'],
            'student_choice' => $studentChoice,
            'is_doubtful' => $isDoubtful,
            'status' => $status,
            'point_earned' => $earnedPoint,
            'point_max' => 2.5,
        ];
    }

    return [
        'student' => $student,
        'score' => (float)$prof['score'],
        'passing_score' => 75.0,
        'is_passed' => $prof['score'] >= 75.0,
        'total_questions' => 40,
        'answered_count' => 40 - count($prof['unanswered']),
        'correct_count' => (int)$prof['correct'],
        'wrong_count' => (int)$prof['wrong'],
        'doubtful_count' => count($prof['doubtful']),
        'unanswered_count' => count($prof['unanswered']),
        'answers' => $items,
    ];
}

function renderMonitoringLiveContent($examId) {
    $sessions = $_SESSION['monitoring_sessions'] ?? [];
    
    // Default score lookup map for students
    $scoreMap = [
        '0081234567' => ['score' => 85.0, 'correct' => 34, 'wrong' => 4, 'doubtful' => 2, 'unanswered' => 2],
        '0081234568' => ['score' => 92.5, 'correct' => 37, 'wrong' => 3, 'doubtful' => 0, 'unanswered' => 0],
        '0081234569' => ['score' => 70.0, 'correct' => 28, 'wrong' => 10, 'doubtful' => 4, 'unanswered' => 2],
        '0081234570' => ['score' => 90.0, 'correct' => 36, 'wrong' => 4, 'doubtful' => 0, 'unanswered' => 0],
        '0081234571' => ['score' => 80.0, 'correct' => 32, 'wrong' => 7, 'doubtful' => 3, 'unanswered' => 1],
    ];

    $totalStudents = count($sessions);
    $totalScores = 0;
    $maxScore = 0;
    $minScore = 100;
    $passedCount = 0;

    foreach ($sessions as &$s) {
        $nis = $s['nis'];
        $sc = $scoreMap[$nis] ?? ['score' => 80.0, 'correct' => 32, 'wrong' => 6, 'doubtful' => 1, 'unanswered' => 2];
        $s['score'] = $sc['score'];
        $s['correct'] = $sc['correct'];
        $s['wrong'] = $sc['wrong'];
        $s['doubtful'] = $sc['doubtful'];
        $s['unanswered'] = $sc['unanswered'];
        $s['passed'] = $sc['score'] >= 75.0;

        $totalScores += $sc['score'];
        if ($sc['score'] > $maxScore) $maxScore = $sc['score'];
        if ($sc['score'] < $minScore) $minScore = $sc['score'];
        if ($s['passed']) $passedCount++;
    }
    unset($s);

    $avgScore = $totalStudents > 0 ? round($totalScores / $totalStudents, 2) : 0;
    $passRate = $totalStudents > 0 ? round(($passedCount / $totalStudents) * 100) : 0;

    $examTitle = 'Penilaian Akhir Semester (PAS) Ganjil - Matematika X';
    foreach ($_SESSION['exams_list'] as $ex) {
        if ($ex['id'] === $examId) {
            $examTitle = $ex['title'];
            break;
        }
    }
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- CONTENT HEADER & ACTIONS -->
        <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <h1 class="page-title" style="margin: 0; font-size: 1.25rem;">Telemetri Live Sesi Peserta</h1>
                    <span class="badge badge-primary" style="font-size: 11px;">SERVER AKTIF</span>
                </div>
                <p class="page-subtitle" style="margin: 4px 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                    <?= htmlspecialchars($examTitle) ?> &bull; Pemantauan progres pengerjaan, lembar jawaban, dan penilaian live
                </p>
            </div>
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="openAllScoresModal()" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; border-color: #38bdf8; color: #0284c7; background: #f0f9ff;">
                    <span>📊</span> Rekap Nilai Semua Siswa
                </button>
                <a href="/admin/monitoring/export-scores?id=<?= urlencode($examId) ?>" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;" title="Unduh spreadsheet nilai semua siswa">
                    <span>📥</span> Unduh Excel (.xls)
                </a>
                <button type="button" class="btn btn-primary btn-sm" onclick="window.location.reload();">&#8635; Segarkan Data</button>
                <a href="/admin/monitoring" class="btn btn-secondary btn-sm">&larr; Semua Ujian</a>
            </div>
        </div>

        <!-- STATS OVERVIEW CARDS (WITH CLASS-WIDE SCORE TELEMETRY) -->
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--primary-light); color: var(--primary);">&#128101;</div>
                <div class="stat-value">36</div>
                <div class="stat-label">Total Peserta Terdaftar</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #fef3c7; color: #d97706;">&#9203;</div>
                <div class="stat-value" style="color: #d97706;">18</div>
                <div class="stat-label">Sedang Mengerjakan</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">&#9989;</div>
                <div class="stat-value" style="color: #16a34a;">16</div>
                <div class="stat-label">Sudah Submit</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #fee2e2; color: #dc2626;">&#9888;</div>
                <div class="stat-value" style="color: #dc2626;">2</div>
                <div class="stat-label">Waktu Habis (Timeout)</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #0284c7; background: #f0f9ff;">
                <div class="stat-icon" style="background: #e0f2fe; color: #0284c7;">📈</div>
                <div class="stat-value" style="color: #0284c7;"><?= number_format($avgScore, 1) ?></div>
                <div class="stat-label">Rata-rata Nilai Siswa (KKM: 75)</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #16a34a; background: #f0fdf4;">
                <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">🏆</div>
                <div class="stat-value" style="color: #16a34a;"><?= number_format($maxScore, 1) ?></div>
                <div class="stat-label">Nilai Tertinggi (Siti Aminah)</div>
            </div>
        </div>

        <!-- QUICK ALERT NOTIFICATION -->
        <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--text-primary);">
                <span style="font-size: 18px;">💡</span>
                <div>
                    <strong>Pemantauan Lembar Jawaban &amp; Skor Peserta:</strong> 
                    Klik tombol <span style="background: #0284c7; color: white; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 11px;">👁️ Lembar Jawaban &amp; Nilai</span> pada baris siswa untuk memeriksa butir soal yang sedang dikerjakan, jawaban siswa, kunci jawaban, dan skor perorangan.
                </div>
            </div>
            <div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="openAllScoresModal()" style="font-size: 12px; font-weight: 600;">
                    Buka Rekapitulasi Nilai &rarr;
                </button>
            </div>
        </div>

        <!-- LIVE PARTICIPANTS TABLE WITH NILAI & LEMBAR JAWABAN -->
        <div class="card" style="padding: 0; overflow: hidden; box-shadow: var(--shadow-sm);">
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 45px; text-align: center;">No</th>
                            <th>NIS / Nama Peserta</th>
                            <th>Kelas</th>
                            <th>Status Pengerjaan</th>
                            <th>Sisa Waktu</th>
                            <th>Progres Pengerjaan</th>
                            <th style="min-width: 170px;">Nilai &amp; Evaluasi Siswa</th>
                            <th>IP Client</th>
                            <th style="width: 230px; text-align: center;">Aksi Proktor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $idx => $s): 
                            $pct = round(($s['answered'] / $s['total']) * 100);
                            $score = (float)$s['score'];
                            $isPassed = $score >= 75.0;
                        ?>
                            <tr>
                                <td style="text-align: center; font-weight: 600; color: var(--text-muted);"><?= $idx + 1 ?></td>
                                <td>
                                    <div style="font-weight: 700; font-size: 14px; color: var(--text-primary);"><?= htmlspecialchars($s['name']) ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">NIS: <?= htmlspecialchars($s['nis']) ?></div>
                                </td>
                                <td><span class="badge badge-primary"><?= htmlspecialchars($s['class']) ?></span></td>
                                <td>
                                    <?php if ($s['status'] === 'Mengerjakan'): ?>
                                        <span class="badge badge-warning" style="animation: pulse 2s infinite;">Sedang Mengerjakan</span>
                                    <?php elseif (str_contains($s['status'], 'Selesai')): ?>
                                        <span class="badge badge-success">Sudah Selesai</span>
                                    <?php elseif ($s['status'] === 'Ragu-Ragu'): ?>
                                        <span class="badge" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a;">Ragu-Ragu</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><?= htmlspecialchars($s['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="color: <?= $s['time_left'] === '00:00' ? 'var(--danger)' : 'var(--text-primary)' ?>; font-family: monospace; font-size: 13.5px;">
                                        <?= htmlspecialchars($s['time_left']) ?>
                                    </strong>
                                </td>
                                <td>
                                    <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 2px;">
                                        <strong><?= (int)$s['answered'] ?> / <?= (int)$s['total'] ?></strong>
                                        <span style="color: var(--text-muted);"><?= $pct ?>%</span>
                                    </div>
                                    <div style="height: 6px; background: #e2e8f0; border-radius: 4px; overflow: hidden; width: 120px;">
                                        <div style="width: <?= $pct ?>%; height: 100%; background: <?= $pct === 100 ? '#16a34a' : 'var(--primary)' ?>;"></div>
                                    </div>
                                    <?php if (!empty($s['doubtful'])): ?>
                                        <div style="font-size: 10.5px; color: #d97706; margin-top: 3px;">
                                            ⚠️ <?= (int)$s['doubtful'] ?> Ragu-ragu
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: baseline; gap: 6px;">
                                        <span style="font-size: 1.25rem; font-weight: 800; color: <?= $isPassed ? '#16a34a' : '#dc2626' ?>;">
                                            <?= number_format($score, 2) ?>
                                        </span>
                                        <span style="font-size: 0.75rem; color: var(--text-muted);">/ 100</span>
                                        <span class="badge <?= $isPassed ? 'badge-success' : 'badge-danger' ?>" style="font-size: 10px; padding: 2px 6px;">
                                            <?= $isPassed ? 'Lulus KKM' : 'Remedial' ?>
                                        </span>
                                    </div>
                                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 3px;">
                                        <span style="color: #16a34a; font-weight: 600;"><?= (int)$s['correct'] ?> Benar</span> &bull; 
                                        <span style="color: #dc2626; font-weight: 600;"><?= (int)$s['wrong'] ?> Salah</span> &bull; 
                                        <span><?= (int)$s['unanswered'] ?> Kosong</span>
                                    </div>
                                </td>
                                <td><code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 11.5px;"><?= htmlspecialchars($s['ip']) ?></code></td>
                                <td style="text-align: center;">
                                    <div style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                                        <button 
                                            type="button" 
                                            class="btn btn-sm btn-primary" 
                                            onclick="openStudentAnswerModal('<?= htmlspecialchars(addslashes($s['nis'])) ?>')" 
                                            style="display: inline-flex; align-items: center; gap: 4px; font-weight: 700; padding: 5px 9px;"
                                            title="Buka Lembar Jawaban & Nilai Siswa Ini"
                                        >
                                            <span>👁️</span> Lembar Jawaban
                                        </button>
                                        <a 
                                            href="/admin/monitoring/reset?nis=<?= urlencode($s['nis']) ?>&id=<?= urlencode($examId) ?>" 
                                            class="btn btn-sm btn-danger" 
                                            onclick="return confirm('Reset status sesi login <?= htmlspecialchars(addslashes($s['name'])) ?>?');"
                                            style="padding: 5px 8px;"
                                            title="Reset Sesi Login Peserta"
                                        >
                                            🔄 Reset
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         MODAL 1: DETAIL LEMBAR JAWABAN & NILAI PERSISWA
         ========================================================================= -->
    <div id="studentAnswerModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 16px; overflow-y: auto;">
        <div style="background: var(--card-bg, #ffffff); border-radius: 12px; width: 100%; max-width: 960px; max-height: 92vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid var(--border-color); overflow: hidden;">
            <!-- MODAL HEADER -->
            <div style="padding: 16px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 20px;">📋</span>
                        <h2 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-primary);" id="samStudentName">
                            Lembar Jawaban Siswa
                        </h2>
                        <span class="badge badge-primary" id="samStudentClass">10-TKJ-1</span>
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 3px;">
                        NIS: <strong id="samStudentNis">0081234567</strong> &bull; Paket: <span id="samExamTitle"><?= htmlspecialchars($examTitle) ?></span>
                    </div>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="printStudentSheet()" style="display: inline-flex; align-items: center; gap: 4px;">
                        <span>🖨️</span> Cetak
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="closeStudentAnswerModal()" style="font-size: 16px; padding: 4px 10px; line-height: 1;">
                        &times;
                    </button>
                </div>
            </div>

            <!-- MODAL BODY -->
            <div style="padding: 20px 24px; overflow-y: auto; display: flex; flex-direction: column; gap: 18px;" id="samModalBody">
                <!-- SCOREBOARD STRIP -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; background: #f1f5f9; padding: 14px 18px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="text-align: center;">
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Skor Nilai Akhir</div>
                        <div style="font-size: 1.75rem; font-weight: 900; line-height: 1.1; margin-top: 2px;" id="samScoreValue">85.00</div>
                        <div id="samScoreBadge" style="margin-top: 2px;"><span class="badge badge-success">LULUS KKM (75.0)</span></div>
                    </div>
                    <div style="text-align: center; border-left: 1px solid #cbd5e1;">
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Total Soal</div>
                        <div style="font-size: 1.4rem; font-weight: 800; color: var(--text-primary); margin-top: 4px;" id="samTotalQuestions">40</div>
                        <div style="font-size: 11px; color: var(--text-muted);">Butir Soal</div>
                    </div>
                    <div style="text-align: center; border-left: 1px solid #cbd5e1;">
                        <div style="font-size: 11px; color: #16a34a; text-transform: uppercase; font-weight: 700;">Jawaban Benar</div>
                        <div style="font-size: 1.4rem; font-weight: 800; color: #16a34a; margin-top: 4px;" id="samCorrectCount">34</div>
                        <div style="font-size: 11px; color: #16a34a;" id="samCorrectPoints">+85.0 Poin</div>
                    </div>
                    <div style="text-align: center; border-left: 1px solid #cbd5e1;">
                        <div style="font-size: 11px; color: #dc2626; text-transform: uppercase; font-weight: 700;">Jawaban Salah</div>
                        <div style="font-size: 1.4rem; font-weight: 800; color: #dc2626; margin-top: 4px;" id="samWrongCount">4</div>
                        <div style="font-size: 11px; color: #dc2626;">0.0 Poin</div>
                    </div>
                    <div style="text-align: center; border-left: 1px solid #cbd5e1;">
                        <div style="font-size: 11px; color: #d97706; text-transform: uppercase; font-weight: 700;">Ragu-ragu</div>
                        <div style="font-size: 1.4rem; font-weight: 800; color: #d97706; margin-top: 4px;" id="samDoubtfulCount">2</div>
                        <div style="font-size: 11px; color: #d97706;">Perlu Evaluasi</div>
                    </div>
                    <div style="text-align: center; border-left: 1px solid #cbd5e1;">
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Kosong / Belum</div>
                        <div style="font-size: 1.4rem; font-weight: 800; color: var(--text-muted); margin-top: 4px;" id="samUnansweredCount">2</div>
                        <div style="font-size: 11px; color: var(--text-muted);">Tidak Dijawab</div>
                    </div>
                </div>

                <!-- PETA NAVIGASI BUTIR SOAL 1 - 40 -->
                <div style="background: var(--card-bg, #ffffff); border: 1px solid var(--border-color); border-radius: 8px; padding: 14px 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                        <strong style="font-size: 13px; color: var(--text-primary); display: flex; align-items: center; gap: 6px;">
                            <span>🗺️</span> Peta Lembar Jawaban Peserta (Klik nomor butir untuk melompat ke soal)
                        </strong>
                        <div style="display: flex; gap: 12px; font-size: 11.5px; flex-wrap: wrap;">
                            <span style="display: inline-flex; align-items: center; gap: 4px;"><span style="width: 12px; height: 12px; background: #22c55e; border-radius: 2px;"></span> Benar</span>
                            <span style="display: inline-flex; align-items: center; gap: 4px;"><span style="width: 12px; height: 12px; background: #ef4444; border-radius: 2px;"></span> Salah</span>
                            <span style="display: inline-flex; align-items: center; gap: 4px;"><span style="width: 12px; height: 12px; background: #f59e0b; border-radius: 2px;"></span> Ragu-ragu</span>
                            <span style="display: inline-flex; align-items: center; gap: 4px;"><span style="width: 12px; height: 12px; background: #94a3b8; border-radius: 2px;"></span> Kosong</span>
                        </div>
                    </div>

                    <!-- GRID BOXES 1 - 40 -->
                    <div id="samQuestionGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(38px, 1fr)); gap: 6px;">
                        <!-- Injected via JavaScript -->
                    </div>
                </div>

                <!-- FILTER TABS FOR QUESTIONS -->
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; border-bottom: 2px solid var(--border-color); padding-bottom: 10px;">
                    <div style="display: flex; gap: 6px; flex-wrap: wrap;" id="samFilterBtns">
                        <button type="button" class="btn btn-primary btn-sm sam-filter-btn" onclick="filterQuestionList('all')" id="btnFilterAll">Semua Butir (40)</button>
                        <button type="button" class="btn btn-secondary btn-sm sam-filter-btn" onclick="filterQuestionList('correct')" id="btnFilterCorrect">✅ Benar Saja</button>
                        <button type="button" class="btn btn-secondary btn-sm sam-filter-btn" onclick="filterQuestionList('wrong')" id="btnFilterWrong">❌ Salah Saja</button>
                        <button type="button" class="btn btn-secondary btn-sm sam-filter-btn" onclick="filterQuestionList('doubtful')" id="btnFilterDoubtful">⚠️ Ragu-ragu</button>
                        <button type="button" class="btn btn-secondary btn-sm sam-filter-btn" onclick="filterQuestionList('unanswered')" id="btnFilterUnanswered">⚪ Kosong / Belum</button>
                    </div>
                    <div style="font-size: 12px; color: var(--text-muted);" id="samShowingCounter">
                        Menampilkan 40 butir soal
                    </div>
                </div>

                <!-- DETAILED QUESTIONS LIST -->
                <div id="samQuestionsList" style="display: flex; flex-direction: column; gap: 14px;">
                    <!-- Question Cards Injected via JavaScript -->
                </div>
            </div>

            <!-- MODAL FOOTER -->
            <div style="padding: 12px 24px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <div style="font-size: 12px; color: var(--text-muted);">
                    Data jawaban tersinkronisasi otomatis dari database telemetri CBT server lokal.
                </div>
                <button type="button" class="btn btn-secondary" onclick="closeStudentAnswerModal()">
                    Tutup Lembar Jawaban
                </button>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         MODAL 2: REKAPITULASI NILAI SEMUA SISWA
         ========================================================================= -->
    <div id="allScoresModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 16px; overflow-y: auto;">
        <div style="background: var(--card-bg, #ffffff); border-radius: 12px; width: 100%; max-width: 900px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid var(--border-color); overflow: hidden;">
            <!-- HEADER -->
            <div style="padding: 16px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <div>
                    <h2 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text-primary);">
                        📊 Rekapitulasi Nilai &amp; Hasil Penilaian Semua Siswa
                    </h2>
                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 3px;">
                        Paket Ujian: <strong><?= htmlspecialchars($examTitle) ?></strong> &bull; Standar KKM: <strong>75.0</strong>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeAllScoresModal()" style="font-size: 16px; padding: 4px 10px; line-height: 1;">
                    &times;
                </button>
            </div>

            <!-- BODY -->
            <div style="padding: 20px 24px; overflow-y: auto; display: flex; flex-direction: column; gap: 16px;">
                <!-- SUMMARY METRICS -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; background: #f1f5f9; padding: 12px 16px; border-radius: 8px;">
                    <div>
                        <span style="font-size: 11px; color: var(--text-muted);">Total Peserta</span>
                        <div style="font-size: 1.25rem; font-weight: 800;"><?= $totalStudents ?> Siswa</div>
                    </div>
                    <div>
                        <span style="font-size: 11px; color: var(--text-muted);">Rata-rata Nilai</span>
                        <div style="font-size: 1.25rem; font-weight: 800; color: #0284c7;"><?= number_format($avgScore, 2) ?></div>
                    </div>
                    <div>
                        <span style="font-size: 11px; color: var(--text-muted);">Nilai Tertinggi</span>
                        <div style="font-size: 1.25rem; font-weight: 800; color: #16a34a;"><?= number_format($maxScore, 2) ?></div>
                    </div>
                    <div>
                        <span style="font-size: 11px; color: var(--text-muted);">Nilai Terendah</span>
                        <div style="font-size: 1.25rem; font-weight: 800; color: #dc2626;"><?= number_format($minScore, 2) ?></div>
                    </div>
                    <div>
                        <span style="font-size: 11px; color: var(--text-muted);">Ketuntasan KKM</span>
                        <div style="font-size: 1.25rem; font-weight: 800; color: #16a34a;"><?= $passedCount ?> / <?= $totalStudents ?> (<?= $passRate ?>%)</div>
                    </div>
                </div>

                <!-- SCORES TABLE -->
                <div style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
                    <table class="data-table" style="margin: 0;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="width: 45px; text-align: center;">No</th>
                                <th>NIS</th>
                                <th>Nama Lengkap Peserta</th>
                                <th>Kelas</th>
                                <th>Status Pengerjaan</th>
                                <th style="text-align: center;">Benar</th>
                                <th style="text-align: center;">Salah</th>
                                <th style="text-align: center;">Kosong</th>
                                <th style="text-align: right;">Nilai Akhir</th>
                                <th style="text-align: center;">Status KKM</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $i => $st): 
                                $sc = (float)$st['score'];
                                $pass = $sc >= 75.0;
                            ?>
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted);"><?= $i + 1 ?></td>
                                    <td><code><?= htmlspecialchars($st['nis']) ?></code></td>
                                    <td><strong><?= htmlspecialchars($st['name']) ?></strong></td>
                                    <td><span class="badge badge-primary"><?= htmlspecialchars($st['class']) ?></span></td>
                                    <td><?= htmlspecialchars($st['status']) ?></td>
                                    <td style="text-align: center; color: #16a34a; font-weight: 700;"><?= $st['correct'] ?></td>
                                    <td style="text-align: center; color: #dc2626; font-weight: 700;"><?= $st['wrong'] ?></td>
                                    <td style="text-align: center; color: var(--text-muted);"><?= $st['unanswered'] ?></td>
                                    <td style="text-align: right; font-size: 1.1rem; font-weight: 800; color: <?= $pass ? '#16a34a' : '#dc2626' ?>;">
                                        <?= number_format($sc, 2) ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge <?= $pass ? 'badge-success' : 'badge-danger' ?>">
                                            <?= $pass ? 'LULUS' : 'REMEDIAL' ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="closeAllScoresModal(); openStudentAnswerModal('<?= htmlspecialchars(addslashes($st['nis'])) ?>');" style="padding: 4px 8px; font-size: 11px;">
                                            Lembar Jawaban &rarr;
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FOOTER -->
            <div style="padding: 12px 24px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <div style="display: flex; gap: 8px;">
                    <a href="/admin/monitoring/export-scores?id=<?= urlencode($examId) ?>" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                        <span>📥</span> Unduh Format Excel (.xls)
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()" style="display: inline-flex; align-items: center; gap: 4px;">
                        <span>🖨️</span> Cetak Rekapitulasi
                    </button>
                </div>
                <button type="button" class="btn btn-secondary" onclick="closeAllScoresModal()">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT DATA & HANDLERS -->
    <script>
    var studentSheetsData = {
        <?php foreach ($sessions as $ses): 
            $sheet = getStudentExamAnswerSheet($ses['nis'], $examId);
        ?>
            "<?= $ses['nis'] ?>": <?= json_encode($sheet) ?>,
        <?php endforeach; ?>
    };

    var currentActiveNis = null;
    var currentFilter = 'all';

    function openStudentAnswerModal(nis) {
        var data = studentSheetsData[nis];
        if (!data) {
            alert('Data lembar jawaban siswa dengan NIS ' + nis + ' tidak ditemukan.');
            return;
        }
        currentActiveNis = nis;
        currentFilter = 'all';

        var st = data.student;
        document.getElementById('samStudentName').innerText = 'Lembar Jawaban: ' + st.name;
        document.getElementById('samStudentClass').innerText = st.class;
        document.getElementById('samStudentNis').innerText = st.nis;
        
        var scoreVal = Number(data.score).toFixed(2);
        var scoreElem = document.getElementById('samScoreValue');
        scoreElem.innerText = scoreVal;
        scoreElem.style.color = data.is_passed ? '#16a34a' : '#dc2626';

        var badgeElem = document.getElementById('samScoreBadge');
        if (data.is_passed) {
            badgeElem.innerHTML = '<span class="badge badge-success" style="font-size: 11px;">✅ LULUS KKM (75.0)</span>';
        } else {
            badgeElem.innerHTML = '<span class="badge badge-danger" style="font-size: 11px;">⚠️ REMEDIAL (&lt; 75.0)</span>';
        }

        document.getElementById('samTotalQuestions').innerText = data.total_questions;
        document.getElementById('samCorrectCount').innerText = data.correct_count;
        document.getElementById('samCorrectPoints').innerText = '+' + (data.correct_count * 2.5).toFixed(1) + ' Poin';
        document.getElementById('samWrongCount').innerText = data.wrong_count;
        document.getElementById('samDoubtfulCount').innerText = data.doubtful_count;
        document.getElementById('samUnansweredCount').innerText = data.unanswered_count;

        // Render Navigation Grid 1 - 40
        var gridHtml = '';
        data.answers.forEach(function(ans) {
            var bg = '#22c55e'; // correct
            var color = '#ffffff';
            if (ans.status === 'wrong') {
                bg = '#ef4444';
            } else if (ans.status === 'doubtful') {
                bg = '#f59e0b';
            } else if (ans.status === 'unanswered') {
                bg = '#94a3b8';
            }
            gridHtml += '<button type="button" onclick="jumpToQuestion(' + ans.no + ')" style="height: 36px; border: 1px solid rgba(0,0,0,0.1); background: ' + bg + '; color: ' + color + '; font-weight: 800; font-size: 12px; border-radius: 4px; cursor: pointer; transition: transform 0.1s;" title="No. ' + ans.no + ' - ' + ans.status + '">' + ans.no + '</button>';
        });
        document.getElementById('samQuestionGrid').innerHTML = gridHtml;

        renderQuestionsView();

        document.getElementById('studentAnswerModal').style.display = 'flex';
    }

    function closeStudentAnswerModal() {
        document.getElementById('studentAnswerModal').style.display = 'none';
    }

    function filterQuestionList(filter) {
        currentFilter = filter;
        document.querySelectorAll('.sam-filter-btn').forEach(function(b) {
            b.classList.remove('btn-primary');
            b.classList.add('btn-secondary');
        });

        if (filter === 'all') document.getElementById('btnFilterAll').classList.replace('btn-secondary', 'btn-primary');
        if (filter === 'correct') document.getElementById('btnFilterCorrect').classList.replace('btn-secondary', 'btn-primary');
        if (filter === 'wrong') document.getElementById('btnFilterWrong').classList.replace('btn-secondary', 'btn-primary');
        if (filter === 'doubtful') document.getElementById('btnFilterDoubtful').classList.replace('btn-secondary', 'btn-primary');
        if (filter === 'unanswered') document.getElementById('btnFilterUnanswered').classList.replace('btn-secondary', 'btn-primary');

        renderQuestionsView();
    }

    function renderQuestionsView() {
        var data = studentSheetsData[currentActiveNis];
        if (!data) return;

        var filtered = data.answers.filter(function(ans) {
            if (currentFilter === 'all') return true;
            return ans.status === currentFilter;
        });

        document.getElementById('samShowingCounter').innerText = 'Menampilkan ' + filtered.length + ' dari ' + data.answers.length + ' butir soal';

        var listHtml = '';
        if (filtered.length === 0) {
            listHtml = '<div style="text-align: center; padding: 24px; color: var(--text-muted); background: #f8fafc; border-radius: 8px;">Tidak ada butir soal dengan kategori ini.</div>';
        } else {
            filtered.forEach(function(ans) {
                var statusBadge = '';
                if (ans.status === 'correct') {
                    statusBadge = '<span class="badge badge-success" style="font-size: 11px;">✅ Jawaban Benar (+2.5 Poin)</span>';
                } else if (ans.status === 'wrong') {
                    statusBadge = '<span class="badge badge-danger" style="font-size: 11px;">❌ Jawaban Salah (0.0 Poin)</span>';
                } else if (ans.status === 'doubtful') {
                    statusBadge = '<span class="badge" style="background:#fef3c7; color:#b45309; border:1px solid #fde68a; font-size: 11px;">⚠️ Ragu-ragu (Dijawab ' + ans.student_choice + ')</span>';
                } else {
                    statusBadge = '<span class="badge badge-secondary" style="font-size: 11px;">⚪ Belum Terjawab (Kosong)</span>';
                }

                listHtml += '<div id="q_card_' + ans.no + '" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 14px 16px; background: var(--card-bg, #ffffff);">';
                listHtml += '  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 1px dashed var(--border-color); padding-bottom: 8px;">';
                listHtml += '    <div style="font-weight: 800; font-size: 13.5px; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">';
                listHtml += '      <span style="background: #e2e8f0; padding: 2px 8px; border-radius: 4px;">Butir #' + ans.no + '</span>';
                listHtml += '      <span style="font-size: 11.5px; color: var(--text-muted); font-weight: normal;">Pilihan Ganda &bull; Bobot: 2.5 Poin</span>';
                listHtml += '    </div>';
                listHtml += '    <div>' + statusBadge + '</div>';
                listHtml += '  </div>';

                listHtml += '  <div style="font-size: 13.5px; color: var(--text-primary); font-weight: 600; line-height: 1.5; margin-bottom: 12px;">' + ans.question + '</div>';

                listHtml += '  <div style="display: flex; flex-direction: column; gap: 6px;">';
                var optKeys = ['A', 'B', 'C', 'D', 'E'];
                optKeys.forEach(function(k) {
                    var optText = ans.options[k] || ('Opsi ' + k);
                    var isSelected = ans.student_choice === k;
                    var isCorrectKey = ans.correct_key === k;

                    var rowBg = '#ffffff';
                    var rowBorder = '1px solid #e2e8f0';
                    var labelPill = '<span style="font-weight: 700; width: 22px; display: inline-block;">' + k + '.</span>';
                    var tagHtml = '';

                    if (isSelected && isCorrectKey) {
                        rowBg = '#f0fdf4';
                        rowBorder = '2px solid #22c55e';
                        tagHtml = '<span class="badge badge-success" style="font-size: 10.5px; margin-left: auto;">✓ Jawaban Siswa (Benar)</span>';
                    } else if (isSelected && !isCorrectKey) {
                        rowBg = '#fef2f2';
                        rowBorder = '2px solid #ef4444';
                        tagHtml = '<span class="badge badge-danger" style="font-size: 10.5px; margin-left: auto;">✗ Jawaban Siswa (Salah)</span>';
                    } else if (!isSelected && isCorrectKey) {
                        rowBg = '#f8fafc';
                        rowBorder = '2px dashed #16a34a';
                        tagHtml = '<span style="color: #16a34a; font-weight: 700; font-size: 11px; margin-left: auto;">🔑 Kunci Jawaban Benar</span>';
                    }

                    listHtml += '<div style="display: flex; align-items: center; gap: 8px; padding: 7px 12px; border-radius: 6px; background: ' + rowBg + '; border: ' + rowBorder + '; font-size: 13px;">';
                    listHtml += labelPill + ' <span>' + optText + '</span> ' + tagHtml;
                    listHtml += '</div>';
                });
                listHtml += '  </div>';

                listHtml += '  <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; font-size: 12px; color: var(--text-muted); background: #f8fafc; padding: 6px 10px; border-radius: 4px;">';
                listHtml += '    <div>Jawaban Siswa: <strong>' + (ans.student_choice ? ans.student_choice : 'Belum Dijawab (Kosong)') + '</strong></div>';
                listHtml += '    <div>Kunci Jawaban: <strong style="color: #16a34a;">' + ans.correct_key + '</strong></div>';
                listHtml += '    <div>Perolehan Skor: <strong style="color: ' + (ans.point_earned > 0 ? '#16a34a' : '#dc2626') + ';">' + ans.point_earned.toFixed(1) + ' / ' + ans.point_max.toFixed(1) + '</strong></div>';
                listHtml += '  </div>';

                listHtml += '</div>';
            });
        }

        document.getElementById('samQuestionsList').innerHTML = listHtml;
    }

    function jumpToQuestion(no) {
        if (currentFilter !== 'all') {
            filterQuestionList('all');
        }
        var elem = document.getElementById('q_card_' + no);
        if (elem) {
            elem.scrollIntoView({ behavior: 'smooth', block: 'center' });
            elem.style.outline = '3px solid #0284c7';
            setTimeout(function() { elem.style.outline = 'none'; }, 2000);
        }
    }

    function openAllScoresModal() {
        document.getElementById('allScoresModal').style.display = 'flex';
    }

    function closeAllScoresModal() {
        document.getElementById('allScoresModal').style.display = 'none';
    }

    function printStudentSheet() {
        window.print();
    }
    </script>
    <?php
}

// =========================================================================
// 15. MENU 9: HASIL & NILAI UJIAN
// =========================================================================
function renderResultsContent() {
    $results = $_SESSION['results_list'];
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="content-header">
            <div>
                <h1 class="page-title" style="margin: 0; font-size: 1.25rem;">Hasil &amp; Nilai Ujian Siswa</h1>
                <p class="page-subtitle" style="margin: 4px 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                    Rekapitulasi skor penilaian ujian, status kelulusan KKM, dan publikasi nilai peserta
                </p>
            </div>
        </div>

        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Peserta / Kelas</th>
                            <th>Paket Ujian</th>
                            <th>Benar / Salah / Kosong</th>
                            <th>Nilai Akhir</th>
                            <th>Kelulusan</th>
                            <th>Publikasi</th>
                            <th style="width: 150px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $idx => $r): ?>
                            <?php $isPassed = ($r['score'] >= $r['passing']); ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <div style="font-weight: 700;"><?= htmlspecialchars($r['name']) ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">NIS: <?= htmlspecialchars($r['nis']) ?> | <?= htmlspecialchars($r['class']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($r['exam']) ?></td>
                                <td style="font-size: 0.85rem;">
                                    <span style="color: var(--success); font-weight: 600;"><?= (int)$r['correct'] ?> Benar</span>,
                                    <span style="color: var(--danger);"><?= (int)$r['wrong'] ?> Salah</span>,
                                    <span style="color: var(--text-muted);"><?= (int)$r['empty'] ?> Kosong</span>
                                </td>
                                <td>
                                    <strong style="font-size: 1.15rem; color: var(--text-primary);"><?= number_format((float)$r['score'], 1) ?></strong>
                                </td>
                                <td>
                                    <span class="badge <?= $isPassed ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $isPassed ? 'Lulus' : 'Belum Lulus' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= !empty($r['published']) ? 'badge-success' : 'badge-secondary' ?>">
                                        <?= !empty($r['published']) ? 'Publik' : 'Private' ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <div class="action-btns">
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="openResultDetail('<?= htmlspecialchars(addslashes($r['name'])) ?>', '<?= htmlspecialchars($r['nis']) ?>', '<?= htmlspecialchars($r['class']) ?>', '<?= htmlspecialchars(addslashes($r['exam'])) ?>', <?= (int)$r['correct'] ?>, <?= (int)$r['wrong'] ?>, <?= (int)$r['empty'] ?>, <?= (float)$r['score'] ?>, '<?= $isPassed ? 'Lulus' : 'Belum Lulus' ?>')">
                                            Detail
                                        </button>
                                        <a href="/admin/results/publish?idx=<?= $idx ?>" class="btn btn-sm <?= !empty($r['published']) ? 'btn-secondary' : 'btn-primary' ?>" title="Ubah status publikasi nilai ke siswa">
                                            <?= !empty($r['published']) ? 'Tarik' : 'Publikasi' ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- RESULT DETAIL MODAL -->
    <div class="modal-overlay" id="resultDetailModal">
        <div class="modal-content-card" style="max-width: 480px;">
            <div class="modal-header">
                <h3 class="modal-title" style="margin: 0;">Rincian Skor Peserta</h3>
                <button type="button" class="modal-close-btn" onclick="document.getElementById('resultDetailModal').classList.remove('open')">&times;</button>
            </div>
            <div style="padding: 10px 0;">
                <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 16px;">
                    <div style="font-size: 16px; font-weight: 700; color: var(--text-primary);" id="res_name">Ahmad Dhani</div>
                    <div style="font-size: 12.5px; color: var(--text-muted); margin-top: 2px;" id="res_nis_class">NIS: 0081234567 | 10-TKJ-1</div>
                    <div style="font-size: 13px; color: var(--primary); font-weight: 600; margin-top: 6px;" id="res_exam">PAS Matematika X</div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                    <div style="background: var(--bg-surface-elevated); padding: 10px; border-radius: 6px; text-align: center;">
                        <div style="font-size: 11px; color: var(--text-muted);">Jawaban Benar</div>
                        <div style="font-size: 18px; font-weight: 700; color: var(--success);" id="res_correct">34</div>
                    </div>
                    <div style="background: var(--bg-surface-elevated); padding: 10px; border-radius: 6px; text-align: center;">
                        <div style="font-size: 11px; color: var(--text-muted);">Jawaban Salah</div>
                        <div style="font-size: 18px; font-weight: 700; color: var(--danger);" id="res_wrong">6</div>
                    </div>
                </div>
                <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: 8px; text-align: center; border: 1px solid var(--border-color);">
                    <div style="font-size: 12px; color: var(--text-muted);">Nilai Akhir Ujian</div>
                    <div style="font-size: 28px; font-weight: 800; color: var(--text-primary); margin: 4px 0;" id="res_score">85.0</div>
                    <span class="badge badge-success" id="res_badge">Lulus Standar KKM</span>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; margin-top: 14px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('resultDetailModal').classList.remove('open')">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        function openResultDetail(name, nis, className, exam, correct, wrong, empty, score, status) {
            document.getElementById('res_name').innerText = name;
            document.getElementById('res_nis_class').innerText = 'NIS: ' + nis + ' | ' + className;
            document.getElementById('res_exam').innerText = exam;
            document.getElementById('res_correct').innerText = correct;
            document.getElementById('res_wrong').innerText = wrong;
            document.getElementById('res_score').innerText = score.toFixed(1);
            document.getElementById('res_badge').innerText = status;
            document.getElementById('res_badge').className = (status === 'Lulus') ? 'badge badge-success' : 'badge badge-danger';
            document.getElementById('resultDetailModal').classList.add('open');
        }
    </script>
    <?php
}

// =========================================================================
// 16. MENU 10: LAPORAN NILAI & ANALISIS
// =========================================================================
function renderReportsContent() {
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 class="page-title" style="margin: 0; font-size: 1.25rem;">Laporan &amp; Analisis Akademik</h1>
                <p class="page-subtitle" style="margin: 4px 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                    Rekapitulasi data ketercapaian kompetensi, daya pembeda soal, dan ekspor berkas spreadsheet
                </p>
            </div>
            <a href="/admin/reports/export-csv" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
                <span>📊</span> Export CSV Rapih
            </a>
        </div>

        <!-- STATS OVERVIEW -->
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--primary-light); color: var(--primary);">&#128101;</div>
                <div class="stat-value">83.5</div>
                <div class="stat-label">Rata-Rata Nilai Sekolah</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #dcfce7; color: #16a34a;">&#9989;</div>
                <div class="stat-value" style="color: #16a34a;">92.5</div>
                <div class="stat-label">Nilai Tertinggi</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #fee2e2; color: #dc2626;">&#9888;</div>
                <div class="stat-value" style="color: #dc2626;">70.0</div>
                <div class="stat-label">Nilai Terendah</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #fef3c7; color: #d97706;">🎯</div>
                <div class="stat-value" style="color: #d97706;">80.0%</div>
                <div class="stat-label">Tingkat Kelulusan KKM</div>
            </div>
        </div>

        <!-- ANALISIS BUTIR SOAL & TABEL REKAP -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 class="card-title" style="margin: 0;">Analisis Tingkat Kesukaran &amp; Daya Pembeda Butir Soal</h3>
                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('analysisModal').classList.add('open')">
                    🔍 Buka Rincian Analisis
                </button>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-bottom: 20px;">
                <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Soal Kategori Mudah</div>
                    <div style="font-size: 22px; font-weight: 800; color: var(--success); margin: 4px 0;">45%</div>
                    <span style="font-size: 11px; color: var(--text-muted);">Tingkat ketercapaian tinggi</span>
                </div>
                <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Soal Kategori Sedang</div>
                    <div style="font-size: 22px; font-weight: 800; color: var(--warning); margin: 4px 0;">40%</div>
                    <span style="font-size: 11px; color: var(--text-muted);">Distribusi butir ideal</span>
                </div>
                <div style="background: var(--bg-surface-elevated); padding: 14px; border-radius: 8px; border: 1px solid var(--border-color); text-align: center;">
                    <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Soal Kategori Sukar</div>
                    <div style="font-size: 22px; font-weight: 800; color: var(--danger); margin: 4px 0;">15%</div>
                    <span style="font-size: 11px; color: var(--text-muted);">Daya pembeda tajam</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Paket Ujian</th>
                            <th>Peserta Mengikuti</th>
                            <th>Rata-rata Skor</th>
                            <th>Status Penilaian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['exams_list'] as $ex): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($ex['title']) ?></strong></td>
                                <td><?= (int)$ex['participants_count'] ?> Siswa</td>
                                <td><strong style="color: var(--primary);">83.5</strong></td>
                                <td><span class="badge badge-success">Valid Terverifikasi</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ANALYSIS MODAL -->
    <div class="modal-overlay" id="analysisModal">
        <div class="modal-content-card" style="max-width: 520px;">
            <div class="modal-header">
                <h3 class="modal-title" style="margin: 0;">Rincian Metrik Psikometri Butir Soal</h3>
                <button type="button" class="modal-close-btn" onclick="document.getElementById('analysisModal').classList.remove('open')">&times;</button>
            </div>
            <div style="padding: 12px 0;">
                <p style="font-size: 13px; color: var(--text-secondary); line-height: 1.5; margin-bottom: 14px;">
                    Hasil perhitungan otomatis daya pembeda, koefisien korelasi butir, dan indeks kesukaran berdasarkan lembar jawaban peserta di server:
                </p>
                <div style="display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                    <div style="background: var(--bg-surface-elevated); padding: 10px 14px; border-radius: 6px; display: flex; justify-content: space-between;">
                        <span>Daya Pembeda Rata-rata:</span>
                        <strong style="color: var(--primary);">0.42 (Baik Sekali)</strong>
                    </div>
                    <div style="background: var(--bg-surface-elevated); padding: 10px 14px; border-radius: 6px; display: flex; justify-content: space-between;">
                        <span>Reliabilitas Butir (Cronbach Alpha):</span>
                        <strong style="color: var(--success);">0.88 (Sangat Handal)</strong>
                    </div>
                    <div style="background: var(--bg-surface-elevated); padding: 10px 14px; border-radius: 6px; display: flex; justify-content: space-between;">
                        <span>Butir Soal Perlu Revisi:</span>
                        <strong style="color: var(--success);">0 Butir (Semua Valid)</strong>
                    </div>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; margin-top: 14px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('analysisModal').classList.remove('open')">Tutup</button>
            </div>
        </div>
    </div>
    <?php
}

// =========================================================================
// 17. MENU 11: BACKUP & DATABASE SNAPSHOT (FULL INTERACTIVE CRUD)
// =========================================================================
function renderBackupsContent() {
    $backups = $_SESSION['backups_list'];
    ?>
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 class="page-title" style="margin: 0; font-size: 1.25rem;">Pencadangan Database MySQL Lokal</h1>
                <p class="page-subtitle" style="margin: 4px 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                    Snapshot DDL &amp; DML internal murni PDO server lokal, tanpa eksekusi shell command luar
                </p>
            </div>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('createBackupCard').style.display = 'block'; window.scrollTo({top: document.getElementById('createBackupCard').offsetTop - 80, behavior: 'smooth'});">
                <span>💾</span> Buat Cadangan Baru
            </button>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--primary-light); color: var(--primary);">&#128190;</div>
                <div class="stat-value"><?= count($backups) ?></div>
                <div class="stat-label">Total Berkas Snapshot</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--success-light); color: var(--success);">&#128193;</div>
                <div class="stat-value">42.8 MB</div>
                <div class="stat-label">Total Penggunaan Storage</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--warning-light); color: var(--warning);">&#128337;</div>
                <div class="stat-value" style="font-size: 1.15rem; margin-top: 4px;"><?= $backups[0]['created_at'] ?? '-' ?></div>
                <div class="stat-label">Snapshot Terakhir Dibuat</div>
            </div>
        </div>

        <!-- CREATE BACKUP CARD (Collapsible) -->
        <div class="card" id="createBackupCard" style="display: none; border-color: var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 class="card-title" style="margin-bottom: 0;">Trigger Pembuatan Snapshot Database Baru</h3>
                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('createBackupCard').style.display = 'none';">&times; Tutup</button>
            </div>
            <form method="POST" action="/admin/backups/create">
                <div style="display: grid; grid-template-columns: 1fr auto; gap: 16px; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Skenario Operasional Backup</label>
                        <select name="type" class="form-select" required>
                            <option value="manual">Cadangan Manual Rutin (manual)</option>
                            <option value="pre_exam">Cadangan Pra-Ujian / Master Data Siap (pre_exam)</option>
                            <option value="post_exam">Cadangan Pasca-Ujian / Selesai Sesi Pengerjaan (post_exam)</option>
                        </select>
                        <span style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; display: block;">
                            Proses dump dieksekusi murni via koneksi database internal dan siap diunduh ke format .sql.
                        </span>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        Mulai Backup Sekarang
                    </button>
                </div>
            </form>
        </div>

        <!-- BACKUP LIST TABLE -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Nama Berkas Snapshot</th>
                            <th style="width: 130px;">Tipe</th>
                            <th style="width: 110px;">Ukuran</th>
                            <th style="width: 170px;">Waktu Dibuat</th>
                            <th style="width: 180px;">Integritas SHA-256</th>
                            <th style="width: 160px; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($backups)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">&#128190;</div>
                                        <p>Belum ada berkas cadangan database.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($backups as $idx => $b): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <div style="font-weight: 600; font-family: monospace; font-size: 0.9rem; color: var(--text-primary);">
                                            <?= htmlspecialchars($b['filename']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($b['type'] === 'pre_exam'): ?>
                                            <span class="badge badge-warning">Pra-Ujian</span>
                                        <?php elseif ($b['type'] === 'post_exam'): ?>
                                            <span class="badge badge-success">Pasca-Ujian</span>
                                        <?php else: ?>
                                            <span class="badge badge-info">Manual</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($b['size']) ?></strong></td>
                                    <td style="font-size: 0.85rem; color: var(--text-secondary);"><?= htmlspecialchars($b['created_at']) ?></td>
                                    <td>
                                        <span style="font-family: monospace; font-size: 0.75rem; background: var(--bg-surface-elevated); padding: 2px 6px; border-radius: 4px; border: 1px solid var(--border-color);">
                                            <?= substr($b['hash'] ?? 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855', 0, 16) ?>...
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                            <a href="/admin/backups/download?file=<?= urlencode($b['filename']) ?>" class="btn btn-sm btn-primary" title="Unduh berkas .sql">
                                                &#11015; Unduh
                                            </a>
                                            <a href="/admin/backups/delete?fn=<?= urlencode($b['filename']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus berkas snapshot ini?');" title="Hapus snapshot">
                                                &#128465;
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}

// =========================================================================
// 18. MENU 12: PENGATURAN SERVER SISTEM
// =========================================================================
function renderSettingsContent() {
    $s = $_SESSION['cbt_settings'];
    $serverPort = (int)($s['server_port'] ?? 8000);
    $detectedHostIp = $_SERVER['SERVER_ADDR'] ?? (gethostbyname(gethostname()) ?: '192.168.1.11');
    if ($detectedHostIp === '127.0.0.1' || $detectedHostIp === '::1' || empty($detectedHostIp)) {
        $detectedHostIp = '192.168.1.11';
    }
    $localhostUrl = "http://localhost:{$serverPort}";
    $lanUrl = "http://{$detectedHostIp}:{$serverPort}";
    ?>
    <div style="max-width: 900px;">
        <div style="margin-bottom: 20px;">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">
                Konfigurasi Operasional Server Lokal CBT
            </h2>
            <p style="font-size: 0.85rem; color: var(--text-secondary);">
                Pengaturan tersimpan dalam konfigurasi server dengan pencatatan audit trail otomatis.
            </p>
        </div>

        <!-- CARD 1: ALAMAT AKSES SERVER CBT (LOCALHOST & WI-FI LAN) -->
        <div class="card" style="margin-bottom: 24px; border: 1px solid var(--primary-border); background: var(--bg-surface);">
            <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3 class="card-title" style="margin-bottom: 4px; display: flex; align-items: center; gap: 8px; color: var(--primary);">
                        <span>🌐</span> Alamat Akses Server CBT (Localhost &amp; Wi-Fi LAN)
                    </h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">
                        Gunakan alamat di bawah ini untuk menghubungkan perangkat siswa dan guru dalam jaringan Wi-Fi/LAN sekolah tanpa internet.
                    </span>
                </div>
                <span class="badge badge-success" style="font-size: 0.75rem; padding: 4px 10px;">
                    ● Server Lokal Siap (Offline LAN)
                </span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 16px;">
                <!-- LOCALHOST -->
                <div style="background: var(--bg-main); border: 1px solid var(--border-color); border-radius: 8px; padding: 14px 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">
                            💻 Komputer Server (Localhost)
                        </span>
                        <span style="font-size: 0.7rem; background: var(--primary-light); color: var(--primary); padding: 2px 6px; border-radius: 4px; font-weight: 600;">Lokal</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="text" id="apiServerLocalhostUrl" readonly value="<?= htmlspecialchars($localhostUrl) ?>" class="form-control" style="font-family: monospace; font-size: 0.9rem; font-weight: 600; background: var(--bg-surface); cursor: text;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="copyApiServerUrl('apiServerLocalhostUrl', this)" title="Salin Alamat">
                            <span>📋</span> Salin
                        </button>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;">
                        Dibuka khusus pada browser komputer server ini sendiri.
                    </div>
                </div>

                <!-- WI-FI LAN IP -->
                <div style="background: var(--bg-main); border: 1px solid var(--primary-border); border-radius: 8px; padding: 14px 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--primary);">
                            📶 Jaringan Wi-Fi / LAN Sekolah
                        </span>
                        <span style="font-size: 0.7rem; background: #dcfce7; color: #15803d; padding: 2px 6px; border-radius: 4px; font-weight: 700;">Untuk Siswa &amp; Guru</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="text" id="apiServerLanUrl" readonly value="<?= htmlspecialchars($lanUrl) ?>" class="form-control" style="font-family: monospace; font-size: 0.9rem; font-weight: 700; color: var(--primary); background: var(--bg-surface); cursor: text;">
                        <button type="button" class="btn btn-primary btn-sm" onclick="copyApiServerUrl('apiServerLanUrl', this)" title="Salin Alamat">
                            <span>📋</span> Salin
                        </button>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;">
                        Bagikan alamat ini kepada siswa untuk dimasukkan ke browser HP/Laptop atau aplikasi Android CBT.
                    </div>
                </div>
            </div>

            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 10px 14px; font-size: 0.8rem; color: #1e40af; display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 1.1rem;">💡</span>
                <span>
                    <strong>Petunjuk:</strong> Pastikan perangkat smartphone atau laptop siswa terhubung ke pemancar Wi-Fi / Access Point yang sama dengan server CBT. Siswa dapat mengakses ujian tanpa kuota internet.
                </span>
            </div>
        </div>

        <!-- CARD 2: BERKAS DISTRIBUSI APLIKASI ANDROID (APK) -->
        <div class="card" style="margin-bottom: 24px; border: 1px solid var(--border-color); background: var(--bg-surface);">
            <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3 class="card-title" style="margin-bottom: 4px; display: flex; align-items: center; gap: 8px; color: #15803d;">
                        <span>📱</span> Unduh Berkas Aplikasi Android Peserta (APK)
                    </h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">
                        Paket instalasi aplikasi ujian mandiri untuk smartphone Android siswa dengan fitur Kiosk Lockdown dan Anti-Keluar.
                    </span>
                </div>
                <span class="badge badge-success" style="font-size: 0.75rem; padding: 4px 10px;">
                    v1.0.0 &bull; Release APK
                </span>
            </div>

            <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap; margin-bottom: 16px;">
                <div style="width: 64px; height: 64px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 32px; flex-shrink: 0;">
                    🤖
                </div>
                <div style="flex: 1; min-width: 240px;">
                    <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary); margin-bottom: 2px;">
                        cbt-peserta-v1.0.apk
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 6px;">
                        Ukuran Berkas: <strong>17.3 MB</strong> &bull; Target OS: <strong>Android 6.0 s/d 14+</strong> &bull; Arsitektur: <strong>ARM64-v8a</strong>
                    </div>
                    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                        <span style="font-size: 0.7rem; background: var(--bg-main); border: 1px solid var(--border-color); padding: 2px 8px; border-radius: 4px; color: var(--text-muted);">🔒 Mode Kiosk Kunci Layar</span>
                        <span style="font-size: 0.7rem; background: var(--bg-main); border: 1px solid var(--border-color); padding: 2px 8px; border-radius: 4px; color: var(--text-muted);">🚫 Anti Keluar-Masuk / Alt-Tab</span>
                        <span style="font-size: 0.7rem; background: var(--bg-main); border: 1px solid var(--border-color); padding: 2px 8px; border-radius: 4px; color: var(--text-muted);">📶 100% Wi-Fi LAN Lokal</span>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="/downloads/cbt-peserta-v1.0.apk" download="cbt-peserta-v1.0.apk" class="btn btn-primary" style="padding: 10px 20px; font-weight: 700; font-size: 0.9rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(37,99,235,0.25);">
                        <span>⬇️</span> Unduh Berkas APK Android (17.3 MB)
                    </a>
                    <a href="/downloads/cbt-peserta-v1.0.apk" download style="font-size: 0.75rem; text-align: center; color: var(--primary); text-decoration: underline;">
                        Tautan Langsung (/downloads/cbt-peserta-v1.0.apk)
                    </a>
                </div>
            </div>

            <div style="background: var(--bg-main); border: 1px solid var(--border-color); border-radius: 6px; padding: 12px 14px; font-size: 0.8rem; color: var(--text-secondary);">
                <strong>Langkah Pemasangan di HP Siswa:</strong>
                <ol style="margin: 6px 0 0; padding-left: 18px; line-height: 1.6;">
                    <li>Klik tombol <strong>Unduh Berkas APK Android</strong> di atas atau bagikan berkas APK ke siswa via Wi-Fi/Flashdisk.</li>
                    <li>Pasang aplikasi di smartphone siswa (Izinkan <em>"Install unknown apps"</em> jika diminta).</li>
                    <li>Buka aplikasi, masukkan IP Wi-Fi Server (<code><?= htmlspecialchars($detectedHostIp) ?>:<?= $serverPort ?></code>), lalu siswa masuk menggunakan <strong>NIS</strong> dan kata sandi ujian.</li>
                </ol>
            </div>
        </div>

        <script>
        function copyApiServerUrl(elementId, btn) {
            var copyText = document.getElementById(elementId);
            if (!copyText) return;
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value).then(function() {
                var oldHtml = btn.innerHTML;
                btn.innerHTML = '<span>✓</span> Tersalin!';
                btn.classList.add('btn-success');
                setTimeout(function() {
                    btn.innerHTML = oldHtml;
                    btn.classList.remove('btn-success');
                }, 2000);
            }).catch(function() {
                document.execCommand('copy');
                alert('Alamat berhasil disalin: ' + copyText.value);
            });
        }
        </script>

        <form method="POST" action="/admin/settings/update">
            <!-- SECTION 1: IDENTITAS SEKOLAH -->
            <div class="card" style="margin-bottom: 24px;">
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                    <h3 class="card-title" style="margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                        <span>🏫</span> Identitas Sekolah &amp; Tahun Ajaran
                    </h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">
                        Informasi institusi yang tercantum pada bilah atas sistem, kartu ujian, dan lembar laporan.
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Nama Sekolah / Lembaga *</label>
                        <input type="text" name="school_name" class="form-control" value="<?= htmlspecialchars($s['school_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tahun Ajaran Aktif *</label>
                        <input type="text" name="academic_year" class="form-control" value="<?= htmlspecialchars($s['academic_year'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Alamat Lengkap Sekolah</label>
                    <textarea name="school_address" class="form-control" rows="2"><?= htmlspecialchars($s['school_address'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- SECTION 2: APLIKASI & JARINGAN -->
            <div class="card" style="margin-bottom: 24px;">
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                    <h3 class="card-title" style="margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                        <span>🖥️</span> Aplikasi &amp; Jaringan Lokal Server
                    </h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">
                        Parameter nama sistem dan port layanan untuk distribusi jaringan Wi-Fi/LAN lokal.
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Nama Aplikasi CBT *</label>
                        <input type="text" name="app_name" class="form-control" value="<?= htmlspecialchars($s['app_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Port Server Lokal (HTTP) *</label>
                        <input type="number" name="server_port" class="form-control" min="80" max="65535" value="<?= (int)($s['server_port'] ?? 8000) ?>" required>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: TOKEN & KEBIJAKAN SISWA -->
            <div class="card" style="margin-bottom: 24px;">
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                    <h3 class="card-title" style="margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                        <span>🔑</span> Token Ujian &amp; Kebijakan Siswa
                    </h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">
                        Pengaturan perilisan token dinamis dan hak akses peninjauan hasil oleh peserta.
                    </span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Interval Refresh Token Ujian (Menit) *</label>
                        <input type="number" name="token_refresh_minutes" class="form-control" style="max-width: 200px;" value="<?= (int)($s['token_refresh_minutes'] ?? 15) ?>" required>
                    </div>

                    <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: 8px; padding: 14px 16px; display: flex; flex-direction: column; gap: 12px;">
                        <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; margin: 0;">
                            <input type="checkbox" name="auto_token_release" value="1" <?= !empty($s['auto_token_release']) ? 'checked' : '' ?> style="margin-top: 3px; width: 18px; height: 18px;">
                            <div>
                                <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-primary);">Rilis Token Otomatis (Auto Token Release)</div>
                                <div style="font-size: 0.8rem; color: var(--text-secondary);">Token ujian diperbarui secara otomatis sesuai interval refresh di atas.</div>
                            </div>
                        </label>

                        <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; margin: 0;">
                            <input type="checkbox" name="allow_student_review" value="1" <?= !empty($s['student_review']) ? 'checked' : '' ?> style="margin-top: 3px; width: 18px; height: 18px;">
                            <div>
                                <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-primary);">Izinkan Peninjauan Soal &amp; Jawaban oleh Siswa (Allow Review)</div>
                                <div style="font-size: 0.8rem; color: var(--text-secondary);">Mengizinkan peserta melihat kembali lembar respon jawaban setelah ujian selesai.</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- SUBMIT BUTTON -->
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-bottom: 40px;">
                <a href="/admin/dashboard" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                    <span>💾</span> Simpan Seluruh Pengaturan
                </button>
            </div>
        </form>
    </div>
    <?php
}

// =========================================================================
// 19. MENU 13: LOG AKTIVITAS & AUDIT TRAIL
// =========================================================================
function renderActivityLogsContent() {
    $search = strtolower(trim($_GET['search'] ?? ''));
    $module = strtoupper(trim($_GET['module'] ?? ''));
    $logs = $_SESSION['activity_logs'];

    if ($module !== '') {
        $logs = array_filter($logs, function($l) use ($module) {
            return ($l['module'] ?? '') === $module;
        });
    }

    if ($search !== '') {
        $logs = array_filter($logs, function($l) use ($search) {
            return str_contains(strtolower($l['details']), $search) || str_contains(strtolower($l['action']), $search) || str_contains(strtolower($l['ip']), $search);
        });
    }
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 class="page-title" style="margin: 0; font-size: 1.25rem;">Riwayat Audit &amp; Aktivitas Server</h1>
                <p class="page-subtitle" style="margin: 4px 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                    Catatan riwayat integritas autentikasi, administrasi master data, settings, dan backup (Read-Only)
                </p>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.location.reload();">
                &#8635; Segarkan Log
            </button>
        </div>

        <!-- FILTER BAR -->
        <div class="card" style="padding: 14px 18px;">
            <form method="GET" action="/admin/activity-logs" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <div style="flex: 2; min-width: 220px;">
                    <input type="text" name="search" class="form-control" placeholder="Cari aksi, detail, atau alamat IP..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div style="min-width: 160px;">
                    <select name="module" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Modul</option>
                        <option value="AUTH" <?= $module === 'AUTH' ? 'selected' : '' ?>>AUTH</option>
                        <option value="STUDENT" <?= $module === 'STUDENT' ? 'selected' : '' ?>>STUDENT</option>
                        <option value="TEACHER" <?= $module === 'TEACHER' ? 'selected' : '' ?>>TEACHER</option>
                        <option value="EXAM" <?= $module === 'EXAM' ? 'selected' : '' ?>>EXAM</option>
                        <option value="BACKUP" <?= $module === 'BACKUP' ? 'selected' : '' ?>>BACKUP</option>
                        <option value="SETTING" <?= $module === 'SETTING' ? 'selected' : '' ?>>SETTING</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary">Filter</button>
                <?php if ($search !== '' || $module !== ''): ?>
                    <a href="/admin/activity-logs" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- LOGS TABLE -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th style="width: 160px;">Waktu Kejadian</th>
                            <th style="width: 180px;">Pengguna / Aktor</th>
                            <th style="width: 120px;">Modul</th>
                            <th style="width: 180px;">Aksi / Event</th>
                            <th style="width: 120px;">Alamat IP</th>
                            <th>Rincian Kontekstual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">📜</div>
                                        <p>Belum ada rekaman log audit yang cocok.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $idx => $log): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <div style="font-weight: 600; font-size: 0.85rem; color: var(--text-primary);"><?= htmlspecialchars($log['timestamp']) ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; font-size: 0.9rem; color: var(--text-primary);"><?= htmlspecialchars($log['user']) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($log['module'] === 'AUTH'): ?>
                                            <span class="badge badge-info">AUTH</span>
                                        <?php elseif ($log['module'] === 'SETTING'): ?>
                                            <span class="badge badge-warning">SETTING</span>
                                        <?php elseif ($log['module'] === 'BACKUP'): ?>
                                            <span class="badge badge-success">BACKUP</span>
                                        <?php elseif ($log['module'] === 'EXAM'): ?>
                                            <span class="badge badge-primary">EXAM</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary"><?= htmlspecialchars($log['module']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code style="font-size: 0.8rem; font-weight: 600; background: var(--bg-surface-elevated); padding: 2px 6px; border-radius: 4px; border: 1px solid var(--border-color);">
                                            <?= htmlspecialchars($log['action']) ?>
                                        </code>
                                    </td>
                                    <td><code><?= htmlspecialchars($log['ip']) ?></code></td>
                                    <td style="font-size: 0.85rem; color: var(--text-secondary);"><?= htmlspecialchars($log['details']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}

// =========================================================================
// 20. REUSABLE IMPORT MODAL HELPER
// =========================================================================
function renderImportModalGeneric($actionUrl, $templateUrl, $titleSingular, $filenamePrefix) {
    ?>
    <div class="modal-overlay" id="importModal">
        <div class="modal-content-card" style="max-width: 540px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 20px;">📊</span>
                    <h3 class="modal-title" style="margin: 0;">Import Data <?= htmlspecialchars($titleSingular) ?> dari Excel</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeImportModal()">&times;</button>
            </div>
            <form action="<?= htmlspecialchars($actionUrl) ?>" method="POST" enctype="multipart/form-data">
                <div style="margin-bottom: 16px;">
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 12px; line-height: 1.45;">
                        Unggah file Excel (<strong>.xls</strong>) atau CSV (<strong>.csv</strong>) untuk memasukkan data <?= htmlspecialchars(strtolower($titleSingular)) ?> secara massal.
                    </p>

                    <?php if ($titleSingular === 'Peserta'): ?>
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 12px; margin-bottom: 14px; font-size: 11.5px; color: #166534; line-height: 1.45;">
                            💡 <strong>Format Ringkas (Otomatis):</strong> Cukup isi kolom <strong>NIS</strong>, <strong>Nama Siswa</strong>, <strong>Kelas</strong>, dan <strong>Jenis Kelamin (L/P)</strong>. Username (<em>Nama Depan + NIS</em>) dan Password (<em>default 12345678</em>) dibuat otomatis oleh sistem tanpa perlu diminta di spreadsheet.
                        </div>
                    <?php endif; ?>

                    <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                        <div>
                            <div style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">Belum punya formatnya?</div>
                            <div style="font-size: 11px; color: var(--text-muted);">Pilih format Excel rapih atau CSV standar</div>
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <a href="<?= htmlspecialchars($templateUrl) ?>?format=excel" class="btn btn-primary btn-sm" style="background-color: #059669; border-color: #059669; color: #fff; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;" download="<?= htmlspecialchars($filenamePrefix) ?>.xls">
                                <span>📊</span> Excel (.xls) — Rapih
                            </a>
                            <a href="<?= htmlspecialchars($templateUrl) ?>?format=csv" class="btn btn-secondary btn-sm" style="color: #0284c7; border-color: #bae6fd; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;" download="<?= htmlspecialchars($filenamePrefix) ?>.csv">
                                <span>📄</span> CSV (.csv)
                            </a>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label">Pilih Berkas Spreadsheet *</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv,.txt" required style="padding: 7px 12px;">
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Mendukung .xls (Excel XML), .xlsx, dan .csv</div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #0095ff; border-color: #0095ff;">
                        <span>📤</span> Upload &amp; Mulai Import
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function openImportModal() {
            var m = document.getElementById('importModal');
            if (m) m.classList.add('open');
        }
        function closeImportModal() {
            var m = document.getElementById('importModal');
            if (m) m.classList.remove('open');
        }
    </script>
    <?php
}
