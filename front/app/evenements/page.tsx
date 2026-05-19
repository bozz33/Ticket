import { getManagedPageMetadata } from "@/components/ManagedFrontPageRoute";
import { ModuleListingView } from "@/components/route/PublicListingViews";
import { getEventCatalogPageData } from "@/lib/data/public";
import { normalizeSearchParams } from "@/lib/utils";

export const revalidate = 120;
export const generateMetadata = () => getManagedPageMetadata("/evenements", {
  title: "Evenements | Ticket",
  description: "Decouvrez les evenements disponibles sur le catalogue public.",
});

export default async function EventsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const filters = normalizeSearchParams(await searchParams);
  const listing = await getEventCatalogPageData(filters);

  return (
    <ModuleListingView {...listing} />
  );
}
