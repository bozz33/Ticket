import { getManagedPageMetadata } from "@/components/ManagedFrontPageRoute";
import { ModuleListingView } from "@/components/RouteViews";
import { getContentByModule } from "@/lib/data/public";
import { normalizeSearchParams } from "@/lib/utils";

export const revalidate = 120;
export const generateMetadata = () => getManagedPageMetadata("/formations", {
  title: "Formations | Ticket",
  description: "Decouvrez les formations disponibles sur le catalogue public.",
});

export default async function FormationsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const filters = normalizeSearchParams(await searchParams);
  const listing = await getContentByModule("formations", filters);

  return (
    <ModuleListingView {...listing} />
  );
}
