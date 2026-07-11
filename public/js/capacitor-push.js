/**
 * Registers FCM/device push tokens when running inside the Capacitor Android WebView.
 * Loaded from the Laravel layout; uses session cookies + CSRF meta tag.
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
      if (perm.receive !== 'granted') {
        console.info('[URG] Push permission not granted');
        return;
      }

      await PushNotifications.register();

      PushNotifications.addListener('registration', function (ev) {
        registerToken(ev.value);
      });

      PushNotifications.addListener('registrationError', function (err) {
        console.warn('[URG] Push registration error', err);
      });

      PushNotifications.addListener('pushNotificationReceived', function (notification) {
        console.info('[URG] Push received', notification);
      });

      PushNotifications.addListener('pushNotificationActionPerformed', function (notification) {
        console.info('[URG] Push action', notification);
      });
    } catch (e) {
      console.warn('[URG] Push init failed', e);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPush);
  } else {
    initPush();
  }
})();
