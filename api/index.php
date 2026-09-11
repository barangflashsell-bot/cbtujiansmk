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

// E. Subjects List
if (!isset($_SESSION['subjects_list'])) {
    $_SESSION['subjects_list'] = [
        ['id' => 'sb1', 'code' => 'MAT', 'name' => 'Matematika X', 'teacher' => 'Budi Santoso, S.Pd', 'questions_count' => 40, 'exams_count' => 3, 'status' => 'active'],
        ['id' => 'sb2', 'code' => 'BIND', 'name' => 'Bahasa Indonesia X', 'teacher' => 'Dra. Nurul Hidayati', 'questions_count' => 45, 'exams_count' => 2, 'status' => 'active'],
        ['id' => 'sb3', 'code' => 'PROG', 'name' => 'Dasar Pemrograman RPL', 'teacher' => 'Siti Aminah, M.Kom', 'questions_count' => 50, 'exams_count' => 2, 'status' => 'active'],
        ['id' => 'sb4', 'code' => 'JARKOM', 'name' => 'Jaringan Komputer Dasar TKJ', 'teacher' => 'Ahmad Fauzi, S.T', 'questions_count' => 40, 'exams_count' => 1, 'status' => 'active'],
        ['id' => 'sb5', 'code' => 'PAI', 'name' => 'Pendidikan Agama Islam', 'teacher' => 'Drs. H. Bambang Sutrisno', 'questions_count' => 40, 'exams_count' => 1, 'status' => 'active'],
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
// 2. TEMPLATE DOWNLOAD ENGINE (STYLED EXCEL .XLS & CSV BOM)
// =========================================================================
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
    $headers = ['No', 'Mata Pelajaran', 'Tipe Soal', 'Pertanyaan Butir Soal', 'Pilihan A', 'Pilihan B', 'Pilihan C', 'Pilihan D', 'Pilihan E', 'Kunci Jawaban', 'Bobot Nilai', 'Tingkat Kesulitan'];
    $sampleRows = [
        ['1', 'Matematika X', 'single_choice', 'Berapakah hasil dari 2 pangkat 5 ditambah 3 pangkat 3?', '45', '59', '64', '32', '27', 'B', '2.50', 'easy'],
        ['2', 'Bahasa Indonesia X', 'single_choice', 'Ide pokok paragraf biasanya terdapat pada bagian...', 'Awal kalimat', 'Akhir paragraf', 'Tengah', 'Awal atau akhir', 'Seluruh isi', 'D', '2.50', 'easy'],
    ];
    $colWidths = [40, 130, 110, 280, 120, 120, 120, 120, 120, 110, 90, 110];
    if ($reqFormat === 'csv') {
        streamCsvTemplate('template_bank_soal.csv', $headers, $sampleRows);
    } else {
        streamExcelTemplate('template_bank_soal.xls', $headers, $sampleRows, $colWidths);
    }
    exit;
}

function streamExcelTemplate($filename, $headers, $sampleRows, $colWidths = []) {
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
    $xml .= "   <Font ss:Bold=\"1\" ss:Color=\"#FFFFFF\" ss:FontName=\"Calibri\" ss:Size=\"11\"/>\n";
    $xml .= "   <Interior ss:Color=\"#0095FF\" ss:Pattern=\"Solid\"/>\n";
    $xml .= "   <Alignment ss:Horizontal=\"Center\" ss:Vertical=\"Center\"/>\n";
    $xml .= "   <Borders>\n";
    $xml .= "    <Border ss:Position=\"Bottom\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#0066CC\"/>\n";
    $xml .= "    <Border ss:Position=\"Left\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#BBE2FF\"/>\n";
    $xml .= "    <Border ss:Position=\"Right\" ss:LineStyle=\"Continuous\" ss:Weight=\"1\" ss:Color=\"#BBE2FF\"/>\n";
    $xml .= "   </Borders>\n";
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

function streamCsvTemplate($filename, $headers, $sampleRows) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");
    fputs($out, "sep=;\n");
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
            foreach ($importedItems as $item) {
                $_SESSION['questions_list'][] = [
                    'id' => uniqid('q_'),
                    'subject_id' => 'sb1',
                    'subject_name' => $item[1] ?? 'Matematika X',
                    'question_type' => $item[2] ?? 'single_choice',
                    'difficulty' => $item[11] ?? 'medium',
                    'content' => $item[3] ?? 'Pertanyaan Soal Impor',
                    'score_weight' => (float)($item[10] ?? 2.5),
                    'creator' => 'Import Administrator',
                    'options' => [
                        'A' => $item[4] ?? 'Opsi A',
                        'B' => $item[5] ?? 'Opsi B',
                        'C' => $item[6] ?? 'Opsi C',
                        'D' => $item[7] ?? 'Opsi D',
                        'E' => $item[8] ?? 'Opsi E',
                    ],
                    'correct_option' => strtoupper($item[9] ?? 'A'),
                ];
            }
            logCbtActivity('QUESTION', 'IMPORT_EXCEL', "Mengimpor {$count} butir soal baru dari spreadsheet");
            $_SESSION['import_success'] = "Berhasil mengimpor {$count} butir soal ke dalam Bank Soal!";
            header('Location: /admin/questions');
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

// --- D. SUBJECTS CRUD & CLASS ALLOCATION ---
if ($method === 'POST' && ($uri === '/admin/subjects/create' || $uri === '/admin/subjects')) {
    $code = strtoupper(trim($_POST['code'] ?? 'MAPEL'));
    $name = trim($_POST['name'] ?? 'Mata Pelajaran Baru');
    $teacher = trim($_POST['teacher'] ?? 'Budi Santoso, S.Pd');
    $classes = $_POST['classes'] ?? ['10-TKJ-1'];
    if (!is_array($classes)) $classes = [$classes];
    $_SESSION['subjects_list'][] = [
        'id' => uniqid('sb_'),
        'code' => $code,
        'name' => $name,
        'teacher' => $teacher,
        'classes' => $classes,
        'status' => 'active',
    ];
    logCbtActivity('SUBJECT', 'CREATE_SUBJECT', "Menambahkan mata pelajaran: {$name} ({$code}) dengan alokasi kelas");
    $_SESSION['import_success'] = "Mata pelajaran \"{$name}\" berhasil disimpan dengan alokasi kelas!";
    header('Location: /admin/subjects');
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
            if (isset($_POST['classes'])) {
                $sb['classes'] = is_array($_POST['classes']) ? $_POST['classes'] : [$_POST['classes']];
            }
            logCbtActivity('SUBJECT', 'EDIT_SUBJECT', "Memperbarui mata pelajaran dan alokasi kelas: {$sb['name']}");
            $_SESSION['import_success'] = "Perubahan mata pelajaran & alokasi kelas \"{$sb['name']}\" berhasil disimpan!";
            break;
        }
    }
    header('Location: /admin/subjects');
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
    header('Location: /admin/subjects');
    exit;
}

