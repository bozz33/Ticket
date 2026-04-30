import { type NextRequest, NextResponse } from "next/server";

import { getTenantSlug, setAuthCookies } from "@/lib/auth";
import { registerAccount } from "@/lib/data/account";

export async function POST(request: NextRequest) {
  const { name, email, password } = await request.json();

  if (!name || !email || !password) {
    return NextResponse.json({ error: "Champs requis." }, { status: 400 });
  }

  const tenantSlug = await getTenantSlug();
  const result = await registerAccount(tenantSlug, name, email, password);

  if (!result) {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }

  if ("error" in result) {
    return NextResponse.json({ error: result.error }, { status: 422 });
  }

  await setAuthCookies(result.token, tenantSlug);

  return NextResponse.json({ user: result.user }, { status: 201 });
}
