package id.cbt.cbt_client

import android.os.Bundle
import android.view.WindowManager
import io.flutter.embedding.android.FlutterActivity

class MainActivity : FlutterActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        // Security hardening: restrict screenshots, screen recording, and recent app thumbnails
        window.addFlags(WindowManager.LayoutParams.FLAG_SECURE)
    }
}
