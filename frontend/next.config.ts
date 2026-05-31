import type { NextConfig } from "next";

const safeOrigin = (value: string | undefined | null): string | null => {
  if (!value) {
    return null;
  }

  try {
    return new URL(value).origin;
  } catch {
    return null;
  }
};

const backendOrigin = safeOrigin(process.env.NEXT_PUBLIC_API_URL);
const sentryOrigin = safeOrigin(process.env.NEXT_PUBLIC_SENTRY_DSN);
const posthogOrigin = safeOrigin(process.env.NEXT_PUBLIC_POSTHOG_HOST ?? "https://us.i.posthog.com");

const connectSrcOrigins = [backendOrigin, sentryOrigin, posthogOrigin].filter(
  (origin): origin is string => Boolean(origin),
);

const scriptSrc = process.env.NODE_ENV === "production"
  ? "script-src 'self' 'unsafe-inline'"
  : "script-src 'self' 'unsafe-inline' 'unsafe-eval'";

const csp = [
  "default-src 'self'",
  "base-uri 'self'",
  "frame-ancestors 'none'",
  "form-action 'self'",
  "object-src 'none'",
  scriptSrc,
  "style-src 'self' 'unsafe-inline'",
  `img-src 'self' data: blob: https:${backendOrigin ? ` ${backendOrigin}` : ""}`,
  "font-src 'self' data:",
  `connect-src 'self'${connectSrcOrigins.length ? ` ${connectSrcOrigins.join(" ")}` : ""}`,
].join("; ");

const nextConfig: NextConfig = {
  output: "standalone", // required for Docker multi-stage build
  async headers() {
    return [
      {
        source: "/:path*",
        headers: [
          {
            key: "Content-Security-Policy",
            value: csp,
          },
          {
            key: "Referrer-Policy",
            value: "strict-origin-when-cross-origin",
          },
          {
            key: "X-Content-Type-Options",
            value: "nosniff",
          },
          {
            key: "X-Frame-Options",
            value: "DENY",
          },
        ],
      },
    ];
  },
  images: {
    remotePatterns: [
      {
        protocol: "https",
        hostname: "images.unsplash.com",
      },
      {
        protocol: "https",
        hostname: "**",
      },
    ],
  },
  // Silence noisy hydration warnings in dev from browser extensions
  reactStrictMode: true,
};

export default nextConfig;
