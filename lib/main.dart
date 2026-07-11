import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:vibration/vibration.dart';
import 'package:audioplayers/audioplayers.dart';

final FlutterLocalNotificationsPlugin flutterLocalNotificationsPlugin =
    FlutterLocalNotificationsPlugin();

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize notifications
  const AndroidInitializationSettings initializationSettingsAndroid =
      AndroidInitializationSettings('@mipmap/ic_launcher');
  const InitializationSettings initializationSettings =
      InitializationSettings(android: initializationSettingsAndroid);
  await flutterLocalNotificationsPlugin.initialize(initializationSettings);

  // Request all permissions
  await _requestPermissions();

  SystemChrome.setEnabledSystemUIMode(SystemUiMode.edgeToEdge);
  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
    statusBarColor: Colors.transparent,
    statusBarIconBrightness: Brightness.dark,
  ));

  runApp(const MyApp());
}

Future<void> _requestPermissions() async {
  await Permission.notification.request();
  await Permission.storage.request();
  await Permission.camera.request();
  await Permission.microphone.request();
  await Permission.location.request();
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'CaseForge',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        primarySwatch: Colors.green,
        useMaterial3: true,
        visualDensity: VisualDensity.adaptivePlatformDensity,
      ),
      home: const SplashScreen(),
    );
  }
}

