import { ModuleListingView } from "@/components/RouteViews";
import { getContentByModule, getReferenceFilters } from "@/lib/data/public";
import { normalizeSearchParams } from "@/lib/utils";

export const dynamic = "force-dynamic";

export default async function StandsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const filters = normalizeSearchParams(await searchParams);
  const [listing, references] = await Promise.all([
    getContentByModule("stands", filters),
    getReferenceFilters(),
  ]);

  return (
    <ModuleListingView
      categories={references.categories}
      cities={references.cities}
      {...listing}
    />
  );
}
