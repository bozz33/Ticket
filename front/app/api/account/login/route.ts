import { type NextRequest, NextResponse } from "next/server";

import { setAuthCookies, getTenantSlug } from "@/lib/auth";
import { loginAccount } from "@/lib/data/account";

export async function POST(request: NextRequest) {
  const { email, password } = await request.json();

  if (!email || !password) {
    return NextResponse.json({ error: "Champs requis." }, { status: 400 });
  }

  const tenantSlug = await getTenantSlug();
  const result = await loginAccount(tenantSlug, email, password);

  if (!result) {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }

  if ("error" in result) {
    return NextResponse.json({ error: result.error }, { status: 401 });
  }

  await setAuthCookies(result.token, tenantSlug);

  return NextResponse.json({ user: result.user });
}