// ====== SPLASH SCREEN ======
class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _fadeAnimation;
  late Animation<double> _scaleAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      duration: const Duration(milliseconds: 1500),
      vsync: this,
    );
    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(_controller);
    _scaleAnimation = Tween<double>(begin: 0.5, end: 1.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeOutBack),
    );
    _controller.forward();

    Timer(const Duration(seconds: 3), () {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const WebViewScreen()),
      );
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Center(
        child: FadeTransition(
          opacity: _fadeAnimation,
          child: ScaleTransition(
            scale: _scaleAnimation,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  width: 120,
                  height: 120,
                  decoration: BoxDecoration(
                    color: Colors.green.shade50,
                    borderRadius: BorderRadius.circular(30),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.green.withOpacity(0.3),
                        blurRadius: 20,
                        spreadRadius: 5,
                      ),
                    ],
                  ),
                  child: const Icon(
                    Icons.auto_fix_high,
                    size: 60,
                    color: Colors.green,
                  ),
                ),
                const SizedBox(height: 30),
                const Text(
                  'CaseForge',
                  style: TextStyle(
                    fontSize: 32,
                    fontWeight: FontWeight.bold,
                    color: Colors.green,
                    letterSpacing: 1.5,
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  'Email Case Studio',
                  style: TextStyle(
                    fontSize: 16,
                    color: Colors.grey.shade600,
                    letterSpacing: 2,
                  ),
                ),
                const SizedBox(height: 50),
                SizedBox(
                  width: 200,
                  child: LinearProgressIndicator(
                    backgroundColor: Colors.green.shade100,
                    valueColor: AlwaysStoppedAnimation<Color>(Colors.green),
                    minHeight: 4,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

// ====== WEBVIEW SCREEN ======
class WebViewScreen extends StatefulWidget {
  const WebViewScreen({super.key});

  @override
  State<WebViewScreen> createState() => _WebViewScreenState();
}

class _WebViewScreenState extends State<WebViewScreen> {
  late final WebViewController _controller;
  bool isLoading = true;
  double progress = 0;
  DateTime? _lastBack;
  final AudioPlayer _audioPlayer = AudioPlayer();

  @override
  void initState() {
    super.initState();
    _initWebView();
  }

  void _initWebView() {
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(const Color(0xFFFFFFFF))
      ..enableZoom(true)
      ..setNavigationDelegate(
        NavigationDelegate(
          onProgress: (int progress) {
            setState(() {
              this.progress = progress / 100;
            });
          },
          onPageStarted: (String url) {
            setState(() {
              isLoading = true;
              progress = 0;
            });
          },
          onPageFinished: (String url) async {
            setState(() {
              isLoading = false;
              progress = 1;
            });
            // Haptic feedback on page load
            HapticFeedback.lightImpact();
            // Vibrate
            if (await Vibration.hasVibrator() ?? false) {
              Vibration.vibrate(duration: 50);
            }
          },
          onNavigationRequest: (NavigationRequest request) {
            final url = request.url;
            final externalSchemes = [
              'whatsapp://', 'https://wa.me/', 'https://api.whatsapp.com/',
              'fb://', 'https://www.facebook.com/', 'https://m.facebook.com/',
              'mailto:', 'tel:', 'https://t.me/', 'tg://',
              'instagram://', 'https://www.instagram.com/',
              'twitter://', 'https://twitter.com/', 'https://x.com/',
              'vnd.youtube://', 'https://www.youtube.com/', 'https://youtu.be/',
              'market://', 'https://play.google.com/',
              'https://www.linkedin.com/', 'linkedin://',
              'https://www.tiktok.com/', 'tiktok://',
              'https://www.reddit.com/', 'reddit://',
              'https://telegram.org/', 'https://discord.com/', 'discord://',
            ];
            for (final scheme in externalSchemes) {
              if (url.startsWith(scheme)) {
                _launchExternal(url);
                return NavigationDecision.prevent;
              }
            }
            return NavigationDecision.navigate;
          },
          onWebResourceError: (WebResourceError error) {
            debugPrint('Error: ${error.description}');
          },
        ),
      )
      ..loadRequest(Uri.parse('https://nayon718.github.io/Temp/'));
  }

  Future<void> _launchExternal(String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
      HapticFeedback.mediumImpact();
    }
  }

  Future<bool> _onWillPop() async {
    if (await _controller.canGoBack()) {
      _controller.goBack();
      HapticFeedback.lightImpact();
      return false;
    }
    final now = DateTime.now();
    if (_lastBack == null || now.difference(_lastBack!) > const Duration(seconds: 2)) {
      _lastBack = now;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('আবার Back চাপুন অ্যাপ বন্ধ করতে', textAlign: TextAlign.center),
          duration: Duration(seconds: 2),
          backgroundColor: Colors.black87,
          behavior: SnackBarBehavior.floating,
        ),
      );
      return false;
    }
    return true;
  }

  Future<void> _showNotification(String title, String body) async {
    const AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
      'caseforge_channel',
      'CaseForge Notifications',
      channelDescription: 'App notifications',
      importance: Importance.high,
      priority: Priority.high,
      showWhen: true,
      enableVibration: true,
      playSound: true,
    );
    const NotificationDetails details = NotificationDetails(android: androidDetails);
    await flutterLocalNotificationsPlugin.show(
      0,
      title,
      body,
      details,
    );
  }

  @override
  Widget build(BuildContext context) {
    return WillPopScope(
      onWillPop: _onWillPop,
      child: Scaffold(
        body: RefreshIndicator(
          onRefresh: () async {
            HapticFeedback.mediumImpact();
            await _controller.reload();
            await _showNotification('CaseForge', 'পেজ রিফ্রেশ হয়েছে!');
          },
          color: Colors.green,
          backgroundColor: Colors.white,
          child: Stack(
            children: [
              WebViewWidget(controller: _controller),

              // Progress bar
              if (isLoading && progress < 1)
                Positioned(
                  top: 0,
                  left: 0,
                  right: 0,
                  child: LinearProgressIndicator(
                    value: progress,
                    backgroundColor: Colors.transparent,
                    valueColor: AlwaysStoppedAnimation<Color>(Colors.green),
                    minHeight: 3,
                  ),
                ),

              // Loading indicator
              if (isLoading && progress == 0)
                const Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      CircularProgressIndicator(
                        color: Colors.green,
                        strokeWidth: 3,
                      ),
                      SizedBox(height: 16),
                      Text(
                        'লোড হচ্ছে...',
                        style: TextStyle(
                          color: Colors.green,
                          fontSize: 14,
                        ),
                      ),
                    ],
                  ),
                ),
            ],
          ),
        ),
        floatingActionButton: Column(
          mainAxisAlignment: MainAxisAlignment.end,
          children: [
            FloatingActionButton.small(
              heroTag: 'refresh',
              onPressed: () async {
                HapticFeedback.mediumImpact();
                await _controller.reload();
              },
              backgroundColor: Colors.green,
              child: const Icon(Icons.refresh, color: Colors.white),
            ),
            const SizedBox(height: 8),
            FloatingActionButton.small(
              heroTag: 'home',
              onPressed: () async {
                HapticFeedback.mediumImpact();
                await _controller.loadRequest(
                  Uri.parse('https://nayon718.github.io/Temp/'),
                );
              },
              backgroundColor: Colors.green.shade700,
              child: const Icon(Icons.home, color: Colors.white),
            ),
          ],
        ),
      ),
    );
  }
}
