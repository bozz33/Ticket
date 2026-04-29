import { NextResponse } from "next/server";

import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountMe } from "@/lib/data/account";

export async function GET() {
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);

  if (!token) {
    return NextResponse.json({ error: "Non authentifié." }, { status: 401 });
  }

  const user = await getAccountMe(tenantSlug, token);

  if (!user) {
    return NextResponse.json({ error: "Session expirée." }, { status: 401 });
  }

  return NextResponse.json({ user });
}
