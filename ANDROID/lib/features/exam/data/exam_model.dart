/// Minimal item representing an exam in the list.
class ExamListItem {
  final int id;
  final String title;
  final String? description;
  final String? instructions;
  final int durationMinutes;
  final int questionsCount;
  final String status;
  final String? subjectName;
  final String? subjectCode;
  final bool hasToken;
  final String? className;
  final String? token;

  const ExamListItem({
    required this.id,
    required this.title,
    this.description,
    this.instructions,
    required this.durationMinutes,
    required this.questionsCount,
    required this.status,
    this.subjectName,
    this.subjectCode,
    required this.hasToken,
    this.className,
    this.token,
  });

  factory ExamListItem.fromJson(Map<String, dynamic> json) {
    final subject = json['subject'] as Map<String, dynamic>?;
    final parsedId = json['id'] is num
        ? (json['id'] as num).toInt()
        : (int.tryParse(json['id']?.toString() ?? '1') ?? 1);
    final duration = json['duration_minutes'] ?? json['duration'];
    final parsedDuration = duration is num
        ? duration.toInt()
        : (int.tryParse(duration?.toString() ?? '60') ?? 60);
    final questionsCount = json['questions_count'] is num
        ? (json['questions_count'] as num).toInt()
        : (int.tryParse(json['questions_count']?.toString() ?? '0') ?? 0);

    return ExamListItem(
      id: parsedId,
      title: json['title']?.toString() ?? 'Ujian CBT',
      description: json['description']?.toString(),
      instructions: json['instructions']?.toString(),
      durationMinutes: parsedDuration,
      questionsCount: questionsCount,
      status: json['status']?.toString() ?? 'active',
      subjectName: subject?['name']?.toString() ?? json['subject']?.toString(),
      subjectCode: subject?['code']?.toString(),
      hasToken: json['token'] != null && json['token'].toString().isNotEmpty,
      className: json['class']?.toString() ?? json['class_name']?.toString(),
      token: json['token']?.toString(),
    );
  }
}

/// Single choice option for a question.
class QuestionOptionItem {
  final int id;
  final String label;
  final String content;

  const QuestionOptionItem({
    required this.id,
    required this.label,
    required this.content,
  });

  factory QuestionOptionItem.fromJson(Map<String, dynamic> json) {
    final parsedId = json['id'] is num
        ? (json['id'] as num).toInt()
        : (int.tryParse(json['id']?.toString() ?? '0') ?? 0);
    return QuestionOptionItem(
      id: parsedId,
      label: json['label']?.toString() ?? '',
      content: json['content']?.toString() ?? '',
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'label': label,
        'content': content,
      };
}

/// Single question in an exam attempt.
class ExamQuestionItem {
  final int id;
  final int orderIndex;
  final int weight;
  final String questionType;
  final String content;
  final String? mediaPath;
  final List<QuestionOptionItem> options;

  const ExamQuestionItem({
    required this.id,
    required this.orderIndex,
    required this.weight,
    required this.questionType,
    required this.content,
    this.mediaPath,
    required this.options,
  });

  factory ExamQuestionItem.fromJson(Map<String, dynamic> json) {
    final rawOptions = (json['options'] as List<dynamic>?) ?? [];
    final parsedId = json['id'] is num
        ? (json['id'] as num).toInt()
        : (int.tryParse(json['id']?.toString() ?? '1') ?? 1);
    final parsedOrder = json['order_index'] is num
        ? (json['order_index'] as num).toInt()
        : (int.tryParse(json['order_index']?.toString() ?? '1') ?? 1);
    final parsedWeight = json['weight'] is num
        ? (json['weight'] as num).toInt()
        : (int.tryParse(json['weight']?.toString() ?? '1') ?? 1);

    return ExamQuestionItem(
      id: parsedId,
      orderIndex: parsedOrder,
      weight: parsedWeight,
      questionType: json['question_type']?.toString() ?? 'multiple_choice',
      content: json['content']?.toString() ?? '',
      mediaPath: json['media_path']?.toString(),
      options: rawOptions
          .map((opt) => QuestionOptionItem.fromJson(opt as Map<String, dynamic>))
          .toList(),
    );
  }
}

/// Detailed session data returned when starting or resuming an attempt.
class ExamAttemptSession {
  final int attemptId;
  final int examId;
  final String? title;
  final int durationSeconds;
  final int remainingSeconds;
  final String status;
  final List<ExamQuestionItem> questions;
  final Map<int, SavedAnswerItem> savedAnswers;

