import { type NextRequest, NextResponse } from "next/server";

import { getAuthToken } from "@/lib/auth";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";
import { normalizeTenantSlug } from "@/lib/tenant";

import { apiBaseUrl, normalizedNumber } from "./common";

const PUBLIC_FOLLOWERS_REVALIDATE = 120;

type OrganizerFollowContext = { params: Promise<{ slug: string }> };

async function getPublicFollowers(slug: string): Promise<number | null> {
  if (!apiBaseUrl) {
    return null;
  }

  try {
    const response = await fetch(`${apiBaseUrl}/api/v1/public/tenants/${encodeURIComponent(slug)}/organization-profile`, {
      cache: "force-cache",
      headers: {
        Accept: "application/json",
      },
      next: { revalidate: PUBLIC_FOLLOWERS_REVALIDATE },
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

    return normalizedNumber(payload.data?.followers_count ?? payload.data?.meta?.followers, null);
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

    return { payload, response };
  } catch {
    return null;
  }
}

export async function getOrganizerFollowStatus(_request: NextRequest, context: OrganizerFollowContext) {
  const { slug } = await context.params;
  const tenantSlug = normalizeTenantSlug(slug);

  if (!tenantSlug) {
    return NextResponse.json({ error: "Tenant invalide." }, { status: 422 });
  }

  const token = await getAuthToken();

  if (!token) {
    return NextResponse.json({
      authenticated: false,
      followers: await getPublicFollowers(tenantSlug),
      following: false,
    });
  }

  const proxied = await proxyFollowStatus(token, tenantSlug);

  if (!proxied || !proxied.response.ok) {
    return NextResponse.json({
      authenticated: false,
      followers: await getPublicFollowers(tenantSlug),
      following: false,
    });
  }

  return NextResponse.json({
    authenticated: true,
    followers: proxied.payload?.data?.followers ?? (await getPublicFollowers(tenantSlug)),
    following: Boolean(proxied.payload?.data?.following),
  });
}

export async function mutateOrganizerFollow(
  request: NextRequest,
  context: OrganizerFollowContext,
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
  const tenantSlug = normalizeTenantSlug(slug);

  if (!tenantSlug) {
    return NextResponse.json({ error: "Tenant invalide." }, { status: 422 });
  }

  if (!apiBaseUrl) {
    return NextResponse.json({ error: "API backend indisponible." }, { status: 500 });
  }

  try {
    const response = await fetch(`${apiBaseUrl}/api/v1/tenants/${encodeURIComponent(tenantSlug)}/organization-profile/follow`, {
      cache: "no-store",
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
      method,
    });
    const payload = await response.json().catch(() => null);

    if (!response.ok) {
      return NextResponse.json(
        {
          error: payload?.message ?? payload?.error ?? "Impossible de mettre à jour l'abonnement.",
        },
        { status: response.status },
      );
    }

    return NextResponse.json({
      authenticated: true,
      followers: payload?.data?.followers ?? (await getPublicFollowers(tenantSlug)),
      following: Boolean(payload?.data?.following),
      message: payload?.message,
    });
  } catch {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }
}
