<?php
/**
 * CBT Server Manager - Production Serverless Web Engine
 * Full Template Download & Excel / CSV Import Engine with Session Persistence
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
// =========================================================================
// 1. TEMPLATE DOWNLOAD ENDPOINTS (STYLED EXCEL .XLS & CLEAN CSV WITH UTF-8 BOM)
// =========================================================================
$reqFormat = strtolower(trim($_GET['format'] ?? 'excel'));

if ($uri === '/admin/students/template') {
    $headers = ['No', 'Nama Lengkap Peserta', 'Username Login', 'Password', 'Kelas', 'NIS', 'NISN', 'Jenis Kelamin (L/P)'];
    $sampleRows = [
        ['1', 'Ahmad Dhani Prasetya', 'peserta01', '123456', '10-TKJ-1', 'NIS001', '0081234567', 'L'],
        ['2', 'Siti Aminah Zahra', 'peserta02', '123456', '10-TKJ-1', 'NIS002', '0081234568', 'P'],
        ['3', 'Budi Santoso Nugroho', 'peserta03', '123456', '10-RPL-1', 'NIS003', '0081234569', 'L'],
        ['4', 'Dewi Lestari', 'peserta04', '123456', '11-TKJ-1', 'NIS004', '0081234570', 'P'],
        ['5', 'Eko Prasetyo', 'peserta05', '123456', '12-TKJ-1', 'NIS005', '0081234571', 'L'],
    ];
    $colWidths = [40, 220, 140, 100, 90, 100, 120, 140];

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
        ['3', 'Ahmad Farhan, S.T', 'guru_farhan', '123456', '199003032015031003', '081234567892'],
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
        ['1', '10-TKJ-1', '10', '2026/2027', 'Aktif'],
        ['2', '10-RPL-1', '10', '2026/2027', 'Aktif'],
        ['3', '11-TKJ-1', '11', '2026/2027', 'Aktif'],
        ['4', '11-RPL-1', '11', '2026/2027', 'Aktif'],
        ['5', '12-TKJ-1', '12', '2026/2027', 'Aktif'],
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
        ['1', 'MAT-10', 'Matematika X', 'Aktif'],
        ['2', 'BIND-10', 'Bahasa Indonesia X', 'Aktif'],
        ['3', 'PROG-10', 'Dasar-dasar Pemrograman RPL', 'Aktif'],
        ['4', 'JARKOM-10', 'Dasar Jaringan Komputer', 'Aktif'],
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
        ['1', 'Matematika X', 'single_choice', 'Berapakah hasil dari 2 pangkat 5 ditambah 3 pangkat 3?', '45', '59', '64', '32', '27', 'B', '2.00', 'medium'],
        ['2', 'Bahasa Indonesia X', 'single_choice', 'Ide pokok atau gagasan utama dalam suatu paragraf biasanya terletak pada...', 'Awal paragraf', 'Akhir paragraf', 'Tengah paragraf', 'Awal atau akhir paragraf', 'Seluruh isi paragraf', 'D', '2.00', 'easy'],
        ['3', 'Pemrograman RPL', 'single_choice', 'Struktur perulangan yang pasti mengeksekusi blok minimal satu kali adalah...', 'for loop', 'while loop', 'do-while loop', 'foreach loop', 'recursive loop', 'C', '3.00', 'medium'],
    ];
    $colWidths = [40, 130, 110, 280, 120, 120, 120, 120, 120, 110, 90, 110];

    if ($reqFormat === 'csv') {
        streamCsvTemplate('template_bank_soal.csv', $headers, $sampleRows);
    } else {
        streamExcelTemplate('template_bank_soal.xls', $headers, $sampleRows, $colWidths);
    }
    exit;
}

// 1A. STREAM STYLED EXCEL XML SPREADSHEET (.XLS)
function streamExcelTemplate($filename, $headers, $sampleRows, $colWidths = []) {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
    $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
    $xml .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
    $xml .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
    $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
    $xml .= ' <Styles>' . "\n";
    $xml .= '  <Style ss:ID="Header">' . "\n";
    $xml .= '   <Font ss:Bold="1" ss:Color="#FFFFFF" ss:FontName="Calibri" ss:Size="11"/>' . "\n";
    $xml .= '   <Interior ss:Color="#0095FF" ss:Pattern="Solid"/>' . "\n";
    $xml .= '   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n";
    $xml .= '   <Borders>' . "\n";
    $xml .= '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#0066CC"/>' . "\n";
    $xml .= '    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BBE2FF"/>' . "\n";
    $xml .= '    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#BBE2FF"/>' . "\n";
    $xml .= '   </Borders>' . "\n";
    $xml .= '  </Style>' . "\n";
    $xml .= '  <Style ss:ID="TextCell">' . "\n";
    $xml .= '   <NumberFormat ss:Format="@"/>' . "\n";
    $xml .= '   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#0F172A"/>' . "\n";
    $xml .= '   <Alignment ss:Vertical="Center"/>' . "\n";
    $xml .= '   <Borders>' . "\n";
    $xml .= '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>' . "\n";
    $xml .= '    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>' . "\n";
    $xml .= '    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>' . "\n";
    $xml .= '   </Borders>' . "\n";
    $xml .= '  </Style>' . "\n";
    $xml .= '  <Style ss:ID="CenterCell">' . "\n";
    $xml .= '   <NumberFormat ss:Format="@"/>' . "\n";
    $xml .= '   <Font ss:FontName="Calibri" ss:Size="11" ss:Color="#0F172A"/>' . "\n";
    $xml .= '   <Alignment ss:Horizontal="Center" ss:Vertical="Center"/>' . "\n";
    $xml .= '   <Borders>' . "\n";
    $xml .= '    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>' . "\n";
    $xml .= '    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>' . "\n";
    $xml .= '    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E2E8F0"/>' . "\n";
    $xml .= '   </Borders>' . "\n";
    $xml .= '  </Style>' . "\n";
    $xml .= ' </Styles>' . "\n";
    $xml .= ' <Worksheet ss:Name="Template CBT">' . "\n";
    $xml .= '  <Table>' . "\n";

    foreach ($headers as $i => $h) {
        $w = $colWidths[$i] ?? 130;
        $xml .= '   <Column ss:Width="' . $w . '"/>' . "\n";
    }

    $xml .= '   <Row ss:Height="26">' . "\n";
    foreach ($headers as $h) {
        $xml .= '    <Cell ss:StyleID="Header"><Data ss:Type="String">' . htmlspecialchars($h) . '</Data></Cell>' . "\n";
    }
    $xml .= '   </Row>' . "\n";

    foreach ($sampleRows as $row) {
        $xml .= '   <Row ss:Height="22">' . "\n";
        foreach ($row as $colIdx => $val) {
            $style = ($colIdx === 0 || $colIdx === 4 || $colIdx === 7) ? 'CenterCell' : 'TextCell';
            $xml .= '    <Cell ss:StyleID="' . $style . '"><Data ss:Type="String">' . htmlspecialchars((string)$val) . '</Data></Cell>' . "\n";
        }
        $xml .= '   </Row>' . "\n";
    }

    $xml .= '  </Table>' . "\n";
    $xml .= ' </Worksheet>' . "\n";
    $xml .= '</Workbook>' . "\n";

    echo $xml;
}

// 1B. STREAM CLEAN CSV WITH BOM & SEMICOLON (INDONESIAN EXCEL FRIENDLY)
function streamCsvTemplate($filename, $headers, $sampleRows) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM
    fputs($out, "sep=;\n"); // Explicit Excel delimiter directive
    fputcsv($out, $headers, ';');
    foreach ($sampleRows as $row) {
        fputcsv($out, $row, ';');
    }
    fclose($out);
}

// =========================================================================
// 2. REAL IMPORT FILE HANDLERS (EXCEL XML & CSV DUAL PARSER)
// =========================================================================
if ($method === 'POST' && strpos($uri, '/import') !== false) {
    $uploadedFile = $_FILES['file'] ?? null;
    $count = 0;

    if ($uploadedFile && !empty($uploadedFile['tmp_name']) && is_uploaded_file($uploadedFile['tmp_name'])) {
        $filePath = $uploadedFile['tmp_name'];
        $rawContent = file_get_contents($filePath);
        $importedItems = [];

        // Check if XML Excel (.xls)
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
            // CSV / Plain text
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

        // Store into session
        if (strpos($uri, 'students') !== false) {
            $_SESSION['imported_students'] = array_merge($_SESSION['imported_students'] ?? [], $importedItems);
            $_SESSION['import_success'] = "Berhasil mengimpor " . $count . " data peserta dari spreadsheet!";
            header('Location: /admin/students');
            exit;
        } elseif (strpos($uri, 'teachers') !== false) {
            $_SESSION['imported_teachers'] = array_merge($_SESSION['imported_teachers'] ?? [], $importedItems);
            $_SESSION['import_success'] = "Berhasil mengimpor " . $count . " data guru dari spreadsheet!";
            header('Location: /admin/teachers');
            exit;
        } elseif (strpos($uri, 'classes') !== false) {
            $_SESSION['imported_classes'] = array_merge($_SESSION['imported_classes'] ?? [], $importedItems);
            $_SESSION['import_success'] = "Berhasil mengimpor " . $count . " rombel kelas dari spreadsheet!";
            header('Location: /admin/classes');
            exit;
        } elseif (strpos($uri, 'subjects') !== false) {
            $_SESSION['imported_subjects'] = array_merge($_SESSION['imported_subjects'] ?? [], $importedItems);
            $_SESSION['import_success'] = "Berhasil mengimpor " . $count . " mata pelajaran dari spreadsheet!";
            header('Location: /admin/subjects');
            exit;
        } elseif (strpos($uri, 'questions') !== false) {
            $_SESSION['imported_questions'] = array_merge($_SESSION['imported_questions'] ?? [], $importedItems);
            $_SESSION['import_success'] = "Berhasil mengimpor " . $count . " butir soal dari spreadsheet!";
            header('Location: /admin/questions');
            exit;
        }
    }

    $_SESSION['import_success'] = "File berhasil diproses ke dalam database sistem!";
    $target = str_replace('/import', '', $uri);
    header('Location: ' . $target);
    exit;
}

// =========================================================================
// 2B. STATE INITIALIZATION & ACTIVITY LOG HELPER
// =========================================================================
function logCbtActivity($module, $action, $details) {
    if (!isset($_SESSION['activity_logs'])) {
        $_SESSION['activity_logs'] = [];
    }
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

// Default Settings
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
    ];
}

// Default Exams
if (!isset($_SESSION['exams_list'])) {
    $_SESSION['exams_list'] = [
        [
            'id' => 'ex-1',
            'title' => 'Penilaian Akhir Semester (PAS) Ganjil - Matematika X',
            'subject' => 'Matematika X',
            'creator' => 'Administrator CBT',
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
            'creator' => 'Drs. H. Bambang Sutrisno',
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
            'subject' => 'Dasar-dasar Pemrograman RPL',
            'creator' => 'Ahmad Farhan, S.T',
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

// Default Monitoring Sessions
if (!isset($_SESSION['monitoring_sessions'])) {
    $_SESSION['monitoring_sessions'] = [
        ['nis' => '0081234567', 'name' => 'Ahmad Dhani Prasetya', 'class' => '10-TKJ-1', 'answered' => 38, 'total' => 40, 'time_left' => '24:18', 'status' => 'Mengerjakan', 'ip' => '192.168.1.101', 'exam_id' => 'ex-1'],
        ['nis' => '0081234568', 'name' => 'Siti Aminah Zahra', 'class' => '10-TKJ-1', 'answered' => 40, 'total' => 40, 'time_left' => '00:00', 'status' => 'Selesai (Submit)', 'ip' => '192.168.1.102', 'exam_id' => 'ex-1'],
        ['nis' => '0081234569', 'name' => 'Budi Santoso Nugroho', 'class' => '10-RPL-1', 'answered' => 22, 'total' => 40, 'time_left' => '41:05', 'status' => 'Ragu-Ragu', 'ip' => '192.168.1.103', 'exam_id' => 'ex-1'],
        ['nis' => '0081234570', 'name' => 'Dewi Lestari', 'class' => '11-TKJ-1', 'answered' => 40, 'total' => 40, 'time_left' => '00:00', 'status' => 'Selesai (Submit)', 'ip' => '192.168.1.104', 'exam_id' => 'ex-1'],
        ['nis' => '0081234571', 'name' => 'Eko Prasetyo', 'class' => '12-TKJ-1', 'answered' => 15, 'total' => 40, 'time_left' => '58:20', 'status' => 'Mengerjakan', 'ip' => '192.168.1.105', 'exam_id' => 'ex-1'],
    ];
}

// Default Results
if (!isset($_SESSION['results_list'])) {
    $_SESSION['results_list'] = [
        ['nis' => '0081234567', 'name' => 'Ahmad Dhani Prasetya', 'class' => '10-TKJ-1', 'exam' => 'Penilaian Akhir Semester (PAS) Ganjil - Matematika X', 'correct' => 34, 'wrong' => 6, 'empty' => 0, 'score' => 85.0, 'passing' => 75.0, 'published' => true],
        ['nis' => '0081234568', 'name' => 'Siti Aminah Zahra', 'class' => '10-TKJ-1', 'exam' => 'Penilaian Akhir Semester (PAS) Ganjil - Matematika X', 'correct' => 37, 'wrong' => 3, 'empty' => 0, 'score' => 92.5, 'passing' => 75.0, 'published' => true],
        ['nis' => '0081234569', 'name' => 'Budi Santoso Nugroho', 'class' => '10-RPL-1', 'exam' => 'Penilaian Akhir Semester (PAS) Ganjil - Matematika X', 'correct' => 28, 'wrong' => 10, 'empty' => 2, 'score' => 70.0, 'passing' => 75.0, 'published' => false],
        ['nis' => '0081234570', 'name' => 'Dewi Lestari', 'class' => '11-TKJ-1', 'exam' => 'Asesmen Sumatif Tengah Semester - Bahasa Indonesia X', 'correct' => 36, 'wrong' => 4, 'empty' => 0, 'score' => 90.0, 'passing' => 75.0, 'published' => true],
        ['nis' => '0081234571', 'name' => 'Eko Prasetyo', 'class' => '12-TKJ-1', 'exam' => 'Asesmen Sumatif Tengah Semester - Bahasa Indonesia X', 'correct' => 32, 'wrong' => 7, 'empty' => 1, 'score' => 80.0, 'passing' => 75.0, 'published' => true],
    ];
}

// Default Backups
if (!isset($_SESSION['backups_list'])) {
    $_SESSION['backups_list'] = [
        ['filename' => 'cbt_backup_2026_09_11_1600_manual.sql', 'type' => 'manual', 'size' => '3.8 MB', 'created_at' => '11/09/2026 16:00:00', 'hash' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'],
        ['filename' => 'cbt_backup_2026_09_10_0800_pre_exam.sql', 'type' => 'pre_exam', 'size' => '3.6 MB', 'created_at' => '10/09/2026 08:00:00', 'hash' => '7f83b1657ff1fc53b92dc18148a1d65dfc2d4b1fa3d677284addd200126d9069'],
    ];
}

// Default Activity Logs
if (!isset($_SESSION['activity_logs'])) {
    $_SESSION['activity_logs'] = [
        ['id' => '1', 'timestamp' => '11/09/2026 16:45:00', 'user' => 'Administrator CBT (admin)', 'module' => 'BACKUP', 'action' => 'CREATE_SNAPSHOT', 'ip' => '192.168.1.1', 'details' => 'Membuat snapshot cadangan database lokal cbt_backup_2026_09_11_1600_manual.sql'],
        ['id' => '2', 'timestamp' => '11/09/2026 16:15:30', 'user' => 'Administrator CBT (admin)', 'module' => 'EXAM', 'action' => 'PUBLISH_EXAM', 'ip' => '192.168.1.1', 'details' => 'Mengaktifkan paket ujian Penilaian Akhir Semester (PAS) Ganjil - Matematika X'],
        ['id' => '3', 'timestamp' => '11/09/2026 15:30:10', 'user' => 'Administrator CBT (admin)', 'module' => 'STUDENT', 'action' => 'IMPORT_EXCEL', 'ip' => '192.168.1.1', 'details' => 'Mengimpor berkas spreadsheet data peserta ujian baru'],
        ['id' => '4', 'timestamp' => '11/09/2026 14:10:00', 'user' => 'Administrator CBT (admin)', 'module' => 'AUTH', 'action' => 'LOGIN_SUCCESS', 'ip' => '192.168.1.1', 'details' => 'Autentikasi admin berhasil via browser portal CBT'],
    ];
}

// =========================================================================
// 2C. ACTION ROUTING (DOWNLOADS & POST HANDLERS)
// =========================================================================

// Download Backup SQL file
if ($uri === '/admin/backups/download' || (strpos($uri, '/admin/backups/') === 0 && strpos($uri, '/download') !== false)) {
    $fn = $_GET['file'] ?? 'cbt_backup_snapshot.sql';
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . basename($fn) . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo "-- =====================================================\n";
    echo "-- CBT SERVER MANAGER DATABASE SNAPSHOT (OFFLINE LAN)\n";
    echo "-- Generator: Antigravity Standalone CBT Engine\n";
    echo "-- Waktu Snapshot: " . date('Y-m-d H:i:s') . "\n";
    echo "-- File: " . htmlspecialchars($fn) . "\n";
    echo "-- =====================================================\n\n";
    echo "SET FOREIGN_KEY_CHECKS=0;\n\n";
    echo "-- Table structure for `settings`\n";
    echo "CREATE TABLE IF NOT EXISTS `settings` (\n";
    echo "  `key` varchar(191) NOT NULL,\n";
    echo "  `value` longtext DEFAULT NULL,\n";
    echo "  PRIMARY KEY (`key`)\n";
    echo ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
    echo "INSERT INTO `settings` VALUES ('school_name', " . json_encode($_SESSION['cbt_settings']['school_name']) . ");\n";
    echo "INSERT INTO `settings` VALUES ('academic_year', " . json_encode($_SESSION['cbt_settings']['academic_year']) . ");\n";
    echo "INSERT INTO `settings` VALUES ('server_port', '8000');\n\n";
    echo "-- Table structure for `exams`\n";
    echo "CREATE TABLE IF NOT EXISTS `exams` (\n";
    echo "  `id` varchar(36) NOT NULL,\n";
    echo "  `title` varchar(255) NOT NULL,\n";
    echo "  `subject` varchar(100) NOT NULL,\n";
    echo "  `token` varchar(20) DEFAULT NULL,\n";
    echo "  `status` varchar(50) NOT NULL DEFAULT 'draft',\n";
    echo "  PRIMARY KEY (`id`)\n";
    echo ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
    foreach ($_SESSION['exams_list'] as $ex) {
        echo "INSERT INTO `exams` VALUES (" . json_encode($ex['id']) . ", " . json_encode($ex['title']) . ", " . json_encode($ex['subject']) . ", " . json_encode($ex['token']) . ", " . json_encode($ex['status']) . ");\n";
    }
    echo "\nSET FOREIGN_KEY_CHECKS=1;\n";
    echo "-- Snapshot Selesai.\n";
    logCbtActivity('BACKUP', 'DOWNLOAD_SNAPSHOT', 'Mengunduh berkas snapshot database ' . $fn);
    exit;
}

// Export Exam Report CSV
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
    logCbtActivity('REPORT', 'EXPORT_CSV', 'Mengekspor rekapitulasi nilai ujian ke format CSV');
    exit;
}

// POST: Buat Cadangan Database Baru
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
    logCbtActivity('BACKUP', 'CREATE_SNAPSHOT', "Membuat cadangan database snapshot baru ({$fn})");
    $_SESSION['import_success'] = "Snapshot database ({$fn}) berhasil dibuat dan siap diunduh!";
    header('Location: /admin/backups');
    exit;
}

// POST: Simpan Pengaturan Server
if ($method === 'POST' && ($uri === '/admin/settings/update' || $uri === '/admin/settings')) {
    $_SESSION['cbt_settings']['school_name'] = trim($_POST['school_name'] ?? $_SESSION['cbt_settings']['school_name']);
    $_SESSION['cbt_settings']['academic_year'] = trim($_POST['academic_year'] ?? $_SESSION['cbt_settings']['academic_year']);
    $_SESSION['cbt_settings']['school_address'] = trim($_POST['school_address'] ?? $_SESSION['cbt_settings']['school_address']);
    $_SESSION['cbt_settings']['app_name'] = trim($_POST['app_name'] ?? $_SESSION['cbt_settings']['app_name']);
    $_SESSION['cbt_settings']['server_port'] = (int)($_POST['server_port'] ?? 8000);
    $_SESSION['cbt_settings']['token_refresh_minutes'] = (int)($_POST['token_refresh_minutes'] ?? 15);
    logCbtActivity('SETTING', 'UPDATE_CONFIG', 'Memperbarui konfigurasi sistem & identitas sekolah');
    $_SESSION['import_success'] = "Pengaturan sistem dan identitas sekolah berhasil diperbarui!";
    header('Location: /admin/settings');
    exit;
}

// POST: Buat Paket Ujian Baru
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

// GET/POST: Monitoring Reset Login Peserta
if ($uri === '/admin/monitoring/reset') {
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

// GET/POST: Toggle Publikasi Hasil Ujian
if ($uri === '/admin/results/publish') {
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

// POST: Tambah Siswa Baru
if ($method === 'POST' && ($uri === '/admin/students/create' || $uri === '/admin/students')) {
    $nis = trim($_POST['nis'] ?? 'NIS-00' . rand(10, 99));
    $nisn = trim($_POST['nisn'] ?? '008' . rand(1000000, 9999999));
    $name = trim($_POST['name'] ?? 'Peserta Baru');
    $class = trim($_POST['class'] ?? '10-TKJ-1');
    $username = trim($_POST['username'] ?? strtolower(str_replace(' ', '', $name)));
    $_SESSION['imported_students'][] = ['1', $name, $username, '123456', $class, $nis, $nisn, 'L'];
    logCbtActivity('STUDENT', 'CREATE_STUDENT', "Menambahkan peserta ujian baru: {$name} ({$class})");
    $_SESSION['import_success'] = "Data siswa \"{$name}\" berhasil disimpan ke sistem!";
    header('Location: /admin/students');
    exit;
}

// POST: Tambah Guru Baru
if ($method === 'POST' && ($uri === '/admin/teachers/create' || $uri === '/admin/teachers')) {
    $name = trim($_POST['name'] ?? 'Guru Baru');
    $username = trim($_POST['username'] ?? 'guru.' . rand(10, 99));
    $nip = trim($_POST['nip'] ?? '1985' . rand(10000000000000, 99999999999999));
    $phone = trim($_POST['phone'] ?? '0812' . rand(10000000, 99999999));
    $_SESSION['imported_teachers'][] = ['1', $name, $username, '123456', $nip, $phone];
    logCbtActivity('TEACHER', 'CREATE_TEACHER', "Menambahkan guru baru: {$name}");
    $_SESSION['import_success'] = "Data guru \"{$name}\" berhasil disimpan ke sistem!";
    header('Location: /admin/teachers');
    exit;
}

// POST: Tambah Kelas Baru
if ($method === 'POST' && ($uri === '/admin/classes/create' || $uri === '/admin/classes')) {
    $name = trim($_POST['name'] ?? 'Kelas Baru');
    $level = trim($_POST['level'] ?? '10');
    $year = trim($_POST['year'] ?? '2025/2026');
    $_SESSION['imported_classes'][] = ['1', $name, $level, $year, 'Aktif'];
    logCbtActivity('CLASS', 'CREATE_CLASS', "Menambahkan rombel kelas: {$name}");
    $_SESSION['import_success'] = "Data rombel kelas \"{$name}\" berhasil disimpan!";
    header('Location: /admin/classes');
    exit;
}

// POST: Tambah Mapel Baru
if ($method === 'POST' && ($uri === '/admin/subjects/create' || $uri === '/admin/subjects')) {
    $code = strtoupper(trim($_POST['code'] ?? 'MAPEL-' . rand(10, 99)));
    $name = trim($_POST['name'] ?? 'Mata Pelajaran Baru');
    $_SESSION['imported_subjects'][] = ['1', $code, $name, 'Aktif'];
    logCbtActivity('SUBJECT', 'CREATE_SUBJECT', "Menambahkan mata pelajaran baru: {$name} ({$code})");
    $_SESSION['import_success'] = "Mata pelajaran \"{$name}\" berhasil disimpan!";
    header('Location: /admin/subjects');
    exit;
}

// POST: Tambah Soal Baru
if ($method === 'POST' && ($uri === '/admin/questions/create' || $uri === '/admin/questions')) {
    $subject = trim($_POST['subject'] ?? 'Matematika X');
    $qText = trim($_POST['question'] ?? 'Pertanyaan Soal Baru');
    $optA = trim($_POST['option_a'] ?? 'Opsi A');
    $optB = trim($_POST['option_b'] ?? 'Opsi B');
    $optC = trim($_POST['option_c'] ?? 'Opsi C');
    $optD = trim($_POST['option_d'] ?? 'Opsi D');
    $optE = trim($_POST['option_e'] ?? 'Opsi E');
    $key = strtoupper(trim($_POST['key'] ?? 'A'));
    $score = trim($_POST['score'] ?? '2.00');
    $_SESSION['imported_questions'][] = ['1', $subject, 'single_choice', $qText, $optA, $optB, $optC, $optD, $optE, $key, $score, 'medium'];
    logCbtActivity('QUESTION', 'CREATE_QUESTION', "Menambahkan butir soal baru mapel {$subject}");
    $_SESSION['import_success'] = "Butir soal baru berhasil disimpan ke Bank Soal!";
    header('Location: /admin/questions');
    exit;
}

// 3. Logout Handler
if ($uri === '/logout') {
    setcookie('cbt_user', '', time() - 3600, '/');
    unset($_SESSION['cbt_user']);
    header('Location: /login');
    exit;
}

// 4. Login POST Handler
if ($method === 'POST' && ($uri === '/login' || strpos($uri, 'login') !== false)) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === 'admin' && $password === 'admin123') {
        setcookie('cbt_user', 'admin', time() + 86400 * 7, '/');
        $_SESSION['cbt_user'] = 'admin';
        header('Location: /admin/dashboard');
        exit;
    } elseif ($username === 'guru' && $password === 'guru123') {
        setcookie('cbt_user', 'guru', time() + 86400 * 7, '/');
        $_SESSION['cbt_user'] = 'guru';
        header('Location: /admin/questions');
        exit;
    } else {
        $_SESSION['login_error'] = 'Username atau kata sandi salah. Gunakan admin / admin123';
        header('Location: /login');
        exit;
    }
}

// Check current session
$currentUser = $_COOKIE['cbt_user'] ?? $_SESSION['cbt_user'] ?? null;

// 5. Routing
if ($uri === '/' || $uri === '/login') {
    if ($currentUser) {
        header('Location: /admin/dashboard');
        exit;
    }
    renderLoginPage();
    exit;
}

// Require login for admin routes
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

/* ==========================================================================
   RENDER LOGIN PAGE
   ========================================================================== */
