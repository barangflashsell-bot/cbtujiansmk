import 'dart:async';
import 'package:flutter/material.dart';

import '../../anti_cheat/anti_cheat_observer.dart';
import '../../timer/exam_timer_controller.dart';
import '../data/exam_model.dart';
import '../data/exam_repository.dart';
import 'exam_finish_screen.dart';

class ExamScreen extends StatefulWidget {
  final ExamAttemptSession session;
  final ExamRepository repository;

  const ExamScreen({
    super.key,
    required this.session,
    required this.repository,
  });

  @override
  State<ExamScreen> createState() => _ExamScreenState();
}

class _ExamScreenState extends State<ExamScreen> {
  late final ExamTimerController _timerController;
  late final AntiCheatObserver _antiCheatObserver;
  Timer? _serverTimerSyncTicker;

  int _currentIndex = 0;
  final Map<int, int?> _selectedAnswers = {};
  final Map<int, bool> _flaggedQuestions = {};

  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();

    // Load initial answers
    for (final entry in widget.session.savedAnswers.entries) {
      _selectedAnswers[entry.key] = entry.value.selectedOptionId;
      _flaggedQuestions[entry.key] = entry.value.isFlagged;
    }

    // Initialize Timer
    _timerController = ExamTimerController(
      initialSeconds: widget.session.remainingSeconds,
      onTimeExpired: _handleTimeExpired,
    );
    _timerController.start();

    // Initialize Anti-Cheat Observer
    _antiCheatObserver = AntiCheatObserver(
      onWarning: _showAntiCheatWarning,
    );
    _antiCheatObserver.attach();

    // Start background sync loop
    widget.repository.syncQueueManager.startPeriodicSync(widget.session.attemptId);

