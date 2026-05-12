import { type NextRequest, NextResponse } from "next/server";

import { getAccountTenantSlug } from "@/lib/auth";
import { requestAccountPasswordReset } from "@/lib/data/account";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";

export async function POST(request: NextRequest) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(request, "account-forgot-password", 5);

  if (rateLimitError) {
    return rateLimitError;
  }

  const { email, tenant } = await request.json() as {
    email?: string;
    tenant?: string;
  };

  if (!email) {
    return NextResponse.json({ error: "Adresse e-mail requise." }, { status: 400 });
  }

  let tenantSlug = "";

  try {
    tenantSlug = await getAccountTenantSlug(tenant);
  } catch (error) {
    return NextResponse.json(
      { error: error instanceof Error ? error.message : "Tenant non configuré." },
      { status: 500 },
    );
  }

  const result = await requestAccountPasswordReset(tenantSlug, email);

  if (!result) {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }

  if ("error" in result) {
    return NextResponse.json({ error: result.error }, { status: 422 });
  }

  return NextResponse.json({ message: result.message });
}
