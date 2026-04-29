import { NextResponse } from "next/server";

import { clearAuthCookies, getAuthToken, getTenantSlug } from "@/lib/auth";
import { logoutAccount } from "@/lib/data/account";

export async function POST() {
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);

  if (token) {
    await logoutAccount(tenantSlug, token);
  }

  await clearAuthCookies();

  return NextResponse.json({ ok: true });
}
