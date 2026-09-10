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
  });

  factory ExamListItem.fromJson(Map<String, dynamic> json) {
    final subject = json['subject'] as Map<String, dynamic>?;
    return ExamListItem(
      id: json['id'] as int,
      title: json['title'] as String,
      description: json['description'] as String?,
      instructions: json['instructions'] as String?,
      durationMinutes: (json['duration_minutes'] as int?) ?? 60,
      questionsCount: (json['questions_count'] as int?) ?? 0,
      status: json['status'] as String? ?? 'active',
      subjectName: subject?['name'] as String?,
      subjectCode: subject?['code'] as String?,
      hasToken: json['token'] != null && json['token'].toString().isNotEmpty,
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
    return QuestionOptionItem(
      id: json['id'] as int,
      label: json['label'] as String,
      content: json['content'] as String,
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
    return ExamQuestionItem(
      id: json['id'] as int,
      orderIndex: (json['order_index'] as int?) ?? 1,
      weight: (json['weight'] as int?) ?? 1,
      questionType: json['question_type'] as String? ?? 'multiple_choice',
      content: json['content'] as String,
      mediaPath: json['media_path'] as String?,
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

    return ExamAttemptSession(
      attemptId: data['attempt_id'] as int,
      examId: data['exam_id'] as int,
      title: examTitle,
      durationSeconds: (data['duration_seconds'] as int?) ?? 3600,
      remainingSeconds: (data['remaining_seconds'] as int?) ?? 3600,
      status: data['status'] as String? ?? 'in_progress',
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
    return SavedAnswerItem(
      questionId: json['question_id'] as int,
      selectedOptionId: json['selected_option_id'] as int?,
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
  final int correctCount;
  final int wrongCount;
  final int unansweredCount;
  final double mcScore;
  final double essayScore;
  final double score;
  final double finalScore;
  final String status;
  final bool isPublished;
  final double? passingScore;

  const ExamResultModel({
    required this.id,
    required this.attemptId,
    required this.examId,
    required this.correctCount,
    required this.wrongCount,
    required this.unansweredCount,
    required this.mcScore,
    required this.essayScore,
    required this.score,
    required this.finalScore,
    required this.status,
    required this.isPublished,
    this.passingScore,
  });

  bool get isPassed => passingScore != null ? finalScore >= passingScore! : true;

  factory ExamResultModel.fromJson(Map<String, dynamic> json) {
    final examData = json['exam'] as Map<String, dynamic>?;
    double? passing;
    if (examData != null && examData['passing_score'] != null) {
      passing = double.tryParse(examData['passing_score'].toString());
    }

    return ExamResultModel(
      id: json['id'] as int,
      attemptId: json['attempt_id'] as int,
      examId: json['exam_id'] as int,
      correctCount: (json['correct_count'] as int?) ?? 0,
      wrongCount: (json['wrong_count'] as int?) ?? 0,
      unansweredCount: (json['unanswered_count'] as int?) ?? 0,
      mcScore: double.tryParse(json['mc_score']?.toString() ?? '0') ?? 0.0,
      essayScore: double.tryParse(json['essay_score']?.toString() ?? '0') ?? 0.0,
      score: double.tryParse(json['score']?.toString() ?? '0') ?? 0.0,
      finalScore: double.tryParse(json['final_score']?.toString() ?? '0') ?? 0.0,
      status: json['status'] as String? ?? 'completed',
      isPublished: (json['is_published'] as bool?) ?? false,
      passingScore: passing,
    );
  }
}
