package com.unityrosegarden.app;

import android.os.Bundle;
import androidx.core.splashscreen.SplashScreen;
import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        // Required when activity theme is Theme.SplashScreen (Android 12+).
        // Must run before super.onCreate(); safe if the theme is not a splash theme.
        try {
            SplashScreen.installSplashScreen(this);
        } catch (Exception ignored) {
            // Fall back to Capacitor SplashScreen plugin behavior.
        }
        super.onCreate(savedInstanceState);
    }
}
