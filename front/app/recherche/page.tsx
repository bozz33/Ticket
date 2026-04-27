import { SearchResultsView } from "@/components/RouteViews";
import { getSearchPageData } from "@/lib/data/public";

export const dynamic = "force-dynamic";

export default async function SearchPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const data = await getSearchPageData(await searchParams);

  return (
    <SearchResultsView
      categories={data.references.categories}
      cities={data.references.cities}
      filters={data.filters}
      items={data.items}
    />
  );
}
