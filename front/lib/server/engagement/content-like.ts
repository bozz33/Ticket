import { type NextRequest, NextResponse } from "next/server";

import { getMarketplaceAuthTokenForTenant } from "@/lib/auth";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";
import { normalizeTenantSlug } from "@/lib/tenant";

import { apiBaseUrl, normalizedNumber } from "./common";

type ContentLikeContext = { params: Promise<{ tenant: string; module: string; slug: string }> };
const CONTENT_LIKE_PROXY_TIMEOUT_MS = 15000;

async function proxyContentLike(
  tenantSlug: string,
  module: string,
  slug: string,
  token: string,
  method = "GET",
) {
  if (!apiBaseUrl) {
    return null;
  }

  try {
    const response = await fetch(
      `${apiBaseUrl}/api/v1/tenants/${encodeURIComponent(tenantSlug)}/content/${encodeURIComponent(module)}/${encodeURIComponent(slug)}/like`,
      {
        cache: "no-store",
        signal: AbortSignal.timeout(CONTENT_LIKE_PROXY_TIMEOUT_MS),
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${token}`,
        },
        method,
      },
    );
    const payload = await response.json().catch(() => null);

    return { payload, response };
  } catch {
    return null;
  }
}

export async function getContentLikeStatus(_request: NextRequest, context: ContentLikeContext) {
  const { tenant, module, slug } = await context.params;
  const tenantSlug = normalizeTenantSlug(tenant);
  if (!tenantSlug) {
    return NextResponse.json({ error: "Espace organisateur invalide." }, { status: 422 });
  }

  const token = await getMarketplaceAuthTokenForTenant(tenantSlug, { persist: true });

  if (!token) {
    return NextResponse.json({ authenticated: false, liked: false, likes: 0 });
  }

  const proxied = await proxyContentLike(tenantSlug, module, slug, token);

  if (proxied?.response.status === 401) {
    const exchangedToken = await getMarketplaceAuthTokenForTenant(tenantSlug, { forceExchange: true, persist: true });
    const retried = exchangedToken ? await proxyContentLike(tenantSlug, module, slug, exchangedToken) : null;

    if (retried?.response.ok) {
      return NextResponse.json({
        authenticated: true,
        liked: Boolean(retried.payload?.data?.liked),
        likes: normalizedNumber(retried.payload?.data?.likes, 0) ?? 0,
      });
    }
  }

  if (!proxied || !proxied.response.ok) {
    return NextResponse.json({ authenticated: false, liked: false, likes: 0 });
  }

  return NextResponse.json({
    authenticated: true,
    liked: Boolean(proxied.payload?.data?.liked),
    likes: normalizedNumber(proxied.payload?.data?.likes, 0) ?? 0,
  });
}

export async function mutateContentLike(
  request: NextRequest,
  context: ContentLikeContext,
  method: "POST" | "DELETE",
) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(request, method === "POST" ? "content-like" : "content-unlike", 120);

  if (rateLimitError) {
    return rateLimitError;
  }

  const { tenant, module, slug } = await context.params;
  const tenantSlug = normalizeTenantSlug(tenant);

  if (!tenantSlug) {
    return NextResponse.json({ error: "Espace organisateur invalide." }, { status: 422 });
  }

  const token = await getMarketplaceAuthTokenForTenant(tenantSlug, { persist: true });

  if (!token) {
    return NextResponse.json({ error: "Non authentifié." }, { status: 401 });
  }

  const proxied = await proxyContentLike(tenantSlug, module, slug, token, method);
  const resolvedProxy = proxied?.response.status === 401
    ? await retryContentLikeWithFreshToken(tenantSlug, module, slug, method)
    : proxied;

  if (!resolvedProxy) {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }

  if (!resolvedProxy.response.ok) {
    return NextResponse.json(
      { error: resolvedProxy.payload?.message ?? resolvedProxy.payload?.error ?? "Impossible de mettre à jour vos favoris." },
      { status: resolvedProxy.response.status },
    );
  }

  return NextResponse.json({
    authenticated: true,
    liked: Boolean(resolvedProxy.payload?.data?.liked),
    likes: normalizedNumber(resolvedProxy.payload?.data?.likes, 0) ?? 0,
    message: resolvedProxy.payload?.message,
  });
}

async function retryContentLikeWithFreshToken(
  tenantSlug: string,
  module: string,
  slug: string,
  method: "POST" | "DELETE",
) {
  const token = await getMarketplaceAuthTokenForTenant(tenantSlug, { forceExchange: true, persist: true });

  return token ? proxyContentLike(tenantSlug, module, slug, token, method) : null;
}
