import 'package:cbt_client/features/exam/data/exam_model.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('ExamModel Tests', () {
    test('ExamListItem parses correctly from API list item', () {
      final json = {
        'id': 1,
        'title': 'Ujian Akhir Semester Matematika',
        'description': 'Materi Aljabar dan Trigonometri',
        'duration_minutes': 90,
        'questions_count': 30,
        'status': 'active',
        'token': 'MATH99',
        'subject': {
          'id': 2,
          'code': 'MAT',
          'name': 'Matematika',
        },
      };

      final item = ExamListItem.fromJson(json);

      expect(item.id, equals(1));
      expect(item.title, equals('Ujian Akhir Semester Matematika'));
      expect(item.durationMinutes, equals(90));
      expect(item.questionsCount, equals(30));
      expect(item.hasToken, isTrue);
      expect(item.subjectName, equals('Matematika'));
    });

    test('ExamAttemptSession parses questions without answer keys', () {
      final json = {
        'success': true,
        'data': {
          'attempt_id': 10,
          'exam_id': 1,
          'duration_seconds': 5400,
          'remaining_seconds': 5390,
          'status': 'in_progress',
          'questions': [
            {
              'id': 101,
              'order_index': 1,
              'weight': 2,
              'question_type': 'multiple_choice',
              'content': 'Berapakah 2 + 2?',
              'options': [
                {'id': 1, 'label': 'A', 'content': '3'},
                {'id': 2, 'label': 'B', 'content': '4'},
                {'id': 3, 'label': 'C', 'content': '5'},
              ]
            }
          ],
          'saved_answers': [
            {
              'question_id': 101,
              'selected_option_id': 2,
              'is_flagged': false,
            }
          ]
        }
      };

      final session = ExamAttemptSession.fromJson(json, examTitle: 'Ujian Matematika');

      expect(session.attemptId, equals(10));
      expect(session.questions.length, equals(1));
      expect(session.questions[0].options.length, equals(3));
      expect(session.questions[0].options[1].label, equals('B'));
      expect(session.savedAnswers[101]?.selectedOptionId, equals(2));
      expect(session.remainingSeconds, equals(5390));
    });

    test('SavedAnswerItem handles int and bool is_flagged gracefully', () {
      final itemInt = SavedAnswerItem.fromJson({
        'question_id': 200,
        'selected_option_id': 5,
        'is_flagged': 1,
      });
      expect(itemInt.isFlagged, isTrue);

      final itemZero = SavedAnswerItem.fromJson({
        'question_id': 201,
        'selected_option_id': 6,
        'is_flagged': 0,
      });
      expect(itemZero.isFlagged, isFalse);

      final itemBool = SavedAnswerItem.fromJson({
        'question_id': 202,
        'selected_option_id': null,
        'is_flagged': true,
      });
      expect(itemBool.isFlagged, isTrue);
    });

    test('ExamResultModel parses correctly and computes isPassed', () {
      final json = {
        'id': 5,
        'attempt_id': 10,
        'exam_id': 1,
        'correct_count': 18,
        'wrong_count': 2,
        'unanswered_count': 0,
        'mc_score': '90.00',
        'essay_score': '0.00',
        'score': '90.00',
        'final_score': '90.00',
        'status': 'completed',
        'is_published': true,
        'exam': {
          'passing_score': 75.0,
        },
      };

      final result = ExamResultModel.fromJson(json);

      expect(result.id, equals(5));
      expect(result.attemptId, equals(10));
      expect(result.correctCount, equals(18));
      expect(result.wrongCount, equals(2));
      expect(result.unansweredCount, equals(0));
      expect(result.finalScore, equals(90.0));
      expect(result.passingScore, equals(75.0));
      expect(result.isPassed, isTrue);
      expect(result.isPublished, isTrue);
    });

    test('ExamResultModel failed when score is below passing score', () {
      final json = {
        'id': 6,
        'attempt_id': 11,
        'exam_id': 1,
        'correct_count': 10,
        'wrong_count': 10,
        'unanswered_count': 0,
        'mc_score': '50.00',
        'essay_score': '0.00',
        'score': '50.00',
        'final_score': '50.00',
        'status': 'completed',
        'is_published': true,
        'exam': {
          'passing_score': 75.0,
        },
      };

      final result = ExamResultModel.fromJson(json);
      expect(result.finalScore, equals(50.0));
      expect(result.isPassed, isFalse);
    });
  });
}
