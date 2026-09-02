# Keep only metadata commonly consumed through reflection. Flutter plugins and
# Firebase provide their own consumer rules; broad package-level keep rules here
# would prevent R8 from optimizing and lower Google Play's obfuscation score.
-keepattributes RuntimeVisibleAnnotations,RuntimeInvisibleAnnotations,AnnotationDefault,Signature,InnerClasses,EnclosingMethod

# Optional platform integrations are selected by the plugins at runtime.
-dontwarn javax.annotation.**
-dontwarn org.conscrypt.**