  const ExamAttemptSession({
    required this.attemptId,
    required this.examId,
    this.title,
    required this.durationSeconds,
    required this.remainingSeconds,
    required this.status,
    required this.questions,
    required this.savedAnswers,
  });

  factory ExamAttemptSession.fromJson(Map<String, dynamic> json, {String? examTitle}) {
    final data = json['data'] as Map<String, dynamic>;
    final rawQuestions = (data['questions'] as List<dynamic>?) ?? [];
    final rawAnswers = (data['saved_answers'] as List<dynamic>?) ?? [];

    final questions = rawQuestions
        .map((q) => ExamQuestionItem.fromJson(q as Map<String, dynamic>))
        .toList();

    final answersMap = <int, SavedAnswerItem>{};
    for (final a in rawAnswers) {
      final ans = SavedAnswerItem.fromJson(a as Map<String, dynamic>);
      answersMap[ans.questionId] = ans;
    }

    final parsedAttemptId = data['attempt_id'] is num
        ? (data['attempt_id'] as num).toInt()
        : (int.tryParse(data['attempt_id']?.toString() ?? '1') ?? 1);
    final parsedExamId = data['exam_id'] is num
        ? (data['exam_id'] as num).toInt()
        : (int.tryParse(data['exam_id']?.toString() ?? '1') ?? 1);
    final durSec = data['duration_seconds'] is num
        ? (data['duration_seconds'] as num).toInt()
        : (int.tryParse(data['duration_seconds']?.toString() ?? '3600') ?? 3600);
    final remSec = data['remaining_seconds'] is num
        ? (data['remaining_seconds'] as num).toInt()
        : (int.tryParse(data['remaining_seconds']?.toString() ?? '3600') ?? 3600);

    return ExamAttemptSession(
      attemptId: parsedAttemptId,
      examId: parsedExamId,
      title: examTitle,
      durationSeconds: durSec,
      remainingSeconds: remSec,
      status: data['status']?.toString() ?? 'in_progress',
      questions: questions,
      savedAnswers: answersMap,
    );
  }
}

/// Saved answer representation.
class SavedAnswerItem {
  final int questionId;
  final int? selectedOptionId;
  final String? essayAnswer;
  final bool isFlagged;

  const SavedAnswerItem({
    required this.questionId,
    this.selectedOptionId,
    this.essayAnswer,
    this.isFlagged = false,
  });

  factory SavedAnswerItem.fromJson(Map<String, dynamic> json) {
    final parsedQId = json['question_id'] is num
        ? (json['question_id'] as num).toInt()
        : (int.tryParse(json['question_id']?.toString() ?? '0') ?? 0);
    final optId = json['selected_option_id'] != null
        ? (json['selected_option_id'] is num
            ? (json['selected_option_id'] as num).toInt()
            : int.tryParse(json['selected_option_id'].toString()))
        : null;

    return SavedAnswerItem(
      questionId: parsedQId,
      selectedOptionId: optId,
      essayAnswer: json['essay_answer'] as String?,
      isFlagged: json['is_flagged'] == true ||
          json['is_flagged'] == 1 ||
          json['is_flagged'] == '1',
    );
  }
}

/// Official server-authoritative exam result model.
class ExamResultModel {
  final int id;
  final int attemptId;
  final int examId;
  final String examTitle;
  final String subjectName;
  final int correctCount;
  final int wrongCount;
  final int unansweredCount;
  final double mcScore;
  final double essayScore;
  final double score;
  final double finalScore;
  final String status;
  final bool isPublished;
  final bool isScoreHidden;
  final double? passingScore;
  final String? submittedAt;

