import { type NextRequest, NextResponse } from "next/server";

import { getAuthToken, requireTenantSlug } from "@/lib/auth";
import { markAccountNotificationAsRead } from "@/lib/data/account";
import { applyMutationRateLimit, validateMutationOrigin } from "@/lib/request-security";

export async function PATCH(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> },
) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(request, "account-notification-read", 30);

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

  const { id } = await params;
  const result = await markAccountNotificationAsRead(tenantSlug, token, id);

  if (!result) {
    return NextResponse.json({ error: "Notification introuvable." }, { status: 404 });
  }

  return NextResponse.json(result);
}
