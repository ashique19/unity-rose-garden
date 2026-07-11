/**
 * Registers FCM/device push tokens when running inside the Capacitor Android WebView.
 * Loaded from the Laravel layout; uses session cookies + CSRF meta tag.
 *
 * Safe without google-services.json: failures are logged and never thrown to the page.
 * Registration is delayed until after the WebView finishes loading.
 */
(function () {
  var Cap = window.Capacitor;
  if (!Cap || !Cap.isNativePlatform || !Cap.isNativePlatform()) {
    return;
  }

  var Plugins = Cap.Plugins || {};
  var PushNotifications = Plugins.PushNotifications;
  if (!PushNotifications) {
    console.warn('[URG] PushNotifications plugin unavailable');
    return;
  }

  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  function postJson(url, body) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify(body),
    });
  }

  function registerToken(token) {
    if (!token) {
      return;
    }
    postJson('/device-tokens', {
      token: token,
      platform: Cap.getPlatform ? Cap.getPlatform() : 'android',
      device_name: navigator.userAgent.slice(0, 120),
    }).catch(function (err) {
      console.warn('[URG] Failed to register device token', err);
    });
  }

  async function initPush() {
    try {
      var perm = await PushNotifications.requestPermissions();
      if (!perm || perm.receive !== 'granted') {
        console.info('[URG] Push permission not granted');
        return;
      }

      await PushNotifications.addListener('registration', function (ev) {
        try {
          registerToken(ev && ev.value);
        } catch (err) {
          console.warn('[URG] Token handler failed', err);
        }
      });

      await PushNotifications.addListener('registrationError', function (err) {
        console.warn('[URG] Push registration error', err);
      });

      await PushNotifications.addListener('pushNotificationReceived', function (notification) {
        console.info('[URG] Push received', notification);
      });

      await PushNotifications.addListener('pushNotificationActionPerformed', function (notification) {
        console.info('[URG] Push action', notification);
      });

      // register() calls FirebaseMessaging; rejects if Firebase is not configured.
      await PushNotifications.register();
    } catch (e) {
      console.warn('[URG] Push init failed (app continues without FCM)', e);
    }
  }

  function schedulePushInit() {
    // Wait until after first paint / Capacitor bridge settle so native startup is done.
    var run = function () {
      setTimeout(function () {
        initPush();
      }, 750);
    };
    if (document.readyState === 'complete') {
      run();
    } else {
      window.addEventListener('load', run, { once: true });
    }
  }

  schedulePushInit();
})();
