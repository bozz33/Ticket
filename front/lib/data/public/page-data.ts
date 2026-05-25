import { normalizeSearchParams } from "@/lib/utils";
import { getPlatformConfiguration, getFrontPageData } from "./cms";
import { fetchContentPage } from "./catalog";
import { getOrganizerHighlights } from "./organizers";
import { publicDataCacheSeconds, rememberPublicData } from "./shared";

export async function getHomePageData() {
  return rememberPublicData("home-page-data", {}, publicDataCacheSeconds, async () => {
    const [platform, page, featuredResult, popularResult, organizers] = await Promise.all([
      getPlatformConfiguration(),
      getFrontPageData("/"),
      fetchContentPage({ sort: "recent" }, 1, 8),
      fetchContentPage({ module: "evenements", sort: "weekly_likes" }, 1, 8),
      getOrganizerHighlights(),
    ]);
    const featured = featuredResult.items.length > 0
      ? featuredResult.items.slice(0, 8)
      : popularResult.items.slice(0, 8);
    const popular = popularResult.items.slice(0, 6);
    const categories = featuredResult.references.categories.length > 0
      ? featuredResult.references.categories
      : popularResult.references.categories;
    const totalItems = popularResult.totalItems || featuredResult.totalItems;

    return {
      page,
      platform,
      featured,
      popular,
      organizers,
      categories,
      stats: [
        { label: "Contenus publies", value: String(totalItems) },
        { label: "Organisateurs", value: String(organizers.length) },
        { label: "Utilisateurs", value: String(platform.usersCount) },
      ],
    };
  });
}

export async function getSearchPageData(searchParams: Record<string, string | string[] | undefined>) {
  const filters = normalizeSearchParams(searchParams);
  return rememberPublicData("search-page-data", { filters }, publicDataCacheSeconds, async () => {
    const currentPage = filters.page ?? 1;
    const [platform, page, result] = await Promise.all([
      getPlatformConfiguration(),
      getFrontPageData("/recherche"),
      fetchContentPage(filters, currentPage, 12),
    ]);

    return {
      platform,
      page,
      filters,
      items: result.items,
      currentPage: result.currentPage,
      totalItems: result.totalItems,
      totalPages: result.totalPages,
      references: result.references,
    };
  });
}
