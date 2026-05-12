import { type NextRequest, NextResponse } from "next/server";

import { clearAuthCookies, getAuthToken, requireTenantSlug } from "@/lib/auth";
import { logoutAccount } from "@/lib/data/account";
import { validateMutationOrigin } from "@/lib/request-security";

export async function POST(request: NextRequest) {
  const originError = validateMutationOrigin(request);

  if (originError) {
    return originError;
  }

  const token = await getAuthToken();

  if (token) {
    try {
      const tenantSlug = await requireTenantSlug();
      await logoutAccount(tenantSlug, token);
    } catch {
    }
  }

  await clearAuthCookies();

  return NextResponse.json({ ok: true });
}
