import { notFound } from "next/navigation";

import { CheckoutView } from "@/components/route/CheckoutRouteViews";
import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountMe } from "@/lib/data/account";
import { getCheckoutData } from "@/lib/data/public";
import { ModuleRoute } from "@/lib/types";

export const dynamic = "force-dynamic";

export const metadata = {
  robots: { index: false, follow: false },
};

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

export default async function CheckoutPage({
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

  const search = await searchParams;
  const offer = Array.isArray(search.offer) ? search.offer[0] : search.offer;
  const ticket = Array.isArray(search.ticket) ? search.ticket[0] : search.ticket;
  const tenant = Array.isArray(search.tenant) ? search.tenant[0] : search.tenant;
  const [data, token, tenantSlug] = await Promise.all([
    getCheckoutData(module, slug, ticket ?? offer, tenant),
    getAuthToken(),
    getTenantSlug(),
  ]);

  if (!data) {
    notFound();
  }

  const accountUser = token ? await getAccountMe(tenantSlug, token) : null;
  const checkoutParams = new URLSearchParams();

  if (offer) {
    checkoutParams.set("offer", offer);
  }

  if (ticket) {
    checkoutParams.set("ticket", ticket);
  }

  if (tenant) {
    checkoutParams.set("tenant", tenant);
  }

  const redirectPath = `/checkout/${module}/${slug}${checkoutParams.size > 0 ? `?${checkoutParams.toString()}` : ""}`;

  return (
    <CheckoutView
      accountUser={accountUser}
      dateLabel={data.dateLabel}
      item={data.item}
      loginUrl={`/compte/connexion?redirect=${encodeURIComponent(redirectPath)}`}
      paymentOptions={data.paymentOptions}
      selectedOffer={data.selectedOffer}
    />
  );
}
