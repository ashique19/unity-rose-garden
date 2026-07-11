/**
 * Capacitor PushNotifications.register()/unregister() throw IllegalStateException when
 * FirebaseInitProvider is disabled (no google-services.json). Capacitor's Bridge turns
 * that into FATAL EXCEPTION on the CapacitorPlugins thread — JS try/catch cannot stop it.
 *
 * Wrap those methods so missing Firebase rejects the plugin call instead of crashing.
 */
const fs = require('fs');
const path = require('path');

const target = path.join(
  __dirname,
  '..',
  'node_modules',
  '@capacitor',
  'push-notifications',
  'android',
  'src',
  'main',
  'java',
  'com',
  'capacitorjs',
  'plugins',
  'pushnotifications',
  'PushNotificationsPlugin.java'
);

if (!fs.existsSync(target)) {
  console.warn('[patch-push] PushNotificationsPlugin.java not found; skip');
  process.exit(0);
}

let src = fs.readFileSync(target, 'utf8');
let changed = false;

if (!src.includes('URG_SAFE_FIREBASE_REGISTER')) {
  const start = src.indexOf('    @PluginMethod\n    public void register(PluginCall call) {');
  const endMarker = '\n    @PluginMethod\n    public void unregister(PluginCall call)';
  const end = start >= 0 ? src.indexOf(endMarker, start) : -1;
  if (start < 0 || end < 0) {
    console.error('[patch-push] register() block not found');
    process.exit(1);
  }
  const replacement = `    @PluginMethod
    public void register(PluginCall call) {
        // URG_SAFE_FIREBASE_REGISTER
        try {
            FirebaseMessaging.getInstance().setAutoInitEnabled(true);
            FirebaseMessaging.getInstance()
                .getToken()
                .addOnCompleteListener((task) -> {
                    if (!task.isSuccessful()) {
                        Exception ex = task.getException();
                        String msg = ex != null ? ex.getLocalizedMessage() : "Push registration failed";
                        sendError(msg);
                        return;
                    }
                    sendToken(task.getResult());
                });
            call.resolve();
        } catch (IllegalStateException e) {
            String msg = e.getMessage() != null ? e.getMessage() : "Firebase not initialized";
            sendError(msg);
            call.reject(msg);
        }
    }
`;
  src = src.slice(0, start) + replacement + src.slice(end);
  changed = true;
}

if (!src.includes('URG_SAFE_FIREBASE_UNREGISTER')) {
  const start = src.indexOf('    @PluginMethod\n    public void unregister(PluginCall call) {');
  const endMarker = '\n    @PluginMethod\n    public void getDeliveredNotifications';
  let end = start >= 0 ? src.indexOf(endMarker, start) : -1;
  if (end < 0 && start >= 0) {
    // fallback: next method after unregister closing brace is fine via regex
    const m = src.slice(start).match(/^    @PluginMethod\n    public void unregister\(PluginCall call\) \{[\s\S]*?\n    \}\n/m);
    if (m) {
      const block = `    @PluginMethod
    public void unregister(PluginCall call) {
        // URG_SAFE_FIREBASE_UNREGISTER
        try {
            FirebaseMessaging.getInstance().setAutoInitEnabled(false);
            FirebaseMessaging.getInstance().deleteToken();
            call.resolve();
        } catch (IllegalStateException e) {
            call.reject(e.getMessage() != null ? e.getMessage() : "Firebase not initialized");
        }
    }
`;
      src = src.slice(0, start) + block + src.slice(start + m[0].length);
      changed = true;
    } else {
      console.warn('[patch-push] unregister() block not found; register patch only');
    }
  } else if (start >= 0 && end >= 0) {
    const block = `    @PluginMethod
    public void unregister(PluginCall call) {
        // URG_SAFE_FIREBASE_UNREGISTER
        try {
            FirebaseMessaging.getInstance().setAutoInitEnabled(false);
            FirebaseMessaging.getInstance().deleteToken();
            call.resolve();
        } catch (IllegalStateException e) {
            call.reject(e.getMessage() != null ? e.getMessage() : "Firebase not initialized");
        }
    }
`;
    src = src.slice(0, start) + block + src.slice(end);
    changed = true;
  }
}

if (changed) {
  fs.writeFileSync(target, src);
  console.log('[patch-push] patched', target);
} else {
  console.log('[patch-push] already applied');
}
