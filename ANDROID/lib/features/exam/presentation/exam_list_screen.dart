import 'package:flutter/material.dart';

import '../../auth/data/auth_model.dart';
import '../../auth/data/auth_repository.dart';
import '../../auth/presentation/login_screen.dart';
import '../data/exam_model.dart';
import '../data/exam_repository.dart';
import 'exam_screen.dart';

class ExamListScreen extends StatefulWidget {
  final UserModel user;
  final ExamRepository? examRepository;
  final AuthRepository? authRepository;

  const ExamListScreen({
    super.key,
    required this.user,
    this.examRepository,
    this.authRepository,
  });

  @override
  State<ExamListScreen> createState() => _ExamListScreenState();
}

class _ExamListScreenState extends State<ExamListScreen> {
  late final ExamRepository _examRepository;
  late final AuthRepository _authRepository;

  List<ExamListItem> _exams = [];
  List<ExamResultModel> _results = [];
  bool _isLoading = true;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _examRepository = widget.examRepository ?? ExamRepository();
    _authRepository = widget.authRepository ?? AuthRepository();
    _loadExams();
  }

  Future<void> _loadExams() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final exams = await _examRepository.getActiveExams();
      List<ExamResultModel> results = [];
      try {
        results = await _examRepository.getStudentResults();
      } catch (_) {
        // Fallback gracefully if results endpoint is unavailable
      }

      if (mounted) {
        setState(() {
          _exams = exams;
          _results = results;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = e.toString().replaceAll('Exception: ', '');
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _handleStartExam(ExamListItem exam) async {
    if (exam.hasToken) {
      _promptExamToken(exam);
    } else {
      _startSession(exam);
    }
  }

  void _promptExamToken(ExamListItem exam) {
    final tokenController = TextEditingController();

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Token Ujian Diperlukan'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Masukkan token 6 karakter yang dirilis oleh pengawas ruangan untuk paket "${exam.title}".',
              style: const TextStyle(fontSize: 13, color: Colors.black54),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: tokenController,
              textCapitalization: TextCapitalization.characters,
              decoration: const InputDecoration(
                labelText: 'Token Ujian',
                hintText: 'Contoh: ABC123',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.key),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Batal'),
          ),
          ElevatedButton(
            onPressed: () {
              final token = tokenController.text.trim();
              if (token.isNotEmpty) {
                Navigator.of(ctx).pop();
                _startSession(exam, token: token);
              }
            },
            child: const Text('Mulai Ujian'),
          ),
        ],
      ),
    );
  }

  Future<void> _startSession(ExamListItem exam, {String? token}) async {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(child: CircularProgressIndicator()),
    );

    try {
      final session = await _examRepository.startOrResumeExam(
        exam.id,
        examToken: token,
        examTitle: exam.title,
      );

      if (mounted) {
        Navigator.of(context).pop(); // Dismiss loading spinner
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => ExamScreen(
              session: session,
              repository: _examRepository,
            ),
          ),
        ).then((_) => _loadExams());
      }
    } catch (e) {
      if (mounted) {
        Navigator.of(context).pop(); // Dismiss loading spinner
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Gagal memulai ujian: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _handleLogout() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Keluar Akun'),
        content: const Text('Apakah Anda yakin ingin keluar dari aplikasi ujian?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Batal'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
            child: const Text('Keluar'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      await _authRepository.logout();
      if (mounted) {
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => const LoginScreen()),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Daftar Ujian Aktif'),
        backgroundColor: Colors.indigo,
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Keluar',
            onPressed: _handleLogout,
          ),
        ],
      ),
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _loadExams,
          child: ListView(
            padding: const EdgeInsets.all(16.0),
            children: [
              // Student Greeting Card
              Card(
                elevation: 2,
                color: Colors.indigo.shade50,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                  side: BorderSide(color: Colors.indigo.shade100),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Row(
                    children: [
                      CircleAvatar(
                        radius: 26,
                        backgroundColor: Colors.indigo,
                        child: Text(
                          widget.user.name.isNotEmpty ? widget.user.name[0].toUpperCase() : 'S',
                          style: const TextStyle(fontSize: 22, color: Colors.white, fontWeight: FontWeight.bold),
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              widget.user.name,
                              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.indigo),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              'NIS / Username: ${widget.user.nis ?? widget.user.username}',
                              style: const TextStyle(fontSize: 13, color: Colors.black54),
                            ),
                            const SizedBox(height: 2),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: Colors.green.shade100,
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(
                                'Peserta Aktif',
                                style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Colors.green.shade900),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),

              const SizedBox(height: 20),

              const Text(
                'Paket Ujian Tersedia',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black87),
              ),
              const SizedBox(height: 8),

              if (_isLoading)
                const Center(
                  child: Padding(
                    padding: EdgeInsets.all(40.0),
                    child: CircularProgressIndicator(),
                  ),
                )
              else if (_errorMessage != null)
                Center(
                  child: Padding(
                    padding: const EdgeInsets.all(32.0),
                    child: Column(
                      children: [
                        Icon(Icons.error_outline, size: 48, color: Colors.red.shade400),
                        const SizedBox(height: 12),
                        Text(_errorMessage!, textAlign: TextAlign.center, style: const TextStyle(color: Colors.red)),
                        const SizedBox(height: 12),
                        ElevatedButton.icon(
                          onPressed: _loadExams,
                          icon: const Icon(Icons.refresh),
                          label: const Text('Coba Lagi'),
                        ),
                      ],
                    ),
                  ),
                )
              else if (_exams.isEmpty)
                Center(
                  child: Padding(
                    padding: const EdgeInsets.all(40.0),
                    child: Column(
                      children: [
                        Icon(Icons.assignment_outlined, size: 60, color: Colors.grey.shade400),
                        const SizedBox(height: 12),
                        const Text(
                          'Belum ada jadwal ujian aktif untuk Anda.',
                          style: TextStyle(fontSize: 14, color: Colors.black54),
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'Tarik layar ke bawah untuk memperbarui daftar.',
                          style: TextStyle(fontSize: 12, color: Colors.black38),
                        ),
                      ],
                    ),
                  ),
                )
              else
                ..._exams.map((exam) {
                  return Card(
                    elevation: 2,
                    margin: const EdgeInsets.only(bottom: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    child: Padding(
                      padding: const EdgeInsets.all(16.0),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Wrap(
                                spacing: 6,
                                runSpacing: 4,
                                crossAxisAlignment: WrapCrossAlignment.center,
                                children: [
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                    decoration: BoxDecoration(
                                      color: Colors.indigo.shade50,
                                      borderRadius: BorderRadius.circular(6),
                                    ),
                                    child: Text(
                                      exam.subjectName ?? exam.subjectCode ?? 'Mata Pelajaran',
                                      style: TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.bold,
                                        color: Colors.indigo.shade800,
                                      ),
                                    ),
                                  ),
                                  if (exam.className != null && exam.className!.isNotEmpty)
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: Colors.blue.shade50,
                                        borderRadius: BorderRadius.circular(6),
                                        border: Border.all(color: Colors.blue.shade200),
                                      ),
                                      child: Text(
                                        'Kelas: ${exam.className}',
                                        style: TextStyle(
                                          fontSize: 11,
                                          fontWeight: FontWeight.w600,
                                          color: Colors.blue.shade900,
                                        ),
                                      ),
                                    ),
                                ],
                              ),
                              if (exam.hasToken)
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: Colors.amber.shade100,
                                    borderRadius: BorderRadius.circular(6),
                                  ),
                                  child: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      Icon(Icons.lock, size: 12, color: Colors.amber.shade900),
                                      const SizedBox(width: 4),
                                      Text(
                                        'Butuh Token',
                                        style: TextStyle(
                                          fontSize: 11,
                                          fontWeight: FontWeight.bold,
                                          color: Colors.amber.shade900,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                            ],
                          ),
                          const SizedBox(height: 10),
                          Text(
                            exam.title,
                            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black87),
                          ),
                          if (exam.description != null && exam.description!.isNotEmpty) ...[
                            const SizedBox(height: 4),
                            Text(
                              exam.description!,
                              style: const TextStyle(fontSize: 13, color: Colors.black54),
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                          const Divider(height: 24),
                          Row(
                            children: [
                              Icon(Icons.schedule, size: 16, color: Colors.grey.shade600),
                              const SizedBox(width: 4),
                              Text('${exam.durationMinutes} Menit', style: const TextStyle(fontSize: 13, color: Colors.black54)),
                              const SizedBox(width: 16),
                              Icon(Icons.help_outline, size: 16, color: Colors.grey.shade600),
                              const SizedBox(width: 4),
                              Text('${exam.questionsCount} Butir Soal', style: const TextStyle(fontSize: 13, color: Colors.black54)),
                            ],
                          ),
                          const SizedBox(height: 16),
                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton.icon(
                              onPressed: () => _handleStartExam(exam),
                              icon: const Icon(Icons.play_arrow_rounded),
                              label: const Text('Mulai Kerjakan Ujian'),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.indigo,
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(vertical: 12),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  );
                }),

              const SizedBox(height: 24),

              // =======================================================
              // RIWAYAT & HASIL NILAI UJIAN SISWA PER MATA PELAJARAN
              // =======================================================
              _buildExamResultsSection(),
              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildExamResultsSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(
                    color: Colors.indigo.shade50,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(Icons.analytics_rounded, size: 20, color: Colors.indigo),
                ),
                const SizedBox(width: 8),
                const Text(
                  'Riwayat & Hasil Nilai Siswa',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Colors.black87,
                  ),
                ),
              ],
            ),
            if (_results.isNotEmpty)
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.indigo.shade50,
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: Colors.indigo.shade100),
                ),
                child: Text(
                  '${_results.length} Selesai',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: Colors.indigo.shade900,
                  ),
                ),
              ),
          ],
        ),
        const SizedBox(height: 4),
        const Text(
          'Rekapitulasi perolehan nilai resmi ujian per mata pelajaran.',
          style: TextStyle(fontSize: 12, color: Colors.black54),
        ),
        const SizedBox(height: 12),
        if (_results.isEmpty)
          Card(
            elevation: 0,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
              side: BorderSide(color: Colors.grey.shade300, style: BorderStyle.solid),
            ),
            color: Colors.grey.shade50,
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 24.0, horizontal: 16.0),
              child: Center(
                child: Column(
                  children: [
                    Icon(Icons.assignment_turned_in_outlined, size: 44, color: Colors.grey.shade400),
                    const SizedBox(height: 10),
                    const Text(
                      'Belum Ada Nilai Ujian Tercatat',
                      style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.black54),
                    ),
                    const SizedBox(height: 4),
                    const Text(
                      'Setelah Anda selesai mengerjakan paket ujian di atas, lembar nilai per mata pelajaran akan muncul otomatis di sini.',
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 12, color: Colors.black38, height: 1.4),
                    ),
                  ],
                ),
              ),
            ),
          )
        else
          ..._results.map((result) => _buildResultCard(result)),
      ],
    );
  }

  Widget _buildResultCard(ExamResultModel result) {
    final bool isPassed = result.isPassed;
    final bool isHidden = result.isScoreHidden;

    final Color statusColor = isHidden
        ? Colors.amber.shade800
        : (isPassed ? Colors.green.shade700 : Colors.red.shade700);

    final Color statusBg = isHidden
        ? Colors.amber.shade50
        : (isPassed ? Colors.green.shade50 : Colors.red.shade50);

    final Color borderColor = isHidden
        ? Colors.amber.shade200
        : (isPassed ? Colors.green.shade300 : Colors.red.shade300);

    return Card(
      elevation: 2,
      margin: const EdgeInsets.only(bottom: 14),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(14),
        side: BorderSide(color: borderColor, width: 1.5),
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Top Bar: Subject Badge & Status
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              color: statusBg,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Row(
                      children: [
                        const Text('📚 ', style: TextStyle(fontSize: 13)),
                        Expanded(
                          child: Text(
                            result.subjectName,
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                              color: isPassed ? Colors.green.shade900 : (isHidden ? Colors.amber.shade900 : Colors.red.shade900),
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: isHidden ? Colors.amber.shade100 : (isPassed ? Colors.green.shade100 : Colors.red.shade100),
                      borderRadius: BorderRadius.circular(6),
                      border: Border.all(color: borderColor),
                    ),
                    child: Text(
                      isHidden ? '🔒 Dirahasiakan' : (isPassed ? '✓ Lulus KKM' : '⚠️ Remedial'),
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w800,
                        color: statusColor,
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // Content Body
            Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    result.examTitle,
                    style: const TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.bold,
                      color: Colors.black87,
                    ),
                  ),
                  const SizedBox(height: 12),

                  if (isHidden)
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: Colors.grey.shade50,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: Colors.grey.shade200),
                      ),
                      child: Column(
                        children: [
                          Icon(Icons.lock_clock_rounded, size: 28, color: Colors.amber.shade700),
                          const SizedBox(height: 6),
                          const Text(
                            'Nilai Belum Diumumkan',
                            style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.black87),
                          ),
                          const SizedBox(height: 2),
                          const Text(
                            'Lembar jawaban Anda telah tersimpan aman di server CBT. Angka nilai dirahasiakan oleh panitia ujian.',
                            textAlign: TextAlign.center,
                            style: TextStyle(fontSize: 11.5, color: Colors.black54),
                          ),
                        ],
                      ),
                    )
                  else ...[
                    // Score Box
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
                      decoration: BoxDecoration(
                        color: isPassed ? Colors.green.shade50 : Colors.red.shade50,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: borderColor),
                      ),
                      child: Column(
                        children: [
                          Text(
                            'SKOR NILAI AKHIR',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 0.8,
                              color: isPassed ? Colors.green.shade800 : Colors.red.shade800,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            result.finalScore.toStringAsFixed(1),
                            style: TextStyle(
                              fontSize: 38,
                              fontWeight: FontWeight.w900,
                              color: isPassed ? Colors.green.shade900 : Colors.red.shade900,
                              height: 1.1,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Standar KKM: ${result.passingScore?.toStringAsFixed(1) ?? '75.0'}',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                              color: Colors.grey.shade700,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),

                    // Correct / Wrong / Unanswered Breakdown
                    Container(
                      padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 12),
                      decoration: BoxDecoration(
                        color: Colors.grey.shade50,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.grey.shade200),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              children: [
                                const Text('Benar', style: TextStyle(fontSize: 11, color: Colors.black54)),
                                const SizedBox(height: 2),
                                Text(
                                  '${result.correctCount}',
                                  style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.green.shade700),
                                ),
                              ],
                            ),
                          ),
                          Container(height: 24, width: 1, color: Colors.grey.shade300),
                          Expanded(
                            child: Column(
                              children: [
                                const Text('Salah', style: TextStyle(fontSize: 11, color: Colors.black54)),
                                const SizedBox(height: 2),
                                Text(
                                  '${result.wrongCount}',
                                  style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.red.shade700),
                                ),
                              ],
                            ),
                          ),
                          Container(height: 24, width: 1, color: Colors.grey.shade300),
                          Expanded(
                            child: Column(
                              children: [
                                const Text('Kosong', style: TextStyle(fontSize: 11, color: Colors.black54)),
                                const SizedBox(height: 2),
                                Text(
                                  '${result.unansweredCount}',
                                  style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.black54),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
