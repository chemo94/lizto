class Environment {
  // ATTENTION Please update your desired data.
  static const String appName = 'Lizto Repartidor';
  static const String version = '2.0.0';

  // Ride and Bids
  static const int bidAcceptSecond = 30; //Bid ACCEPT second
  static const int driverLocationUpdateAfterNmetersOrMovements = 50; //Driver location update after n meters or movements

  //Language
  // Default display name for the app's language (used in UI language selectors)
  static String defaultLanguageName = "Español";

  // Default language code (ISO 639-1) used by the app at startup
  static String defaultLanguageCode = "es";

  // Default country code (ISO 3166-1 alpha-2) used for locale-specific formatting
  static const String defaultCountryCode = 'PE';

  //MAP CONFIG
  static const bool addressPickerFromGoogleMapApi = true; //If true, use Google Map API for formate address picker from lat , long, else use free service reverse geocode
  static const String mapKey = "AIzaSyD7QuD0d3lt1HGJG9d_-ZiivcHOo4mL6Kg"; // Enter Your Map Api Key
  static const double mapDefaultZoom = 20;
  static const String devToken = "\$2y\$12\$mEVBW3QASB5HMBv8igls3ejh6zw2A0Xb480HWAmYq6BY9xEifyBjG"; //Do not change this token
}
