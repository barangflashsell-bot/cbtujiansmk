<?php
/**
 * CBT Server Manager - Standalone Serverless Web Engine
 * Fully Synchronized with Localhost CBT Architecture & Features
 * All 12 Menus with Live Interactive CRUD, Modals, Downloads & State Management
 */

$autoloader = __DIR__ . '/../SERVER/vendor/autoload.php';
if (file_exists($autoloader)) {
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

// B. Teachers List
if (!isset($_SESSION['teachers_list'])) {
    $_SESSION['teachers_list'] = [
        ['id' => 't1', 'nip' => '197501012000011001', 'name' => 'Budi Santoso, S.Pd', 'username' => 'guru.budi', 'email' => 'budi@smk.sch.id', 'phone' => '081234567890', 'is_active' => true],
        ['id' => 't2', 'nip' => '198203152005012003', 'name' => 'Siti Aminah, M.Kom', 'username' => 'guru.siti', 'email' => 'siti@smk.sch.id', 'phone' => '081298765432', 'is_active' => true],
        ['id' => 't3', 'nip' => '198811202010011005', 'name' => 'Ahmad Fauzi, S.T', 'username' => 'guru.ahmad', 'email' => 'ahmad@smk.sch.id', 'phone' => '081345678901', 'is_active' => true],
        ['id' => 't4', 'nip' => '199204122019031008', 'name' => 'Dra. Nurul Hidayati', 'username' => 'guru.nurul', 'email' => 'nurul@smk.sch.id', 'phone' => '085712345678', 'is_active' => true],
    ];
}

// C. Classes List
if (!isset($_SESSION['classes_list'])) {
    $_SESSION['classes_list'] = [
        ['id' => 'c1', 'name' => '10-TKJ-1', 'level' => '10', 'academic_year' => '2025/2026', 'students_count' => 36, 'status' => 'active'],
        ['id' => 'c2', 'name' => '10-RPL-1', 'level' => '10', 'academic_year' => '2025/2026', 'students_count' => 36, 'status' => 'active'],
        ['id' => 'c3', 'name' => '11-TKJ-1', 'level' => '11', 'academic_year' => '2025/2026', 'students_count' => 35, 'status' => 'active'],
        ['id' => 'c4', 'name' => '11-RPL-1', 'level' => '11', 'academic_year' => '2025/2026', 'students_count' => 35, 'status' => 'active'],
        ['id' => 'c5', 'name' => '12-TKJ-1', 'level' => '12', 'academic_year' => '2025/2026', 'students_count' => 34, 'status' => 'active'],
    ];
}

// D. Students List
if (!isset($_SESSION['students_list'])) {
    $_SESSION['students_list'] = [
        ['id' => 's1', 'nis' => '0081234567', 'nisn' => '0081234567', 'name' => 'Ahmad Dhani Prasetya', 'username' => 'peserta01', 'class' => '10-TKJ-1', 'gender' => 'L', 'status' => 'Online'],
        ['id' => 's2', 'nis' => '0081234568', 'nisn' => '0081234568', 'name' => 'Siti Aminah Zahra', 'username' => 'peserta02', 'class' => '10-TKJ-1', 'gender' => 'P', 'status' => 'Offline'],
        ['id' => 's3', 'nis' => '0081234569', 'nisn' => '0081234569', 'name' => 'Budi Santoso Nugroho', 'username' => 'peserta03', 'class' => '10-RPL-1', 'gender' => 'L', 'status' => 'Online'],
        ['id' => 's4', 'nis' => '0081234570', 'nisn' => '0081234570', 'name' => 'Dewi Lestari', 'username' => 'peserta04', 'class' => '11-TKJ-1', 'gender' => 'P', 'status' => 'Online'],
        ['id' => 's5', 'nis' => '0081234571', 'nisn' => '0081234571', 'name' => 'Eko Prasetyo', 'username' => 'peserta05', 'class' => '12-TKJ-1', 'gender' => 'L', 'status' => 'Offline'],
        ['id' => 's6', 'nis' => '0081234572', 'nisn' => '0081234572', 'name' => 'Farhan Maulana', 'username' => 'peserta06', 'class' => '10-RPL-1', 'gender' => 'L', 'status' => 'Online'],
    ];
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
    $headers = ['No', 'Nama Lengkap Peserta', 'Username Login', 'Password', 'Kelas', 'NIS', 'NISN', 'Jenis Kelamin (L/P)'];
    $sampleRows = [
        ['1', 'Ahmad Dhani Prasetya', 'peserta01', '123456', '10-TKJ-1', '0081234567', '0081234567', 'L'],
        ['2', 'Siti Aminah Zahra', 'peserta02', '123456', '10-TKJ-1', '0081234568', '0081234568', 'P'],
        ['3', 'Budi Santoso Nugroho', 'peserta03', '123456', '10-RPL-1', '0081234569', '0081234569', 'L'],
    ];
    $colWidths = [40, 220, 140, 100, 90, 120, 120, 140];
    if ($reqFormat === 'csv') {
        streamCsvTemplate('template_data_peserta.csv', $headers, $sampleRows);
    } else {
        streamExcelTemplate('template_data_peserta.xls', $headers, $sampleRows, $colWidths);
    }
    exit;
}

if ($uri === '/admin/teachers/template') {
    $headers = ['No', 'Nama Lengkap Guru', 'Username Login', 'Password', 'NIP', 'No. WhatsApp / HP'];
    $sampleRows = [
        ['1', 'Drs. H. Bambang Sutrisno, M.Kom', 'guru_bambang', '123456', '198001012005011001', '081234567890'],
        ['2', 'Sri Wahyuni, S.Pd', 'guru_sri', '123456', '198502022008022002', '081234567891'],
    ];
    $colWidths = [40, 240, 140, 100, 160, 140];
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
            $style = ($colIdx === 0 || $colIdx === 4 || $colIdx === 7) ? 'CenterCell' : 'TextCell';
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
                $_SESSION['students_list'][] = [
                    'id' => uniqid('s_'),
                    'name' => $item[1] ?? 'Peserta Impor',
                    'username' => $item[2] ?? ('peserta_' . rand(100, 999)),
                    'class' => $item[4] ?? '10-TKJ-1',
                    'nis' => $item[5] ?? ('NIS-' . rand(100, 999)),
                    'nisn' => $item[6] ?? ('008' . rand(1000000, 9999999)),
                    'gender' => strtoupper($item[7] ?? 'L'),
                    'status' => 'Aktif',
                ];
            }
            logCbtActivity('STUDENT', 'IMPORT_EXCEL', "Mengimpor {$count} peserta ujian baru dari spreadsheet");
            $_SESSION['import_success'] = "Berhasil mengimpor {$count} data peserta ke dalam sistem!";
            header('Location: /admin/students');
            exit;
        } elseif (strpos($uri, 'teachers') !== false) {
            foreach ($importedItems as $item) {
                $_SESSION['teachers_list'][] = [
                    'id' => uniqid('t_'),
                    'name' => $item[1] ?? 'Guru Impor',
                    'username' => $item[2] ?? ('guru.' . rand(100, 999)),
                    'nip' => $item[4] ?? ('1985' . rand(10000000000000, 99999999999999)),
                    'phone' => $item[5] ?? '08123456789',
                    'email' => ($item[2] ?? 'guru') . '@smk.sch.id',
                    'is_active' => true,
                ];
            }
            logCbtActivity('TEACHER', 'IMPORT_EXCEL', "Mengimpor {$count} data guru baru dari spreadsheet");
            $_SESSION['import_success'] = "Berhasil mengimpor {$count} data guru ke dalam sistem!";
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
    $phone = trim($_POST['phone'] ?? '0812' . rand(10000000, 99999999));
    $_SESSION['teachers_list'][] = [
        'id' => uniqid('t_'),
        'nip' => $nip,
        'name' => $name,
        'username' => $username,
        'email' => $username . '@smk.sch.id',
        'phone' => $phone,
        'is_active' => true,
    ];
    logCbtActivity('TEACHER', 'CREATE_TEACHER', "Menambahkan guru baru: {$name}");
    $_SESSION['import_success'] = "Data guru \"{$name}\" berhasil disimpan!";
    header('Location: /admin/teachers');
    exit;
}

