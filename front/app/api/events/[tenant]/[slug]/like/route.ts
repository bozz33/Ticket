import { type NextRequest, NextResponse } from "next/server";

import { getAuthToken } from "@/lib/auth";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";

const apiBaseUrl =
  process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") ??
  (process.env.NODE_ENV === "development" ? "http://127.0.0.1:8000" : "");

async function getPublicLikes(tenant: string, slug: string): Promise<number> {
  if (!apiBaseUrl) {
    return 0;
  }

  try {
    const response = await fetch(`${apiBaseUrl}/api/v1/public/tenants/${encodeURIComponent(tenant)}/content/evenements/${encodeURIComponent(slug)}`, {
      cache: "no-store",
      headers: {
        Accept: "application/json",
      },
    });

    if (!response.ok) {
      return 0;
    }

    const payload = (await response.json()) as {
      data?: {
        likesCount?: number | string | null;
      };
    };
    const rawValue = payload.data?.likesCount;

    return typeof rawValue === "number"
      ? rawValue
      : typeof rawValue === "string" && rawValue.trim() !== "" && !Number.isNaN(Number(rawValue))
        ? Number(rawValue)
        : 0;
  } catch {
    return 0;
  }
}

export async function GET(
  _request: NextRequest,
  context: { params: Promise<{ tenant: string; slug: string }> },
) {
  const { tenant, slug } = await context.params;
  const token = await getAuthToken();

  if (!token || !apiBaseUrl) {
    return NextResponse.json({
      authenticated: false,
      liked: false,
      likes: await getPublicLikes(tenant, slug),
    });
  }

  try {
    const response = await fetch(`${apiBaseUrl}/api/v1/tenants/${encodeURIComponent(tenant)}/events/${encodeURIComponent(slug)}/like`, {
      cache: "no-store",
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
    });
    const payload = await response.json().catch(() => null);

    if (!response.ok) {
      return NextResponse.json({
        authenticated: false,
        liked: false,
        likes: await getPublicLikes(tenant, slug),
      });
    }

    return NextResponse.json({
      authenticated: true,
      liked: Boolean(payload?.data?.liked),
      likes: payload?.data?.likes ?? await getPublicLikes(tenant, slug),
    });
  } catch {
    return NextResponse.json({
      authenticated: false,
      liked: false,
      likes: await getPublicLikes(tenant, slug),
    });
  }
}

async function mutateLike(
  request: NextRequest,
  context: { params: Promise<{ tenant: string; slug: string }> },
  method: "POST" | "DELETE",
) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(
    request,
    method === "POST" ? "event-like" : "event-unlike",
    30,
  );

  if (rateLimitError) {
    return rateLimitError;
  }

  const token = await getAuthToken();

  if (!token) {
    return NextResponse.json({ error: "Non authentifié." }, { status: 401 });
  }

  const { tenant, slug } = await context.params;

  if (!apiBaseUrl) {
    return NextResponse.json({ error: "API backend indisponible." }, { status: 500 });
  }

  try {
    const response = await fetch(`${apiBaseUrl}/api/v1/tenants/${encodeURIComponent(tenant)}/events/${encodeURIComponent(slug)}/like`, {
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
        error: payload?.message ?? payload?.error ?? "Impossible de mettre à jour vos j'aime.",
      }, { status: response.status });
    }

    return NextResponse.json({
      authenticated: true,
      liked: Boolean(payload?.data?.liked),
      likes: payload?.data?.likes ?? await getPublicLikes(tenant, slug),
      message: payload?.message,
    });
  } catch {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }
}

export async function POST(
  request: NextRequest,
  context: { params: Promise<{ tenant: string; slug: string }> },
) {
  return mutateLike(request, context, "POST");
}

export async function DELETE(
  request: NextRequest,
  context: { params: Promise<{ tenant: string; slug: string }> },
) {
  return mutateLike(request, context, "DELETE");
}
