import { getManagedPageMetadata } from "@/components/ManagedFrontPageRoute";
import { ModuleListingView } from "@/components/route/PublicListingViews";
import { getContentByModule } from "@/lib/data/public";
import { normalizeSearchParams } from "@/lib/utils";

export const revalidate = 120;
export const generateMetadata = () => getManagedPageMetadata("/crowdfunding", {
  title: "Crowdfunding | Ticket",
  description: "Soutenez les campagnes de financement participatif du catalogue public.",
});

export default async function CrowdfundingPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const filters = normalizeSearchParams(await searchParams);
  const listing = await getContentByModule("crowdfunding", filters);

  return (
    <ModuleListingView {...listing} />
  );
}