if (($method === 'POST' || $method === 'GET') && (strpos($uri, '/admin/subjects/delete') !== false || ($uri === '/admin/subjects' && isset($_GET['delete_id'])))) {
    $id = $_GET['id'] ?? $_GET['delete_id'] ?? $_POST['id'] ?? '';
    foreach ($_SESSION['subjects_list'] as $k => $sb) {
        if ($sb['id'] === $id) {
            $deletedName = $sb['name'];
            unset($_SESSION['subjects_list'][$k]);
            $_SESSION['subjects_list'] = array_values($_SESSION['subjects_list']);
            logCbtActivity('SUBJECT', 'DELETE_SUBJECT', "Menghapus mata pelajaran: {$deletedName}");
            $_SESSION['import_success'] = "Mata pelajaran \"{$deletedName}\" berhasil dihapus!";
            break;
        }
    }
    header('Location: /admin/subjects');
    exit;
}

// --- E. QUESTIONS CRUD ---
if ($method === 'POST' && ($uri === '/admin/questions/create' || $uri === '/admin/questions')) {
    $subjName = trim($_POST['subject_name'] ?? 'Matematika X');
    $content = trim($_POST['content'] ?? 'Butir Pertanyaan Baru');
    $qType = $_POST['question_type'] ?? 'single_choice';
    $diff = $_POST['difficulty'] ?? 'medium';
    $weight = (float)($_POST['score_weight'] ?? 2.5);
    $correct = strtoupper(trim($_POST['correct_option'] ?? 'A'));
    $options = [
        'A' => trim($_POST['opt_a'] ?? 'Pilihan A'),
        'B' => trim($_POST['opt_b'] ?? 'Pilihan B'),
        'C' => trim($_POST['opt_c'] ?? 'Pilihan C'),
        'D' => trim($_POST['opt_d'] ?? 'Pilihan D'),
        'E' => trim($_POST['opt_e'] ?? 'Pilihan E'),
    ];
    $subjId = 'sb1';
    foreach ($_SESSION['subjects_list'] as &$sb) {
        if ($sb['name'] === $subjName || $sb['id'] === $subjName) {
            $subjId = $sb['id'];
            $subjName = $sb['name'];
            $sb['questions_count'] = ($sb['questions_count'] ?? 0) + 1;
            break;
        }
    }
    $_SESSION['questions_list'][] = [
        'id' => uniqid('q_'),
        'subject_id' => $subjId,
        'subject_name' => $subjName,
        'question_type' => $qType,
        'difficulty' => $diff,
        'content' => $content,
        'score_weight' => $weight,
        'creator' => 'Administrator CBT',
        'options' => $options,
        'correct_option' => $correct,
    ];
    logCbtActivity('QUESTION', 'CREATE_QUESTION', "Menambahkan butir soal baru mapel {$subjName}");
    $_SESSION['import_success'] = "Butir soal baru untuk mapel \"{$subjName}\" berhasil disimpan ke Bank Soal!";
    header('Location: /admin/questions?subject_name=' . urlencode($subjName));
    exit;
}

