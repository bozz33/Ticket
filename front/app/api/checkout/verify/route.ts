import { type NextRequest, NextResponse } from "next/server";

import { getTenantSlug } from "@/lib/auth";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";

const apiBaseUrl =
  process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") ??
  (process.env.NODE_ENV === "development" ? "http://127.0.0.1:8000" : "");

export async function POST(request: NextRequest) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(request, "checkout-verify", 40);

  if (rateLimitError) {
    return rateLimitError;
  }

  if (!apiBaseUrl) {
    return NextResponse.json({ error: "API backend non configurée." }, { status: 503 });
  }

  const payload = await request.json().catch(() => null) as {
    reference?: string;
    tenant?: string;
  } | null;

  const reference = typeof payload?.reference === "string" ? payload.reference.trim() : "";

  if (!reference) {
    return NextResponse.json({ error: "Référence de paiement manquante." }, { status: 422 });
  }

  const tenantSlug = await getTenantSlug(typeof payload?.tenant === "string" ? payload.tenant : undefined);

  if (!tenantSlug) {
    return NextResponse.json(
      { error: "Aucun espace organisateur n'est disponible pour vérifier ce paiement." },
      { status: 503 },
    );
  }

  const response = await fetch(
    `${apiBaseUrl}/api/v1/public/tenants/${encodeURIComponent(tenantSlug)}/payments/verify/${encodeURIComponent(reference)}`,
    {
      method: "GET",
      cache: "no-store",
      headers: {
        Accept: "application/json",
      },
    },
  );

  const body = await response.json().catch(() => ({}));

  if (!response.ok) {
    return NextResponse.json(
      {
        error: body?.message ?? body?.error ?? "Impossible de vérifier le paiement.",
      },
      { status: response.status },
    );
  }

  return NextResponse.json(body);
}
