import { notFound } from "next/navigation";

import { CheckoutView } from "@/components/RouteViews";
import { getCheckoutData } from "@/lib/data/public";
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
  const data = await getCheckoutData(module, slug, offer);

  if (!data) {
    notFound();
  }

  return (
    <CheckoutView
      dateLabel={data.dateLabel}
      item={data.item}
      platform={data.platform}
      selectedOffer={data.selectedOffer}
    />
  );
}