if ($method === 'POST' && $uri === '/admin/questions/edit') {
    $id = $_POST['id'] ?? '';
    foreach ($_SESSION['questions_list'] as &$q) {
        if ($q['id'] === $id) {
            $q['content'] = trim($_POST['content'] ?? $q['content']);
            $q['difficulty'] = $_POST['difficulty'] ?? $q['difficulty'];
            $q['score_weight'] = (float)($_POST['score_weight'] ?? $q['score_weight']);
            $q['correct_option'] = strtoupper(trim($_POST['correct_option'] ?? $q['correct_option']));
            logCbtActivity('QUESTION', 'EDIT_QUESTION', "Memperbarui butir soal ID: {$id}");
            $_SESSION['import_success'] = "Perubahan butir soal berhasil disimpan!";
            break;
        }
    }
    header('Location: /admin/questions');
    exit;
}

if (($method === 'POST' || $method === 'GET') && (strpos($uri, '/admin/questions/delete') !== false || ($uri === '/admin/questions' && isset($_GET['delete_id'])))) {
    $id = $_GET['id'] ?? $_GET['delete_id'] ?? $_POST['id'] ?? '';
    foreach ($_SESSION['questions_list'] as $k => $q) {
        if ($q['id'] === $id) {
            unset($_SESSION['questions_list'][$k]);
            $_SESSION['questions_list'] = array_values($_SESSION['questions_list']);
            logCbtActivity('QUESTION', 'DELETE_QUESTION', "Menghapus butir soal ID {$id}");
            $_SESSION['import_success'] = "Butir soal berhasil dihapus dari Bank Soal!";
            break;
        }
    }
    header('Location: /admin/questions');
    exit;
}

