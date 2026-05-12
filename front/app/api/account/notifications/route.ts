import { type NextRequest, NextResponse } from "next/server";

import { getAuthToken, requireTenantSlug } from "@/lib/auth";
import { getAccountNotifications, markAllAccountNotificationsAsRead } from "@/lib/data/account";
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

  const result = await getAccountNotifications(tenantSlug, token);

  if (!result) {
    return NextResponse.json({ notifications: [], unreadCount: 0 });
  }

  return NextResponse.json(result);
}

export async function PATCH(request: NextRequest) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const rateLimitError = applyMutationRateLimit(request, "account-notifications-read-all", 12);

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

  const result = await markAllAccountNotificationsAsRead(tenantSlug, token);

  return NextResponse.json({ unreadCount: result?.unreadCount ?? 0 });
}
