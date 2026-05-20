import { type NextRequest, NextResponse } from "next/server";

import { getAuthToken, getDefaultTenantSlug, requireTenantSlug } from "@/lib/auth";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";
import { normalizeTenantSlug } from "@/lib/tenant";

const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") ?? (process.env.NODE_ENV === "development" ? "http://127.0.0.1:8000" : "");

export async function POST(
  request: NextRequest,
  context: { params: Promise<{ formId: string }> },
) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(request, "public-dynamic-form-submit", 5);

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
  const { formId } = await context.params;
  const contentType = request.headers.get("content-type") ?? "";
  const isMultipart = contentType.includes("multipart/form-data");
  const body = isMultipart
    ? await request.formData()
    : JSON.stringify(await request.json().catch(() => ({})));

  try {
    const response = await fetch(`${apiBaseUrl}/api/v1/public/tenants/${encodeURIComponent(tenantSlug)}/forms/${encodeURIComponent(formId)}/submissions`, {
      method: "POST",
      headers: {
        Accept: "application/json",
        ...(isMultipart ? {} : { "Content-Type": "application/json" }),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body,
    });

    const responsePayload = await response.json().catch(() => ({ message: "Réponse serveur invalide." }));

    return NextResponse.json(responsePayload, { status: response.status });
  } catch {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }
}
