import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.linkocial.app',
  appName: 'Linkocial',
  webDir: 'public',
  server: {
    url: 'https://linkocial.com',
    cleartext: true
  },
  appendUserAgent: 'LinkocialApp'
};

export default config;
