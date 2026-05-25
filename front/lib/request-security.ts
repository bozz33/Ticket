import { type NextRequest, NextResponse } from "next/server";

const configuredSiteUrl = process.env.NEXT_PUBLIC_SITE_URL?.replace(/\/$/, "") ?? "";
const WINDOW_MS = 60_000;
const MAX_RATE_BUCKETS = 5_000;
const mutationRateBuckets = new Map<string, number[]>();
const mutationMethods = new Set(["POST", "PUT", "PATCH", "DELETE"]);
let lastPruneAt = 0;

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

  if (!origin && !referer && process.env.NODE_ENV === "production") {
    return NextResponse.json({ error: "Origine non vérifiable." }, { status: 403 });
  }

  return null;
}

function getClientIp(request: NextRequest): string {
  const forwardedFor = request.headers.get("x-forwarded-for") ?? "";
  const realIp = request.headers.get("x-real-ip") ?? request.headers.get("cf-connecting-ip") ?? "";

  return forwardedFor.split(",")[0]?.trim() || realIp.trim() || "local";
}

function pruneRateBuckets(now: number): void {
  if (now - lastPruneAt < WINDOW_MS && mutationRateBuckets.size <= MAX_RATE_BUCKETS) {
    return;
  }

  const windowStart = now - WINDOW_MS;

  for (const [key, entries] of mutationRateBuckets.entries()) {
    const recentEntries = entries.filter((timestamp) => timestamp > windowStart);

    if (recentEntries.length > 0) {
      mutationRateBuckets.set(key, recentEntries);
    } else {
      mutationRateBuckets.delete(key);
    }
  }

  if (mutationRateBuckets.size > MAX_RATE_BUCKETS) {
    const overflow = mutationRateBuckets.size - MAX_RATE_BUCKETS;
    const oldestKeys = Array.from(mutationRateBuckets.entries())
      .sort(([, left], [, right]) => (left[0] ?? 0) - (right[0] ?? 0))
      .slice(0, overflow)
      .map(([key]) => key);

    for (const key of oldestKeys) {
      mutationRateBuckets.delete(key);
    }
  }

  lastPruneAt = now;
}

export function applyMutationRateLimit(request: NextRequest, key: string, maxRequests: number): NextResponse | null {
  const clientIp = getClientIp(request);
  const bucketKey = `${key}:${clientIp}`;
  const now = Date.now();
  const windowStart = now - WINDOW_MS;

  pruneRateBuckets(now);

  const currentEntries = mutationRateBuckets.get(bucketKey) ?? [];
  const recentEntries = currentEntries.filter((timestamp) => timestamp > windowStart);

  if (recentEntries.length >= maxRequests) {
    mutationRateBuckets.set(bucketKey, recentEntries);
    const oldestEntry = recentEntries[0] ?? now;
    const retryAfterSeconds = Math.max(1, Math.ceil((oldestEntry + WINDOW_MS - now) / 1000));

    return NextResponse.json(
      { error: "Trop de tentatives. Réessayez plus tard." },
      {
        status: 429,
        headers: {
          "Retry-After": String(retryAfterSeconds),
        },
      },
    );
  }

  recentEntries.push(now);
  mutationRateBuckets.set(bucketKey, recentEntries);

  return null;
}