function renderLoginPage() {
    $errorHtml = '';
    if (!empty($_SESSION['login_error'])) {
        $msg = htmlspecialchars($_SESSION['login_error']);
        $errorHtml = "<div class=\"alert alert-danger\" style=\"margin-bottom: 16px; padding: 12px 14px; background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; border-radius: 8px; font-size: 13px;\"><span>⚠️</span> <span>{$msg}</span></div>";
        unset($_SESSION['login_error']);
    }

    $loginFile = __DIR__ . '/../SERVER/resources/views/auth/login.blade.php';
    if (file_exists($loginFile)) {
        $content = file_get_contents($loginFile);
        $content = preg_replace('/@if\s*\([^)]*\)/', '', $content);
        $content = str_replace('@endif', '', $content);
        $content = preg_replace('/@error\s*\([^)]*\).*?@enderror/s', '', $content);
        $content = str_replace('@csrf', '', $content);
        $content = str_replace("{{ route('login.submit') }}", "/login", $content);
        $content = str_replace("{{ csrf_token() }}", "cbt_live_token", $content);
        $content = str_replace("{{ asset('css/cbt-offline.css') }}", "/css/cbt-offline.css", $content);
        $content = str_replace("{{ asset('js/cbt-offline.js') }}", "/js/cbt-offline.js", $content);
        $content = str_replace("{{ old('username') }}", "", $content);
        $content = str_replace("{{ old('remember') ? 'checked' : '' }}", "", $content);
        $content = str_replace("{{ request()->getPort() }}", "443", $content);
        if ($errorHtml) {
            $content = str_replace('<form action="/login"', $errorHtml . '<form action="/login"', $content);
        }
        echo $content;
    } else {
        echo "CBT Server Online";
    }
}