if ($method === 'POST' && $uri === '/admin/teachers/edit') {
    $id = $_POST['id'] ?? '';
    foreach ($_SESSION['teachers_list'] as &$t) {
        if ($t['id'] === $id) {
            $t['name'] = trim($_POST['name'] ?? $t['name']);
            $t['nip'] = trim($_POST['nip'] ?? $t['nip']);
            $t['phone'] = trim($_POST['phone'] ?? $t['phone']);
            $t['is_active'] = isset($_POST['is_active']);
            logCbtActivity('TEACHER', 'EDIT_TEACHER', "Memperbarui data guru: {$t['name']}");
            $_SESSION['import_success'] = "Perubahan data guru \"{$t['name']}\" berhasil disimpan!";
            break;
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
if ($method === 'POST' && ($uri === '/admin/students/create' || $uri === '/admin/students')) {
    $name = trim($_POST['name'] ?? 'Peserta Baru');
    $nis = trim($_POST['nis'] ?? ('008' . rand(1000000, 9999999)));
    $nisn = trim($_POST['nisn'] ?? $nis);
    $username = trim($_POST['username'] ?? ('peserta_' . rand(10, 99)));
    $class = trim($_POST['class'] ?? '10-TKJ-1');
    $gender = strtoupper(trim($_POST['gender'] ?? 'L'));
    $_SESSION['students_list'][] = [
        'id' => uniqid('s_'),
        'nis' => $nis,
        'nisn' => $nisn,
        'name' => $name,
        'username' => $username,
        'class' => $class,
        'gender' => $gender,
        'status' => 'Aktif',
    ];
    logCbtActivity('STUDENT', 'CREATE_STUDENT', "Menambahkan peserta ujian: {$name} ({$class})");
    $_SESSION['import_success'] = "Data peserta \"{$name}\" berhasil disimpan!";
    header('Location: /admin/students');
    exit;
}

if ($method === 'POST' && $uri === '/admin/students/edit') {
    $id = $_POST['id'] ?? '';
    foreach ($_SESSION['students_list'] as &$s) {
        if ($s['id'] === $id) {
            $s['name'] = trim($_POST['name'] ?? $s['name']);
            $s['nis'] = trim($_POST['nis'] ?? $s['nis']);
            $s['nisn'] = trim($_POST['nisn'] ?? $s['nisn']);
            $s['class'] = trim($_POST['class'] ?? $s['class']);
            $s['gender'] = strtoupper(trim($_POST['gender'] ?? $s['gender']));
            logCbtActivity('STUDENT', 'EDIT_STUDENT', "Memperbarui data peserta: {$s['name']}");
            $_SESSION['import_success'] = "Perubahan data peserta \"{$s['name']}\" berhasil disimpan!";
            break;
        }
    }
    header('Location: /admin/students');
    exit;
}

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

// --- C. CLASSES CRUD ---
if ($method === 'POST' && ($uri === '/admin/classes/create' || $uri === '/admin/classes')) {
    $name = trim($_POST['name'] ?? 'Kelas Baru');
    $level = trim($_POST['level'] ?? '10');
    $year = trim($_POST['academic_year'] ?? '2025/2026');
    $_SESSION['classes_list'][] = [
        'id' => uniqid('c_'),
        'name' => $name,
        'level' => $level,
        'academic_year' => $year,
        'students_count' => 36,
        'status' => 'active',
    ];
    logCbtActivity('CLASS', 'CREATE_CLASS', "Menambahkan rombel kelas: {$name}");
    $_SESSION['import_success'] = "Rombel kelas \"{$name}\" berhasil disimpan!";
    header('Location: /admin/classes');
    exit;
}

if ($method === 'POST' && $uri === '/admin/classes/edit') {
    $id = $_POST['id'] ?? '';
    foreach ($_SESSION['classes_list'] as &$c) {
        if ($c['id'] === $id) {
            $c['name'] = trim($_POST['name'] ?? $c['name']);
            $c['level'] = trim($_POST['level'] ?? $c['level']);
            $c['academic_year'] = trim($_POST['academic_year'] ?? $c['academic_year']);
            logCbtActivity('CLASS', 'EDIT_CLASS', "Memperbarui rombel kelas: {$c['name']}");
            $_SESSION['import_success'] = "Perubahan rombel kelas \"{$c['name']}\" berhasil disimpan!";
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

// --- D. SUBJECTS CRUD ---
if ($method === 'POST' && ($uri === '/admin/subjects/create' || $uri === '/admin/subjects')) {
    $code = strtoupper(trim($_POST['code'] ?? 'MAPEL'));
    $name = trim($_POST['name'] ?? 'Mata Pelajaran Baru');
    $teacher = trim($_POST['teacher'] ?? 'Budi Santoso, S.Pd');
    $_SESSION['subjects_list'][] = [
        'id' => uniqid('sb_'),
        'code' => $code,
        'name' => $name,
        'teacher' => $teacher,
        'questions_count' => 40,
        'exams_count' => 1,
        'status' => 'active',
    ];
    logCbtActivity('SUBJECT', 'CREATE_SUBJECT', "Menambahkan mata pelajaran: {$name} ({$code})");
    $_SESSION['import_success'] = "Mata pelajaran \"{$name}\" berhasil disimpan!";
    header('Location: /admin/subjects');
    exit;
}

if ($method === 'POST' && $uri === '/admin/subjects/edit') {
    $id = $_POST['id'] ?? '';
    foreach ($_SESSION['subjects_list'] as &$sb) {
        if ($sb['id'] === $id) {
            $sb['code'] = strtoupper(trim($_POST['code'] ?? $sb['code']));
            $sb['name'] = trim($_POST['name'] ?? $sb['name']);
            logCbtActivity('SUBJECT', 'EDIT_SUBJECT', "Memperbarui mata pelajaran: {$sb['name']}");
            $_SESSION['import_success'] = "Perubahan mata pelajaran \"{$sb['name']}\" berhasil disimpan!";
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

// --- G. MONITORING RESET ---
if ($uri === '/admin/monitoring/reset' || (isset($_GET['action']) && $_GET['action'] === 'reset_monitoring')) {
    $targetNis = $_GET['nis'] ?? '';
    foreach ($_SESSION['monitoring_sessions'] as &$s) {
        if ($s['nis'] === $targetNis) {
            $s['status'] = 'Belum Mulai (Reset)';
            $s['ip'] = '-';
            break;
        }
    }
    logCbtActivity('MONITORING', 'RESET_LOGIN', 'Mereset sesi login peserta NIS ' . $targetNis);
    $_SESSION['import_success'] = "Sesi login peserta NIS {$targetNis} berhasil di-reset!";
    header('Location: /admin/monitoring');
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
    setcookie('cbt_user', '', time() - 3600, '/');
    unset($_SESSION['cbt_user']);
    header('Location: /login?logged_out=1');
    exit;
}

if ($method === 'POST' && ($uri === '/login' || strpos($uri, 'login') !== false)) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === 'admin' && $password === 'admin123') {
        setcookie('cbt_user', 'admin', time() + 86400 * 7, '/');
        $_SESSION['cbt_user'] = 'admin';
        logCbtActivity('AUTH', 'LOGIN_SUCCESS', 'Administrator CBT berhasil login');
        header('Location: /admin/dashboard');
        exit;
    } elseif ($username === 'guru' && $password === 'guru123') {
        setcookie('cbt_user', 'guru', time() + 86400 * 7, '/');
        $_SESSION['cbt_user'] = 'guru';
        logCbtActivity('AUTH', 'LOGIN_SUCCESS', 'Guru pengajar berhasil login');
        header('Location: /admin/questions');
        exit;
    } else {
        $_SESSION['login_error'] = 'Username atau kata sandi salah. Gunakan admin / admin123';
        header('Location: /login');
        exit;
    }
}

// Current user check
$isLoggedOut = isset($_GET['logged_out']);
$currentUser = $_COOKIE['cbt_user'] ?? $_SESSION['cbt_user'] ?? null;

// In standalone serverless deployment, keep user logged in as 'admin' unless explicitly logged out
if (!$currentUser && !$isLoggedOut) {
    $currentUser = 'admin';
    $_SESSION['cbt_user'] = 'admin';
    setcookie('cbt_user', 'admin', time() + 86400 * 30, '/');
}

if ($uri === '/' || $uri === '/login') {
    if ($currentUser && !$isLoggedOut) {
        header('Location: /admin/dashboard');
        exit;
    }
    renderLoginPage();
    exit;
}

// Protected routes
if (strpos($uri, '/admin') === 0 || strpos($uri, '/guru') === 0) {
    if (!$currentUser) {
        header('Location: /login');
        exit;
    }
    renderAppPage($uri);
    exit;
}

header('Location: /login');
exit;

// =========================================================================
// 5. RENDER LOGIN PAGE (ANBK THEME)
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
    <title>Login Administrator &bull; CBT Server Manager</title>
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
                    <label class="form-label">Username Login</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus value="admin">
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label">Kata Sandi</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan kata sandi" required value="admin123">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 10px; font-size: 14px; font-weight: 700;">
                    Masuk ke Web Dashboard
                </button>
            </form>
            <div style="margin-top: 20px; text-align: center; font-size: 11.5px; color: var(--text-muted);">
                Kredensial Default: Admin (<code>admin</code> / <code>admin123</code>) &bull; Guru (<code>guru</code> / <code>guru123</code>)
            </div>
        </div>
    </div>
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
                <a href="/admin/classes" class="nav-link <?= $activeMenu === 'classes' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box amber">🏫</span>
                        <span>Data Kelas</span>
                    </span>
                    <span class="nav-badge-pill"><?= count($_SESSION['classes_list']) ?></span>
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

                    <div class="user-pill">
                        <div class="user-avatar-circle">A</div>
                        <div style="display: flex; flex-direction: column; text-align: left;">
                            <span style="font-size: 13px; font-weight: 700; color: var(--text-primary); line-height: 1.1;">Administrator</span>
                            <span style="font-size: 11px; color: var(--text-muted); line-height: 1.1;">Administrator Sistem</span>
                        </div>
                    </div>

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
                <h3 class="card-title" style="margin-bottom: 0;">Tambah Akun & Data Guru Baru</h3>
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
                        <label class="form-label">Password Awal *</label>
                        <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">NIP (Nomor Induk Pegawai)</label>
                        <input type="text" name="nip" class="form-control" placeholder="18 digit NIP">
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. Telepon / WhatsApp</label>
                        <input type="text" name="phone" class="form-control" placeholder="08xxxxxxxxxx">
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('createTeacherCard').style.display = 'none';">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Data Guru</button>
                </div>
            </form>
        </div>

        <!-- TEACHERS TABLE -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>NIP</th>
                            <th>Nama Lengkap Guru</th>
                            <th>Username Akun</th>
                            <th>No. WhatsApp / HP</th>
                            <th>Status Akun</th>
                            <th style="width: 150px; text-align: center;">Aksi</th>
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
                                    <td><?= $idx + 1 ?></td>
                                    <td><code><?= htmlspecialchars($t['nip']) ?></code></td>
                                    <td><strong><?= htmlspecialchars($t['name']) ?></strong></td>
                                    <td><code><?= htmlspecialchars($t['username']) ?></code></td>
                                    <td><?= htmlspecialchars($t['phone']) ?></td>
                                    <td>
                                        <span class="badge <?= !empty($t['is_active']) ? 'badge-success' : 'badge-danger' ?>">
                                            <?= !empty($t['is_active']) ? 'Aktif' : 'Nonaktif' ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="action-btns">
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="openEditTeacher('<?= htmlspecialchars($t['id']) ?>', '<?= htmlspecialchars(addslashes($t['name'])) ?>', '<?= htmlspecialchars(addslashes($t['nip'])) ?>', '<?= htmlspecialchars(addslashes($t['phone'])) ?>', <?= !empty($t['is_active']) ? '1' : '0' ?>)">
                                                Edit
                                            </button>
                                            <a href="/admin/teachers/delete?id=<?= urlencode($t['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data guru <?= htmlspecialchars(addslashes($t['name'])) ?>?');">
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
                    <label class="form-label">NIP *</label>
                    <input type="text" name="nip" id="edit_teacher_nip" class="form-control" required>
                </div>
                <div style="margin-bottom: 14px;">
                    <label class="form-label">No. Telepon / WA</label>
                    <input type="text" name="phone" id="edit_teacher_phone" class="form-control">
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
        function openEditTeacher(id, name, nip, phone, isActive) {
            document.getElementById('edit_teacher_id').value = id;
            document.getElementById('edit_teacher_name').value = name;
            document.getElementById('edit_teacher_nip').value = nip;
            document.getElementById('edit_teacher_phone').value = phone;
            document.getElementById('edit_teacher_active').checked = (isActive == 1);
            document.getElementById('editTeacherModal').classList.add('open');
        }
    </script>
    <?php
}

// =========================================================================
// 9. MENU 3: DATA PESERTA / SISWA (FULL INTERACTIVE CRUD & CLASS TABS)
// =========================================================================
function renderStudentsContent() {
    $search = strtolower(trim($_GET['search'] ?? ''));
    $currentClass = $_GET['class_id'] ?? 'all';
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
        <div class="action-bar">
            <div class="filter-group">
                <form action="/admin/students" method="GET" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                    <input type="hidden" name="class_id" value="<?= htmlspecialchars($currentClass) ?>">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama, NIS, username..." value="<?= htmlspecialchars($search) ?>" style="max-width: 240px;">
                    <button type="submit" class="btn btn-secondary">Cari</button>
                    <?php if ($search !== '' || $currentClass !== 'all'): ?>
                        <a href="/admin/students" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-secondary" onclick="openImportModal()" style="display: inline-flex; align-items: center; gap: 6px; border-color: #bae6fd; color: #0284c7;">
                    <span>📊</span> Import Data Excel
                </button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('createStudentCard').style.display = 'block'; window.scrollTo({top: document.getElementById('createStudentCard').offsetTop - 80, behavior: 'smooth'});">
                    <span>+</span> Tambah Peserta Baru
                </button>
            </div>
        </div>

        <!-- CREATE STUDENT CARD (Collapsible) -->
        <div class="card" id="createStudentCard" style="display: none; border-color: var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 class="card-title" style="margin-bottom: 0;">Tambah Akun & Data Peserta Baru</h3>
                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('createStudentCard').style.display = 'none';">&times; Batal</button>
            </div>
            <form action="/admin/students/create" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Username / Nomor Peserta *</label>
                        <input type="text" name="username" class="form-control" placeholder="Contoh: peserta01" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap Siswa *</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Ahmad Dhani" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password Login *</label>
                        <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kelas / Rombel *</label>
                        <select name="class" class="form-select" required>
                            <?php foreach ($_SESSION['classes_list'] as $c): ?>
                                <option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?> (Tingkat <?= htmlspecialchars($c['level']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">NIS (Nomor Induk Siswa) *</label>
                        <input type="text" name="nis" class="form-control" placeholder="10 Digit NIS" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">NISN (Opsional)</label>
                        <input type="text" name="nisn" class="form-control" placeholder="10 Digit NISN">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Kelamin *</label>
                        <select name="gender" class="form-select" required>
                            <option value="L">Laki-laki (L)</option>
                            <option value="P">Perempuan (P)</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('createStudentCard').style.display = 'none';">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Data Peserta</button>
                </div>
            </form>
        </div>

        <!-- STUDENTS TABLE -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>NIS / NISN</th>
                            <th>Nama Lengkap Peserta</th>
                            <th>Kelas</th>
                            <th>Username Akun</th>
                            <th>Jenis Kelamin</th>
                            <th style="width: 150px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">👥</div>
                                        <p>Tidak ada peserta yang cocok dengan filter yang dipilih.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $idx => $s): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <code><?= htmlspecialchars($s['nis']) ?></code><br>
                                        <span style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($s['nisn'] ?? $s['nis']) ?></span>
                                    </td>
                                    <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                                    <td><span class="badge badge-primary"><?= htmlspecialchars($s['class']) ?></span></td>
                                    <td><code><?= htmlspecialchars($s['username']) ?></code></td>
                                    <td><?= ($s['gender'] ?? 'L') === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                                    <td style="text-align: center;">
                                        <div class="action-btns">
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="openEditStudent('<?= htmlspecialchars($s['id']) ?>', '<?= htmlspecialchars(addslashes($s['name'])) ?>', '<?= htmlspecialchars(addslashes($s['nis'])) ?>', '<?= htmlspecialchars(addslashes($s['nisn'] ?? $s['nis'])) ?>', '<?= htmlspecialchars(addslashes($s['class'])) ?>', '<?= htmlspecialchars($s['gender'] ?? 'L') ?>')">
                                                Edit
                                            </button>
                                            <a href="/admin/students/delete?id=<?= urlencode($s['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data peserta <?= htmlspecialchars(addslashes($s['name'])) ?>?');">
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
    <div class="modal-overlay" id="editStudentModal">
        <div class="modal-content-card" style="max-width: 520px;">
            <div class="modal-header">
                <h3 class="modal-title" style="margin: 0;">Edit Data Peserta</h3>
                <button type="button" class="modal-close-btn" onclick="document.getElementById('editStudentModal').classList.remove('open')">&times;</button>
            </div>
            <form action="/admin/students/edit" method="POST">
                <input type="hidden" name="id" id="edit_student_id">
                <div style="margin-bottom: 12px;">
                    <label class="form-label">Nama Lengkap Siswa *</label>
                    <input type="text" name="name" id="edit_student_name" class="form-control" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div>
                        <label class="form-label">NIS *</label>
                        <input type="text" name="nis" id="edit_student_nis" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">NISN</label>
                        <input type="text" name="nisn" id="edit_student_nisn" class="form-control">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px;">
                    <div>
                        <label class="form-label">Kelas</label>
                        <select name="class" id="edit_student_class" class="form-select">
                            <?php foreach ($_SESSION['classes_list'] as $c): ?>
                                <option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Jenis Kelamin</label>
                        <select name="gender" id="edit_student_gender" class="form-select">
                            <option value="L">Laki-laki (L)</option>
                            <option value="P">Perempuan (P)</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editStudentModal').classList.remove('open')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- IMPORT MODAL -->
    <?php renderImportModalGeneric('/admin/students/import', '/admin/students/template', 'Peserta', 'template_siswa'); ?>

    <script>
        function openEditStudent(id, name, nis, nisn, className, gender) {
            document.getElementById('edit_student_id').value = id;
            document.getElementById('edit_student_name').value = name;
            document.getElementById('edit_student_nis').value = nis;
            document.getElementById('edit_student_nisn').value = nisn;
            document.getElementById('edit_student_class').value = className;
            document.getElementById('edit_student_gender').value = gender;
            document.getElementById('editStudentModal').classList.add('open');
        }
    </script>
    <?php
}

// =========================================================================
// 10. MENU 4: DATA KELAS / ROMBEL (FULL INTERACTIVE CRUD)
// =========================================================================
function renderClassesContent() {
    $search = strtolower(trim($_GET['search'] ?? ''));
    $classes = $_SESSION['classes_list'];
    if ($search !== '') {
        $classes = array_filter($classes, function($c) use ($search) {
            return str_contains(strtolower($c['name']), $search) || str_contains(strtolower($c['level']), $search);
        });
    }
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="action-bar">
            <div class="filter-group">
                <form action="/admin/classes" method="GET" style="display: flex; gap: 8px;">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama kelas..." value="<?= htmlspecialchars($search) ?>" style="max-width: 260px;">
                    <button type="submit" class="btn btn-secondary">Cari</button>
                    <?php if ($search !== ''): ?>
                        <a href="/admin/classes" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-secondary" onclick="openImportModal()" style="display: inline-flex; align-items: center; gap: 6px; border-color: #bae6fd; color: #0284c7;">
                    <span>📊</span> Import Data Excel
                </button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('createClassCard').style.display = 'block'; window.scrollTo({top: document.getElementById('createClassCard').offsetTop - 80, behavior: 'smooth'});">
                    <span>+</span> Tambah Kelas Baru
                </button>
            </div>
        </div>

        <!-- CREATE CLASS CARD (Collapsible) -->
        <div class="card" id="createClassCard" style="display: none; border-color: var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 class="card-title" style="margin-bottom: 0;">Tambah Kelas / Rombel Baru</h3>
                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('createClassCard').style.display = 'none';">&times; Batal</button>
            </div>
            <form action="/admin/classes/create" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nama Kelas / Rombel *</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: 10-TKJ-1, 10-RPL-1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tingkat / Jenjang *</label>
                        <input type="text" name="level" class="form-control" placeholder="Contoh: 10, 11, 12" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tahun Ajaran *</label>
                        <input type="text" name="academic_year" class="form-control" value="2025/2026" required>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('createClassCard').style.display = 'none';">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Kelas</button>
                </div>
            </form>
        </div>

        <!-- CLASSES TABLE -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Nama Kelas</th>
                            <th>Tingkat</th>
                            <th>Tahun Ajaran</th>
                            <th>Jumlah Siswa</th>
                            <th>Status</th>
                            <th style="width: 150px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($classes)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">🏫</div>
                                        <p>Belum ada data rombel kelas.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($classes as $idx => $c): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                                    <td>Tingkat <?= htmlspecialchars($c['level']) ?></td>
                                    <td><?= htmlspecialchars($c['academic_year']) ?></td>
                                    <td><span class="badge badge-primary"><?= (int)($c['students_count'] ?? 36) ?> Siswa</span></td>
                                    <td><span class="badge badge-success">Aktif</span></td>
                                    <td style="text-align: center;">
                                        <div class="action-btns">
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="openEditClass('<?= htmlspecialchars($c['id']) ?>', '<?= htmlspecialchars(addslashes($c['name'])) ?>', '<?= htmlspecialchars(addslashes($c['level'])) ?>', '<?= htmlspecialchars(addslashes($c['academic_year'])) ?>')">
                                                Edit
                                            </button>
                                            <a href="/admin/classes/delete?id=<?= urlencode($c['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus kelas <?= htmlspecialchars(addslashes($c['name'])) ?>?');">
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

    <!-- EDIT CLASS MODAL -->
    <div class="modal-overlay" id="editClassModal">
        <div class="modal-content-card" style="max-width: 460px;">
            <div class="modal-header">
                <h3 class="modal-title" style="margin: 0;">Edit Data Kelas</h3>
                <button type="button" class="modal-close-btn" onclick="document.getElementById('editClassModal').classList.remove('open')">&times;</button>
            </div>
            <form action="/admin/classes/edit" method="POST">
                <input type="hidden" name="id" id="edit_class_id">
                <div style="margin-bottom: 14px;">
                    <label class="form-label">Nama Kelas / Rombel *</label>
                    <input type="text" name="name" id="edit_class_name" class="form-control" required>
                </div>
                <div style="margin-bottom: 14px;">
                    <label class="form-label">Tingkat / Jenjang *</label>
                    <input type="text" name="level" id="edit_class_level" class="form-control" required>
                </div>
                <div style="margin-bottom: 18px;">
                    <label class="form-label">Tahun Ajaran *</label>
                    <input type="text" name="academic_year" id="edit_class_year" class="form-control" required>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editClassModal').classList.remove('open')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <?php renderImportModalGeneric('/admin/classes/import', '/admin/classes/template', 'Kelas', 'template_kelas'); ?>

    <script>
        function openEditClass(id, name, level, year) {
            document.getElementById('edit_class_id').value = id;
            document.getElementById('edit_class_name').value = name;
            document.getElementById('edit_class_level').value = level;
            document.getElementById('edit_class_year').value = year;
            document.getElementById('editClassModal').classList.add('open');
        }
    </script>
    <?php
}

// =========================================================================
// 11. MENU 5: MATA PELAJARAN (FULL INTERACTIVE CRUD)
// =========================================================================
function renderSubjectsContent() {
    $search = strtolower(trim($_GET['search'] ?? ''));
    $subjects = $_SESSION['subjects_list'];
    if ($search !== '') {
        $subjects = array_filter($subjects, function($sb) use ($search) {
            return str_contains(strtolower($sb['name']), $search) || str_contains(strtolower($sb['code']), $search);
        });
    }
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="action-bar">
            <div class="filter-group">
                <form action="/admin/subjects" method="GET" style="display: flex; gap: 8px;">
                    <input type="text" name="search" class="form-control" placeholder="Cari kode / nama mapel..." value="<?= htmlspecialchars($search) ?>" style="max-width: 260px;">
                    <button type="submit" class="btn btn-secondary">Cari</button>
                    <?php if ($search !== ''): ?>
                        <a href="/admin/subjects" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('chooseSubjectToCreateModal').classList.add('open')" style="display: inline-flex; align-items: center; gap: 6px; border-color: #bae6fd; color: #0284c7;">
                    <span>📝</span> Buat Soal Mapel
                </button>
                <button type="button" class="btn btn-secondary" onclick="openImportModal()" style="display: inline-flex; align-items: center; gap: 6px; border-color: #bae6fd; color: #0284c7;">
                    <span>📊</span> Import Data Excel
                </button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('createSubjectCard').style.display = 'block'; window.scrollTo({top: document.getElementById('createSubjectCard').offsetTop - 80, behavior: 'smooth'});">
                    <span>+</span> Tambah Mapel Baru
                </button>
            </div>
        </div>

        <!-- CREATE SUBJECT CARD (Collapsible) -->
        <div class="card" id="createSubjectCard" style="display: none; border-color: var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 class="card-title" style="margin-bottom: 0;">Tambah Mata Pelajaran Baru</h3>
                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('createSubjectCard').style.display = 'none';">&times; Batal</button>
            </div>
            <form action="/admin/subjects/create" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Kode Mapel *</label>
                        <input type="text" name="code" class="form-control" placeholder="Contoh: MAT, BIND, PROG" style="text-transform: uppercase;" required>
                    </div>
                    <div class="form-group" style="grid-column: span 2;">
                        <label class="form-label">Nama Mata Pelajaran *</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Matematika X, Bahasa Indonesia X" required>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('createSubjectCard').style.display = 'none';">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Mata Pelajaran</button>
                </div>
            </form>
        </div>

        <!-- SUBJECTS TABLE -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Kode</th>
                            <th>Nama Mata Pelajaran</th>
                            <th>Guru Pengampu</th>
                            <th>Total Soal</th>
                            <th>Status</th>
                            <th style="width: 240px; text-align: center;">Aksi</th>
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
                            <?php foreach ($subjects as $idx => $sb): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><span class="badge badge-primary"><?= htmlspecialchars($sb['code']) ?></span></td>
                                    <td><strong><?= htmlspecialchars($sb['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($sb['teacher'] ?? 'Guru Pengampu') ?></td>
                                    <td>
                                        <a href="/admin/questions?subject_name=<?= urlencode($sb['name']) ?>" class="badge badge-info" style="text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" title="Lihat semua butir soal <?= htmlspecialchars($sb['name']) ?>">
                                            <span>📖</span> <?= (int)($sb['questions_count'] ?? 40) ?> Butir &rarr;
                                        </a>
                                    </td>
                                    <td><span class="badge badge-success">Aktif</span></td>
                                    <td style="text-align: center;">
                                        <div class="action-btns" style="justify-content: center;">
                                            <a href="/admin/questions?subject_name=<?= urlencode($sb['name']) ?>&action=create" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 4px; font-weight: 700;" title="Buat butir soal baru untuk <?= htmlspecialchars($sb['name']) ?>">
                                                <span>📝</span> Buat Soal
                                            </a>
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="openEditSubject('<?= htmlspecialchars($sb['id']) ?>', '<?= htmlspecialchars(addslashes($sb['code'])) ?>', '<?= htmlspecialchars(addslashes($sb['name'])) ?>')">
                                                Edit
                                            </button>
                                            <a href="/admin/subjects/delete?id=<?= urlencode($sb['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus mata pelajaran <?= htmlspecialchars(addslashes($sb['name'])) ?>?');">
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

    <!-- CHOOSE SUBJECT MODAL (BUAT SOAL SESUAI MAPEL YANG DIINGINKAN) -->
    <div class="modal-overlay" id="chooseSubjectToCreateModal">
        <div class="modal-content-card" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title" style="margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span>📝</span> Buat Soal Mata Pelajaran
                </h3>
                <button type="button" class="modal-close-btn" onclick="document.getElementById('chooseSubjectToCreateModal').classList.remove('open')">&times;</button>
            </div>
            <form action="/admin/questions" method="GET">
                <input type="hidden" name="action" value="create">
                <div style="margin-bottom: 16px;">
                    <label class="form-label" style="font-weight: 700;">Pilih Mata Pelajaran Yang Diinginkan *</label>
                    <select name="subject_name" class="form-select" required style="font-size: 14px; padding: 10px 12px;">
                        <?php foreach ($_SESSION['subjects_list'] as $sb): ?>
                            <option value="<?= htmlspecialchars($sb['name']) ?>">
                                <?= htmlspecialchars($sb['name']) ?> (<?= htmlspecialchars($sb['code']) ?>) &bull; <?= (int)($sb['questions_count'] ?? 40) ?> Butir Soal
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div style="margin-top: 10px; padding: 10px 12px; background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: 6px; font-size: 12px; color: var(--text-secondary); line-height: 1.45;">
                        💡 Anda akan diarahkan langsung ke halaman Bank Soal dengan form input soal baru yang sudah otomatis memilih mata pelajaran tersebut.
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('chooseSubjectToCreateModal').classList.remove('open')">Batal</button>
                    <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
                        <span>📝</span> Lanjut Buat Soal &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT SUBJECT MODAL -->
    <div class="modal-overlay" id="editSubjectModal">
        <div class="modal-content-card" style="max-width: 480px;">
            <div class="modal-header">
                <h3 class="modal-title" style="margin: 0;">Edit Mata Pelajaran</h3>
                <button type="button" class="modal-close-btn" onclick="document.getElementById('editSubjectModal').classList.remove('open')">&times;</button>
            </div>
            <form action="/admin/subjects/edit" method="POST">
                <input type="hidden" name="id" id="edit_subject_id">
                <div style="margin-bottom: 14px;">
                    <label class="form-label">Kode Mapel *</label>
                    <input type="text" name="code" id="edit_subject_code" class="form-control" style="text-transform: uppercase;" required>
                </div>
                <div style="margin-bottom: 18px;">
                    <label class="form-label">Nama Mata Pelajaran *</label>
                    <input type="text" name="name" id="edit_subject_name" class="form-control" required>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editSubjectModal').classList.remove('open')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <?php renderImportModalGeneric('/admin/subjects/import', '/admin/subjects/template', 'Mata Pelajaran', 'template_mapel'); ?>

    <script>
        function openEditSubject(id, code, name) {
            document.getElementById('edit_subject_id').value = id;
            document.getElementById('edit_subject_code').value = code;
            document.getElementById('edit_subject_name').value = name;
            document.getElementById('editSubjectModal').classList.add('open');
        }
    </script>
    <?php
}

// =========================================================================
// 12. MENU 6: BANK SOAL (FULL INTERACTIVE CRUD)
// =========================================================================
function renderQuestionsContent() {
    $search = strtolower(trim($_GET['search'] ?? ''));
    $subjectParam = trim($_GET['subject_name'] ?? $_GET['subject_id'] ?? '');
    $type = $_GET['type'] ?? '';
    $action = $_GET['action'] ?? '';
    $questions = $_SESSION['questions_list'];

    // Identify active subject if specified
    $activeSubject = null;
    $activeSubjectName = '';
    if ($subjectParam !== '' && $subjectParam !== 'all') {
        foreach ($_SESSION['subjects_list'] as $sb) {
            if ($sb['name'] === $subjectParam || $sb['id'] === $subjectParam || $sb['code'] === $subjectParam) {
                $activeSubject = $sb;
                $activeSubjectName = $sb['name'];
                break;
            }
        }
        if (!$activeSubject && $subjectParam !== '') {
            $activeSubjectName = $subjectParam;
        }
    }

    if ($activeSubjectName !== '') {
        $questions = array_filter($questions, function($q) use ($activeSubjectName, $activeSubject) {
            return ($q['subject_name'] ?? '') === $activeSubjectName || ($activeSubject && ($q['subject_id'] ?? '') === $activeSubject['id']);
        });
    }

    if ($type !== '') {
        $questions = array_filter($questions, function($q) use ($type) {
            return ($q['question_type'] ?? 'single_choice') === $type;
        });
    }

    if ($search !== '') {
        $questions = array_filter($questions, function($q) use ($search) {
            return str_contains(strtolower($q['content']), $search);
        });
    }
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 class="page-title" style="margin: 0; font-size: 1.25rem;">Bank Soal & Kisi-Kisi</h1>
                <p class="page-subtitle" style="margin: 4px 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                    Kelola butir soal ujian, opsi pilihan jawaban, bobot nilai, dan kunci jawaban per-mata pelajaran
                </p>
            </div>
            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-secondary" onclick="openImportModal()" style="display: inline-flex; align-items: center; gap: 6px; border-color: #bae6fd; color: #0284c7;">
                    <span>📊</span> Import Soal Excel
                </button>
                <button type="button" class="btn btn-primary" onclick="openCreateQuestionWithSubject('<?= htmlspecialchars(addslashes($activeSubjectName)) ?>')">
                    <span>+</span> Buat Soal <?= $activeSubjectName ? htmlspecialchars($activeSubjectName) : 'Baru' ?>
                </button>
            </div>
        </div>

        <!-- TABS NAVIGASI BANK SOAL PER-MATA PELAJARAN -->
        <div class="card" style="padding: 14px 18px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 16px;">📚</span>
                    <strong style="font-size: 14px; color: var(--text-primary);">Bank Soal Per-Mata Pelajaran</strong>
                    <span style="font-size: 11.5px; color: var(--text-muted);">(Pilih mata pelajaran untuk filter instan &amp; kelola butir soal per-mapel)</span>
                </div>
                <div style="font-size: 12px; color: var(--text-secondary); font-weight: 600;">
                    Total: <span style="color: var(--primary);"><?= count($_SESSION['questions_list']) ?> Butir Soal</span>
                </div>
            </div>

            <div class="subject-nav-tabs">
                <a href="/admin/questions?subject_name=all" class="subject-tab-pill <?= empty($activeSubjectName) ? 'active' : '' ?>">
                    <span>Semua Mapel</span>
                    <span class="subject-tab-badge"><?= count($_SESSION['questions_list']) ?></span>
                </a>
                <?php foreach ($_SESSION['subjects_list'] as $sb): ?>
                    <?php
                    $sbCount = count(array_filter($_SESSION['questions_list'], fn($q) => ($q['subject_name'] ?? '') === $sb['name'] || ($q['subject_id'] ?? '') === $sb['id']));
                    $isActive = ($activeSubjectName === $sb['name'] || ($activeSubject && $activeSubject['id'] === $sb['id']));
                    ?>
                    <a href="/admin/questions?subject_name=<?= urlencode($sb['name']) ?>" class="subject-tab-pill <?= $isActive ? 'active' : '' ?>">
                        <span><?= htmlspecialchars($sb['code']) ?> - <?= htmlspecialchars($sb['name']) ?></span>
                        <span class="subject-tab-badge"><?= $sbCount ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($activeSubject): ?>
            <!-- ACTIVE SUBJECT BANNER -->
            <div class="card" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 1px solid #bae6fd; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: #0284c7; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 800; box-shadow: 0 2px 6px rgba(2, 132, 199, 0.3);">
                        📖
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #0284c7; letter-spacing: 0.5px;">Mata Pelajaran Terpilih</div>
                        <div style="font-size: 17px; font-weight: 800; color: #0369a1; margin-top: 1px;">
                            <?= htmlspecialchars($activeSubject['name']) ?> <span style="font-size: 13px; font-weight: 600; opacity: 0.85;">(<?= htmlspecialchars($activeSubject['code']) ?>)</span>
                        </div>
                        <div style="font-size: 12.5px; color: #475569; margin-top: 2px;">
                            Guru Pengampu: <strong><?= htmlspecialchars($activeSubject['teacher'] ?? 'Budi Santoso, S.Pd') ?></strong> &bull; Total Soal Mapel Ini: <strong><?= count($questions) ?> Butir</strong>
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <button type="button" class="btn btn-primary" onclick="openCreateQuestionWithSubject('<?= htmlspecialchars(addslashes($activeSubjectName)) ?>')" style="font-weight: 700;">
                        <span>+</span> Buat Soal <?= htmlspecialchars($activeSubject['name']) ?>
                    </button>
                    <a href="/admin/questions?subject_name=all" class="btn btn-secondary">
                        <span>🔄</span> Semua Mapel
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- GRID MAPEL KARTU RINGKASAN & BUAT SOAL CEPAT -->
            <div>
                <div style="font-size: 13px; font-weight: 700; color: var(--text-secondary); margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                    <span>🎯</span> Pilih Mata Pelajaran Untuk Buat / Kelola Soal:
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                    <?php foreach ($_SESSION['subjects_list'] as $sb): ?>
                        <?php
                        $sbCount = count(array_filter($_SESSION['questions_list'], fn($q) => ($q['subject_name'] ?? '') === $sb['name'] || ($q['subject_id'] ?? '') === $sb['id']));
                        ?>
                        <div class="card" style="padding: 12px 14px; display: flex; flex-direction: column; justify-content: space-between; border-left: 3px solid var(--primary); transition: transform 0.15s ease, box-shadow 0.15s ease;">
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                    <span class="badge badge-primary"><?= htmlspecialchars($sb['code']) ?></span>
                                    <span style="font-size: 11.5px; font-weight: 700; color: var(--primary);"><?= $sbCount ?> Soal</span>
                                </div>
                                <div style="font-weight: 700; font-size: 13.5px; color: var(--text-primary); line-height: 1.3;">
                                    <?= htmlspecialchars($sb['name']) ?>
                                </div>
                                <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 3px;">
                                    <?= htmlspecialchars($sb['teacher'] ?? 'Guru Pengampu') ?>
                                </div>
                            </div>
                            <div style="display: flex; gap: 6px; margin-top: 12px;">
                                <a href="/admin/questions?subject_name=<?= urlencode($sb['name']) ?>" class="btn btn-secondary btn-sm" style="flex: 1; padding: 5px 8px; font-size: 11.5px; justify-content: center;" title="Lihat daftar butir soal">
                                    📋 Lihat (<?= $sbCount ?>)
                                </a>
                                <a href="/admin/questions?subject_name=<?= urlencode($sb['name']) ?>&action=create" class="btn btn-primary btn-sm" style="flex: 1; padding: 5px 8px; font-size: 11.5px; justify-content: center; font-weight: 700;" title="Buat soal baru untuk mapel ini">
                                    ➕ Buat Soal
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- SEARCH & FILTER BAR -->
        <div class="card" style="padding: 14px 18px;">
            <form method="GET" action="/admin/questions" class="search-filter-bar" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <div class="search-input-group" style="flex: 2; min-width: 200px;">
                    <input type="text" name="search" class="form-control" placeholder="Cari butir soal..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="filter-select-group" style="display: flex; gap: 8px; flex: 3; min-width: 240px;">
                    <select name="subject_name" class="form-select" onchange="this.form.submit()">
                        <option value="all">Semua Mapel</option>
                        <?php foreach ($_SESSION['subjects_list'] as $sb): ?>
                            <option value="<?= htmlspecialchars($sb['name']) ?>" <?= $activeSubjectName === $sb['name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sb['name']) ?> (<?= htmlspecialchars($sb['code']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Tipe</option>
                        <option value="single_choice" <?= $type === 'single_choice' ? 'selected' : '' ?>>Pilihan Ganda</option>
                        <option value="multiple_choice" <?= $type === 'multiple_choice' ? 'selected' : '' ?>>Pilihan Majemuk</option>
                        <option value="essay" <?= $type === 'essay' ? 'selected' : '' ?>>Uraian / Essay</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary">Filter</button>
                <?php if ($search !== '' || !empty($activeSubjectName) || $type !== ''): ?>
                    <a href="/admin/questions" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- CREATE QUESTION CARD (Collapsible) -->
        <div class="card" id="createQuestionCard" style="display: <?= $action === 'create' ? 'block' : 'none' ?>; border-color: var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 18px;">📝</span>
                    <h3 class="card-title" style="margin-bottom: 0;">
                        Buat Butir Soal Baru <?= $activeSubjectName ? '— ' . htmlspecialchars($activeSubjectName) : '' ?>
                    </h3>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('createQuestionCard').style.display = 'none';">&times; Batal</button>
            </div>
            <form action="/admin/questions/create" method="POST">
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label class="form-label">Mata Pelajaran *</label>
                        <select name="subject_name" id="create_q_subject" class="form-select" required>
                            <?php foreach ($_SESSION['subjects_list'] as $sb): ?>
                                <option value="<?= htmlspecialchars($sb['name']) ?>" <?= ($activeSubjectName === $sb['name']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sb['name']) ?> (<?= htmlspecialchars($sb['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Tipe Soal *</label>
                        <select name="question_type" class="form-select" required>
                            <option value="single_choice">Pilihan Ganda</option>
                            <option value="multiple_choice">Pilihan Majemuk</option>
                            <option value="essay">Uraian / Essay</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Tingkat Kesulitan *</label>
                        <select name="difficulty" class="form-select" required>
                            <option value="easy">Mudah</option>
                            <option value="medium" selected>Sedang</option>
                            <option value="hard">Sulit</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label class="form-label">Bobot Nilai *</label>
                        <input type="number" step="0.1" name="score_weight" class="form-control" value="2.5" required>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 14px;">
                    <label class="form-label">Pertanyaan Butir Soal *</label>
                    <textarea name="content" class="textarea-control" rows="4" placeholder="Tuliskan pertanyaan butir soal secara lengkap..." required></textarea>
                </div>

                <div style="margin-top: 16px; padding: 14px 16px; background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: 8px;">
                    <div style="font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 10px;">
                        Pilihan Jawaban &amp; Kunci Benar (Pilih Radio untuk Kunci)
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <?php foreach (['A', 'B', 'C', 'D', 'E'] as $lbl): ?>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <label style="display: flex; align-items: center; gap: 4px; cursor: pointer; min-width: 45px;">
                                    <input type="radio" name="correct_option" value="<?= $lbl ?>" <?= $lbl === 'A' ? 'checked' : '' ?>>
                                    <strong><?= $lbl ?>.</strong>
                                </label>
                                <input type="text" name="opt_<?= strtolower($lbl) ?>" class="form-control" placeholder="Teks jawaban <?= $lbl ?>..." required>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('createQuestionCard').style.display = 'none';">Batal</button>
                    <button type="submit" class="btn btn-primary" style="font-weight: 700;">Simpan Butir Soal</button>
                </div>
            </form>
        </div>

        <!-- QUESTIONS TABLE -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Mata Pelajaran</th>
                            <th>Tipe &amp; Tingkat</th>
                            <th>Isi Butir Pertanyaan</th>
                            <th>Bobot</th>
                            <th>Pembuat</th>
                            <th style="width: 140px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($questions)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">📝</div>
                                        <p>Belum ada butir soal <?= $activeSubjectName ? 'untuk mata pelajaran ' . htmlspecialchars($activeSubjectName) : '' ?> yang sesuai dengan pencarian Anda.</p>
                                        <div style="margin-top: 12px;">
                                            <button type="button" class="btn btn-primary btn-sm" onclick="openCreateQuestionWithSubject('<?= htmlspecialchars(addslashes($activeSubjectName)) ?>')">
                                                + Buat Soal <?= $activeSubjectName ? htmlspecialchars($activeSubjectName) : '' ?> Sekarang
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($questions as $idx => $q): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <a href="/admin/questions?subject_name=<?= urlencode($q['subject_name'] ?? 'Matematika X') ?>" class="badge badge-info" style="text-decoration: none;" title="Filter khusus mapel ini">
                                            <?= htmlspecialchars($q['subject_name'] ?? 'Matematika X') ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; font-size: 0.85rem;">
                                            <?= ($q['question_type'] ?? 'single_choice') === 'single_choice' ? 'Pilihan Ganda' : 'Uraian' ?>
                                        </div>
                                        <div style="margin-top: 3px;">
                                            <?php if (($q['difficulty'] ?? 'easy') === 'easy'): ?>
                                                <span class="badge badge-success">Mudah</span>
                                            <?php elseif (($q['difficulty'] ?? 'easy') === 'hard'): ?>
                                                <span class="badge badge-danger">Sulit</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Sedang</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500; font-size: 0.9rem; line-height: 1.4;">
                                            <?= htmlspecialchars($q['content']) ?>
                                        </div>
                                        <?php if (!empty($q['options'])): ?>
                                            <div style="margin-top: 6px; font-size: 0.8rem; color: var(--text-muted);">
                                                <?= count($q['options']) ?> Opsi Jawaban &bull; Kunci: <strong style="color: var(--success); font-weight: 700;"><?= htmlspecialchars($q['correct_option'] ?? 'A') ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= number_format((float)($q['score_weight'] ?? 2.5), 1) ?></strong></td>
                                    <td><span style="font-size: 0.85rem; color: var(--text-secondary);"><?= htmlspecialchars($q['creator'] ?? 'Admin') ?></span></td>
                                    <td style="text-align: center;">
                                        <div class="action-btns">
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="openEditQuestion('<?= htmlspecialchars($q['id']) ?>', '<?= htmlspecialchars(addslashes($q['content'])) ?>', '<?= htmlspecialchars($q['difficulty'] ?? 'medium') ?>', <?= (float)($q['score_weight'] ?? 2.5) ?>, '<?= htmlspecialchars($q['correct_option'] ?? 'A') ?>')">
                                                Edit
                                            </button>
                                            <a href="/admin/questions/delete?id=<?= urlencode($q['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus butir soal ini?');">
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
    <div class="modal-overlay" id="editQuestionModal">
        <div class="modal-content-card" style="max-width: 540px;">
            <div class="modal-header">
                <h3 class="modal-title" style="margin: 0;">Edit Butir Soal</h3>
                <button type="button" class="modal-close-btn" onclick="document.getElementById('editQuestionModal').classList.remove('open')">&times;</button>
            </div>
            <form action="/admin/questions/edit" method="POST">
                <input type="hidden" name="id" id="edit_q_id">
                <div style="margin-bottom: 12px;">
                    <label class="form-label">Isi Pertanyaan *</label>
                    <textarea name="content" id="edit_q_content" class="textarea-control" rows="4" required></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 18px;">
                    <div>
                        <label class="form-label">Tingkat Kesulitan</label>
                        <select name="difficulty" id="edit_q_diff" class="form-select">
                            <option value="easy">Mudah</option>
                            <option value="medium">Sedang</option>
                            <option value="hard">Sulit</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Bobot Nilai</label>
                        <input type="number" step="0.1" name="score_weight" id="edit_q_weight" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Kunci Jawaban</label>
                        <select name="correct_option" id="edit_q_key" class="form-select">
                            <option value="A">Opsi A</option>
                            <option value="B">Opsi B</option>
                            <option value="C">Opsi C</option>
                            <option value="D">Opsi D</option>
                            <option value="E">Opsi E</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editQuestionModal').classList.remove('open')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <?php renderImportModalGeneric('/admin/questions/import', '/admin/questions/template', 'Bank Soal', 'template_soal'); ?>

    <script>
        function openCreateQuestionWithSubject(subjName) {
            var card = document.getElementById('createQuestionCard');
            card.style.display = 'block';
            if (subjName) {
                var sel = document.getElementById('create_q_subject');
                if (sel) {
                    sel.value = subjName;
                }
            }
            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function openEditQuestion(id, content, diff, weight, key) {
            document.getElementById('edit_q_id').value = id;
            document.getElementById('edit_q_content').value = content;
            document.getElementById('edit_q_diff').value = diff;
            document.getElementById('edit_q_weight').value = weight;
            document.getElementById('edit_q_key').value = key;
            document.getElementById('editQuestionModal').classList.add('open');
        }

        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($action === 'create'): ?>
                openCreateQuestionWithSubject('<?= htmlspecialchars(addslashes($activeSubjectName)) ?>');
            <?php endif; ?>
        });
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

function renderMonitoringLiveContent($examId) {
    $sessions = $_SESSION['monitoring_sessions'];
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="content-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 class="page-title" style="margin: 0; font-size: 1.25rem;">Telemetri Live Sesi Peserta</h1>
                <p class="page-subtitle" style="margin: 4px 0 0; font-size: 0.85rem; color: var(--text-secondary);">
                    Pemantauan langsung status waktu pengerjaan dan reset login siswa yang mengalami gangguan
                </p>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-primary btn-sm" onclick="window.location.reload();">&#8635; Segarkan Data</button>
                <a href="/admin/monitoring" class="btn btn-secondary btn-sm">&larr; Semua Ujian</a>
            </div>
        </div>

        <!-- STATS OVERVIEW CARDS -->
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
        </div>

        <!-- LIVE PARTICIPANTS TABLE -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>NIS / Nama Peserta</th>
                            <th>Kelas</th>
                            <th>Status Pengerjaan</th>
                            <th>Sisa Waktu</th>
                            <th>Progres Soal</th>
                            <th>IP Client</th>
                            <th style="width: 140px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $idx => $s): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <div style="font-weight: 700;"><?= htmlspecialchars($s['name']) ?></div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">NIS: <?= htmlspecialchars($s['nis']) ?></div>
                                </td>
                                <td><span class="badge badge-primary"><?= htmlspecialchars($s['class']) ?></span></td>
                                <td>
                                    <?php if ($s['status'] === 'Mengerjakan'): ?>
                                        <span class="badge badge-warning" style="animation: pulse 2s infinite;">Sedang Mengerjakan</span>
                                    <?php elseif (str_contains($s['status'], 'Selesai')): ?>
                                        <span class="badge badge-success">Sudah Selesai</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><?= htmlspecialchars($s['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="color: <?= $s['time_left'] === '00:00' ? 'var(--danger)' : 'var(--text-primary)' ?>;">
                                        <?= htmlspecialchars($s['time_left']) ?>
                                    </strong>
                                </td>
                                <td>
                                    <div><strong><?= (int)$s['answered'] ?></strong> / <?= (int)$s['total'] ?> Terjawab</div>
                                    <?php $pct = round(($s['answered'] / $s['total']) * 100); ?>
                                    <div style="height: 5px; background: #e2e8f0; border-radius: 3px; overflow: hidden; width: 100px; margin-top: 4px;">
                                        <div style="width: <?= $pct ?>%; height: 100%; background: var(--primary);"></div>
                                    </div>
                                </td>
                                <td><code><?= htmlspecialchars($s['ip']) ?></code></td>
                                <td style="text-align: center;">
                                    <a href="/admin/monitoring/reset?nis=<?= urlencode($s['nis']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin me-reset status sesi login <?= htmlspecialchars(addslashes($s['name'])) ?>?');">
                                        🔄 Reset Login
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
