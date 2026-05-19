import { type NextRequest, NextResponse } from "next/server";

import { getAuthToken } from "@/lib/auth";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";
import { normalizeTenantSlug } from "@/lib/tenant";

import { apiBaseUrl, normalizedNumber } from "./common";

type EventLikeContext = { params: Promise<{ tenant: string; slug: string }> };

async function getPublicLikes(tenant: string, slug: string): Promise<number> {
  if (!apiBaseUrl) {
    return 0;
  }

  try {
    const response = await fetch(
      `${apiBaseUrl}/api/v1/public/tenants/${encodeURIComponent(tenant)}/content/evenements/${encodeURIComponent(slug)}`,
      {
        cache: "no-store",
        headers: {
          Accept: "application/json",
        },
      },
    );

    if (!response.ok) {
      return 0;
    }

    const payload = (await response.json()) as {
      data?: {
        likesCount?: number | string | null;
      };
    };

    return normalizedNumber(payload.data?.likesCount, 0) ?? 0;
  } catch {
    return 0;
  }
}

export async function getEventLikeStatus(_request: NextRequest, context: EventLikeContext) {
  const { tenant, slug } = await context.params;
  const tenantSlug = normalizeTenantSlug(tenant);

  if (!tenantSlug) {
    return NextResponse.json({ error: "Tenant invalide." }, { status: 422 });
  }

  const token = await getAuthToken();

  if (!token || !apiBaseUrl) {
    return NextResponse.json({
      authenticated: false,
      liked: false,
      likes: await getPublicLikes(tenantSlug, slug),
    });
  }

  try {
    const response = await fetch(
      `${apiBaseUrl}/api/v1/tenants/${encodeURIComponent(tenantSlug)}/events/${encodeURIComponent(slug)}/like`,
      {
        cache: "no-store",
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${token}`,
        },
      },
    );
    const payload = await response.json().catch(() => null);

    if (!response.ok) {
      return NextResponse.json({
        authenticated: false,
        liked: false,
        likes: await getPublicLikes(tenantSlug, slug),
      });
    }

    return NextResponse.json({
      authenticated: true,
      liked: Boolean(payload?.data?.liked),
      likes: payload?.data?.likes ?? (await getPublicLikes(tenantSlug, slug)),
    });
  } catch {
    return NextResponse.json({
      authenticated: false,
      liked: false,
      likes: await getPublicLikes(tenantSlug, slug),
    });
  }
}

export async function mutateEventLike(
  request: NextRequest,
  context: EventLikeContext,
  method: "POST" | "DELETE",
) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(request, method === "POST" ? "event-like" : "event-unlike", 30);

  if (rateLimitError) {
    return rateLimitError;
  }

  const token = await getAuthToken();

  if (!token) {
    return NextResponse.json({ error: "Non authentifié." }, { status: 401 });
  }

  const { tenant, slug } = await context.params;
  const tenantSlug = normalizeTenantSlug(tenant);

  if (!tenantSlug) {
    return NextResponse.json({ error: "Tenant invalide." }, { status: 422 });
  }

  if (!apiBaseUrl) {
    return NextResponse.json({ error: "API backend indisponible." }, { status: 500 });
  }

  try {
    const response = await fetch(
      `${apiBaseUrl}/api/v1/tenants/${encodeURIComponent(tenantSlug)}/events/${encodeURIComponent(slug)}/like`,
      {
        cache: "no-store",
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${token}`,
        },
        method,
      },
    );
    const payload = await response.json().catch(() => null);

    if (!response.ok) {
      return NextResponse.json(
        {
          error: payload?.message ?? payload?.error ?? "Impossible de mettre à jour vos j'aime.",
        },
        { status: response.status },
      );
    }

    return NextResponse.json({
      authenticated: true,
      liked: Boolean(payload?.data?.liked),
      likes: payload?.data?.likes ?? (await getPublicLikes(tenantSlug, slug)),
      message: payload?.message,
    });
  } catch {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }
}