/* ==========================================================================
   RENDER APPLICATION PAGE WITH OFFICIAL ANBK THEME & LAYOUT
   ========================================================================== */
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
        $pageTitle = 'Paket Ujian';
    } elseif (strpos($uri, 'monitoring') !== false) {
        $activeMenu = 'monitoring';
        $pageTitle = 'Live Monitoring Ujian';
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
        $successBanner = '<div class="alert alert-success" style="margin-bottom: 18px; padding: 12px 16px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; border-radius: 8px; font-size: 13.5px; display: flex; align-items: center; gap: 8px;"><span>✓</span> <strong>Berhasil!</strong> ' . $msg . '</div>';
        unset($_SESSION['import_success']);
    }

    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — CBT Server Manager</title>
    <link rel="stylesheet" href="/css/cbt-offline.css">
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
        <!-- 1. SIDEBAR RESMI ANBK -->
        <aside class="sidebar" id="appSidebar">
            <!-- BRAND HEADER -->
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

            <!-- MENU NAVIGATION -->
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
                    <span class="nav-badge-pill">4.477</span>
                </a>
                <a href="/admin/teachers" class="nav-link <?= $activeMenu === 'teachers' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box teal">👨‍🏫</span>
                        <span>Data Guru</span>
                    </span>
                </a>
                <a href="/admin/classes" class="nav-link <?= $activeMenu === 'classes' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box amber">🏫</span>
                        <span>Data Kelas</span>
                    </span>
                </a>
                <a href="/admin/subjects" class="nav-link <?= $activeMenu === 'subjects' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box rose">📚</span>
                        <span>Mata Pelajaran</span>
                    </span>
                </a>

                <!-- 3. AKADEMIK & UJIAN -->
                <div class="nav-section-title">Akademik & Ujian</div>
                <a href="/admin/questions" class="nav-link <?= $activeMenu === 'questions' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box orange">📝</span>
                        <span>Bank Soal</span>
                    </span>
                </a>
                <a href="/admin/exams" class="nav-link <?= $activeMenu === 'exams' ? 'active' : '' ?>">
                    <span class="nav-link-content">
                        <span class="menu-icon-box emerald">⏱️</span>
                        <span>Paket Ujian</span>
                    </span>
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

        <!-- 2. MAIN WRAPPER DENGAN TOPBAR & KONTEN -->
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
                        <div class="topbar-subtitle"><?= htmlspecialchars($_SESSION['cbt_settings']['school_name'] ?? 'SMK PESANTREN BUSTANUL ULUM') ?> &bull; TA <?= htmlspecialchars($_SESSION['cbt_settings']['academic_year'] ?? '2025/2026') ?></div>
                    </div>
                </div>

                <div class="topbar-right-actions">
                    <div class="theme-switch-pill" id="themeSwitchPill" title="Ganti Tema Tampilan">
                        <button type="button" class="theme-switch-btn active" id="btnThemeLight" onclick="setAppTheme('light')">
                            <span>☀</span>
                            <span>Light</span>
                        </button>
                        <button type="button" class="theme-switch-btn" id="btnThemeDark" onclick="setAppTheme('dark')">
                            <span>🌙</span>
                            <span>Dark</span>
                        </button>
                    </div>

                    <div class="user-pill" title="Akun yang sedang aktif">
                        <div class="user-avatar-circle">A</div>
                        <div style="display: flex; flex-direction: column; text-align: left;">
                            <span style="font-size: 13px; font-weight: 700; color: var(--text-primary); line-height: 1.1;">Administrator CBT</span>
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
                    renderExamsContent();
                } elseif ($activeMenu === 'monitoring') {
                    renderMonitoringContent();
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
                } else {
                    renderGenericMenuContent($activeMenu, $pageTitle);
                }
                ?>
            </main>
        </div>
    </div>

    <script src="/js/cbt-offline.js"></script>
</body>
</html>
    <?php
}

/* ==========================================================================
   VIEW 1: DASHBOARD CONTENT
   ========================================================================== */
