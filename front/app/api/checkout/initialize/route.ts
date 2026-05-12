import { type NextRequest, NextResponse } from "next/server";

import { getAuthToken, requireTenantSlug } from "@/lib/auth";
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

  const tenantSlug = await requireTenantSlug();
  const payload = await request.json();

  const response = await fetch(`${apiBaseUrl}/api/v1/public/tenants/${tenantSlug}/payments/initialize`, {
    method: "POST",
    cache: "no-store",
    headers: {
      Accept: "application/json",
      Authorization: `Bearer ${token}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      offer: payload.offer,
      quantity: payload.quantity,
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
