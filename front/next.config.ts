import type { NextConfig } from "next";

const isProduction = process.env.NODE_ENV === "production";

function getOrigin(value: string | undefined): string | null {
  if (!value) {
    return null;
  }

  try {
    return new URL(value).origin;
  } catch {
    return null;
  }
}

function unique(values: Array<string | null | undefined>): string[] {
  return Array.from(new Set(values.filter((value): value is string => Boolean(value))));
}

function getRemoteImagePatterns(origins: string[]) {
  return origins.flatMap((origin) => {
    try {
      const url = new URL(origin);

      if (url.protocol !== "http:" && url.protocol !== "https:") {
        return [];
      }

      return [{
        protocol: url.protocol.replace(":", "") as "http" | "https",
        hostname: url.hostname,
        port: url.port,
        pathname: "/**",
      }];
    } catch {
      return [];
    }
  });
}

const apiOrigin = getOrigin(process.env.NEXT_PUBLIC_API_BASE_URL);
const siteOrigin = getOrigin(process.env.NEXT_PUBLIC_SITE_URL);
const disableBuildWorkers = process.env.NEXT_DISABLE_BUILD_WORKERS === "1";
const skipBuildTypecheck = process.env.NEXT_SKIP_BUILD_TYPECHECK === "1";
const imageRemotePatterns = getRemoteImagePatterns(unique([
  apiOrigin,
  siteOrigin,
  process.env.NODE_ENV === "development" ? "http://127.0.0.1:8000" : null,
]));
const connectSources = isProduction
  ? unique(["'self'", apiOrigin, siteOrigin, "https://api.paystack.co"])
  : ["'self'", "http:", "https:", "ws:", "wss:"];
const scriptSources = isProduction
  ? ["'self'", "'unsafe-inline'"]
  : ["'self'", "'unsafe-inline'", "'unsafe-eval'"];
const contentSecurityPolicy = [
  "default-src 'self'",
  "base-uri 'self'",
  "frame-ancestors 'self'",
  "form-action 'self'",
  "object-src 'none'",
  "img-src 'self' data: blob: https:",
  "font-src 'self' data:",
  "style-src 'self' 'unsafe-inline'",
  `script-src ${scriptSources.join(" ")}`,
  `connect-src ${connectSources.join(" ")}`,
  "frame-src 'self' https://checkout.paystack.com",
  ...(isProduction ? ["upgrade-insecure-requests"] : []),
].join("; ");

const securityHeaders = [
  { key: "X-Content-Type-Options", value: "nosniff" },
  { key: "X-Frame-Options", value: "SAMEORIGIN" },
  { key: "Content-Security-Policy", value: contentSecurityPolicy },
  { key: "Referrer-Policy", value: "strict-origin-when-cross-origin" },
  { key: "X-DNS-Prefetch-Control", value: "off" },
  { key: "X-Permitted-Cross-Domain-Policies", value: "none" },
  { key: "Permissions-Policy", value: "camera=(), microphone=(), geolocation=()" },
  { key: "Cross-Origin-Opener-Policy", value: "same-origin" },
  { key: "Cross-Origin-Resource-Policy", value: "same-origin" },
  { key: "Cross-Origin-Embedder-Policy", value: "unsafe-none" },
  ...(isProduction
    ? [{ key: "Strict-Transport-Security", value: "max-age=31536000; includeSubDomains; preload" }]
    : []),
];

const nextConfig: NextConfig = {
  reactStrictMode: true,
  distDir: process.env.NEXT_DIST_DIR || ".next",
  typescript: {
    ignoreBuildErrors: skipBuildTypecheck,
  },
  experimental: {
    cpus: disableBuildWorkers ? 1 : undefined,
    webpackBuildWorker: disableBuildWorkers ? false : undefined,
    workerThreads: false,
  },
  images: {
    remotePatterns: imageRemotePatterns,
  },
  webpack(config) {
    if (disableBuildWorkers) {
      config.cache = false;
    }

    return config;
  },
  async headers() {
    return [
      {
        source: "/:path*",
        headers: securityHeaders,
      },
    ];
  },
};

export default nextConfig;