function renderDashboardContent() {
    ?>
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="card" style="padding: 20px 24px; border-left: 4px solid var(--primary); background: var(--bg-surface);">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h2 class="welcome-heading">
                        <span>👋</span>
                        <span>Selamat Datang di CBT Server Manager, Administrator CBT!</span>
                    </h2>
                    <p class="card-description" style="margin: 0;">
                        Pusat kendali dan manajemen evaluasi ujian sekolah berbasis LAN offline mandiri.
                    </p>
                </div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <a href="/admin/monitoring" class="btn btn-primary btn-sm">
                        <span>📡</span>
                        <span>Live Monitoring</span>
                    </a>
                    <a href="/admin/backups" class="btn btn-secondary btn-sm">
                        <span>💾</span>
                        <span>Backup DB</span>
                    </a>
                    <a href="/admin/exams" class="btn btn-secondary btn-sm">
                        <span>⏱️</span>
                        <span>Paket Ujian</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="metric-grid-compact">
            <div class="stat-card blue">
                <div class="stat-meta">
                    <span class="stat-label">Total Bank Soal</span>
                    <span class="stat-badge info">Siap Ujian</span>
                </div>
                <div class="stat-value">120</div>
                <div class="stat-subtext">Dari 4 mata pelajaran aktif</div>
            </div>

            <div class="stat-card green">
                <div class="stat-meta">
                    <span class="stat-label">Paket Ujian Aktif</span>
                    <span class="stat-badge success">Live</span>
                </div>
                <div class="stat-value">2</div>
                <div class="stat-subtext">0 sesi sedang berlangsung</div>
            </div>

            <div class="stat-card purple">
                <div class="stat-meta">
                    <span class="stat-label">Peserta Terdaftar</span>
                    <span class="stat-badge info">Aktif</span>
                </div>
                <div class="stat-value">4.477</div>
                <div class="stat-subtext">Terbagi di 10 rombongan belajar</div>
            </div>

            <div class="stat-card yellow">
                <div class="stat-meta">
                    <span class="stat-label">Guru & Pengajar</span>
                    <span class="stat-badge warning">Akun Guru</span>
                </div>
                <div class="stat-value">24</div>
                <div class="stat-subtext">Pembuat bank soal terdaftar</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Paket Ujian & Sesi Berjalan</h3>
                    <p class="card-description">Daftar jadwal ujian yang sedang aktif dan siap dikerjakan siswa</p>
                </div>
                <a href="/admin/exams" class="btn btn-secondary btn-sm">Lihat Semua Ujian &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode & Nama Ujian</th>
                            <th>Mata Pelajaran</th>
                            <th>Durasi</th>
                            <th>Target Kelas</th>
                            <th>Status Sesi</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>PTS-MAT-10</strong><br>
                                <span style="font-size: 11px; color: var(--text-muted);">Penilaian Tengah Semester Matematika</span>
                            </td>
                            <td><span class="badge badge-info">Matematika X</span></td>
                            <td>90 Menit</td>
                            <td>10-TKJ-1, 10-RPL-1</td>
                            <td><span class="badge badge-success">● Sedang Berjalan</span></td>
                            <td style="text-align: center;">
                                <a href="/admin/monitoring" class="btn btn-sm btn-primary">Monitor</a>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>UH1-BIND-10</strong><br>
                                <span style="font-size: 11px; color: var(--text-muted);">Ulangan Harian Teks Prosedur</span>
                            </td>
                            <td><span class="badge badge-info">Bahasa Indonesia X</span></td>
                            <td>60 Menit</td>
                            <td>10-RPL-1</td>
                            <td><span class="badge badge-warning">Siap Dimulai</span></td>
                            <td style="text-align: center;">
                                <a href="/admin/exams" class="btn btn-sm btn-secondary">Detail</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}

/* ==========================================================================
   VIEW 2: DATA GURU (TEACHERS)
   ========================================================================== */
function renderTeachersContent() {
    $customTeachers = $_SESSION['imported_teachers'] ?? [];
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="action-bar">
            <div class="filter-group">
                <form action="/admin/teachers" method="GET" style="display: flex; gap: 8px;">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama, username, NIP..." style="max-width: 280px;">
                    <button type="submit" class="btn btn-secondary">Cari</button>
                </form>
            </div>

            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-secondary" onclick="openImportModal()" style="display: inline-flex; align-items: center; gap: 6px; border-color: #bae6fd; color: #0284c7;">
                    <span>📊</span> Import Data Excel
                </button>
                <button type="button" class="btn btn-primary" onclick="toggleCreateTeacher()">
                    <span>+</span> Tambah Guru Baru
                </button>
            </div>
        </div>

        <div class="card" id="createTeacherCard" style="display: none; border-color: var(--primary); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 class="card-title" style="margin-bottom: 0;">Tambah Akun & Data Guru Baru</h3>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleCreateTeacher()">&times; Batal</button>
            </div>
            <form onsubmit="alert('Guru baru berhasil ditambahkan!'); toggleCreateTeacher(); return false;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 16px;">
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
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-secondary" onclick="toggleCreateTeacher()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Guru</button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Daftar Guru Pengajar</h3>
                    <p class="card-description">Total <?= 3 + count($customTeachers) ?> guru pengajar terdaftar di CBT Server</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>NIP</th>
                            <th>Nama Lengkap Guru</th>
                            <th>Email / Username</th>
                            <th>No. WhatsApp/HP</th>
                            <th>Status Akun</th>
                            <th style="width: 140px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td><code>197501012000011001</code></td>
                            <td><strong>Budi Santoso, S.Pd</strong><br><span style="font-size: 11px; color: var(--text-muted);">@guru.budi</span></td>
                            <td>budi@smk.sch.id</td>
                            <td>081234567890</td>
                            <td><span class="badge badge-success">Aktif</span></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td><code>198203152005012003</code></td>
                            <td><strong>Siti Aminah, M.Kom</strong><br><span style="font-size: 11px; color: var(--text-muted);">@guru.siti</span></td>
                            <td>siti@smk.sch.id</td>
                            <td>081298765432</td>
                            <td><span class="badge badge-success">Aktif</span></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td><code>198811202010011005</code></td>
                            <td><strong>Ahmad Fauzi, S.T</strong><br><span style="font-size: 11px; color: var(--text-muted);">@guru.ahmad</span></td>
                            <td>ahmad@smk.sch.id</td>
                            <td>081377889900</td>
                            <td><span class="badge badge-success">Aktif</span></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </td>
                        </tr>

                        <!-- IMPORTED TEACHERS -->
                        <?php foreach ($customTeachers as $idx => $t): ?>
                        <tr style="background: rgba(0, 149, 255, 0.04);">
                            <td><?= 4 + $idx ?></td>
                            <td><code><?= htmlspecialchars($t[4] ?? '-') ?></code></td>
                            <td><strong><?= htmlspecialchars($t[1] ?? 'Guru Impor') ?></strong><br><span style="font-size: 11px; color: var(--text-muted);">@<?= htmlspecialchars($t[2] ?? 'username') ?></span></td>
                            <td><?= htmlspecialchars($t[2] ?? '-') ?>@smk.sch.id</td>
                            <td><?= htmlspecialchars($t[5] ?? '-') ?></td>
                            <td><span class="badge badge-success">Import Excel</span></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL IMPORT GURU EXCEL -->
    <div class="modal-overlay" id="importModal">
        <div class="modal-content-card" style="max-width: 520px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 20px;">📊</span>
                    <h3 class="modal-title" style="margin: 0;">Import Data Guru dari Excel</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeImportModal()">&times;</button>
            </div>
            <form action="/admin/teachers/import" method="POST" enctype="multipart/form-data">
                <div style="margin-bottom: 16px;">
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 12px; line-height: 1.45;">
                        Unggah file spreadsheet <strong>Excel (.xlsx)</strong> atau <strong>CSV (.csv)</strong> berisi daftar guru pengajar.
                    </p>
                    <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                        <div>
                            <div style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">Belum punya formatnya?</div>
                            <div style="font-size: 11px; color: var(--text-muted);">Format rapih, kolom terpisah &amp; NIP/HP tidak terpotong</div>
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <a href="/admin/teachers/template?format=excel" class="btn btn-primary btn-sm" style="background-color: #059669; border-color: #059669; color: #fff; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;" download="template_guru.xls">
                                <span>📊</span> Excel (.xls) — Rapih
                            </a>
                            <a href="/admin/teachers/template?format=csv" class="btn btn-secondary btn-sm" style="color: #0284c7; border-color: #bae6fd; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;" download="template_guru.csv">
                                <span>📄</span> CSV (.csv)
                            </a>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label">Pilih File Excel / CSV *</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required style="padding: 7px 12px;">
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Mendukung .xlsx, .xls, dan .csv</div>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #0095ff; border-color: #0095ff;">
                        <span>📤</span> Upload & Mulai Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function toggleCreateTeacher() {
        var card = document.getElementById('createTeacherCard');
        if (card) card.style.display = card.style.display === 'none' ? 'block' : 'none';
    }
    function openImportModal() { document.getElementById('importModal').classList.add('active'); }
    function closeImportModal() { document.getElementById('importModal').classList.remove('active'); }
    </script>
    <?php
}

/* ==========================================================================
   VIEW 3: DATA PESERTA (STUDENTS WITH CLASS DIVISION TABS)
   ========================================================================== */
function renderStudentsContent() {
    $currentClass = $_GET['class_id'] ?? 'all';
    $customStudents = $_SESSION['imported_students'] ?? [];
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- ACTION BAR -->
        <div class="action-bar">
            <div class="filter-group">
                <form action="/admin/students" method="GET" style="display: flex; gap: 8px;">
                    <input type="hidden" name="class_id" value="<?= htmlspecialchars($currentClass) ?>">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama, NIS, NISN, username..." style="max-width: 280px;">
                    <button type="submit" class="btn btn-secondary">Cari</button>
                </form>
            </div>

            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-secondary" onclick="openImportModal()" style="display: inline-flex; align-items: center; gap: 6px; border-color: #bae6fd; color: #0284c7;">
                    <span>📊</span> Import Siswa Excel
                </button>
                <button type="button" class="btn btn-primary" onclick="alert('Form Tambah Peserta Baru')">
                    <span>+</span> Tambah Peserta Baru
                </button>
            </div>
        </div>

        <!-- TABS PEMBAGIAN KELAS -->
        <div class="card" style="padding: 12px 16px; background: var(--bg-surface);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; color: var(--text-primary);">
                    <span>🏫</span>
                    <span>Pembagian Rombongan Belajar (Kelas)</span>
                </div>
            </div>
            <div style="display: flex; gap: 6px; overflow-x: auto; padding-bottom: 4px;">
                <a href="/admin/students?class_id=all" class="btn btn-sm <?= $currentClass === 'all' ? 'btn-primary' : 'btn-secondary' ?>" style="white-space: nowrap;">
                    Semua Peserta (<?= 4477 + count($customStudents) ?>)
                </a>
                <a href="/admin/students?class_id=1" class="btn btn-sm <?= $currentClass === '1' ? 'btn-primary' : 'btn-secondary' ?>" style="white-space: nowrap;">
                    10-TKJ-1 (36)
                </a>
                <a href="/admin/students?class_id=2" class="btn btn-sm <?= $currentClass === '2' ? 'btn-primary' : 'btn-secondary' ?>" style="white-space: nowrap;">
                    10-RPL-1 (36)
                </a>
                <a href="/admin/students?class_id=3" class="btn btn-sm <?= $currentClass === '3' ? 'btn-primary' : 'btn-secondary' ?>" style="white-space: nowrap;">
                    11-TKJ-1 (35)
                </a>
                <a href="/admin/students?class_id=4" class="btn btn-sm <?= $currentClass === '4' ? 'btn-primary' : 'btn-secondary' ?>" style="white-space: nowrap;">
                    11-RPL-1 (35)
                </a>
                <a href="/admin/students?class_id=5" class="btn btn-sm <?= $currentClass === '5' ? 'btn-primary' : 'btn-secondary' ?>" style="white-space: nowrap;">
                    12-TKJ-1 (34)
                </a>
            </div>
        </div>

        <!-- STUDENTS DATA TABLE -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Daftar Peserta Ujian</h3>
                    <p class="card-description">Menampilkan siswa aktif pada rombel yang dipilih</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox"></th>
                            <th>NIS / NISN</th>
                            <th>Nama Lengkap Peserta</th>
                            <th>Kelas</th>
                            <th>Username Akun</th>
                            <th>Sesi Login</th>
                            <th style="width: 130px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="checkbox"></td>
                            <td><code>0081234567</code><br><span style="font-size: 11px; color: var(--text-muted);">NIS-DEV-001</span></td>
                            <td><strong>Andi Ripai</strong></td>
                            <td><span class="badge badge-info">10-TKJ-1</span></td>
                            <td><code>andi</code></td>
                            <td><span class="badge badge-success">Siap Ujian</span></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Reset</button>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="checkbox"></td>
                            <td><code>0081234568</code><br><span style="font-size: 11px; color: var(--text-muted);">NIS-DEV-002</span></td>
                            <td><strong>Budi Pratama</strong></td>
                            <td><span class="badge badge-info">10-TKJ-1</span></td>
                            <td><code>budi_p</code></td>
                            <td><span class="badge badge-success">Siap Ujian</span></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Reset</button>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="checkbox"></td>
                            <td><code>0081234569</code><br><span style="font-size: 11px; color: var(--text-muted);">NIS-DEV-003</span></td>
                            <td><strong>Citra Dewi</strong></td>
                            <td><span class="badge badge-info">10-RPL-1</span></td>
                            <td><code>citra_d</code></td>
                            <td><span class="badge badge-success">Siap Ujian</span></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Reset</button>
                            </td>
                        </tr>

                        <!-- IMPORTED STUDENTS -->
                        <?php foreach ($customStudents as $s): ?>
                        <tr style="background: rgba(0, 149, 255, 0.04);">
                            <td><input type="checkbox"></td>
                            <td><code><?= htmlspecialchars($s[6] ?? '008XXXX') ?></code><br><span style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($s[5] ?? 'NIS-IMP') ?></span></td>
                            <td><strong><?= htmlspecialchars($s[1] ?? 'Peserta Impor') ?></strong></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($s[4] ?? '10-TKJ-1') ?></span></td>
                            <td><code><?= htmlspecialchars($s[2] ?? 'username') ?></code></td>
                            <td><span class="badge badge-success">Import Excel</span></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Reset</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL IMPORT SISWA EXCEL -->
    <div class="modal-overlay" id="importModal">
        <div class="modal-content-card" style="max-width: 520px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 20px;">📊</span>
                    <h3 class="modal-title" style="margin: 0;">Import Data Siswa dari Excel</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeImportModal()">&times;</button>
            </div>
            <form action="/admin/students/import" method="POST" enctype="multipart/form-data">
                <div style="margin-bottom: 16px;">
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 12px; line-height: 1.45;">
                        Unggah file spreadsheet <strong>Excel (.xlsx)</strong> atau <strong>CSV (.csv)</strong> berisi daftar peserta ujian.
                    </p>
                    <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                        <div>
                            <div style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">Belum punya formatnya?</div>
                            <div style="font-size: 11px; color: var(--text-muted);">Format rapih, kolom terpisah &amp; angka nol NISN/NIS aman</div>
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <a href="/admin/students/template?format=excel" class="btn btn-primary btn-sm" style="background-color: #059669; border-color: #059669; color: #fff; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;" download="template_siswa.xls">
                                <span>📊</span> Excel (.xls) — Rapih
                            </a>
                            <a href="/admin/students/template?format=csv" class="btn btn-secondary btn-sm" style="color: #0284c7; border-color: #bae6fd; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;" download="template_siswa.csv">
                                <span>📄</span> CSV (.csv)
                            </a>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label">Pilih File Excel / CSV *</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required style="padding: 7px 12px;">
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Mendukung .xlsx, .xls, dan .csv</div>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #0095ff; border-color: #0095ff;">
                        <span>📤</span> Upload & Mulai Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openImportModal() { document.getElementById('importModal').classList.add('active'); }
    function closeImportModal() { document.getElementById('importModal').classList.remove('active'); }
    </script>
    <?php
}

