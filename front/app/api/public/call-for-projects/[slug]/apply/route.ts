import { type NextRequest, NextResponse } from "next/server";

import { getAuthToken, getDefaultTenantSlug, requireTenantSlug } from "@/lib/auth";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";
import { normalizeTenantSlug } from "@/lib/tenant";

const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") ?? (process.env.NODE_ENV === "development" ? "http://127.0.0.1:8000" : "");

export async function POST(
  request: NextRequest,
  context: { params: Promise<{ slug: string }> },
) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(request, "public-call-for-project-apply", 5);

  if (rateLimitError) {
    return rateLimitError;
  }

  if (!apiBaseUrl) {
    return NextResponse.json({ error: "API backend indisponible." }, { status: 500 });
  }

  const token = await getAuthToken();

  if (!token) {
    return NextResponse.json(
      { error: "Connexion acheteur requise pour soumettre une candidature.", code: "AUTH_REQUIRED" },
      { status: 401 },
    );
  }

  let tenantSlug = normalizeTenantSlug(request.nextUrl.searchParams.get("tenant"));

  try {
    tenantSlug = tenantSlug || await getDefaultTenantSlug() || await requireTenantSlug();
  } catch (error) {
    return NextResponse.json({ error: error instanceof Error ? error.message : "Tenant non configuré." }, { status: 500 });
  }

  const { slug } = await context.params;
  const formData = await request.formData();

  try {
    const response = await fetch(`${apiBaseUrl}/api/v1/public/tenants/${encodeURIComponent(tenantSlug)}/calls-for-projects/${encodeURIComponent(slug)}/applications`, {
      method: "POST",
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: formData,
    });

    const payload = await response.json().catch(() => ({ message: "Réponse serveur invalide." }));

    return NextResponse.json(payload, { status: response.status });
  } catch {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }
}
