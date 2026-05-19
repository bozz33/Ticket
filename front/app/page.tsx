import { getManagedPageMetadata } from "@/components/ManagedFrontPageRoute";
import { HomeView } from "@/components/route/HomeRouteViews";
import { getHomePageData } from "@/lib/data/public";

export const revalidate = 120;

export const generateMetadata = () => getManagedPageMetadata("/", {
  title: "Ticket | Portail public multi-modules",
  description:
    "Decouvrez des evenements, formations, stands, appels a projets et campagnes de crowdfunding sur un portail public unifie.",
});

export default async function HomePage() {
  const data = await getHomePageData();

  return <HomeView {...data} />;
}
