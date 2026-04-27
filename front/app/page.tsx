import { HomeView } from "@/components/RouteViews";
import { getHomePageData } from "@/lib/data/public";
import { createMetadata } from "@/lib/metadata";

export const dynamic = "force-dynamic";
export const metadata = createMetadata({
  title: "Ticket | Portail public multi-modules",
  description:
    "Decouvrez des evenements, formations, stands, appels a projets et campagnes de crowdfunding sur un portail public unifie.",
  path: "/",
});

export default async function HomePage() {
  const data = await getHomePageData();

  return <HomeView {...data} />;
}
