import { type NextRequest, NextResponse } from "next/server";

import { getTenantSlug } from "@/lib/auth";
import { getCheckoutPaymentOptions } from "@/lib/data/public";

const MAX_CHECKOUT_QUANTITY = 50;

function parseQuantity(value: string | null): number {
  const quantity = Number(value ?? 1);

  if (!Number.isFinite(quantity)) {
    return 1;
  }

  return Math.min(Math.max(Math.trunc(quantity), 1), MAX_CHECKOUT_QUANTITY);
}

export async function GET(request: NextRequest) {
  const searchParams = request.nextUrl.searchParams;
  const offer = searchParams.get("offer")?.trim() ?? "";

  if (!offer) {
    return NextResponse.json({ error: "Offre manquante." }, { status: 422 });
  }

  const tenantSlug = await getTenantSlug(searchParams.get("tenant"));

  if (!tenantSlug) {
    return NextResponse.json({ error: "Tenant non configuré." }, { status: 503 });
  }

  const paymentOptions = await getCheckoutPaymentOptions(
    offer,
    parseQuantity(searchParams.get("quantity")),
    searchParams.get("payment_method")?.trim() || undefined,
    tenantSlug,
  );

  if (!paymentOptions) {
    return NextResponse.json({ error: "Options de paiement indisponibles." }, { status: 404 });
  }

  return NextResponse.json(
    { data: paymentOptions },
    { headers: { "Cache-Control": "no-store" } },
  );
}
