import type { CapacitorConfig } from '@capacitor/cli';
import { KeyboardResize } from '@capacitor/keyboard';

const config: CapacitorConfig = {
  appId: 'com.dts.app',
  appName: 'dts',
  webDir: 'dist',
  plugins: {
    Keyboard: {
      resize: KeyboardResize.Body, // or 'native' if you want full viewport resize
      // style: 'DARK', // optional, for keyboard theme
      resizeOnFullScreen: true // Android fix
    }
  }
};

export default config;
