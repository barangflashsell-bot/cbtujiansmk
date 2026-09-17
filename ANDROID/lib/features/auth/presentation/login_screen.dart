import 'package:flutter/material.dart';

import '../../../core/config/api_config.dart';
import '../../../core/config/app_preferences.dart';
import '../../health/data/health_repository.dart';
import '../../health/presentation/health_check_screen.dart';
import '../data/auth_repository.dart';
import '../../exam/presentation/exam_list_screen.dart';

class LoginScreen extends StatefulWidget {
  final AuthRepository? authRepository;
  final HealthRepository? healthRepository;

  const LoginScreen({
    super.key,
    this.authRepository,
    this.healthRepository,
  });

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  late final AuthRepository _authRepository;
  late final HealthRepository _healthRepository;

  final _formKey = GlobalKey<FormState>();
  final _usernameController = TextEditingController();
  final _passwordController = TextEditingController();

  bool _obscurePassword = true;
  bool _isLoading = false;
  bool _isDiscovering = false;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _authRepository = widget.authRepository ?? AuthRepository();
    _healthRepository = widget.healthRepository ?? HealthRepository();
    _checkServer();
  }

  void _checkServer() {
    _healthRepository.checkHealth().then((_) {}, onError: (_) {});
  }

  Future<void> _handleAutoDiscover() async {
    setState(() {
      _isDiscovering = true;
      _errorMessage = null;
    });

    try {
      final foundUrl = await _healthRepository.autoDiscoverServer();
      if (foundUrl != null) {
        await AppPreferences.saveServerUrl(foundUrl);
        _checkServer();
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('✓ Server ditemukan & terhubung: $foundUrl'),
              backgroundColor: Colors.green.shade700,
              duration: const Duration(seconds: 3),
            ),
          );
        }
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Server tidak terdeteksi otomatis. Pastikan HP terhubung ke Wi-Fi sekolah.'),
              backgroundColor: Colors.orange,
              duration: Duration(seconds: 4),
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = 'Gagal memindai jaringan: $e';
        });
      }
    } finally {
      if (mounted) {
        setState(() {
          _isDiscovering = false;
        });
      }
    }
  }

  @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final response = await _authRepository.login(
        _usernameController.text,
        _passwordController.text,
      );

      if (mounted) {
        // Navigate to Exam List Screen
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(
            builder: (_) => ExamListScreen(user: response.user),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = e.toString().replaceAll('Exception: ', '');
        });
      }
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  void _openServerConfigDialog() {
    final controller = TextEditingController(text: ApiConfig.baseUrl);
    bool isDialogScanning = false;

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (dialogCtx, setDialogState) => AlertDialog(
          title: const Text('Konfigurasi Server CBT'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Masukkan IP Address server lokal di sekolah atau gunakan tombol cari otomatis di bawah:',
                style: TextStyle(fontSize: 13, color: Colors.black54),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: controller,
                decoration: const InputDecoration(
                  labelText: 'Server URL',
                  hintText: 'http://192.168.1.11:8000',
                  border: OutlineInputBorder(),
                  prefixIcon: Icon(Icons.lan),
                ),
                keyboardType: TextInputType.url,
              ),
              const SizedBox(height: 12),
              // Preset & Auto-Detect Buttons
              Wrap(
                spacing: 8,
                runSpacing: 6,
                children: [
                  OutlinedButton.icon(
                    onPressed: isDialogScanning
                        ? null
                        : () async {
                            setDialogState(() => isDialogScanning = true);
                            final found = await _healthRepository.autoDiscoverServer();
                            setDialogState(() => isDialogScanning = false);
                            if (found != null) {
                              controller.text = found;
                            }
                          },
                    icon: isDialogScanning
                        ? const SizedBox(width: 12, height: 12, child: CircularProgressIndicator(strokeWidth: 2))
                        : const Icon(Icons.wifi_find, size: 16),
                    label: Text(isDialogScanning ? 'Mencari...' : '🔍 Pindai Wi-Fi'),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    ),
                  ),
                  ActionChip(
                    label: const Text('LAN Default', style: TextStyle(fontSize: 11)),
                    onPressed: () {
                      controller.text = ApiConfig.defaultLanUrl;
                    },
                  ),
                  ActionChip(
                    label: const Text('Cloud Vercel', style: TextStyle(fontSize: 11)),
                    onPressed: () {
                      controller.text = ApiConfig.defaultCloudUrl;
                    },
                  ),
                ],
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.of(ctx).pop();
                Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const HealthCheckScreen()),
                );
              },
              child: const Text('Diagnosa'),
            ),
            ElevatedButton(
              onPressed: () async {
                final newUrl = controller.text.trim();
                if (ApiConfig.isValidUrl(newUrl)) {
                  await AppPreferences.saveServerUrl(newUrl);
                  if (ctx.mounted) {
                    Navigator.of(ctx).pop();
                  }
                  _checkServer();
                }
              },
              child: const Text('Simpan & Hubungkan'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24.0, vertical: 16.0),
            child: Form(
              key: _formKey,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // App Icon & Branding
                  Center(
                    child: Container(
                      width: 80,
                      height: 80,
                      decoration: BoxDecoration(
                        color: Colors.indigo.shade50,
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        Icons.school_rounded,
                        size: 48,
                        color: Colors.indigo.shade700,
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  const Text(
                    'CBT Peserta Ujian',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: Colors.black87,
                    ),
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    'Sistem Ujian Sekolah Berbasis Komputer & Android',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 13, color: Colors.black54),
                  ),

                  const SizedBox(height: 24),

                  // Server Connection Chip
                  Center(
                    child: ValueListenableBuilder(
                      valueListenable: _healthRepository.connectionManager,
                      builder: (context, state, _) {
                        final isOnline = state.isOnline;
                        return ActionChip(
                          avatar: Icon(
                            isOnline ? Icons.circle : Icons.error_outline,
                            size: 14,
                            color: isOnline ? Colors.green : Colors.orange,
                          ),
                          label: Text(
                            isOnline ? 'Server Terhubung' : 'Server Belum Terhubung',
                            style: TextStyle(
                              fontSize: 12,
                              color: isOnline ? Colors.green.shade800 : Colors.orange.shade800,
                            ),
                          ),
                          backgroundColor: isOnline ? Colors.green.shade50 : Colors.orange.shade50,
                          onPressed: _openServerConfigDialog,
                        );
                      },
                    ),
                  ),
                  const SizedBox(height: 8),
                  Center(
                    child: OutlinedButton.icon(
                      onPressed: _isDiscovering ? null : _handleAutoDiscover,
                      icon: _isDiscovering
                          ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2))
                          : const Icon(Icons.wifi_find_rounded, size: 18),
                      label: Text(_isDiscovering ? 'Memindai Jaringan Wi-Fi...' : '🔍 Cari Server Otomatis di Wi-Fi'),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                        foregroundColor: Colors.indigo.shade700,
                        side: BorderSide(color: Colors.indigo.shade200),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Error Banner
                  if (_errorMessage != null)
                    Container(
                      padding: const EdgeInsets.all(12),
                      margin: const EdgeInsets.only(bottom: 16),
                      decoration: BoxDecoration(
                        color: Colors.red.shade50,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.red.shade200),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.error_outline, color: Colors.red.shade700, size: 20),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              _errorMessage!,
                              style: TextStyle(color: Colors.red.shade900, fontSize: 13),
                            ),
                          ),
                        ],
                      ),
                    ),

                  // Username field
                  TextFormField(
                    controller: _usernameController,
                    decoration: InputDecoration(
                      labelText: 'Username / NIS',
                      hintText: 'Masukkan NIS atau username ujian',
                      prefixIcon: const Icon(Icons.person_outline),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    validator: (val) {
                      if (val == null || val.trim().isEmpty) {
                        return 'Username / NIS tidak boleh kosong';
                      }
                      return null;
                    },
                  ),

                  const SizedBox(height: 16),

                  // Password field
                  TextFormField(
                    controller: _passwordController,
                    obscureText: _obscurePassword,
                    decoration: InputDecoration(
                      labelText: 'Password',
                      hintText: 'Masukkan password Anda',
                      prefixIcon: const Icon(Icons.lock_outline),
                      suffixIcon: IconButton(
                        icon: Icon(
                          _obscurePassword ? Icons.visibility_off : Icons.visibility,
                        ),
                        onPressed: () {
                          setState(() {
                            _obscurePassword = !_obscurePassword;
                          });
                        },
                      ),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    validator: (val) {
                      if (val == null || val.isEmpty) {
                        return 'Password tidak boleh kosong';
                      }
                      return null;
                    },
                  ),

                  const SizedBox(height: 24),

                  // Login Button
                  ElevatedButton(
                    onPressed: _isLoading ? null : _handleLogin,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.indigo,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                      elevation: 2,
                    ),
                    child: _isLoading
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : const Text(
                            'Masuk',
                            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                          ),
                  ),

                  const SizedBox(height: 24),

                  // Bottom Settings Shortcut
                  TextButton.icon(
                    onPressed: _openServerConfigDialog,
                    icon: const Icon(Icons.settings, size: 16, color: Colors.black54),
                    label: const Text(
                      'Ubah Alamat IP Server CBT',
                      style: TextStyle(fontSize: 13, color: Colors.black54),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
