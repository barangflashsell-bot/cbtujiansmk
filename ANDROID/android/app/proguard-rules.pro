# Flutter Proguard Rules for Minimal APK Size
-keep class io.flutter.app.** { *; }
-keep class io.flutter.plugin.**  { *; }
-keep class io.flutter.util.**  { *; }
-keep class io.flutter.view.**  { *; }
-keep class io.flutter.**  { *; }
-keep class io.flutter.plugins.**  { *; }
-dontwarn io.flutter.embedding.**

# Keep data models for JSON deserialization
-keep class id.cbt.cbt_client.features.**.data.** { *; }
-keepattributes *Annotation*,EnclosingMethod,Signature

# Keep SQLite and SharedPreferences
-keep class com.tekartik.sqflite.** { *; }
-keep class io.flutter.plugins.sharedpreferences.** { *; }
