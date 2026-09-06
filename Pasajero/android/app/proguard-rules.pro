# Preserve runtime metadata used by Flutter plugins and JSON serializers.
-keepattributes Signature,InnerClasses,EnclosingMethod
-keepattributes RuntimeVisibleAnnotations,RuntimeInvisibleAnnotations,AnnotationDefault

# Flutter registers plugins and callbacks through generated entry points.
-keep class io.flutter.plugins.** { *; }
-keep class io.flutter.embedding.** { *; }
-keep class * extends io.flutter.embedding.engine.plugins.FlutterPlugin { *; }

# Keep Firebase components instantiated from AndroidManifest metadata.
-keep class com.google.firebase.components.ComponentRegistrar { *; }
-keep class * implements com.google.firebase.components.ComponentRegistrar { *; }
-keep class * extends com.google.firebase.messaging.FirebaseMessagingService { *; }

# Keep native method names and their declaring classes for JNI bindings.
-keepclasseswithmembernames,includedescriptorclasses class * {
    native <methods>;
}

# Flutter includes optional Play Feature Delivery hooks even when this app does
# not declare deferred components.
-dontwarn com.google.android.play.core.splitcompat.SplitCompatApplication
-dontwarn com.google.android.play.core.splitinstall.**
-dontwarn com.google.android.play.core.tasks.**
