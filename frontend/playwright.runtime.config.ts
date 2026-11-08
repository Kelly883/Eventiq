import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: './tests',
  testMatch: 'dashboard-components.spec.ts',
  fullyParallel: true,
  reporter: 'line',
  use: {
    baseURL: 'http://localhost:3000',
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'firefox-runtime',
      use: {
        browserName: 'firefox',
        launchOptions: {
          executablePath: '/root/.cache/ms-playwright/firefox-1538/firefox/firefox',
        },
      },
    },
  ],
});
