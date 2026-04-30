import { type NextRequest, NextResponse } from "next/server";

import { registerOrganizer } from "@/lib/data/account";

export async function POST(request: NextRequest) {
  const body = await request.json();
  const { org_name, email, password, country_code, currency_code } = body;

  if (!org_name || !email || !password) {
    return NextResponse.json({ error: "Champs requis." }, { status: 400 });
  }

  const result = await registerOrganizer({ org_name, email, password, country_code, currency_code });

  if (!result) {
    return NextResponse.json({ error: "Impossible de contacter le serveur." }, { status: 503 });
  }

  if ("error" in result) {
    return NextResponse.json({ error: result.error }, { status: 422 });
  }

  return NextResponse.json(result, { status: 201 });
}