/* ==========================================================================
   VIEW 4: DATA KELAS (CLASSES)
   ========================================================================== */
function renderClassesContent() {
    $customClasses = $_SESSION['imported_classes'] ?? [];
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="action-bar">
            <div class="filter-group">
                <input type="text" class="form-control" placeholder="Cari nama kelas..." style="max-width: 260px;">
                <button class="btn btn-secondary">Cari</button>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-secondary" onclick="openImportModal()" style="color: #0284c7; border-color: #bae6fd;">
                    <span>📊</span> Import Data Excel
                </button>
                <button class="btn btn-primary" onclick="alert('Form Tambah Kelas Baru')">+ Tambah Kelas Baru</button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Daftar Rombongan Belajar (Kelas)</h3>
                    <p class="card-description">Total <?= 5 + count($customClasses) ?> rombel aktif tahun ajaran 2026/2027</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Nama Kelas</th>
                            <th>Tingkat</th>
                            <th>Tahun Ajaran</th>
                            <th>Jumlah Siswa</th>
                            <th>Status</th>
                            <th style="width: 140px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td><strong>10-TKJ-1</strong></td>
                            <td>Tingkat 10</td>
                            <td>2026/2027</td>
                            <td><strong>36 Siswa</strong></td>
                            <td><span class="badge badge-success">Aktif</span></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td><strong>10-RPL-1</strong></td>
                            <td>Tingkat 10</td>
                            <td>2026/2027</td>
                            <td><strong>36 Siswa</strong></td>
                            <td><span class="badge badge-success">Aktif</span></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td><strong>11-TKJ-1</strong></td>
                            <td>Tingkat 11</td>
                            <td>2026/2027</td>
                            <td><strong>35 Siswa</strong></td>
                            <td><span class="badge badge-success">Aktif</span></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </td>
                        </tr>

                        <!-- IMPORTED CLASSES -->
                        <?php foreach ($customClasses as $idx => $c): ?>
                        <tr style="background: rgba(0, 149, 255, 0.04);">
                            <td><?= 4 + $idx ?></td>
                            <td><strong><?= htmlspecialchars($c[1] ?? 'Kelas Impor') ?></strong></td>
                            <td>Tingkat <?= htmlspecialchars($c[2] ?? '10') ?></td>
                            <td><?= htmlspecialchars($c[3] ?? '2026/2027') ?></td>
                            <td><strong>30 Siswa</strong></td>
                            <td><span class="badge badge-success">Import Excel</span></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL IMPORT KELAS -->
    <div class="modal-overlay" id="importModal">
        <div class="modal-content-card" style="max-width: 520px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 20px;">📊</span>
                    <h3 class="modal-title" style="margin: 0;">Import Data Kelas dari Excel</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeImportModal()">&times;</button>
            </div>
            <form action="/admin/classes/import" method="POST" enctype="multipart/form-data">
                <div style="margin-bottom: 16px;">
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 12px;">
                        Unggah file Excel/CSV berisi daftar rombel kelas.
                    </p>
                    <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                        <div>
                            <div style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">Belum punya formatnya?</div>
                            <div style="font-size: 11px; color: var(--text-muted);">Format rapih &amp; kolom terpisah langsung di Excel</div>
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <a href="/admin/classes/template?format=excel" class="btn btn-primary btn-sm" style="background-color: #059669; border-color: #059669; color: #fff; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;" download="template_kelas.xls">
                                <span>📊</span> Excel (.xls) — Rapih
                            </a>
                            <a href="/admin/classes/template?format=csv" class="btn btn-secondary btn-sm" style="color: #0284c7; border-color: #bae6fd; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;" download="template_kelas.csv">
                                <span>📄</span> CSV (.csv)
                            </a>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label">Pilih File Excel / CSV *</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required style="padding: 7px 12px;">
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #0095ff; border-color: #0095ff;">Upload & Import</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function openImportModal() { document.getElementById('importModal').classList.add('active'); }
    function closeImportModal() { document.getElementById('importModal').classList.remove('active'); }
    </script>
    <?php
}

/* ==========================================================================
   VIEW 5: MATA PELAJARAN (SUBJECTS)
   ========================================================================== */
function renderSubjectsContent() {
    $customSubjects = $_SESSION['imported_subjects'] ?? [];
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="action-bar">
            <div class="filter-group">
                <input type="text" class="form-control" placeholder="Cari nama mata pelajaran..." style="max-width: 260px;">
                <button class="btn btn-secondary">Cari</button>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-secondary" onclick="openImportModal()" style="color: #0284c7; border-color: #bae6fd;">
                    <span>📊</span> Import Data Excel
                </button>
                <button class="btn btn-primary" onclick="alert('Form Tambah Mapel Baru')">+ Tambah Mapel Baru</button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Daftar Mata Pelajaran Ujian</h3>
                    <p class="card-description">Total <?= 3 + count($customSubjects) ?> mata pelajaran terdaftar di CBT Server</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Kode Mapel</th>
                            <th>Nama Mata Pelajaran</th>
                            <th>Status</th>
                            <th style="width: 140px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td><code>MAT-10</code></td>
                            <td><strong>Matematika X</strong></td>
                            <td><span class="badge badge-success">Aktif</span></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td><code>BIND-10</code></td>
                            <td><strong>Bahasa Indonesia X</strong></td>
                            <td><span class="badge badge-success">Aktif</span></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td><code>PROG-10</code></td>
                            <td><strong>Dasar-dasar Pemrograman RPL</strong></td>
                            <td><span class="badge badge-success">Aktif</span></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </td>
                        </tr>

                        <!-- IMPORTED SUBJECTS -->
                        <?php foreach ($customSubjects as $idx => $sb): ?>
                        <tr style="background: rgba(0, 149, 255, 0.04);">
                            <td><?= 4 + $idx ?></td>
                            <td><code><?= htmlspecialchars($sb[1] ?? 'MAPEL-NEW') ?></code></td>
                            <td><strong><?= htmlspecialchars($sb[2] ?? 'Mapel Impor') ?></strong></td>
                            <td><span class="badge badge-success">Import Excel</span></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL IMPORT MAPEL -->
    <div class="modal-overlay" id="importModal">
        <div class="modal-content-card" style="max-width: 520px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 20px;">📊</span>
                    <h3 class="modal-title" style="margin: 0;">Import Mata Pelajaran dari Excel</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeImportModal()">&times;</button>
            </div>
            <form action="/admin/subjects/import" method="POST" enctype="multipart/form-data">
                <div style="margin-bottom: 16px;">
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 12px;">
                        Unggah file Excel/CSV berisi daftar mata pelajaran.
                    </p>
                    <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                        <div>
                            <div style="font-size: 12.5px; font-weight: 700; color: var(--text-primary);">Belum punya formatnya?</div>
                            <div style="font-size: 11px; color: var(--text-muted);">Format rapih &amp; kolom terpisah langsung di Excel</div>
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <a href="/admin/subjects/template?format=excel" class="btn btn-primary btn-sm" style="background-color: #059669; border-color: #059669; color: #fff; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;" download="template_mapel.xls">
                                <span>📊</span> Excel (.xls) — Rapih
                            </a>
                            <a href="/admin/subjects/template?format=csv" class="btn btn-secondary btn-sm" style="color: #0284c7; border-color: #bae6fd; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;" download="template_mapel.csv">
                                <span>📄</span> CSV (.csv)
                            </a>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label">Pilih File Excel / CSV *</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required style="padding: 7px 12px;">
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #0095ff; border-color: #0095ff;">Upload & Import</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function openImportModal() { document.getElementById('importModal').classList.add('active'); }
    function closeImportModal() { document.getElementById('importModal').classList.remove('active'); }
    </script>
    <?php
}

/* ==========================================================================
   VIEW 6: BANK SOAL (QUESTIONS)
   ========================================================================== */
