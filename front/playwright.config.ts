import { defineConfig, devices } from "@playwright/test";

const baseURL = process.env.PLAYWRIGHT_BASE_URL || "http://127.0.0.1:3000";
const useLocalChrome = process.platform === "win32" && !process.env.CI;

export default defineConfig({
  testDir: "./e2e",
  fullyParallel: false,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 2 : 0,
  reporter: process.env.CI ? [["list"], ["html", { open: "never" }]] : [["list"], ["html", { open: "never" }]],
  timeout: 60_000,
  expect: {
    timeout: 8_000,
  },
  use: {
    baseURL,
    channel: useLocalChrome ? "chrome" : undefined,
    trace: "on-first-retry",
    screenshot: "only-on-failure",
    video: "retain-on-failure",
    navigationTimeout: 60_000,
  },
  webServer: {
    command: "npm run dev -- --hostname 127.0.0.1",
    url: baseURL,
    reuseExistingServer: true,
    timeout: 120_000,
  },
  projects: [
    {
      name: "desktop",
      use: { ...devices["Desktop Chrome"] },
    },
    {
      name: "mobile",
      use: { ...devices["Pixel 7"] },
    },
  ],
});
