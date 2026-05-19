import { getManagedPageMetadata } from "@/components/ManagedFrontPageRoute";
import { SearchResultsView } from "@/components/route/PublicListingViews";
import { getSearchPageData } from "@/lib/data/public";

export const revalidate = 60;
export const generateMetadata = () => getManagedPageMetadata("/recherche", {
  title: "Recherche | Ticket",
  description: "Recherchez dans tous les modules et les contenus publics.",
});

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
      currentPage={data.currentPage}
      filters={data.filters}
      items={data.items}
      page={data.page}
      totalItems={data.totalItems}
      totalPages={data.totalPages}
    />
  );
}
