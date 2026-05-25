import { notFound } from "next/navigation";

import { ReceiptView } from "@/components/route/CheckoutRouteViews";
import { getCheckoutData, verifyCheckoutPayment } from "@/lib/data/public";
import { ModuleRoute } from "@/lib/types";

export const dynamic = "force-dynamic";

const modules: ModuleRoute[] = [
  "evenements",
  "formations",
  "stands",
  "appels-a-projets",
  "crowdfunding",
];

function isModuleRoute(value: string): value is ModuleRoute {
  return modules.includes(value as ModuleRoute);
}

export default async function CheckoutReceiptPage({
  params,
  searchParams,
}: {
  params: Promise<{ module: string; slug: string }>;
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const { module, slug } = await params;

  if (!isModuleRoute(module)) {
    notFound();
  }

  if (module === "crowdfunding") {
    notFound();
  }

  const search = await searchParams;
  const offer = Array.isArray(search.offer) ? search.offer[0] : search.offer;
  const ticket = Array.isArray(search.ticket) ? search.ticket[0] : search.ticket;
  const tenant = Array.isArray(search.tenant) ? search.tenant[0] : search.tenant;
  const tx =
    (Array.isArray(search.tx) ? search.tx[0] : search.tx) ??
    (Array.isArray(search.reference) ? search.reference[0] : search.reference) ??
    (Array.isArray(search.trxref) ? search.trxref[0] : search.trxref);
  const data = await getCheckoutData(module, slug, ticket ?? offer, tenant);

  if (!data || !tx) {
    notFound();
  }

  const verification = await verifyCheckoutPayment(tx, data.item.organizerSlug);

  return (
    <ReceiptView
      item={data.item}
      isConfirmed={verification?.is_successful ?? false}
      paidAt={verification?.paid_at ?? undefined}
      paymentReference={verification?.reference ?? tx}
      platform={data.platform}
      selectedOffer={data.selectedOffer}
      verification={verification}
    />
  );
}
