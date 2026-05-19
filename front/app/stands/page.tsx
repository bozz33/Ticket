import { getManagedPageMetadata } from "@/components/ManagedFrontPageRoute";
import { ModuleListingView } from "@/components/route/PublicListingViews";
import { getContentByModule } from "@/lib/data/public";
import { normalizeSearchParams } from "@/lib/utils";

export const revalidate = 120;
export const generateMetadata = () => getManagedPageMetadata("/stands", {
  title: "Stands | Ticket",
  description: "Decouvrez les stands disponibles sur le catalogue public.",
});

export default async function StandsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const filters = normalizeSearchParams(await searchParams);
  const listing = await getContentByModule("stands", filters);

  return (
    <ModuleListingView {...listing} />
  );
}
