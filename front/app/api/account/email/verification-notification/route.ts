import { type NextRequest, NextResponse } from "next/server";

import { getAuthToken, requireTenantSlug } from "@/lib/auth";
import { sendAccountEmailVerificationNotification } from "@/lib/data/account";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";

export async function POST(request: NextRequest) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(request, "account-email-verification", 5);

  if (rateLimitError) {
    return rateLimitError;
  }

  const token = await getAuthToken();

  if (!token) {
    return NextResponse.json({ error: "Non authentifié." }, { status: 401 });
  }

  const tenantSlug = await requireTenantSlug();
  const result = await sendAccountEmailVerificationNotification(tenantSlug, token);

  if (!result) {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }

  if ("error" in result) {
    return NextResponse.json({ error: result.error }, { status: 422 });
  }

  return NextResponse.json({ message: result.message, user: result.user });
}