  const ExamResultModel({
    required this.id,
    required this.attemptId,
    required this.examId,
    this.examTitle = 'Ujian CBT',
    this.subjectName = 'Mata Pelajaran',
    required this.correctCount,
    required this.wrongCount,
    required this.unansweredCount,
    required this.mcScore,
    required this.essayScore,
    required this.score,
    required this.finalScore,
    required this.status,
    required this.isPublished,
    this.isScoreHidden = false,
    this.passingScore,
    this.submittedAt,
  });

  bool get isPassed => passingScore != null ? finalScore >= passingScore! : true;

  factory ExamResultModel.fromJson(Map<String, dynamic> json) {
    final examData = json['exam'] as Map<String, dynamic>?;
    double? passing;
    if (json['passing_score'] != null) {
      passing = double.tryParse(json['passing_score'].toString());
    } else if (examData != null && examData['passing_score'] != null) {
      passing = double.tryParse(examData['passing_score'].toString());
    }

    // Exam Title
    String title = json['exam_title'] as String? ?? '';
    if (title.isEmpty && examData != null && examData['title'] != null) {
      title = examData['title'].toString();
    }
    if (title.isEmpty) {
      title = 'Ujian CBT';
    }

    // Subject Name
    String subject = json['subject_name'] as String? ?? '';
    if (subject.isEmpty && examData != null) {
      final subObj = examData['subject'];
      if (subObj is Map<String, dynamic> && subObj['name'] != null) {
        subject = subObj['name'].toString();
      } else if (examData['subject_name'] != null) {
        subject = examData['subject_name'].toString();
      }
    }
    if (subject.isEmpty) {
      subject = 'Mata Pelajaran';
    }

    final scoreHidden = json['is_score_hidden'] == true || json['show_score'] == false;
    final parsedScore = double.tryParse(json['score']?.toString() ?? '0') ?? 0.0;
    final parsedFinalScore = double.tryParse(json['final_score']?.toString() ?? json['score']?.toString() ?? '0') ?? 0.0;

    final parsedId = json['id'] is num ? (json['id'] as num).toInt() : (int.tryParse(json['id']?.toString() ?? '1') ?? 1);
    final parsedAttemptId = json['attempt_id'] is num ? (json['attempt_id'] as num).toInt() : (int.tryParse(json['attempt_id']?.toString() ?? '1') ?? 1);
    final parsedExamId = json['exam_id'] is num ? (json['exam_id'] as num).toInt() : (int.tryParse(json['exam_id']?.toString() ?? '1') ?? 1);

    return ExamResultModel(
      id: parsedId,
      attemptId: parsedAttemptId,
      examId: parsedExamId,
      examTitle: title,
      subjectName: subject,
      correctCount: (json['correct_count'] as int?) ?? (json['correct_answers'] as int?) ?? 0,
      wrongCount: (json['wrong_count'] as int?) ?? (json['wrong_answers'] as int?) ?? 0,
      unansweredCount: (json['unanswered_count'] as int?) ?? 0,
      mcScore: double.tryParse(json['mc_score']?.toString() ?? '0') ?? 0.0,
      essayScore: double.tryParse(json['essay_score']?.toString() ?? '0') ?? 0.0,
      score: parsedScore,
      finalScore: parsedFinalScore,
      status: json['status'] as String? ?? 'completed',
      isPublished: (json['is_published'] as bool?) ?? true,
      isScoreHidden: scoreHidden,
      passingScore: passing ?? 75.0,
      submittedAt: json['submitted_at'] as String?,
    );
  }
}
