import { type NextRequest, NextResponse } from "next/server";

import { getTenantSlug } from "@/lib/auth";
import { getPublicPass, getPublicReceiptVerification } from "@/lib/data/account";

export async function GET(request: NextRequest) {
  const searchParams = request.nextUrl.searchParams;
  const reference = (searchParams.get("ref") ?? searchParams.get("reference") ?? "").trim();

  if (!reference) {
    return NextResponse.json({ error: "Référence requise." }, { status: 422 });
  }

  const tenantSlug = await getTenantSlug(searchParams.get("tenant")?.trim() || undefined);

  if (!tenantSlug) {
    return NextResponse.json({ error: "Espace public indisponible." }, { status: 503 });
  }

  const receipt = await getPublicReceiptVerification(tenantSlug, reference);

  if (receipt) {
    return NextResponse.json(
      { data: { type: "receipt", receipt } },
      { headers: { "Cache-Control": "no-store" } },
    );
  }

  const pass = await getPublicPass(tenantSlug, reference);

  if (pass) {
    return NextResponse.json(
      { data: { type: "pass", pass } },
      { headers: { "Cache-Control": "no-store" } },
    );
  }

  return NextResponse.json(
    { error: "Référence introuvable ou non authentique." },
    { status: 404, headers: { "Cache-Control": "no-store" } },
  );
}