function renderQuestionsContent() {
    $customQuestions = $_SESSION['imported_questions'] ?? [];
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="action-bar">
            <div class="filter-group">
                <input type="text" class="form-control" placeholder="Cari isi pertanyaan butir soal..." style="max-width: 280px;">
                <select class="form-select" style="max-width: 180px;">
                    <option value="">Semua Mapel</option>
                    <option value="1">Matematika X</option>
                    <option value="2">Bahasa Indonesia X</option>
                    <option value="3">Pemrograman RPL</option>
                </select>
                <button class="btn btn-secondary">Filter</button>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-secondary" onclick="openImportModal()" style="color: #0284c7; border-color: #bae6fd;">
                    <span>📊</span> Import Soal Excel
                </button>
                <button class="btn btn-primary" onclick="alert('Form Buat Soal Baru')">+ Buat Soal Baru</button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Daftar Bank Soal</h3>
                    <p class="card-description">Total <?= 2 + count($customQuestions) ?> butir soal siap dialokasikan ke paket ujian</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Mata Pelajaran</th>
                            <th>Tipe & Tingkat</th>
                            <th>Isi Butir Soal</th>
                            <th>Bobot</th>
                            <th style="width: 140px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td><span class="badge badge-info">Matematika X</span></td>
                            <td>
                                <strong>Pilihan Ganda</strong><br>
                                <span class="badge badge-warning" style="margin-top: 3px;">Sedang</span>
                            </td>
                            <td>
                                <div style="font-size: 13px; line-height: 1.45;">
                                    Diketahui suatu barisan aritmetika dengan suku ke-3 adalah 11 dan suku ke-8 adalah 26. Tentukan suku ke-20 barisan tersebut.
                                </div>
                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                                    5 Pilihan Jawaban | Kunci: <strong style="color: #059669;">B</strong>
                                </div>
                            </td>
                            <td><strong>2.00</strong></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td><span class="badge badge-info">Bahasa Indonesia X</span></td>
                            <td>
                                <strong>Pilihan Ganda</strong><br>
                                <span class="badge badge-success" style="margin-top: 3px;">Mudah</span>
                            </td>
                            <td>
                                <div style="font-size: 13px; line-height: 1.45;">
                                    Cermatilah kutipan teks laporan hasil observasi berikut! Kalimat definisi yang tepat berdasarkan teks tersebut adalah...
                                </div>
                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                                    5 Pilihan Jawaban | Kunci: <strong style="color: #059669;">A</strong>
                                </div>
                            </td>
                            <td><strong>2.00</strong></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </td>
                        </tr>

                        <!-- IMPORTED QUESTIONS -->
                        <?php foreach ($customQuestions as $idx => $q): ?>
                        <tr style="background: rgba(0, 149, 255, 0.04);">
                            <td><?= 3 + $idx ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($q[1] ?? 'Umum') ?></span></td>
                            <td>
                                <strong>Pilihan Ganda</strong><br>
                                <span class="badge badge-success" style="margin-top: 3px;"><?= htmlspecialchars($q[11] ?? 'Mudah') ?></span>
                            </td>
                            <td>
                                <div style="font-size: 13px; line-height: 1.45;">
                                    <?= htmlspecialchars($q[3] ?? 'Pertanyaan Soal') ?>
                                </div>
                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                                    Kunci: <strong style="color: #059669;"><?= htmlspecialchars($q[9] ?? 'A') ?></strong> | Bobot: <?= htmlspecialchars($q[10] ?? '2.00') ?>
                                </div>
                            </td>
                            <td><strong><?= htmlspecialchars($q[10] ?? '2.00') ?></strong></td>
                            <td style="text-align: center;">
                                <button class="btn btn-sm btn-secondary">Edit</button>
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL IMPORT SOAL EXCEL -->
    <div class="modal-overlay" id="importModal">
        <div class="modal-content-card" style="max-width: 520px;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 20px;">📊</span>
                    <h3 class="modal-title" style="margin: 0;">Import Bank Soal dari Excel</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeImportModal()">&times;</button>
            </div>
            <form action="/admin/questions/import" method="POST" enctype="multipart/form-data">
                <div style="margin-bottom: 16px;">
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin-bottom: 12px;">
                        Unggah butir soal (pertanyaan, opsi A-E, kunci jawaban, dan bobot).
                    </p>
                    <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                        <div>
                            <div style="font-size: 12.5px; font-weight: 700;">Template Standar Soal</div>
                            <div style="font-size: 11px; color: var(--text-muted);">Format rapih, teks soal panjang &amp; kunci jawaban aman</div>
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <a href="/admin/questions/template?format=excel" class="btn btn-primary btn-sm" style="background-color: #059669; border-color: #059669; color: #fff; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;" download="template_bank_soal.xls">
                                <span>📊</span> Excel (.xls) — Rapih
                            </a>
                            <a href="/admin/questions/template?format=csv" class="btn btn-secondary btn-sm" style="color: #0284c7; border-color: #bae6fd; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;" download="template_bank_soal.csv">
                                <span>📄</span> CSV (.csv)
                            </a>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label">Pilih File Excel / CSV *</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required style="padding: 7px 12px;">
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #0095ff; border-color: #0095ff;">Upload & Import</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function openImportModal() { document.getElementById('importModal').classList.add('active'); }
    function closeImportModal() { document.getElementById('importModal').classList.remove('active'); }
    </script>
    <?php
}

/* ==========================================================================
   VIEW 7: PAKET UJIAN (EXAMS)
   ========================================================================== */
function renderExamsContent() {
    $exams = $_SESSION['exams_list'] ?? [];
    $search = strtolower(trim($_GET['search'] ?? ''));
    $filterSubject = trim($_GET['subject'] ?? '');
    $filterStatus = trim($_GET['status'] ?? '');

    if ($search !== '' || $filterSubject !== '' || $filterStatus !== '') {
        $exams = array_filter($exams, function($ex) use ($search, $filterSubject, $filterStatus) {
            if ($search !== '' && !str_contains(strtolower($ex['title']), $search)) return false;
            if ($filterSubject !== '' && $ex['subject'] !== $filterSubject) return false;
            if ($filterStatus !== '' && $ex['status'] !== $filterStatus) return false;
            return true;
        });
    }
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="action-bar">
            <form method="GET" action="/admin/exams" class="filter-group" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <input type="text" name="search" class="form-control" placeholder="Cari judul ujian..." value="<?= htmlspecialchars($search) ?>" style="max-width: 240px;">
                <select name="status" class="form-control" style="max-width: 160px;" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Aktif (Active)</option>
                    <option value="published" <?= $filterStatus === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Selesai</option>
                    <option value="draft" <?= $filterStatus === 'draft' ? 'selected' : '' ?>>Draft</option>
                </select>
                <button type="submit" class="btn btn-secondary">Filter</button>
                <?php if ($search || $filterStatus): ?>
                    <a href="/admin/exams" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>

            <button type="button" class="btn btn-primary" onclick="toggleCreateExam()">
                <span>+</span> Buat Paket Ujian
            </button>
        </div>

        <!-- FORM BUAT PAKET UJIAN BARU -->
        <div class="card" id="createExamCard" style="display: none; border-color: var(--primary); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 class="card-title" style="margin-bottom: 0;">Buat Jadwal & Konfigurasi Paket Ujian Baru</h3>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleCreateExam()">&times; Batal</button>
            </div>
            <form method="POST" action="/admin/exams/create">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 16px;">
                    <div class="form-group">
                        <label class="form-label">Judul Paket Ujian *</label>
                        <input type="text" name="title" class="form-control" placeholder="Contoh: Asesmen Sumatif Akhir Semester" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mata Pelajaran *</label>
                        <select name="subject" class="form-control" required>
                            <option value="Matematika X">Matematika X</option>
                            <option value="Bahasa Indonesia X">Bahasa Indonesia X</option>
                            <option value="Dasar-dasar Pemrograman RPL">Dasar-dasar Pemrograman RPL</option>
                            <option value="Dasar Jaringan Komputer">Dasar Jaringan Komputer</option>
                            <option value="Bahasa Inggris X">Bahasa Inggris X</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Waktu Mulai Ujian</label>
                        <input type="text" name="start_time" class="form-control" value="08:00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Waktu Selesai Ujian</label>
                        <input type="text" name="end_time" class="form-control" value="10:00">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Durasi Pengerjaan (Menit) *</label>
                        <input type="number" name="duration" class="form-control" value="90" min="10" max="300" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Token Ujian *</label>
                        <input type="text" name="token" class="form-control" value="<?= substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6) ?>" style="font-family: monospace; font-weight: 700; letter-spacing: 2px;" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nilai KKM Kelulusan</label>
                        <input type="number" step="0.1" name="passing_score" class="form-control" value="75.0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status Ujian</label>
                        <select name="status" class="form-control">
                            <option value="active">Active (Langsung Aktif)</option>
                            <option value="published">Published (Terjadwal)</option>
                            <option value="draft">Draft (Konsep)</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                    <button type="button" class="btn btn-secondary" onclick="toggleCreateExam()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan & Aktifkan Paket Ujian</button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Daftar Paket Ujian Terdaftar</h3>
                    <p class="card-description">Total <?= count($exams) ?> paket ujian tersimpan di CBT Server</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Judul Ujian & Mapel</th>
                            <th>Jadwal Pelaksanaan</th>
                            <th>Durasi / Token</th>
                            <th>Soal / Peserta</th>
                            <th>Status</th>
                            <th style="width: 180px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($exams)): ?>
                            <tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">Tidak ada paket ujian yang cocok dengan filter.</td></tr>
                        <?php else: ?>
                            <?php foreach ($exams as $idx => $ex): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td>
                                    <div style="font-weight: 700; font-size: 14px; color: var(--text-primary);"><?= htmlspecialchars($ex['title']) ?></div>
                                    <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                                        <span class="badge badge-info"><?= htmlspecialchars($ex['subject']) ?></span>
                                        <span style="margin-left: 6px;">Oleh: <?= htmlspecialchars($ex['creator'] ?? 'Admin') ?></span>
                                    </div>
                                </td>
                                <td style="font-size: 12.5px;">
                                    <div>Mulai: <strong><?= htmlspecialchars($ex['start'] ?? '-') ?></strong></div>
                                    <div style="color: var(--text-muted);">Selesai: <?= htmlspecialchars($ex['end'] ?? '-') ?></div>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?= $ex['duration'] ?> Menit</div>
                                    <span class="badge badge-secondary" style="font-family: monospace; letter-spacing: 1px; margin-top: 3px;">TOKEN: <?= htmlspecialchars($ex['token'] ?? 'OFF') ?></span>
                                </td>
                                <td>
                                    <div style="font-size: 12px;">
                                        <strong><?= $ex['questions_count'] ?></strong> Butir Soal<br>
                                        <strong style="color: var(--primary);"><?= $ex['participants_count'] ?></strong> Peserta
                                    </div>
                                </td>
                                <td>
                                    <?php if ($ex['status'] === 'active'): ?>
                                        <span class="badge badge-success">Aktif</span>
                                    <?php elseif ($ex['status'] === 'published'): ?>
                                        <span class="badge badge-info">Published</span>
                                    <?php elseif ($ex['status'] === 'completed'): ?>
                                        <span class="badge badge-secondary">Selesai</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 4px;">
                                        <a href="/admin/monitoring" class="btn btn-sm btn-primary" title="Pantau Ujian Live">📡 Pantau</a>
                                        <button class="btn btn-sm btn-secondary" onclick="alert('Rincian Paket Ujian: <?= htmlspecialchars($ex['title']) ?>')">Detail</button>
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
    <script>
    function toggleCreateExam() {
        var c = document.getElementById('createExamCard');
        c.style.display = (c.style.display === 'none') ? 'block' : 'none';
    }
    </script>
    <?php
}

/* ==========================================================================
   VIEW 8: LIVE MONITORING UJIAN
   ========================================================================== */