    // Start periodic server-authoritative timer sync (detects admin time extension)
    _startServerTimerSync();
  }

  @override
  void dispose() {
    _serverTimerSyncTicker?.cancel();
    _serverTimerSyncTicker = null;
    _timerController.dispose();
    _antiCheatObserver.detach();
    widget.repository.syncQueueManager.stopPeriodicSync();
    super.dispose();
  }

  void _showAntiCheatWarning() {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: const Row(
          children: [
            Icon(Icons.warning_amber_rounded, color: Colors.white),
            SizedBox(width: 8),
            Expanded(
              child: Text(
                'Peringatan Integritas: Aplikasi terdeteksi kehilangan fokus atau berpindah ke latar belakang.',
              ),
            ),
          ],
        ),
        backgroundColor: Colors.red.shade700,
        duration: const Duration(seconds: 4),
      ),
    );
  }

  void _startServerTimerSync() {
    _serverTimerSyncTicker?.cancel();
    _serverTimerSyncTicker = Timer.periodic(const Duration(seconds: 30), (_) {
      _syncServerTimer();
    });
  }

  Future<void> _syncServerTimer() async {
    try {
      final timerData = await widget.repository.getAttemptTimer(widget.session.attemptId);
      final remaining = timerData['remaining_seconds'] as int?;
      if (remaining != null && mounted) {
        _timerController.syncWithServer(remaining);

        final isExpired = timerData['is_expired'] == true || timerData['status'] == 'timeout';
        if ((isExpired || remaining <= 0) && !_isSubmitting) {
          _handleTimeExpired();
        }
      }
    } catch (_) {
      // Offline/server unreachable: visual countdown proceeds reliably based on last server state
    }
  }

  void _handleTimeExpired() {
    if (!mounted || _isSubmitting) return;

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        title: const Row(
          children: [
            Icon(Icons.timer_off_rounded, color: Colors.red),
            SizedBox(width: 8),
            Text('Waktu Ujian Habis'),
          ],
        ),
        content: const Text(
          'Batas waktu pengerjaan ujian telah berakhir. Seluruh jawaban Anda akan disinkronkan dan dikirim secara otomatis ke server CBT.',
        ),
        actions: [
          ElevatedButton(
            onPressed: () {
              Navigator.of(ctx).pop();
              _submitExam(forced: true);
            },
            child: const Text('Kirim Jawaban Sekarang'),
          ),
        ],
      ),
    );
  }

  void _selectOption(int questionId, int optionId) async {
    setState(() {
      _selectedAnswers[questionId] = optionId;
    });

    // Autosave immediately and sync timer if server returns authoritative remaining_seconds
    final serverRemaining = await widget.repository.saveAnswer(
      attemptId: widget.session.attemptId,
      questionId: questionId,
      selectedOptionId: optionId,
      isFlagged: _flaggedQuestions[questionId] ?? false,
    );

    if (serverRemaining != null && mounted) {
      _timerController.syncWithServer(serverRemaining);
      if (serverRemaining <= 0 && !_isSubmitting) {
        _handleTimeExpired();
      }
    }
  }

  void _toggleFlag(int questionId) async {
    final current = _flaggedQuestions[questionId] ?? false;
    final next = !current;

    setState(() {
      _flaggedQuestions[questionId] = next;
    });

    final serverRemaining = await widget.repository.saveAnswer(
      attemptId: widget.session.attemptId,
      questionId: questionId,
      selectedOptionId: _selectedAnswers[questionId],
      isFlagged: next,
    );

    if (serverRemaining != null && mounted) {
      _timerController.syncWithServer(serverRemaining);
      if (serverRemaining <= 0 && !_isSubmitting) {
        _handleTimeExpired();
      }
    }
  }

  Future<void> _submitExam({bool forced = false}) async {
    if (_isSubmitting) return;

    setState(() {
      _isSubmitting = true;
    });

    try {
      await widget.repository.submitExam(widget.session.attemptId);

      // Attempt to retrieve result if published/available
      final result = await widget.repository.getExamResult(widget.session.attemptId);

      if (mounted) {
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(
            builder: (_) => ExamFinishScreen(
              examTitle: widget.session.title ?? 'Ujian CBT',
              result: result,
            ),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _isSubmitting = false;
        });

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Gagal menyelesaikan ujian: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  void _confirmSubmit() {
    final totalQuestions = widget.session.questions.length;
    final answeredCount = _selectedAnswers.values.where((v) => v != null).length;
    final flaggedCount = _flaggedQuestions.values.where((v) => v == true).length;
    final unansweredCount = totalQuestions - answeredCount;

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Konfirmasi Selesai Ujian'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Apakah Anda yakin ingin menyelesaikan sesi ujian ini? Pastikan semua soal telah Anda periksa.',
              style: TextStyle(fontSize: 13, color: Colors.black87),
            ),
            const SizedBox(height: 16),
            _buildSummaryRow('Total Butir Soal', '$totalQuestions soal', Colors.black87),
            const SizedBox(height: 6),
            _buildSummaryRow('Sudah Dijawab', '$answeredCount soal', Colors.green.shade700),
            const SizedBox(height: 6),
            _buildSummaryRow('Ditandai Ragu-ragu', '$flaggedCount soal', Colors.amber.shade800),
            const SizedBox(height: 6),
            _buildSummaryRow('Belum Dijawab', '$unansweredCount soal', Colors.red.shade700),
            if (unansweredCount > 0 || flaggedCount > 0) ...[
              const SizedBox(height: 14),
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.amber.shade50,
                  borderRadius: BorderRadius.circular(6),
                  border: Border.all(color: Colors.amber.shade200),
                ),
                child: Row(
                  children: [
                    Icon(Icons.info_outline, size: 16, color: Colors.amber.shade900),
                    const SizedBox(width: 6),
                    const Expanded(
                      child: Text(
                        'Masih terdapat soal yang belum dijawab atau ragu-ragu.',
                        style: TextStyle(fontSize: 11, color: Colors.black87),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(),
            child: const Text('Lanjutkan Mengerjakan'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.of(ctx).pop();
              _submitExam();
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.green.shade700,
              foregroundColor: Colors.white,
            ),
            child: const Text('Ya, Selesai Ujian'),
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryRow(String label, String value, Color valueColor) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontSize: 13, color: Colors.black54)),
        Text(
          value,
          style: TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: valueColor),
        ),
      ],
    );
  }

  void _openQuestionPalette() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (ctx) {
        return DraggableScrollableSheet(
          initialChildSize: 0.6,
          maxChildSize: 0.85,
          minChildSize: 0.4,
          expand: false,
          builder: (_, scrollController) {
            return Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      decoration: BoxDecoration(
                        color: Colors.grey.shade300,
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Daftar Nomor Soal',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close),
                        onPressed: () => Navigator.of(ctx).pop(),
                      ),
                    ],
                  ),
                  const Divider(),
                  Expanded(
                    child: GridView.builder(
                      controller: scrollController,
                      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: 5,
                        crossAxisSpacing: 10,
                        mainAxisSpacing: 10,
                      ),
                      itemCount: widget.session.questions.length,
                      itemBuilder: (context, index) {
                        final question = widget.session.questions[index];
                        final hasAnswer = _selectedAnswers[question.id] != null;
                        final isFlagged = _flaggedQuestions[question.id] == true;
                        final isCurrent = index == _currentIndex;

                        Color bgColor;
                        Color textColor = Colors.white;

                        if (isFlagged) {
                          bgColor = Colors.amber.shade600;
                        } else if (hasAnswer) {
                          bgColor = Colors.green.shade600;
                        } else {
                          bgColor = Colors.grey.shade200;
                          textColor = Colors.black87;
                        }

                        return InkWell(
                          onTap: () {
                            setState(() {
                              _currentIndex = index;
                            });
                            Navigator.of(ctx).pop();
                          },
                          child: Container(
                            decoration: BoxDecoration(
                              color: bgColor,
                              borderRadius: BorderRadius.circular(8),
                              border: isCurrent
                                  ? Border.all(color: Colors.indigo, width: 3)
                                  : null,
                            ),
                            alignment: Alignment.center,
                            child: Text(
                              '${index + 1}',
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.bold,
                                color: textColor,
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final questions = widget.session.questions;
    if (questions.isEmpty) {
      return const Scaffold(
        body: Center(child: Text('Paket ujian belum memiliki butir soal.')),
      );
    }

    final currentQuestion = questions[_currentIndex];
    final selectedOptionId = _selectedAnswers[currentQuestion.id];
    final isFlagged = _flaggedQuestions[currentQuestion.id] ?? false;

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, _) {
        if (!didPop) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Ujian sedang berlangsung. Gunakan tombol "Selesai Ujian" untuk keluar.'),
            ),
          );
        }
      },
      child: Scaffold(
        appBar: AppBar(
          backgroundColor: Colors.indigo,
          foregroundColor: Colors.white,
          automaticallyImplyLeading: false,
          title: Text(
            widget.session.title ?? 'Sesi Ujian CBT',
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
          ),
          actions: [
            // Sync status badge
            ListenableBuilder(
              listenable: widget.repository.syncQueueManager,
              builder: (context, _) {
                final pending = widget.repository.syncQueueManager.pendingCount;
                if (pending > 0) {
                  return Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 8),
                    child: Center(
                      child: Chip(
                        avatar: const Icon(Icons.sync_problem, size: 14, color: Colors.white),
                        label: Text(
                          '$pending antrean offline',
                          style: const TextStyle(color: Colors.white, fontSize: 10),
                        ),
                        backgroundColor: Colors.orange.shade800,
                        padding: EdgeInsets.zero,
                        visualDensity: VisualDensity.compact,
                      ),
                    ),
                  );
                }
                return const SizedBox.shrink();
              },
            ),

            // Countdown timer badge
            ListenableBuilder(
              listenable: _timerController,
              builder: (context, _) {
                final isWarning = _timerController.isWarning;
                return Container(
                  margin: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: isWarning ? Colors.red.shade700 : Colors.indigo.shade900,
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.timer, size: 16, color: Colors.white),
                      const SizedBox(width: 4),
                      Text(
                        _timerController.formattedTime,
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                          fontSize: 13,
                        ),
                      ),
                    ],
                  ),
                );
              },
            ),

            // Question palette launcher
            IconButton(
              icon: const Icon(Icons.grid_view_rounded),
              tooltip: 'Daftar Nomor Soal',
              onPressed: _openQuestionPalette,
            ),
          ],
        ),
        body: SafeArea(
          child: Column(
            children: [
              // Progress Bar
              LinearProgressIndicator(
                value: (_currentIndex + 1) / questions.length,
                backgroundColor: Colors.grey.shade200,
                color: Colors.indigo,
                minHeight: 4,
              ),

              // Question body scrollable
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Question Header Pill
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: Colors.indigo.shade50,
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              'Soal No. ${_currentIndex + 1} dari ${questions.length}',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                color: Colors.indigo.shade800,
                              ),
                            ),
                          ),
                          Text(
                            'Bobot: ${currentQuestion.weight}',
                            style: const TextStyle(fontSize: 12, color: Colors.black54),
                          ),
                        ],
                      ),

                      const SizedBox(height: 12),

                      // Question Text
                      Card(
                        elevation: 1,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        child: Padding(
                          padding: const EdgeInsets.all(16.0),
                          child: Text(
                            currentQuestion.content,
                            style: const TextStyle(fontSize: 15, height: 1.4, color: Colors.black87),
                          ),
                        ),
                      ),

                      const SizedBox(height: 16),

                      // Options List
                      ...currentQuestion.options.map((opt) {
                        final isSelected = selectedOptionId == opt.id;
                        return Container(
                          margin: const EdgeInsets.only(bottom: 10),
                          decoration: BoxDecoration(
                            color: isSelected ? Colors.indigo.shade50 : Colors.white,
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(
                              color: isSelected ? Colors.indigo : Colors.grey.shade300,
                              width: isSelected ? 1.5 : 1,
                            ),
                          ),
                          child: InkWell(
                            borderRadius: BorderRadius.circular(8),
                            onTap: () => _selectOption(currentQuestion.id, opt.id),
                            child: Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  CircleAvatar(
                                    radius: 14,
                                    backgroundColor: isSelected ? Colors.indigo : Colors.grey.shade200,
                                    child: Text(
                                      opt.label,
                                      style: TextStyle(
                                        fontSize: 12,
                                        fontWeight: FontWeight.bold,
                                        color: isSelected ? Colors.white : Colors.black87,
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Text(
                                      opt.content,
                                      style: TextStyle(
                                        fontSize: 14,
                                        height: 1.3,
                                        color: isSelected ? Colors.indigo.shade900 : Colors.black87,
                                        fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        );
                      }),
                    ],
                  ),
                ),
              ),

              // Bottom Navigation Bar
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                decoration: BoxDecoration(
                  color: Colors.white,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withAlpha(12),
                      blurRadius: 4,
                      offset: const Offset(0, -2),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    // Previous button
                    ElevatedButton.icon(
                      onPressed: _currentIndex > 0
                          ? () => setState(() => _currentIndex--)
                          : null,
                      icon: const Icon(Icons.arrow_back, size: 16),
                      label: const Text('Sebelumnya'),
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      ),
                    ),

                    const Spacer(),

                    // Ragu-ragu Toggle
                    OutlinedButton.icon(
                      onPressed: () => _toggleFlag(currentQuestion.id),
                      icon: Icon(
                        isFlagged ? Icons.bookmark : Icons.bookmark_border,
                        color: isFlagged ? Colors.amber.shade800 : Colors.grey,
                        size: 18,
                      ),
                      label: Text(
                        'Ragu-ragu',
                        style: TextStyle(
                          color: isFlagged ? Colors.amber.shade900 : Colors.black87,
                          fontWeight: isFlagged ? FontWeight.bold : FontWeight.normal,
                        ),
                      ),
                      style: OutlinedButton.styleFrom(
                        side: BorderSide(
                          color: isFlagged ? Colors.amber.shade600 : Colors.grey.shade300,
                        ),
                        backgroundColor: isFlagged ? Colors.amber.shade50 : null,
                      ),
                    ),

                    const Spacer(),

                    // Next / Finish button
                    if (_currentIndex < questions.length - 1)
                      ElevatedButton.icon(
                        onPressed: () => setState(() => _currentIndex++),
                        icon: const Icon(Icons.arrow_forward, size: 16),
                        label: const Text('Berikutnya'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.indigo,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                        ),
                      )
                    else
                      ElevatedButton.icon(
                        onPressed: _isSubmitting ? null : _confirmSubmit,
                        icon: const Icon(Icons.check, size: 16),
                        label: const Text('Selesai'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.green.shade700,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                        ),
                      ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
