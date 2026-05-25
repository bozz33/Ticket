import { cookies } from "next/headers";

import { getManagedPageMetadata } from "@/components/ManagedFrontPageRoute";
import { HomeView } from "@/components/route/HomeRouteViews";
import { getHomePageData } from "@/lib/data/public";
import { PUBLIC_LOCALE_COOKIE, resolveSupportedLocale } from "@/lib/i18n/public-translations";

export const dynamic = "force-dynamic";
export const revalidate = 0;

export const generateMetadata = () => getManagedPageMetadata("/", {
  title: "Ticket | Portail public multi-modules",
  description:
    "Decouvrez des evenements, formations, stands, appels a projets et campagnes de crowdfunding sur un portail public unifie.",
});

export default async function HomePage() {
  const data = await getHomePageData();
  const cookieStore = await cookies();
  const locale = resolveSupportedLocale(data.platform, cookieStore.get(PUBLIC_LOCALE_COOKIE)?.value);

  return <HomeView {...data} locale={locale} />;
}