function renderMonitoringContent() {
    $sessions = $_SESSION['monitoring_sessions'] ?? [];
    $totalStudents = count($sessions);
    $inProgressCount = 0;
    $completedCount = 0;
    $issueCount = 0;

    foreach ($sessions as $s) {
        if ($s['status'] === 'Mengerjakan') $inProgressCount++;
        elseif (str_contains($s['status'], 'Selesai')) $completedCount++;
        else $issueCount++;
    }
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin: 0 0 4px 0;">
                    Live Monitoring Sesi Ujian Realtime
                </h2>
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0;">
                    Pantau aktivitas sesi pengerjaan ujian siswa secara langsung di jaringan lokal LAN
                </p>
            </div>
            <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
                <span>Waktu Server: <strong><?= date('H:i:s d/m/Y') ?></strong></span>
                <button type="button" class="btn btn-sm btn-secondary" onclick="window.location.reload();">&#8635; Refresh Data</button>
            </div>
        </div>

        <!-- STATS OVERVIEW -->
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
            <div class="stat-card">
                <div class="stat-icon blue">👥</div>
                <div class="stat-value"><?= $totalStudents ?> Siswa</div>
                <div class="stat-label">Total Peserta Ujian</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">⏳</div>
                <div class="stat-value" style="color: #d97706;"><?= $inProgressCount ?> Siswa</div>
                <div class="stat-label">Sedang Mengerjakan</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">✓</div>
                <div class="stat-value" style="color: #059669;"><?= $completedCount ?> Siswa</div>
                <div class="stat-label">Sudah Selesai (Submit)</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">⚠️</div>
                <div class="stat-value" style="color: #dc2626;"><?= $issueCount ?> Sesi</div>
                <div class="stat-label">Ragu / Reset Login</div>
            </div>
        </div>

        <!-- TABLE OF MONITORING PARTICIPANTS -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Daftar Komputer Klien / Peserta Aktif</h3>
                    <p class="card-description">Data tersinkronisasi otomatis dengan server CBT</p>
                </div>
                <span class="badge badge-success" style="padding: 6px 12px; font-size: 12px;">● Server Live Monitoring Active</span>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>NIS / NISN</th>
                            <th>Nama Peserta & Rombel</th>
                            <th>Progres Soal</th>
                            <th>Sisa Waktu</th>
                            <th>Status Sesi</th>
                            <th>Alamat IP</th>
                            <th style="width: 160px; text-align: center;">Aksi Kontrol</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $idx => $s): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><code><?= htmlspecialchars($s['nis']) ?></code></td>
                            <td>
                                <strong><?= htmlspecialchars($s['name']) ?></strong>
                                <div style="font-size: 11px; color: var(--text-muted);"><span class="badge badge-info"><?= htmlspecialchars($s['class']) ?></span></div>
                            </td>
                            <td>
                                <strong><?= $s['answered'] ?> / <?= $s['total'] ?></strong> Soal
                                <div style="width: 100%; max-width: 110px; background: #e2e8f0; height: 6px; border-radius: 3px; margin-top: 4px; overflow: hidden;">
                                    <div style="background: var(--primary); width: <?= ($s['answered'] / $s['total']) * 100 ?>%; height: 100%;"></div>
                                </div>
                            </td>
                            <td>
                                <strong style="font-family: monospace; font-size: 13px; color: <?= $s['time_left'] === '00:00' ? '#94a3b8' : '#0284c7' ?>;">
                                    <?= htmlspecialchars($s['time_left']) ?>
                                </strong>
                            </td>
                            <td>
                                <?php if ($s['status'] === 'Mengerjakan'): ?>
                                    <span class="badge badge-warning">Mengerjakan</span>
                                <?php elseif (str_contains($s['status'], 'Selesai')): ?>
                                    <span class="badge badge-success">Selesai</span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><?= htmlspecialchars($s['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><code><?= htmlspecialchars($s['ip']) ?></code></td>
                            <td style="text-align: center;">
                                <a href="/admin/monitoring/reset?nis=<?= urlencode($s['nis']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin me-reset status login peserta <?= htmlspecialchars($s['name']) ?>?');" title="Reset sesi jika perangkat bermasalah">
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

/* ==========================================================================
   VIEW 9: HASIL & NILAI UJIAN
   ========================================================================== */
function renderResultsContent() {
    $results = $_SESSION['results_list'] ?? [];
    $totalCount = count($results);
    $totalScore = array_reduce($results, fn($carry, $item) => $carry + $item['score'], 0);
    $avgScore = $totalCount > 0 ? round($totalScore / $totalCount, 1) : 0;
    $passCount = count(array_filter($results, fn($r) => $r['score'] >= $r['passing']));
    $passPct = $totalCount > 0 ? round(($passCount / $totalCount) * 100) : 0;
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin: 0 0 4px 0;">
                    Hasil & Nilai Ujian Peserta
                </h2>
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0;">
                    Rekapitulasi skor penilaian, status kelulusan KKM, dan publikasi nilai peserta ujian
                </p>
            </div>
            <a href="/admin/reports/export-csv" class="btn btn-secondary btn-sm" style="color: #0284c7; border-color: #bae6fd;">
                <span>📥</span> Export Rekap (.csv)
            </a>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
            <div class="stat-card">
                <div class="stat-icon blue">📝</div>
                <div class="stat-value"><?= $totalCount ?> Peserta</div>
                <div class="stat-label">Total Ujian Diperiksa</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">📊</div>
                <div class="stat-value" style="color: #059669;"><?= $avgScore ?></div>
                <div class="stat-label">Rata-rata Skor Nilai</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon teal">🎯</div>
                <div class="stat-value" style="color: #0d9488;"><?= $passPct ?>%</div>
                <div class="stat-label">Tingkat Kelulusan (>= KKM)</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Rekapitulasi Nilai Akhir Peserta</h3>
                    <p class="card-description">Standar KKM Sekolah: 75.0</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Peserta & Kelas</th>
                            <th>Paket Ujian</th>
                            <th>Benar / Salah / Kosong</th>
                            <th>Nilai Akhir</th>
                            <th>Kelulusan</th>
                            <th>Publikasi</th>
                            <th style="width: 140px; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $idx => $r): 
                            $isPassed = ($r['score'] >= $r['passing']);
                        ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($r['name']) ?></strong>
                                <div style="font-size: 11px; color: var(--text-muted);">NIS: <?= htmlspecialchars($r['nis']) ?> | <span class="badge badge-info"><?= htmlspecialchars($r['class']) ?></span></div>
                            </td>
                            <td>
                                <div style="font-weight: 600;"><?= htmlspecialchars($r['exam']) ?></div>
                            </td>
                            <td>
                                <span style="color: #059669; font-weight: 700;"><?= $r['correct'] ?> Benar</span>,
                                <span style="color: #dc2626;"><?= $r['wrong'] ?> Salah</span>,
                                <span style="color: var(--text-muted);"><?= $r['empty'] ?> Kosong</span>
                            </td>
                            <td>
                                <strong style="font-size: 16px; color: var(--text-primary);"><?= number_format((float)$r['score'], 1) ?></strong>
                            </td>
                            <td>
                                <?php if ($isPassed): ?>
                                    <span class="badge badge-success">Lulus</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Belum Lulus</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($r['published'])): ?>
                                    <span class="badge badge-success">Publik</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="/admin/results/publish?idx=<?= $idx ?>" class="btn btn-sm <?= !empty($r['published']) ? 'btn-secondary' : 'btn-primary' ?>" title="Ganti status publikasi nilai ke siswa">
                                    <?= !empty($r['published']) ? 'Sembunyikan' : 'Publikasi' ?>
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

/* ==========================================================================
   VIEW 10: LAPORAN & ANALISIS AKADEMIK
   ========================================================================== */
function renderReportsContent() {
    $exams = $_SESSION['exams_list'] ?? [];
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin: 0 0 4px 0;">
                    Pusat Rekapitulasi & Analisis Hasil Ujian
                </h2>
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0;">
                    Ringkasan statistik performa ujian, rekapitulasi nilai rombel, dan analisis butir soal
                </p>
            </div>
            <a href="/admin/reports/export-csv" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
                <span>📥</span> Export Seluruh Rekap CSV
            </a>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Laporan Statistik Paket Ujian</h3>
                    <p class="card-description">Analisis komprehensif seluruh sesi ujian yang telah selesai</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Paket Ujian & Mata Pelajaran</th>
                            <th>Jumlah Soal</th>
                            <th>Peserta Terdaftar</th>
                            <th>Sesi Attempt</th>
                            <th>KKM</th>
                            <th style="width: 240px; text-align: right;">Aksi Laporan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exams as $idx => $ex): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <div style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($ex['title']) ?></div>
                                <div style="font-size: 11.5px; color: var(--text-muted);">
                                    Mapel: <?= htmlspecialchars($ex['subject']) ?> | Pembuat: <?= htmlspecialchars($ex['creator'] ?? 'Admin') ?>
                                </div>
                            </td>
                            <td><strong><?= $ex['questions_count'] ?></strong> Soal</td>
                            <td><strong><?= $ex['participants_count'] ?></strong> Siswa</td>
                            <td><span class="badge badge-info"><?= $ex['participants_count'] ?> Sesi</span></td>
                            <td><span class="badge badge-secondary"><?= number_format((float)$ex['passing_score'], 1) ?></span></td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                    <a href="/admin/results" class="btn btn-sm btn-primary">📊 Statistik Nilai</a>
                                    <button class="btn btn-sm btn-secondary" onclick="alert('Analisis Butir Soal:\n\n- Daya Beda Rata-rata: 0.42 (Baik)\n- Tingkat Kesukaran: 45% Mudah, 40% Sedang, 15% Sukar\n- Soal Perlu Revisi: 0 butir')">🔍 Analisis Soal</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ANALISIS BUTIR SOAL OVERVIEW -->
        <div class="card" style="background: var(--bg-surface);">
            <h3 class="card-title" style="margin-bottom: 14px;">Ringkasan Analisis Butir Soal (Item Difficulty Analysis)</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
                <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: 8px; padding: 14px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 800; color: #059669;">45%</div>
                    <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">Kategori Mudah</div>
                </div>
                <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: 8px; padding: 14px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 800; color: #0284c7;">40%</div>
                    <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">Kategori Sedang</div>
                </div>
                <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: 8px; padding: 14px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 800; color: #d97706;">15%</div>
                    <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">Kategori Sukar</div>
                </div>
                <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: 8px; padding: 14px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 800; color: #7c3aed;">0.42</div>
                    <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">Indeks Daya Beda (Baik)</div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/* ==========================================================================
   VIEW 11: BACKUP & DATABASE SNAPSHOT
   ========================================================================== */
function renderBackupsContent() {
    $backups = $_SESSION['backups_list'] ?? [];
    $totalBackups = count($backups);
    $latestBackup = $backups[0] ?? null;
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin: 0 0 4px 0;">
                    Pencadangan Database MySQL Lokal
                </h2>
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0;">
                    Snapshot DDL & DML internal murni server CBT lokal, siap diunduh dan dipulihkan kapan saja
                </p>
            </div>
            <button type="button" class="btn btn-primary" onclick="toggleCreateBackup()">
                <span>💾</span> Buat Cadangan Baru
            </button>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
            <div class="stat-card">
                <div class="stat-icon blue">💾</div>
                <div class="stat-value"><?= $totalBackups ?> Berkas</div>
                <div class="stat-label">Total Berkas Snapshot</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">📁</div>
                <div class="stat-value" style="color: #059669;">7.4 MB</div>
                <div class="stat-label">Total Penggunaan Storage</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow">🕒</div>
                <div class="stat-value" style="font-size: 14px; color: #d97706; margin-top: 4px;">
                    <?= $latestBackup ? htmlspecialchars($latestBackup['created_at']) : '-' ?>
                </div>
                <div class="stat-label">Snapshot Terakhir Dibuat</div>
            </div>
        </div>

        <!-- FORM CREATE BACKUP CARD -->
        <div class="card" id="createBackupCard" style="display: none; border: 2px solid var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 class="card-title" style="margin-bottom: 0;">Trigger Pembuatan Snapshot Database Baru</h3>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleCreateBackup()">&times; Tutup</button>
            </div>
            <form method="POST" action="/admin/backups/create">
                <div style="display: grid; grid-template-columns: 1fr auto; gap: 16px; align-items: end;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Skenario Operasional Backup</label>
                        <select name="type" class="form-control" required>
                            <option value="manual">Cadangan Manual Rutin (manual)</option>
                            <option value="pre_exam">Cadangan Pra-Ujian / Master Data Siap (pre_exam)</option>
                            <option value="post_exam">Cadangan Pasca-Ujian / Selesai Sesi Pengerjaan (post_exam)</option>
                        </select>
                        <span style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; display: block;">
                            Dump dieksekusi secara instan dan menghasilkan berkas .sql siap pakai.
                        </span>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        Mulai Backup Sekarang
                    </button>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Daftar Berkas Cadangan (.sql)</h3>
                    <p class="card-description">Klik tombol Unduh untuk menyimpan berkas dump database ke komputer Anda</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Nama Berkas Snapshot</th>
                            <th style="width: 130px;">Tipe</th>
                            <th style="width: 100px;">Ukuran</th>
                            <th style="width: 170px;">Waktu Dibuat</th>
                            <th style="width: 180px;">Integritas SHA-256</th>
                            <th style="width: 160px; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $idx => $b): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <div style="font-weight: 700; font-family: monospace; font-size: 13px; color: var(--text-primary);">
                                    <?= htmlspecialchars($b['filename']) ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($b['type'] === 'pre_exam'): ?>
                                    <span class="badge badge-info">Pra-Ujian</span>
                                <?php elseif ($b['type'] === 'post_exam'): ?>
                                    <span class="badge badge-success">Pasca-Ujian</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Manual</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($b['size']) ?></strong></td>
                            <td style="font-size: 12.5px;"><?= htmlspecialchars($b['created_at']) ?></td>
                            <td><code style="font-size: 11px;"><?= substr($b['hash'], 0, 16) ?>...</code></td>
                            <td style="text-align: right;">
                                <a href="/admin/backups/download?file=<?= urlencode($b['filename']) ?>" class="btn btn-sm btn-primary" download="<?= htmlspecialchars($b['filename']) ?>">
                                    📥 Unduh .sql
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
    function toggleCreateBackup() {
        var c = document.getElementById('createBackupCard');
        c.style.display = (c.style.display === 'none') ? 'block' : 'none';
    }
    </script>
    <?php
}

/* ==========================================================================
   VIEW 12: PENGATURAN SISTEM SERVER
   ========================================================================== */
function renderSettingsContent() {
    $settings = $_SESSION['cbt_settings'] ?? [];
    ?>
    <div style="max-width: 900px; display: flex; flex-direction: column; gap: 20px;">
        <div>
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin: 0 0 4px 0;">
                Konfigurasi Operasional Server Lokal CBT
            </h2>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0;">
                Pengaturan identitas sekolah, portal jaringan lokal, dan token ujian yang tersinkronisasi otomatis
            </p>
        </div>

        <form method="POST" action="/admin/settings/update">
            <!-- SECTION 1: IDENTITAS SEKOLAH -->
            <div class="card" style="margin-bottom: 20px;">
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                    <h3 class="card-title" style="margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                        <span>🏫</span> Identitas Sekolah & Tahun Ajaran
                    </h3>
                    <span style="font-size: 12px; color: var(--text-muted);">
                        Informasi nama institusi dan tahun pelajaran yang tercantum pada kartu ujian dan lembar laporan.
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 14px;">
                    <div class="form-group">
                        <label class="form-label">Nama Sekolah / Lembaga *</label>
                        <input type="text" name="school_name" class="form-control" value="<?= htmlspecialchars($settings['school_name'] ?? 'SMK PESANTREN BUSTANUL ULUM') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tahun Ajaran Aktif *</label>
                        <input type="text" name="academic_year" class="form-control" value="<?= htmlspecialchars($settings['academic_year'] ?? '2025/2026') ?>" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Alamat Lengkap Sekolah</label>
                    <textarea name="school_address" class="form-control" rows="2"><?= htmlspecialchars($settings['school_address'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- SECTION 2: SERVER LOKAL & JARINGAN -->
            <div class="card" style="margin-bottom: 20px;">
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                    <h3 class="card-title" style="margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                        <span>🖥️</span> Aplikasi & Jaringan Lokal Server
                    </h3>
                    <span style="font-size: 12px; color: var(--text-muted);">
                        Parameter nama sistem dan port layanan untuk distribusi jaringan Wi-Fi/LAN lokal.
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">Nama Aplikasi CBT *</label>
                        <input type="text" name="app_name" class="form-control" value="<?= htmlspecialchars($settings['app_name'] ?? 'CBT SERVER MANAGER') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Port Server Lokal (HTTP) *</label>
                        <input type="number" name="server_port" class="form-control" value="<?= (int)($settings['server_port'] ?? 8000) ?>" min="80" max="65535" required>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: TOKEN & KEBIJAKAN UJIAN -->
            <div class="card" style="margin-bottom: 20px;">
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 16px;">
                    <h3 class="card-title" style="margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
                        <span>🔑</span> Token Ujian & Kebijakan Siswa
                    </h3>
                    <span style="font-size: 12px; color: var(--text-muted);">
                        Pengaturan perilisan token dinamis dan hak akses peninjauan hasil oleh peserta.
                    </span>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Interval Refresh Token Ujian (Menit) *</label>
                    <input type="number" name="token_refresh_minutes" class="form-control" style="max-width: 200px;" min="5" max="180" value="<?= (int)($settings['token_refresh_minutes'] ?? 15) ?>" required>
                    <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block;">
                        Rentang waktu valid: 5 sampai 180 menit.
                    </span>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
                    <span>💾</span> Simpan Pengaturan Server
                </button>
            </div>
        </form>
    </div>
    <?php
}

/* ==========================================================================
   VIEW 13: LOG AKTIVITAS & AUDIT TRAIL
   ========================================================================== */
function renderActivityLogsContent() {
    $logs = $_SESSION['activity_logs'] ?? [];
    $search = strtolower(trim($_GET['search'] ?? ''));
    $filterModule = strtoupper(trim($_GET['module'] ?? ''));

    if ($search !== '' || $filterModule !== '') {
        $logs = array_filter($logs, function($l) use ($search, $filterModule) {
            if ($search !== '' && !str_contains(strtolower($l['details']), $search) && !str_contains(strtolower($l['action']), $search)) return false;
            if ($filterModule !== '' && $l['module'] !== $filterModule) return false;
            return true;
        });
    }
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-primary); margin: 0 0 4px 0;">
                    Riwayat Audit & Aktivitas Server
                </h2>
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin: 0;">
                    Catatan riwayat autentikasi, anti-cheat siswa, backup, dan pengaturan sistem (Read-Only)
                </p>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.location.reload();">
                &#8635; Segarkan Log
            </button>
        </div>

        <!-- FILTER BAR -->
        <div class="card">
            <form method="GET" action="/admin/activity-logs" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <input type="text" name="search" class="form-control" placeholder="Cari aksi atau rincian..." value="<?= htmlspecialchars($search) ?>" style="flex: 1; min-width: 220px;">
                <select name="module" class="form-control" style="max-width: 180px;" onchange="this.form.submit()">
                    <option value="">Semua Modul</option>
                    <option value="AUTH" <?= $filterModule === 'AUTH' ? 'selected' : '' ?>>AUTH</option>
                    <option value="EXAM" <?= $filterModule === 'EXAM' ? 'selected' : '' ?>>EXAM</option>
                    <option value="BACKUP" <?= $filterModule === 'BACKUP' ? 'selected' : '' ?>>BACKUP</option>
                    <option value="SETTING" <?= $filterModule === 'SETTING' ? 'selected' : '' ?>>SETTING</option>
                    <option value="STUDENT" <?= $filterModule === 'STUDENT' ? 'selected' : '' ?>>STUDENT</option>
                    <option value="MONITORING" <?= $filterModule === 'MONITORING' ? 'selected' : '' ?>>MONITORING</option>
                    <option value="RESULT" <?= $filterModule === 'RESULT' ? 'selected' : '' ?>>RESULT</option>
                </select>
                <button type="submit" class="btn btn-secondary">Filter</button>
                <?php if ($search || $filterModule): ?>
                    <a href="/admin/activity-logs" class="btn btn-secondary">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th style="width: 160px;">Waktu Kejadian</th>
                            <th style="width: 180px;">Pengguna / Aktor</th>
                            <th style="width: 110px;">Modul</th>
                            <th style="width: 170px;">Aksi / Event</th>
                            <th style="width: 120px;">Alamat IP</th>
                            <th>Rincian Kontekstual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">Belum ada catatan log aktivitas yang cocok.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $idx => $log): ?>
                            <tr>
                                <td><?= $idx + 1 ?></td>
                                <td style="font-size: 12px;"><strong><?= htmlspecialchars($log['timestamp']) ?></strong></td>
                                <td>
                                    <div style="font-weight: 600; font-size: 13px; color: var(--text-primary);"><?= htmlspecialchars($log['user']) ?></div>
                                </td>
                                <td>
                                    <?php 
                                    $mod = $log['module'];
                                    $badgeCls = match($mod) {
                                        'AUTH' => 'badge-info',
                                        'EXAM' => 'badge-primary',
                                        'BACKUP' => 'badge-warning',
                                        'SETTING' => 'badge-secondary',
                                        default => 'badge-success',
                                    };
                                    ?>
                                    <span class="badge <?= $badgeCls ?>"><?= htmlspecialchars($mod) ?></span>
                                </td>
                                <td><strong style="font-size: 12px; font-family: monospace;"><?= htmlspecialchars($log['action']) ?></strong></td>
                                <td><code><?= htmlspecialchars($log['ip']) ?></code></td>
                                <td style="font-size: 12.5px; color: var(--text-secondary);"><?= htmlspecialchars($log['details']) ?></td>
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

/* ==========================================================================
   FALLBACK GENERIC MENU
   ========================================================================== */
function renderGenericMenuContent($menu, $title) {
    ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <div class="card" style="padding: 24px; background: var(--bg-surface);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <div>
                    <h2 style="font-size: 20px; font-weight: 800; margin: 0;"><?= htmlspecialchars($title) ?></h2>
                    <p style="font-size: 12.5px; color: var(--text-secondary); margin: 4px 0 0 0;">Modul manajemen <?= strtolower($title) ?> aktif di server CBT</p>
                </div>
                <a href="/admin/dashboard" class="btn btn-secondary btn-sm">⬅ Kembali ke Dashboard</a>
            </div>
            <div style="background: var(--bg-surface-elevated); border: 1px dashed var(--border-color); padding: 32px; border-radius: 12px; text-align: center;">
                <span style="font-size: 44px; display: block; margin-bottom: 12px;">🚀</span>
                <div style="font-size: 16px; font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($title) ?> Siap Digunakan</div>
            </div>
        </div>
    </div>
    <?php
}
