import 'package:flutter/material.dart';

import 'core/config/app_preferences.dart';
import 'features/auth/data/auth_model.dart';
import 'features/auth/presentation/login_screen.dart';
import 'features/exam/presentation/exam_list_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await AppPreferences.init();
  runApp(const CbtApp());
}

class CbtApp extends StatelessWidget {
  const CbtApp({super.key});

  @override
  Widget build(BuildContext context) {
    // Check if previous session exists
    Widget initialScreen = const LoginScreen();

    if (AppPreferences.isAuthenticated && AppPreferences.userId != null) {
      final user = UserModel(
        id: AppPreferences.userId!,
        username: AppPreferences.username ?? 'peserta',
        name: AppPreferences.userName ?? 'Siswa Peserta',
        role: 'student',
        studentId: AppPreferences.studentId,
        nis: AppPreferences.studentNis,
      );
      initialScreen = ExamListScreen(user: user);
    }

    return MaterialApp(
      title: 'CBT Client Peserta',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: Colors.indigo,
          brightness: Brightness.light,
        ),
        useMaterial3: true,
        scaffoldBackgroundColor: const Color(0xFFF8FAFC),
        cardTheme: const CardThemeData(
          color: Colors.white,
          elevation: 1,
        ),
      ),
      home: initialScreen,
    );
  }
}
