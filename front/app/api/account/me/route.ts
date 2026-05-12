import { type NextRequest, NextResponse } from "next/server";

import { getAuthToken, requireTenantSlug } from "@/lib/auth";
import { getAccountMe, updateAccountMe } from "@/lib/data/account";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";

export async function GET() {
  const token = await getAuthToken();

  if (!token) {
    return NextResponse.json({ error: "Non authentifié." }, { status: 401 });
  }

  let tenantSlug = "";

  try {
    tenantSlug = await requireTenantSlug();
  } catch (error) {
    return NextResponse.json({ error: error instanceof Error ? error.message : "Tenant non configuré." }, { status: 500 });
  }

  const user = await getAccountMe(tenantSlug, token);

  if (!user) {
    return NextResponse.json({ error: "Session expirée." }, { status: 401 });
  }

  return NextResponse.json({ user });
}

export async function PUT(request: NextRequest) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(request, "account-profile-update", 10);

  if (rateLimitError) {
    return rateLimitError;
  }

  const token = await getAuthToken();

  if (!token) {
    return NextResponse.json({ error: "Non authentifié." }, { status: 401 });
  }

  let tenantSlug = "";

  try {
    tenantSlug = await requireTenantSlug();
  } catch (error) {
    return NextResponse.json({ error: error instanceof Error ? error.message : "Tenant non configuré." }, { status: 500 });
  }

  const payload = await request.json();
  const result = await updateAccountMe(tenantSlug, token, payload);

  if (!result) {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }

  if ("error" in result) {
    return NextResponse.json({ error: result.error }, { status: 422 });
  }

  return NextResponse.json({ user: result.user, message: result.message });
}
