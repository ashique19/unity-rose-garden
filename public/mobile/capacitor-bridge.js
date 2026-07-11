/**
 * Minimal bridge for the offline Capacitor shell (before server.url loads Laravel).
 * Push registration primarily runs from /js/capacitor-push.js on the Laravel site.
 */
(function () {
  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  ready(function () {
    var Cap = window.Capacitor;
    if (!Cap) {
      return;
    }

    var App = Cap.Plugins && Cap.Plugins.App;
    var SplashScreen = Cap.Plugins && Cap.Plugins.SplashScreen;
    var StatusBar = Cap.Plugins && Cap.Plugins.StatusBar;

    if (SplashScreen && SplashScreen.hide) {
      SplashScreen.hide().catch(function () {});
    }

    if (StatusBar && StatusBar.setBackgroundColor) {
      StatusBar.setBackgroundColor({ color: '#1a3a2a' }).catch(function () {});
    }

    if (App && App.addListener) {
      App.addListener('backButton', function (ev) {
        if (ev && ev.canGoBack) {
          window.history.back();
        }
      });
    }
  });
})();
