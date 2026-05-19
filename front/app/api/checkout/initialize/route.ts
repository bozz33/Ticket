import { type NextRequest, NextResponse } from "next/server";

import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";

const apiBaseUrl =
  process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") ??
  (process.env.NODE_ENV === "development" ? "http://127.0.0.1:8000" : "");

export async function POST(request: NextRequest) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(request, "checkout-initialize", 10);

  if (rateLimitError) {
    return rateLimitError;
  }

  const token = await getAuthToken();

  if (!token) {
    return NextResponse.json(
      { error: "Connexion acheteur requise pour réserver ou acheter.", code: "AUTH_REQUIRED" },
      { status: 401 },
    );
  }

  if (!apiBaseUrl) {
    return NextResponse.json({ error: "API backend non configurée." }, { status: 503 });
  }

  const payload = await request.json().catch(() => null) as {
    offer?: string;
    quantity?: number;
    payment_method?: string;
    content_module?: string;
    content_slug?: string;
    callback_url?: string;
    tenant?: string;
  } | null;

  if (!payload || typeof payload !== "object") {
    return NextResponse.json({ error: "Payload invalide." }, { status: 400 });
  }

  if (typeof payload.offer !== "string" || !payload.offer.trim()) {
    return NextResponse.json({ error: "Offre manquante." }, { status: 422 });
  }

  const tenantSlug = await getTenantSlug(typeof payload?.tenant === "string" ? payload.tenant : undefined);

  if (!tenantSlug) {
    return NextResponse.json(
      { error: "Aucun espace acheteur actif n'est disponible pour ce paiement." },
      { status: 503 },
    );
  }

  const response = await fetch(`${apiBaseUrl}/api/v1/public/tenants/${encodeURIComponent(tenantSlug)}/payments/initialize`, {
    method: "POST",
    cache: "no-store",
    headers: {
      Accept: "application/json",
      Authorization: `Bearer ${token}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      offer: payload.offer,
      quantity: Number.isFinite(Number(payload.quantity)) ? Math.max(1, Math.trunc(Number(payload.quantity))) : 1,
      payment_method: payload.payment_method,
      content_module: payload.content_module,
      content_slug: payload.content_slug,
      callback_url: payload.callback_url,
    }),
  });

  const body = await response.json().catch(() => ({}));

  if (!response.ok) {
    return NextResponse.json(
      {
        error: body?.message ?? body?.error ?? "Impossible d'initialiser le paiement.",
        code: body?.code,
      },
      { status: response.status },
    );
  }

  return NextResponse.json(body);
}
