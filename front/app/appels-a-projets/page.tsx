import { getManagedPageMetadata } from "@/components/ManagedFrontPageRoute";
import { ModuleListingView } from "@/components/route/PublicListingViews";
import { getContentByModule } from "@/lib/data/public";
import { normalizeSearchParams } from "@/lib/utils";

export const revalidate = 120;
export const generateMetadata = () => getManagedPageMetadata("/appels-a-projets", {
  title: "Appels a projets | Ticket",
  description: "Consultez les appels a projets et soumettez votre candidature.",
});

export default async function CallsForProjectsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const filters = normalizeSearchParams(await searchParams);
  const listing = await getContentByModule("appels-a-projets", filters);

  return (
    <ModuleListingView {...listing} />
  );
}
