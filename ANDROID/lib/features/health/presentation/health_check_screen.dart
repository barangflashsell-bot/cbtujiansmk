import 'package:flutter/material.dart';

import '../../../core/config/api_config.dart';
import '../../../core/connection/connection_state.dart';
import '../data/health_model.dart';
import '../data/health_repository.dart';

class HealthCheckScreen extends StatefulWidget {
  final HealthRepository? repository;

  const HealthCheckScreen({super.key, this.repository});

  @override
  State<HealthCheckScreen> createState() => _HealthCheckScreenState();
}

class _HealthCheckScreenState extends State<HealthCheckScreen> {
  late final HealthRepository _repository;
  late final TextEditingController _urlController;
  HealthResponse? _healthData;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _repository = widget.repository ?? HealthRepository();
    _urlController = TextEditingController(text: ApiConfig.baseUrl);
    // Initial check on launch
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _performHealthCheck();
    });
  }

  @override
  void dispose() {
    _urlController.dispose();
    super.dispose();
  }

  Future<void> _performHealthCheck() async {
    final inputUrl = _urlController.text.trim();
    if (!ApiConfig.isValidUrl(inputUrl)) {
      setState(() {
        _errorMessage = 'Format URL tidak valid (contoh: http://192.168.1.100:8000)';
      });
      _repository.connectionManager.setError(inputUrl, 'Format URL tidak valid');
      return;
    }

    ApiConfig.setBaseUrl(inputUrl);

    setState(() {
      _errorMessage = null;
    });

    try {
      final response = await _repository.checkHealth();
      if (mounted) {
        setState(() {
          _healthData = response;
          _errorMessage = null;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _healthData = null;
          _errorMessage = e.toString();
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('CBT Server Diagnostics'),
        elevation: 0,
        backgroundColor: Colors.indigo,
        foregroundColor: Colors.white,
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Header Card
              Card(
                elevation: 2,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Konfigurasi Host CBT (LAN)',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 8),
                      const Text(
                        'Masukkan IP Address dan Port server CBT yang berada dalam jaringan Wi-Fi/LAN yang sama.',
                        style: TextStyle(fontSize: 13, color: Colors.black54),
                      ),
                      const SizedBox(height: 16),
                      TextField(
                        controller: _urlController,
                        decoration: InputDecoration(
                          labelText: 'Server Base URL',
                          hintText: 'http://192.168.1.100:8000',
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                          prefixIcon: const Icon(Icons.lan),
                          suffixIcon: IconButton(
                            icon: const Icon(Icons.refresh),
                            tooltip: 'Reset ke default',
                            onPressed: () {
                              ApiConfig.resetToDefault();
                              _urlController.text = ApiConfig.baseUrl;
                            },
                          ),
                        ),
                        keyboardType: TextInputType.url,
                      ),
                      const SizedBox(height: 12),
                      ValueListenableBuilder<ConnectionStateInfo>(
                        valueListenable: _repository.connectionManager,
                        builder: (context, state, _) {
                          final isChecking = state.isChecking;
                          return SizedBox(
                            width: double.infinity,
                            child: ElevatedButton.icon(
                              onPressed: isChecking ? null : _performHealthCheck,
                              icon: isChecking
                                  ? const SizedBox(
                                      width: 18,
                                      height: 18,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        color: Colors.white,
                                      ),
                                    )
                                  : const Icon(Icons.network_check),
                              label: Text(
                                isChecking
                                    ? 'Memeriksa Server...'
                                    : 'Periksa Koneksi Server',
                              ),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: Colors.indigo,
                                foregroundColor: Colors.white,
                                padding: const EdgeInsets.symmetric(vertical: 14),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(8),
                                ),
                              ),
                            ),
                          );
                        },
                      ),
                    ],
                  ),
                ),
              ),

              const SizedBox(height: 20),

              // Status Indicator Card
              ValueListenableBuilder<ConnectionStateInfo>(
                valueListenable: _repository.connectionManager,
                builder: (context, state, _) {
                  return _buildStatusCard(state);
                },
              ),

              const SizedBox(height: 20),

              // Payload Details Card (Visible when healthy)
              if (_healthData != null) _buildMetadataCard(_healthData!),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStatusCard(ConnectionStateInfo state) {
    Color statusColor;
    IconData statusIcon;
    String statusTitle;
    String statusSubtitle;

    switch (state.status) {
      case ServerConnectionStatus.online:
        statusColor = Colors.green;
        statusIcon = Icons.check_circle;
        statusTitle = 'ONLINE';
        statusSubtitle = state.message ?? 'Terhubung ke server CBT dengan sukses.';
        break;
      case ServerConnectionStatus.offline:
        statusColor = Colors.orange;
        statusIcon = Icons.cloud_off;
        statusTitle = 'OFFLINE';
        statusSubtitle = state.message ?? 'Server tidak dapat dijangkau di jaringan LAN.';
        break;
      case ServerConnectionStatus.error:
        statusColor = Colors.red;
        statusIcon = Icons.error_outline;
        statusTitle = 'ERROR';
        statusSubtitle = _errorMessage ?? state.message ?? 'Terjadi kesalahan komunikasi dengan server.';
        break;
      case ServerConnectionStatus.checking:
        statusColor = Colors.blue;
        statusIcon = Icons.sync;
        statusTitle = 'CHECKING';
        statusSubtitle = 'Menguji koneksi ke ${state.serverUrl ?? ApiConfig.baseUrl}...';
        break;
      case ServerConnectionStatus.unknown:
        statusColor = Colors.grey;
        statusIcon = Icons.help_outline;
        statusTitle = 'UNKNOWN';
        statusSubtitle = 'Status server belum diperiksa.';
        break;
    }

    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(12),
        side: BorderSide(color: statusColor.withAlpha(128), width: 1.5),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            Row(
              children: [
                CircleAvatar(
                  backgroundColor: statusColor.withAlpha(40),
                  child: Icon(statusIcon, color: statusColor),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Status: $statusTitle',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: statusColor,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        statusSubtitle,
                        style: const TextStyle(fontSize: 13, color: Colors.black87),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            if (state.lastChecked != null) ...[
              const Divider(height: 24),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Pemeriksaan Terakhir:',
                    style: TextStyle(fontSize: 12, color: Colors.black54),
                  ),
                  Text(
                    state.lastChecked!.toLocal().toString().split('.')[0],
                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w500),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildMetadataCard(HealthResponse data) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Row(
              children: [
                Icon(Icons.info_outline, size: 20, color: Colors.indigo),
                SizedBox(width: 8),
                Text(
                  'Detail Respon Server CBT',
                  style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                ),
              ],
            ),
            const Divider(height: 20),
            _buildMetaRow('API Version', data.apiVersion),
            const SizedBox(height: 8),
            _buildMetaRow('Health Status', data.status),
            const SizedBox(height: 8),
            _buildMetaRow('Server Time', data.timestamp),
            const SizedBox(height: 8),
            _buildMetaRow('Endpoint', ApiConfig.healthPath),
          ],
        ),
      ),
    );
  }

  Widget _buildMetaRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontSize: 13, color: Colors.black54)),
        Text(
          value,
          style: const TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: Colors.black87,
          ),
        ),
      ],
    );
  }
}
