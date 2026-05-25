import { type NextRequest, NextResponse } from "next/server";

import { getMarketplaceAuthTokenForTenant } from "@/lib/auth";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";
import { normalizeTenantSlug } from "@/lib/tenant";

import { apiBaseUrl, normalizedNumber } from "./common";

const PUBLIC_FOLLOWERS_REVALIDATE = 120;
const ORGANIZER_FOLLOW_PROXY_TIMEOUT_MS = 15000;

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
      signal: AbortSignal.timeout(ORGANIZER_FOLLOW_PROXY_TIMEOUT_MS),
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

  const token = await getMarketplaceAuthTokenForTenant(tenantSlug, { persist: true });

  if (!token) {
    return NextResponse.json({
      authenticated: false,
      followers: await getPublicFollowers(tenantSlug),
      following: false,
    });
  }

  const proxied = await proxyFollowStatus(token, tenantSlug);

  if (proxied?.response.status === 401) {
    const exchangedToken = await getMarketplaceAuthTokenForTenant(tenantSlug, { forceExchange: true, persist: true });
    const retried = exchangedToken ? await proxyFollowStatus(exchangedToken, tenantSlug) : null;

    if (retried?.response.ok) {
      return NextResponse.json({
        authenticated: true,
        followers: retried.payload?.data?.followers ?? (await getPublicFollowers(tenantSlug)),
        following: Boolean(retried.payload?.data?.following),
      });
    }
  }

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
    80,
  );

  if (rateLimitError) {
    return rateLimitError;
  }

  const { slug } = await context.params;
  const tenantSlug = normalizeTenantSlug(slug);

  if (!tenantSlug) {
    return NextResponse.json({ error: "Tenant invalide." }, { status: 422 });
  }

  const token = await getMarketplaceAuthTokenForTenant(tenantSlug, { persist: true });

  if (!token) {
    return NextResponse.json({ error: "Non authentifié." }, { status: 401 });
  }

  if (!apiBaseUrl) {
    return NextResponse.json({ error: "API backend indisponible." }, { status: 500 });
  }

  try {
    let response = await proxyFollowMutation(token, tenantSlug, method);

    if (response?.response.status === 401) {
      const exchangedToken = await getMarketplaceAuthTokenForTenant(tenantSlug, { forceExchange: true, persist: true });
      response = exchangedToken ? await proxyFollowMutation(exchangedToken, tenantSlug, method) : null;
    }

    if (!response) {
      return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
    }

    if (!response.response.ok) {
      return NextResponse.json(
        {
          error: response.payload?.message ?? response.payload?.error ?? "Impossible de mettre à jour le suivi.",
        },
        { status: response.response.status },
      );
    }

    return NextResponse.json({
      authenticated: true,
      followers: response.payload?.data?.followers ?? (await getPublicFollowers(tenantSlug)),
      following: Boolean(response.payload?.data?.following),
      message: response.payload?.message,
    });
  } catch {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }
}

async function proxyFollowMutation(token: string, tenantSlug: string, method: "POST" | "DELETE") {
  if (!apiBaseUrl) {
    return null;
  }

  try {
    const response = await fetch(`${apiBaseUrl}/api/v1/tenants/${encodeURIComponent(tenantSlug)}/organization-profile/follow`, {
      cache: "no-store",
      signal: AbortSignal.timeout(ORGANIZER_FOLLOW_PROXY_TIMEOUT_MS),
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
      method,
    });
    const payload = await response.json().catch(() => null);

    return { payload, response };
  } catch {
    return null;
  }
}