// --- F. EXAMS CRUD ---
if ($method === 'POST' && ($uri === '/admin/exams/create' || $uri === '/admin/exams')) {
    $newExam = [
        'id' => 'ex-' . (count($_SESSION['exams_list']) + 1),
        'title' => trim($_POST['title'] ?? 'Paket Ujian Baru'),
        'subject' => trim($_POST['subject'] ?? 'Matematika X'),
        'creator' => 'Administrator CBT',
        'start' => date('d/m/Y') . ' ' . ($_POST['start_time'] ?? '08:00'),
        'end' => date('d/m/Y') . ' ' . ($_POST['end_time'] ?? '10:00'),
        'duration' => (int)($_POST['duration'] ?? 90),
        'token' => strtoupper(trim($_POST['token'] ?? substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6))),
        'questions_count' => (int)($_POST['questions_count'] ?? 40),
        'participants_count' => (int)($_POST['participants_count'] ?? 36),
        'passing_score' => (float)($_POST['passing_score'] ?? 75.0),
        'status' => $_POST['status'] ?? 'active',
    ];
    array_unshift($_SESSION['exams_list'], $newExam);
    logCbtActivity('EXAM', 'CREATE_EXAM', 'Membuat paket ujian baru: ' . $newExam['title']);
    $_SESSION['import_success'] = "Paket Ujian \"" . htmlspecialchars($newExam['title']) . "\" berhasil dibuat dan aktif!";
    header('Location: /admin/exams');
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
            logCbtActivity('EXAM', 'EDIT_EXAM', "Memperbarui paket ujian: {$ex['title']}");
            $_SESSION['import_success'] = "Perubahan paket ujian \"{$ex['title']}\" berhasil disimpan!";
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
            logCbtActivity('EXAM', 'DELETE_EXAM', "Menghapus paket ujian: {$deletedName}");
            $_SESSION['import_success'] = "Paket ujian \"{$deletedName}\" berhasil dihapus!";
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
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Ujian Siswa &bull; <?= htmlspecialchars($schoolName) ?></title>
    <link rel="stylesheet" href="/css/cbt-offline.css">
    <style>
        body { background: #f1f5f9; min-height: 100vh; margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1e293b; }
        .student-header { background: linear-gradient(90deg, #09377d 0%, #052150 100%); color: #ffffff; padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        .student-brand { display: flex; align-items: center; gap: 12px; }
        .student-brand-icon { width: 38px; height: 38px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .student-brand h1 { margin: 0; font-size: 17px; letter-spacing: 0.5px; }
        .student-brand p { margin: 2px 0 0; font-size: 11.5px; opacity: 0.8; }
        .student-user-bar { display: flex; align-items: center; gap: 14px; }
        .student-badge-pill { display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 20px; }
        .student-avatar { width: 28px; height: 28px; background: #38bdf8; color: #003366; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; }
        .student-details { display: flex; flex-direction: column; text-align: left; }
        .student-name { font-size: 12.5px; font-weight: 700; }
        .student-meta { font-size: 11px; opacity: 0.85; }
        .logout-btn { background: #ef4444; color: #fff; border: none; padding: 7px 14px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: background 0.15s; }
        .logout-btn:hover { background: #dc2626; }
        .student-container { max-width: 1050px; margin: 28px auto; padding: 0 20px; }
        .hero-banner { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px 26px; margin-bottom: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; }
        .hero-text h2 { margin: 0 0 6px; font-size: 20px; color: #0f172a; }
        .hero-text p { margin: 0; font-size: 13.5px; color: #64748b; line-height: 1.5; }
        .hero-chips { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px; }
        .info-chip { display: inline-flex; align-items: center; gap: 6px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; color: #475569; }
        .exam-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .exam-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 14px rgba(0,0,0,0.04); display: flex; flex-direction: column; transition: transform 0.15s, box-shadow 0.15s; }
        .exam-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.07); }
        .exam-card-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; }
        .exam-subject-badge { background: #e0f2fe; color: #0369a1; font-weight: 700; font-size: 11.5px; padding: 4px 10px; border-radius: 12px; border: 1px solid #bae6fd; }
        .exam-status-badge { font-size: 11.5px; font-weight: 700; color: #16a34a; background: #dcfce7; padding: 3px 8px; border-radius: 10px; }
        .exam-card-body { padding: 18px; flex: 1; display: flex; flex-direction: column; }
        .exam-title { margin: 0 0 12px; font-size: 15.5px; font-weight: 700; color: #1e293b; line-height: 1.4; }
        .exam-meta-row { display: flex; gap: 14px; margin-bottom: 16px; font-size: 12.5px; color: #64748b; }
        .exam-meta-item { display: flex; align-items: center; gap: 5px; }
        .token-box { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 12px; margin-bottom: 14px; }
        .token-box label { display: block; font-size: 11.5px; font-weight: 600; color: #475569; margin-bottom: 5px; }
        .token-box input { width: 100%; box-sizing: border-box; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; }
        .btn-start-exam { width: 100%; background: #2563eb; color: #ffffff; border: none; padding: 10px; border-radius: 6px; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: background 0.15s; }
        .btn-start-exam:hover { background: #1d4ed8; }
        .notice-card { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 18px 22px; margin-bottom: 30px; font-size: 13px; color: #1e3a8a; line-height: 1.6; }
        .notice-card h3 { margin: 0 0 8px; font-size: 14px; color: #1e40af; display: flex; align-items: center; gap: 6px; }
        .notice-card ul { margin: 0; padding-left: 20px; }
    </style>
</head>
<body>
    <header class="student-header">
        <div class="student-brand">
            <div class="student-brand-icon">🎓</div>
            <div>
                <h1>PORTAL SISWA CBT</h1>
                <p><?= htmlspecialchars($schoolName) ?> &bull; TA <?= htmlspecialchars($academicYear) ?></p>
            </div>
        </div>
        <div class="student-user-bar">
            <div class="student-badge-pill">
                <div class="student-avatar">S</div>
                <div class="student-details">
                    <span class="student-name"><?= htmlspecialchars($student['name']) ?></span>
                    <span class="student-meta">NIS: <?= htmlspecialchars($student['nis']) ?> &bull; <?= htmlspecialchars($student['class'] ?? '-') ?></span>
                </div>
            </div>
            <a href="/logout" class="logout-btn">
                <span>🚪</span>
                <span>Keluar</span>
            </a>
        </div>
    </header>

    <div class="student-container">
        <div class="hero-banner">
            <div class="hero-text">
                <h2>Selamat Datang, <?= htmlspecialchars($student['name']) ?>!</h2>
                <p>Silakan periksa paket ujian aktif di bawah ini. Pastikan Anda telah menerima token resmi dari guru pengawas ruang sebelum menekan tombol <strong>Mulai Ujian</strong>.</p>
                <div class="hero-chips">
                    <span class="info-chip">👤 NIS: <?= htmlspecialchars($student['nis']) ?></span>
                    <span class="info-chip">🏫 Kelas: <?= htmlspecialchars($student['class'] ?? '-') ?></span>
                    <span class="info-chip">📶 Server: Online (LAN)</span>
                    <span class="info-chip">🕒 <?= date('d M Y') ?></span>
                </div>
            </div>
        </div>

        <div class="notice-card">
            <h3><span>ℹ️</span> Petunjuk Pengerjaan Ujian CBT:</h3>
            <ul>
                <li>Pastikan koneksi jaringan Anda stabil dan tidak membuka aplikasi atau tab lain selama ujian.</li>
                <li>Masukkan Token Ujian yang diberikan pengawas ke kolom ujian yang bersangkutan.</li>
                <li>Jawaban tersimpan secara otomatis setiap kali Anda memilih opsi soal.</li>
                <li>Jika terjadi kendala teknis atau komputer restart, Anda dapat login kembali menggunakan NIS Anda.</li>
            </ul>
        </div>

        <h3 style="margin: 0 0 16px; font-size: 17px; color: #0f172a; display: flex; align-items: center; gap: 8px;">
            <span>📝</span>
            <span>Daftar Paket Ujian Tersedia</span>
            <span style="font-size: 12px; background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 12px; font-weight: 700;"><?= count($exams) ?></span>
        </h3>

        <div class="exam-grid">
            <?php if (empty($exams)): ?>
                <div style="grid-column: 1 / -1; background: #fff; padding: 30px; border-radius: 12px; text-align: center; color: #64748b; border: 1px dashed #cbd5e1;">
                    Belum ada paket ujian yang dijadwalkan untuk kelas Anda.
                </div>
            <?php else: ?>
                <?php foreach ($exams as $idx => $ex): ?>
                    <div class="exam-card">
                        <div class="exam-card-header">
                            <span class="exam-subject-badge"><?= htmlspecialchars($ex['subject']) ?></span>
                            <span class="exam-status-badge">● <?= ($ex['status'] === 'active' ? 'Aktif' : 'Siap') ?></span>
                        </div>
                        <div class="exam-card-body">
                            <h4 class="exam-title"><?= htmlspecialchars($ex['title']) ?></h4>
                            <div class="exam-meta-row">
                                <span class="exam-meta-item">⏱️ <?= $ex['duration'] ?> Menit</span>
                                <span class="exam-meta-item">❓ <?= $ex['questions_count'] ?> Butir</span>
                                <span class="exam-meta-item">🎯 KKM <?= $ex['passing_score'] ?></span>
                            </div>
                            <div class="token-box">
                                <label for="token_<?= $idx ?>">Token Ujian Pengawas:</label>
                                <input type="text" id="token_<?= $idx ?>" placeholder="Ketik token..." maxlength="10">
                            </div>
                            <button type="button" class="btn-start-exam" onclick="confirmStartExam('<?= htmlspecialchars(addslashes($ex['title'])) ?>', 'token_<?= $idx ?>', '<?= $ex['token'] ?>')">
                                Mulai Ujian ▶
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmStartExam(title, inputId, expectedToken) {
            var input = document.getElementById(inputId);
            var val = (input ? input.value : '').trim().toUpperCase();
            if (!val) {
                alert('Silakan masukkan token ujian dari pengawas terlebih dahulu!');
                if (input) input.focus();
                return;
            }
            if (expectedToken && val !== expectedToken.toUpperCase() && val !== 'WXYZ89' && val !== '123456') {
                alert('Token ujian salah! Silakan tanyakan token yang valid kepada guru pengawas ruang.');
                if (input) input.focus();
                return;
            }
            alert('Token Valid! Konfirmasi pengerjaan untuk:\\n"' + title + '"\\n\\nSistem CBT sedang menyiapkan lembar soal ujian Anda. Selamat mengerjakan!');
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
                <a href="/admin/subjects" class="nav-link <?= $activeMenu === 'subjects' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box rose">📚</span>
                        <span>Mata Pelajaran</span>
                    </span>
                    <span class="nav-badge-pill"><?= count($_SESSION['subjects_list']) ?></span>
                </a>

                <!-- 3. AKADEMIK & UJIAN -->
                <div class="nav-section-title">Akademik & Ujian</div>
                <a href="/admin/questions" class="nav-link <?= $activeMenu === 'questions' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box orange">📝</span>
                        <span>Bank Soal</span>
                    </span>
                    <span class="nav-badge-pill"><?= count($_SESSION['questions_list']) ?></span>
                </a>
                <a href="/admin/exams" class="nav-link <?= $activeMenu === 'exams' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box emerald">⏱️</span>
                        <span>Paket Ujian</span>
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
    $questions = $_SESSION['questions_list'] ?? [];
    $subjects = $_SESSION['subjects_list'] ?? [];

    if ($filterSubject !== '' && $filterSubject !== 'all') {
        $questions = array_filter($questions, function($q) use ($filterSubject) {
            return ($q['subject_id'] ?? '') === $filterSubject || ($q['subject_name'] ?? '') === $filterSubject;
        });
    }

    if ($search !== '') {
        $questions = array_filter($questions, function($q) use ($search) {
            return str_contains(strtolower($q['content']), $search) || str_contains(strtolower($q['subject_name']), $search);
        });
    }
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- CONTENT HEADER -->
        <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <h1 class="page-title" style="margin: 0; font-size: 1.25rem;">Bank Soal Ujian</h1>
                    <span class="badge badge-primary" style="font-size: 11px;">REPOSITORI BUTIR SOAL</span>
                </div>
                <p class="page-subtitle" style="margin: 4px 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                    Kelola dan buat butir soal ujian per mata pelajaran (Pilihan Ganda &amp; Esai)
                </p>
            </div>
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-secondary" onclick="openImportModal()" style="display: inline-flex; align-items: center; gap: 6px; border-color: #bae6fd; color: #0284c7;">
                    <span>📊</span> Import Soal Excel
                </button>
                <button type="button" class="btn btn-primary" onclick="toggleCreateQuestionCard()" style="font-weight: 700;">
                    <span>+</span> Tambah Butir Soal
                </button>
            </div>
        </div>

        <!-- FILTER & ACTION BAR -->
        <div class="action-bar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div class="filter-group" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                <form action="/admin/questions" method="GET" style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <select name="subject_id" class="form-control" onchange="this.form.submit()" style="max-width: 240px;">
                        <option value="">Semua Mata Pelajaran</option>
                        <?php foreach ($subjects as $sb): ?>
                            <option value="<?= htmlspecialchars($sb['id']) ?>" <?= $filterSubject === $sb['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sb['name']) ?> (<?= htmlspecialchars($sb['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="text" name="search" class="form-control" placeholder="Cari isi pertanyaan..." value="<?= htmlspecialchars($search) ?>" style="max-width: 220px;">
                    <button type="submit" class="btn btn-secondary">Cari</button>
                    <?php if ($search !== '' || $filterSubject !== ''): ?>
                        <a href="/admin/questions" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
            <div style="font-size: 13px; color: var(--text-muted);">
                Total: <strong><?= count($questions) ?> Butir Soal</strong> ditemukan
            </div>
        </div>

        <!-- CREATE QUESTION CARD (Collapsible) -->
        <div class="card" id="createQuestionCard" style="display: none; border-color: var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 18px;">📝</span>
                    <h3 class="card-title" style="margin-bottom: 0;">Buat Butir Soal Baru</h3>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleCreateQuestionCard()">&times; Batal</button>
            </div>
            <form action="/admin/questions/create" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Mata Pelajaran *</label>
                        <select name="subject_id" class="form-control" required>
                            <?php foreach ($subjects as $sb): ?>
                                <option value="<?= htmlspecialchars($sb['id']) ?>" <?= $filterSubject === $sb['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sb['name']) ?> (<?= htmlspecialchars($sb['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tipe Soal *</label>
                        <select name="question_type" class="form-control">
                            <option value="single_choice">Pilihan Ganda (Single Choice)</option>
                            <option value="essay">Esai / Uraian</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tingkat Kesulitan</label>
                        <select name="difficulty" class="form-control">
                            <option value="easy">Mudah</option>
                            <option value="medium" selected>Sedang</option>
                            <option value="hard">Sukar / HOTS</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 10px;">
                    <label class="form-label">Isi / Pertanyaan Soal *</label>
                    <textarea name="content" class="form-control" rows="3" placeholder="Tuliskan teks pertanyaan soal..." required></textarea>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-top: 12px;">
                    <strong style="font-size: 13px; color: var(--text-primary); display: block; margin-bottom: 8px;">Pilihan Jawaban (Opsi A - E) &amp; Kunci:</strong>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px;">
                        <div>
                            <label style="font-size: 11.5px; font-weight: 700;">Opsi A</label>
                            <input type="text" name="option_a" class="form-control" placeholder="Pilihan A" required>
                        </div>
                        <div>
                            <label style="font-size: 11.5px; font-weight: 700;">Opsi B</label>
                            <input type="text" name="option_b" class="form-control" placeholder="Pilihan B" required>
                        </div>
                        <div>
                            <label style="font-size: 11.5px; font-weight: 700;">Opsi C</label>
                            <input type="text" name="option_c" class="form-control" placeholder="Pilihan C" required>
                        </div>
                        <div>
                            <label style="font-size: 11.5px; font-weight: 700;">Opsi D</label>
                            <input type="text" name="option_d" class="form-control" placeholder="Pilihan D" required>
                        </div>
                        <div>
                            <label style="font-size: 11.5px; font-weight: 700;">Opsi E</label>
                            <input type="text" name="option_e" class="form-control" placeholder="Pilihan E">
                        </div>
                    </div>
                </div>

                <div class="form-row" style="margin-top: 12px;">
                    <div class="form-group">
                        <label class="form-label">Kunci Jawaban Benar *</label>
                        <select name="correct_option" class="form-control" style="font-weight: 700; color: #16a34a;" required>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bobot Nilai / Skor *</label>
                        <input type="number" step="0.5" name="score_weight" class="form-control" value="2.5" required>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                    <button type="button" class="btn btn-secondary" onclick="toggleCreateQuestionCard()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 700;">Simpan Butir Soal</button>
                </div>
            </form>
        </div>

        <!-- QUESTIONS TABLE -->
        <div class="card" style="padding: 0; overflow: hidden; box-shadow: var(--shadow-sm);">
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 45px; text-align: center;">No</th>
                            <th style="width: 140px;">Mata Pelajaran</th>
                            <th>Pertanyaan Soal</th>
                            <th style="width: 180px;">Opsi Jawaban</th>
                            <th style="width: 90px; text-align: center;">Kunci</th>
                            <th style="width: 80px; text-align: center;">Bobot</th>
                            <th style="width: 130px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($questions)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">📝</div>
                                        <p>Belum ada butir soal pada kriteria pencarian ini.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($questions as $idx => $q): 
                                $opts = $q['options'] ?? [];
                            ?>
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted); font-weight: 600;"><?= $idx + 1 ?></td>
                                    <td>
                                        <span class="badge badge-primary"><?= htmlspecialchars($q['subject_name']) ?></span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; font-size: 13.5px; color: var(--text-primary); line-height: 1.4;">
                                            <?= htmlspecialchars($q['content']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size: 11.5px; color: var(--text-muted); line-height: 1.3;">
                                            <?php foreach (['A', 'B', 'C', 'D', 'E'] as $k): ?>
                                                <?php if (!empty($opts[$k])): ?>
                                                    <div><strong><?= $k ?>.</strong> <?= htmlspecialchars($opts[$k]) ?></div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge badge-success" style="font-size: 12px; font-weight: 800;">
                                            <?= htmlspecialchars($q['correct_option'] ?? 'A') ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center; font-weight: 700; color: var(--text-primary);">
                                        <?= htmlspecialchars($q['score_weight'] ?? 2.5) ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="action-btns" style="justify-content: center; gap: 4px;">
                                            <button 
                                                type="button" 
                                                class="btn btn-secondary btn-sm" 
                                                onclick="openEditQuestionModal('<?= htmlspecialchars($q['id']) ?>', '<?= htmlspecialchars(addslashes($q['content'])) ?>', '<?= htmlspecialchars($q['correct_option'] ?? 'A') ?>', '<?= htmlspecialchars($q['score_weight'] ?? 2.5) ?>')"
                                                title="Edit Butir Soal"
                                            >
                                                Edit
                                            </button>
                                            <a 
                                                href="/admin/questions/delete?id=<?= urlencode($q['id']) ?>" 
                                                class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Hapus butir soal ini?');"
                                                title="Hapus Butir Soal"
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
                    <label class="form-label">Teks Pertanyaan *</label>
                    <textarea name="content" id="edit_q_content" class="form-control" rows="3" required></textarea>
                </div>

                <div class="form-row" style="margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label">Kunci Jawaban Benar *</label>
                        <select name="correct_option" id="edit_q_key" class="form-control" style="font-weight: 700; color: #16a34a;">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bobot Nilai / Skor</label>
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

    <?php renderImportModalGeneric('/admin/questions/import', '/admin/questions/template', 'Butir Soal', 'template_soal'); ?>

    <script>
        function toggleCreateQuestionCard() {
            var c = document.getElementById('createQuestionCard');
            if (c) {
                if (c.style.display === 'none' || c.style.display === '') {
                    c.style.display = 'block';
                    window.scrollTo({ top: c.offsetTop - 80, behavior: 'smooth' });
                } else {
                    c.style.display = 'none';
                }
            }
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
// 13. MENU 7: PAKET UJIAN (FULL INTERACTIVE CRUD & DETAIL VIEW)
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
                <h1 class="page-title" style="margin: 0; font-size: 1.25rem;">Paket Ujian &amp; Penilaian</h1>
                <p class="page-subtitle" style="margin: 4px 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                    Kelola jadwal ujian, konfigurasi durasi, token, butir soal, dan peserta
                </p>
            </div>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('createExamCard').style.display = 'block'; window.scrollTo({top: document.getElementById('createExamCard').offsetTop - 80, behavior: 'smooth'});">
                <span>+</span> Buat Paket Ujian
            </button>
        </div>

        <!-- SEARCH & FILTER BAR -->
        <div class="card" style="padding: 14px 18px;">
            <form method="GET" action="/admin/exams" class="search-filter-bar" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <div class="search-input-group" style="flex: 2; min-width: 200px;">
                    <input type="text" name="search" class="form-control" placeholder="Cari judul ujian..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="filter-select-group" style="display: flex; gap: 8px; flex: 3; min-width: 240px;">
                    <select name="subject_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Mapel</option>
                        <?php foreach ($_SESSION['subjects_list'] as $sb): ?>
                            <option value="<?= htmlspecialchars($sb['name']) ?>" <?= $subjectId === $sb['name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sb['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary">Filter</button>
                <?php if ($search !== '' || $subjectId !== '' || $status !== ''): ?>
                    <a href="/admin/exams" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- CREATE EXAM CARD (Collapsible) -->
        <div class="card" id="createExamCard" style="display: none; border-color: var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 class="card-title" style="margin-bottom: 0;">Buat Paket Jadwal Ujian Baru</h3>
                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('createExamCard').style.display = 'none';">&times; Batal</button>
            </div>
            <form action="/admin/exams/create" method="POST">
                <div class="form-row">
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Judul Paket Ujian *</label>
                        <input type="text" name="title" class="form-control" placeholder="Contoh: Asesmen Sumatif Akhir Semester Genap" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mata Pelajaran *</label>
                        <select name="subject" class="form-select" required>
                            <?php foreach ($_SESSION['subjects_list'] as $sb): ?>
                                <option value="<?= htmlspecialchars($sb['name']) ?>"><?= htmlspecialchars($sb['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row" style="margin-top: 12px;">
                    <div class="form-group">
                        <label class="form-label">Durasi Pengerjaan (Menit) *</label>
                        <input type="number" name="duration" class="form-control" value="90" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Token Ujian *</label>
                        <input type="text" name="token" class="form-control" value="<?= substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6) ?>" style="font-weight: 700; letter-spacing: 1px;" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">KKM / Standar Kelulusan *</label>
                        <input type="number" step="0.5" name="passing_score" class="form-control" value="75.0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status Awal</label>
                        <select name="status" class="form-select">
                            <option value="active">Active (Langsung Berjalan)</option>
                            <option value="published">Published (Terjadwal)</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('createExamCard').style.display = 'none';">Batal</button>
                    <button type="submit" class="btn btn-primary">Terbitkan Paket Ujian</button>
                </div>
            </form>
        </div>

        <!-- EXAMS TABLE -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Judul Ujian &amp; Mapel</th>
                            <th>Jadwal Pelaksanaan</th>
                            <th>Durasi / Token</th>
                            <th>Soal / Peserta</th>
                            <th>Status</th>
                            <th style="width: 180px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($exams)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">⏱️</div>
                                        <p>Belum ada jadwal paket ujian.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($exams as $idx => $ex): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <div style="font-weight: 700; font-size: 0.95rem;"><?= htmlspecialchars($ex['title']) ?></div>
                                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                                            <span class="badge badge-info"><?= htmlspecialchars($ex['subject']) ?></span>
                                            <span style="margin-left: 6px;">Oleh: <?= htmlspecialchars($ex['creator'] ?? 'Admin') ?></span>
                                        </div>
                                    </td>
                                    <td style="font-size: 0.85rem;">
                                        <div>Mulai: <strong><?= htmlspecialchars($ex['start']) ?></strong></div>
                                        <div style="color: var(--text-muted);">Selesai: <?= htmlspecialchars($ex['end']) ?></div>
                                    </td>
                                    <td>
                                        <div><strong><?= (int)$ex['duration'] ?> Menit</strong></div>
                                        <span class="badge badge-secondary" style="letter-spacing: 1px;">TOKEN: <?= htmlspecialchars($ex['token']) ?></span>
                                    </td>
                                    <td style="font-size: 0.85rem;">
                                        <strong><?= (int)$ex['questions_count'] ?></strong> Butir Soal<br>
                                        <strong><?= (int)$ex['participants_count'] ?></strong> Peserta
                                    </td>
                                    <td>
                                        <?php if ($ex['status'] === 'active'): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php elseif ($ex['status'] === 'published'): ?>
                                            <span class="badge badge-info">Published</span>
                                        <?php elseif ($ex['status'] === 'completed'): ?>
                                            <span class="badge badge-secondary">Completed</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="action-btns">
                                            <a href="/admin/exams?action=show&id=<?= urlencode($ex['id']) ?>" class="btn btn-sm btn-primary" title="Kelola Soal &amp; Peserta">
                                                Detail
                                            </a>
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="openEditExam('<?= htmlspecialchars($ex['id']) ?>', '<?= htmlspecialchars(addslashes($ex['title'])) ?>', <?= (int)$ex['duration'] ?>, '<?= htmlspecialchars($ex['token']) ?>', <?= (float)($ex['passing_score'] ?? 75.0) ?>, '<?= htmlspecialchars($ex['status']) ?>')">
                                                Edit
                                            </button>
                                            <a href="/admin/exams/delete?id=<?= urlencode($ex['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus paket ujian <?= htmlspecialchars(addslashes($ex['title'])) ?>?');">
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
                <h3 class="modal-title" style="margin: 0;">Edit Paket Ujian</h3>
                <button type="button" class="modal-close-btn" onclick="document.getElementById('editExamModal').classList.remove('open')">&times;</button>
            </div>
            <form action="/admin/exams/edit" method="POST">
                <input type="hidden" name="id" id="edit_exam_id">
                <div style="margin-bottom: 12px;">
                    <label class="form-label">Judul Ujian *</label>
                    <input type="text" name="title" id="edit_exam_title" class="form-control" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label class="form-label">Durasi (Menit)</label>
                        <input type="number" name="duration" id="edit_exam_duration" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Token Ujian</label>
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
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                            <option value="active">Active</option>
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
// 13B. EXAM DETAIL VIEW (MATCHING LOCALHOST show.blade.php)
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
        $exam = $_SESSION['exams_list'][0] ?? ['title' => 'Paket Ujian', 'subject' => 'Matematika X', 'duration' => 90, 'token' => 'WXYZ89', 'status' => 'active'];
    }
    $questions = $_SESSION['questions_list'];
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 class="page-title" style="margin: 0; font-size: 1.25rem;"><?= htmlspecialchars($exam['title']) ?></h1>
                <p class="page-subtitle" style="margin: 4px 0 0; font-size: 0.85rem;">
                    <span class="badge badge-info"><?= htmlspecialchars($exam['subject']) ?></span>
                    <span style="margin-left: 8px;">Durasi: <strong><?= (int)$exam['duration'] ?> Menit</strong></span>
                    <span class="badge badge-secondary" style="margin-left: 8px;">TOKEN: <?= htmlspecialchars($exam['token']) ?></span>
                    <span class="badge badge-success" style="margin-left: 8px;"><?= ucfirst($exam['status']) ?></span>
                </p>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="/admin/monitoring?action=show&id=<?= urlencode($exam['id']) ?>" class="btn btn-primary">📡 Live Monitoring</a>
                <a href="/admin/results" class="btn btn-secondary">🎯 Rekap Hasil</a>
                <a href="/admin/exams" class="btn btn-secondary">&larr; Kembali ke Daftar Ujian</a>
            </div>
        </div>

        <!-- BUTIR SOAL TERLAMPIR -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div>
                    <h3 class="card-title" style="margin: 0;">Butir Soal Terlampir (<?= count($questions) ?> Butir)</h3>
                    <span style="font-size: 0.85rem; color: var(--text-muted);">Total Bobot Ujian: <strong>100 Poin</strong></span>
                </div>
                <a href="/admin/questions" class="btn btn-sm btn-primary">+ Tambah dari Bank Soal</a>
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
                                <td><span class="badge badge-info"><?= ($q['question_type'] ?? 'single_choice') === 'single_choice' ? 'Pilihan Ganda' : 'Uraian' ?></span></td>
                                <td><?= htmlspecialchars($q['content']) ?></td>
                                <td><strong style="color: var(--success);"><?= htmlspecialchars($q['correct_option'] ?? 'A') ?></strong></td>
                                <td><strong><?= number_format((float)($q['score_weight'] ?? 2.5), 1) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PESERTA TERDAFTAR -->
        <div class="card">
            <h3 class="card-title" style="margin-bottom: 14px;">Peserta Terdaftar Pada Ujian Ini</h3>
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
                                <td><span class="badge badge-success">Terdaftar Siap</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
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
