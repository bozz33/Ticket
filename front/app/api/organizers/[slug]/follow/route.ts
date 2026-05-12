import { type NextRequest, NextResponse } from "next/server";

import { getAuthToken } from "@/lib/auth";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";

const apiBaseUrl =
  process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") ??
  (process.env.NODE_ENV === "development" ? "http://127.0.0.1:8000" : "");
const PUBLIC_FOLLOWERS_REVALIDATE = 120;

async function getPublicFollowers(slug: string): Promise<number | null> {
  if (!apiBaseUrl) {
    return null;
  }

  try {
    const response = await fetch(`${apiBaseUrl}/api/v1/public/tenants/${encodeURIComponent(slug)}/organization-profile`, {
      cache: "force-cache",
      next: { revalidate: PUBLIC_FOLLOWERS_REVALIDATE },
      headers: {
        Accept: "application/json",
      },
    });

    if (!response.ok) {
      return null;
    }

    const payload = (await response.json()) as {
      data?: {
        followers_count?: number | string | null;
        meta?: Record<string, unknown>;
      };
    };
    const rawValue = payload.data?.followers_count ?? payload.data?.meta?.followers;

    return typeof rawValue === "number"
      ? rawValue
      : typeof rawValue === "string" && rawValue.trim() !== "" && !Number.isNaN(Number(rawValue))
        ? Number(rawValue)
        : null;
  } catch {
    return null;
  }
}

async function proxyFollowStatus(token: string, slug: string) {
  if (!apiBaseUrl) {
    return null;
  }

  try {
    const response = await fetch(`${apiBaseUrl}/api/v1/tenants/${encodeURIComponent(slug)}/organization-profile/follow`, {
      cache: "no-store",
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
    });

    const payload = await response.json().catch(() => null);

    return { response, payload };
  } catch {
    return null;
  }
}

export async function GET(
  _request: NextRequest,
  context: { params: Promise<{ slug: string }> },
) {
  const { slug } = await context.params;
  const token = await getAuthToken();

  if (!token) {
    return NextResponse.json({
      authenticated: false,
      following: false,
      followers: await getPublicFollowers(slug),
    });
  }

  const proxied = await proxyFollowStatus(token, slug);

  if (!proxied || !proxied.response.ok) {
    return NextResponse.json({
      authenticated: false,
      following: false,
      followers: await getPublicFollowers(slug),
    });
  }

  return NextResponse.json({
    authenticated: true,
    following: Boolean(proxied.payload?.data?.following),
    followers: proxied.payload?.data?.followers ?? await getPublicFollowers(slug),
  });
}

async function mutateFollow(
  request: NextRequest,
  context: { params: Promise<{ slug: string }> },
  method: "POST" | "DELETE",
) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(
    request,
    method === "POST" ? "organizer-follow" : "organizer-unfollow",
    20,
  );

  if (rateLimitError) {
    return rateLimitError;
  }

  const token = await getAuthToken();

  if (!token) {
    return NextResponse.json({ error: "Non authentifié." }, { status: 401 });
  }

  const { slug } = await context.params;

  if (!apiBaseUrl) {
    return NextResponse.json({ error: "API backend indisponible." }, { status: 500 });
  }

  try {
    const response = await fetch(`${apiBaseUrl}/api/v1/tenants/${encodeURIComponent(slug)}/organization-profile/follow`, {
      method,
      cache: "no-store",
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
    });
    const payload = await response.json().catch(() => null);

    if (!response.ok) {
      return NextResponse.json({
        error: payload?.message ?? payload?.error ?? "Impossible de mettre à jour l'abonnement.",
      }, { status: response.status });
    }

    return NextResponse.json({
      authenticated: true,
      following: Boolean(payload?.data?.following),
      followers: payload?.data?.followers ?? await getPublicFollowers(slug),
      message: payload?.message,
    });
  } catch {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }
}

export async function POST(
  request: NextRequest,
  context: { params: Promise<{ slug: string }> },
) {
  return mutateFollow(request, context, "POST");
}

export async function DELETE(
  request: NextRequest,
  context: { params: Promise<{ slug: string }> },
) {
  return mutateFollow(request, context, "DELETE");
}
