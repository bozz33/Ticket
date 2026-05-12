import { type NextRequest, NextResponse } from "next/server";

const configuredSiteUrl = process.env.NEXT_PUBLIC_SITE_URL?.replace(/\/$/, "") ?? "";
const WINDOW_MS = 60_000;
const mutationRateBuckets = new Map<string, number[]>();
const mutationMethods = new Set(["POST", "PUT", "PATCH", "DELETE"]);

export function isMutationMethod(method: string): boolean {
  return mutationMethods.has(method.toUpperCase());
}

export function validateMutationOrigin(request: NextRequest): NextResponse | null {
  const localPeerOrigin = request.nextUrl.hostname === "localhost"
    ? `${request.nextUrl.protocol}//127.0.0.1${request.nextUrl.port ? `:${request.nextUrl.port}` : ""}`
    : request.nextUrl.hostname === "127.0.0.1"
      ? `${request.nextUrl.protocol}//localhost${request.nextUrl.port ? `:${request.nextUrl.port}` : ""}`
      : "";
  const trustedOrigins = new Set(
    [request.nextUrl.origin, localPeerOrigin, configuredSiteUrl]
      .map((value) => value.trim())
      .filter((value) => value.length > 0),
  );

  const origin = request.headers.get("origin");

  if (origin && !trustedOrigins.has(origin)) {
    return NextResponse.json({ error: "Origine non autorisée." }, { status: 403 });
  }

  const referer = request.headers.get("referer");

  if (!origin && referer) {
    try {
      const refererOrigin = new URL(referer).origin;

      if (!trustedOrigins.has(refererOrigin)) {
        return NextResponse.json({ error: "Origine non autorisée." }, { status: 403 });
      }
    } catch {
      return NextResponse.json({ error: "Origine non autorisée." }, { status: 403 });
    }
  }

  return null;
}

export function applyMutationRateLimit(request: NextRequest, key: string, maxRequests: number): NextResponse | null {
  const forwardedFor = request.headers.get("x-forwarded-for") ?? "";
  const clientIp = forwardedFor.split(",")[0]?.trim() || "local";
  const bucketKey = `${key}:${clientIp}`;
  const now = Date.now();
  const windowStart = now - WINDOW_MS;
  const currentEntries = mutationRateBuckets.get(bucketKey) ?? [];
  const recentEntries = currentEntries.filter((timestamp) => timestamp > windowStart);

  if (recentEntries.length >= maxRequests) {
    mutationRateBuckets.set(bucketKey, recentEntries);
    return NextResponse.json({ error: "Trop de tentatives. Réessayez plus tard." }, { status: 429 });
  }

  recentEntries.push(now);
  mutationRateBuckets.set(bucketKey, recentEntries);

  return null;
}
