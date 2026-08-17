import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart' show defaultTargetPlatform, kIsWeb, TargetPlatform;

class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    if (kIsWeb) {
      throw UnsupportedError(
        'DefaultFirebaseOptions have not been configured for web - '
        'you can reconfigure this by running the FlutterFire CLI again.',
      );
    }
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      case TargetPlatform.iOS:
        return ios;
      case TargetPlatform.windows:
        throw UnsupportedError(
          'DefaultFirebaseOptions have not been configured for windows - '
          'you can reconfigure this by running the FlutterFire CLI again.',
        );
      case TargetPlatform.linux:
        throw UnsupportedError(
          'DefaultFirebaseOptions have not been configured for linux - '
          'you can reconfigure this by running the FlutterFire CLI again.',
        );
      default:
        throw UnsupportedError(
          'DefaultFirebaseOptions are not supported for this platform.',
        );
    }
  }

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyDKRXWlmeYZFW0nYPWo5GctEW-6-G3F3ug',
    appId: '1:714316853778:android:7d6c7051f71ea3c5885ecb',
    messagingSenderId: '714316853778',
    projectId: 'services-c8c8d',
    databaseURL: 'https://services-c8c8d-default-rtdb.firebaseio.com',
    storageBucket: 'services-c8c8d.firebasestorage.app',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'AIzaSyCTsfY1CJN56_uFbUE_NGPPvLU-pLzcSvQ',
    appId: '1:714316853778:ios:e765ab7b1108edf3885ecb',
    messagingSenderId: '714316853778',
    projectId: 'services-c8c8d',
    databaseURL: 'https://services-c8c8d-default-rtdb.firebaseio.com',
    storageBucket: 'services-c8c8d.firebasestorage.app',
    androidClientId: '714316853778-huo0838a1j55m5nsvhtdps9qpedatovr.apps.googleusercontent.com',
    iosClientId: '714316853778-5og291i5so7bn16t5afda3aijr6qeg0m.apps.googleusercontent.com',
    iosBundleId: 'com.liztogo.repartidor',
  );

}