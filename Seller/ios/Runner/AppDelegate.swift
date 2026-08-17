import Flutter
import UIKit
import GoogleMaps
import FirebaseCore
import AVFoundation

@main
@objc class AppDelegate: FlutterAppDelegate, FlutterImplicitEngineDelegate {
  override func application(
    _ application: UIApplication,
    didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?
  ) -> Bool {
    FirebaseApp.configure()
    GMSServices.provideAPIKey("AIzaSyD7QuD0d3lt1HGJG9d_-ZiivcHOo4mL6Kg")

    // Configurar la sesión de audio para haptics y evitar el crash -4809
    try? AVAudioSession.sharedInstance().setCategory(.ambient, mode: .default)
    try? AVAudioSession.sharedInstance().setActive(true)

    return super.application(application, didFinishLaunchingWithOptions: launchOptions)
  }

  func didInitializeImplicitFlutterEngine(_ engineBridge: FlutterImplicitEngineBridge) {
    GeneratedPluginRegistrant.register(with: engineBridge.pluginRegistry)
  }
}
