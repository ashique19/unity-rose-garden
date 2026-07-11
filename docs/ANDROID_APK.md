# Android APK (Capacitor + FCM)

Sideloadable Android app for **Unity Rose Garden**. The WebView loads your live Laravel site (`server.url`); native Capacitor plugins handle splash, status bar, and FCM push.

## Prerequisites

| Tool | Notes |
|------|--------|
| **Node.js 20+** and npm | Already used for Vite |
| **JDK 17** | Required by Android Gradle / Android Studio |
| **Android Studio** | Install SDK Platform 34+, build-tools, and an emulator or use a physical device |
| **PHP / Laravel** | Running so the WebView has something to load |

This repo includes a Capacitor Android project under `android/`. **Java and Android Studio may be missing on a given machine** â€” install them before building an APK.


## Build without Firebase (first debug APK)

`android/app/build.gradle` applies the Google Services plugin **only if** `android/app/google-services.json` exists. A debug APK builds without that file; **push notifications will not work** until you add the real Firebase JSON and service account (see section 1).

After `assembleDebug`, the APK is at:

- `android/app/build/outputs/apk/debug/app-debug.apk`
- Optionally copy to `dist/unity-rose-garden-debug.apk` (`dist/` is gitignored)

For a physical phone, serve Laravel on all interfaces:

```powershell
php artisan serve --host=0.0.0.0 --port=8000
```

Emulator alias: `http://10.0.2.2:8000`. Physical device: your PC LAN IPv4 (e.g. `http://192.168.x.x:8000`) via `CAPACITOR_SERVER_URL` before `npm run android:sync`.

## Architecture

1. `capacitor.config.json` â€” `appId` `com.unityrosegarden.app`, `webDir` `public/mobile`
2. `CAPACITOR_SERVER_URL` â€” applied by `scripts/write-capacitor-server-url.js` at **sync** time so the WebView opens Laravel
3. Offline shell `public/mobile/index.html` â€” only if you remove `server.url`
4. On the Laravel site, `public/js/capacitor-push.js` registers the FCM token and `POST`s it to `/device-tokens` (auth + CSRF)

## 1. Firebase project

1. Open [Firebase Console](https://console.firebase.google.com/) â†’ create/select a project
2. Add an **Android** app with package name **`com.unityrosegarden.app`**
3. Download **`google-services.json`** â†’ place at **`android/app/google-services.json`** (gitignored)
4. Enable **Cloud Messaging**
5. Project settings â†’ **Service accounts** â†’ Generate new private key  
   Save as **`storage/app/firebase-service-account.json`** (gitignored)
6. In `.env`:

```env
FCM_PROJECT_ID=your-firebase-project-id
FCM_SERVICE_ACCOUNT=storage/app/firebase-service-account.json
```

Without these, the app still runs; `php artisan push:test` and month-generate pushes no-op with a log line.

Placeholder instructions also live in `android-firebase/README.md`.

## 2. Set the server URL (required for a useful APK)

Set **before** every sync that should change the target host:

```powershell
# Android emulator â†’ Laravel on your PC (php artisan serve)
$env:CAPACITOR_SERVER_URL = "http://10.0.2.2:8000"

# Physical phone on same Wi-Fi â†’ your PC LAN IP
$env:CAPACITOR_SERVER_URL = "http://192.168.1.42:8000"

# Production HTTPS
$env:CAPACITOR_SERVER_URL = "https://your-production-host.example"
```

Then:

```powershell
npm run android:sync
# runs scripts/write-capacitor-server-url.js then npx cap sync android
```

Notes:

- `http://` enables cleartext in Capacitor config for local LAN testing
- Ensure Laravel `APP_URL` matches how you open the app (cookies / redirects)
- Firewall must allow inbound TCP 8000 (or your port) from the phone

## 3. Sync native project

```powershell
npm install
npm run android:sync
```

Open in Android Studio:

```powershell
npm run cap:open
```

## 4. Build and share the APK

In Android Studio:

1. Wait for Gradle sync
2. Confirm `android/app/google-services.json` is present (for push)
3. **Build â†’ Build Bundle(s) / APK(s) â†’ Build APK(s)**
4. Share the debug APK from `android/app/build/outputs/apk/debug/`

Or CLI (with SDK + JDK installed):

```powershell
cd android
.\gradlew.bat assembleDebug
```

Install on a device (USB debugging):

```powershell
adb install -r app\build\outputs\apk\debug\app-debug.apk
```

## 5. Push token registration

1. User opens the Capacitor app (WebView loads Laravel)
2. User logs in (session cookie)
3. If `window.Capacitor.isNativePlatform()`, layout loads `/js/capacitor-push.js`
4. Plugin requests notification permission â†’ `register()` â†’ FCM token
5. JS `POST /device-tokens` with CSRF from `<meta name="csrf-token">` and `credentials: 'same-origin'`
6. Rows land in `device_tokens` (`user_id`, unique `token`, `platform`, `device_name`)

Unregister: `DELETE /device-tokens` with JSON `{ "token": "..." }` (authenticated).

### Test send

```powershell
php artisan migrate
php artisan push:test {userId} --title="Hello" --body="Test from artisan"
```

After **Generate month**, admins with registered tokens get a push when FCM is configured.

## 6. npm scripts

| Script | Command |
|--------|---------|
| `npm run cap:sync` | Write server URL from env, then `npx cap sync` |
| `npm run android:sync` | Write server URL from env, then `npx cap sync android` |
| `npm run cap:open` | `npx cap open android` |

## 7. Cleartext / LAN HTTP

`AndroidManifest.xml` sets `android:usesCleartextTraffic="true"` so emulator/LAN `http://` URLs work during development. Prefer HTTPS in production and tighten cleartext if you only use HTTPS.

## 8. Security / what not to commit

- `android/app/google-services.json`
- `storage/app/firebase-service-account.json`
- `.env`
- `android/local.properties`, Gradle build caches (see `.gitignore`)

The `android/` project sources **are** committed so others can open them in Android Studio after adding their own `google-services.json`.