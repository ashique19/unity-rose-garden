/**
 * Writes capacitor.config.json server.url from CAPACITOR_SERVER_URL (or keeps existing / default).
 * Usage: node scripts/write-capacitor-server-url.js && npx cap sync android
 */
import fs from 'fs';

const path = 'capacitor.config.json';
const existing = fs.existsSync(path)
  ? JSON.parse(fs.readFileSync(path, 'utf8'))
  : {
      appId: 'com.unityrosegarden.app',
      appName: 'Unity Rose Garden',
      webDir: 'public/mobile',
      plugins: {
        SplashScreen: {
          launchShowDuration: 2000,
          backgroundColor: '#1a3a2a',
          showSpinner: false,
        },
        PushNotifications: {
          presentationOptions: ['badge', 'sound', 'alert'],
        },
      },
    };

const fromEnv = (process.env.CAPACITOR_SERVER_URL || '').trim();
const serverUrl = fromEnv || existing.server?.url || 'http://10.0.2.2:8000';

existing.server = {
  url: serverUrl,
  cleartext: serverUrl.startsWith('http://'),
};

fs.writeFileSync(path, JSON.stringify(existing, null, 2) + '\n');
console.log('Capacitor server.url =', serverUrl);
