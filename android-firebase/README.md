# Firebase Android config (do not commit secrets)

1. Create a Firebase project at https://console.firebase.google.com/
2. Add an Android app with package name: `com.unityrosegarden.app`
3. Download `google-services.json`
4. Place it at: `android/app/google-services.json`
5. For server-side sends, create a service account with Firebase Cloud Messaging Admin,
   download the JSON key, and save as `storage/app/firebase-service-account.json`
   (gitignored). Set `FCM_PROJECT_ID` and `FCM_SERVICE_ACCOUNT` in `.env`.

See `docs/ANDROID_APK.md` for full steps.
