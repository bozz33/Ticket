import { type NextRequest, NextResponse } from "next/server";

import { getAuthToken, getDefaultTenantSlug, requireTenantSlug } from "@/lib/auth";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";
import { normalizeTenantSlug } from "@/lib/tenant";

const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") ?? (process.env.NODE_ENV === "development" ? "http://127.0.0.1:8000" : "");

export async function POST(request: NextRequest) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(request, "checkout-ticket-reserve", 10);

  if (rateLimitError) {
    return rateLimitError;
  }

  if (!apiBaseUrl) {
    return NextResponse.json({ error: "API backend indisponible." }, { status: 500 });
  }

  let tenantSlug = normalizeTenantSlug(request.nextUrl.searchParams.get("tenant"));

  try {
    tenantSlug = tenantSlug || await getDefaultTenantSlug() || await requireTenantSlug();
  } catch (error) {
    return NextResponse.json({ error: error instanceof Error ? error.message : "Tenant non configuré." }, { status: 500 });
  }

  const token = await getAuthToken();

  if (!token) {
    return NextResponse.json({ error: "Connexion acheteur requise pour réserver.", code: "AUTH_REQUIRED" }, { status: 401 });
  }

  const payload = await request.json().catch(() => ({}));

  try {
    const response = await fetch(`${apiBaseUrl}/api/v1/public/tenants/${encodeURIComponent(tenantSlug)}/ticket-reservations`, {
      method: "POST",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(payload),
    });

    const responsePayload = await response.json().catch(() => ({ message: "Réponse serveur invalide." }));

    return NextResponse.json(responsePayload, { status: response.status });
  } catch {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }
}
