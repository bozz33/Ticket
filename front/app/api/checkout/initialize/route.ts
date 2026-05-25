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

  if (!apiBaseUrl) {
    return NextResponse.json({ error: "API backend non configurée." }, { status: 503 });
  }

  const payload = await request.json().catch(() => null) as {
    offer?: string;
    ticket?: string;
    quantity?: number;
    payment_method?: string;
    custom_amount?: number;
    buyer_name?: string;
    buyer_email?: string;
    buyer_phone?: string;
    content_module?: string;
    content_slug?: string;
    contributor_display_name?: string;
    contributor_is_anonymous?: boolean;
    callback_url?: string;
    tenant?: string;
  } | null;

  if (!payload || typeof payload !== "object") {
    return NextResponse.json({ error: "Payload invalide." }, { status: 400 });
  }

  const offer = typeof payload.offer === "string" ? payload.offer.trim() : "";
  const ticket = typeof payload.ticket === "string" ? payload.ticket.trim() : "";

  if (!offer && !ticket) {
    return NextResponse.json({ error: "Offre ou ticket manquant." }, { status: 422 });
  }

  const token = await getAuthToken();
  const isCrowdfunding = payload.content_module === "crowdfunding";

  if (!token && !isCrowdfunding) {
    return NextResponse.json(
      { error: "Connexion acheteur requise pour réserver ou acheter.", code: "AUTH_REQUIRED" },
      { status: 401 },
    );
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
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      offer: offer || undefined,
      ticket: ticket || undefined,
      quantity: Number.isFinite(Number(payload.quantity)) ? Math.max(1, Math.trunc(Number(payload.quantity))) : 1,
      payment_method: payload.payment_method,
      custom_amount: Number.isFinite(Number(payload.custom_amount))
        ? Math.max(1, Math.trunc(Number(payload.custom_amount)))
        : undefined,
      buyer_name: payload.buyer_name,
      buyer_email: payload.buyer_email,
      buyer_phone: payload.buyer_phone,
      content_module: payload.content_module,
      content_slug: payload.content_slug,
      contributor_display_name: payload.contributor_display_name,
      contributor_is_anonymous: payload.contributor_is_anonymous,
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
