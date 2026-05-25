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
  const ticket = searchParams.get("ticket")?.trim() ?? "";

  if (!offer && !ticket) {
    return NextResponse.json({ error: "Offre ou ticket manquant." }, { status: 422 });
  }

  const tenantSlug = await getTenantSlug(searchParams.get("tenant"));

  if (!tenantSlug) {
    return NextResponse.json({ error: "Tenant non configuré." }, { status: 503 });
  }

  const customAmountParam = searchParams.get("custom_amount");
  const customAmount = customAmountParam !== null && Number.isFinite(Number(customAmountParam))
    ? Math.max(1, Math.trunc(Number(customAmountParam)))
    : undefined;

  const paymentOptions = await getCheckoutPaymentOptions(
    ticket || offer,
    parseQuantity(searchParams.get("quantity")),
    searchParams.get("payment_method")?.trim() || undefined,
    tenantSlug,
    ticket ? "ticket" : "offer",
    customAmount,
  );

  if (!paymentOptions) {
    return NextResponse.json({ error: "Options de paiement indisponibles." }, { status: 404 });
  }

  return NextResponse.json(
    { data: paymentOptions },
    { headers: { "Cache-Control": "no-store" } },
  );
}
