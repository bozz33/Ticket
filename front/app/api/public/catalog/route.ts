import { NextRequest, NextResponse } from "next/server";

import { getCatalogPageData } from "@/lib/data/public";
import { ModuleRoute, SearchFilters } from "@/lib/types";

const PUBLIC_CATALOG_REVALIDATE = 120;
const DEFAULT_PER_PAGE = 12;
const MAX_PER_PAGE = 48;

function isModuleRoute(value: string | null): value is ModuleRoute {
  return (
    value === "evenements" ||
    value === "formations" ||
    value === "stands" ||
    value === "appels-a-projets" ||
    value === "crowdfunding"
  );
}

export async function GET(request: NextRequest) {
  const searchParams = request.nextUrl.searchParams;
  const module = searchParams.get("module");
  const page = searchParams.get("page");
  const perPage = searchParams.get("per_page");
  const parsedPage = Math.max(1, Math.trunc(Number(page) || 1));
  const parsedPerPage = Math.min(
    MAX_PER_PAGE,
    Math.max(1, Math.trunc(Number(perPage) || DEFAULT_PER_PAGE)),
  );

  const filters: SearchFilters = {
    q: searchParams.get("q") ?? undefined,
    category: searchParams.get("category") ?? undefined,
    city: searchParams.get("city") ?? undefined,
    price: (searchParams.get("price") as SearchFilters["price"]) ?? "all",
    sort: (searchParams.get("sort") as SearchFilters["sort"]) ?? "popular",
    module: isModuleRoute(module) ? module : "all",
    page: parsedPage,
  };
  const result = await getCatalogPageData(filters, parsedPage, parsedPerPage);

  return NextResponse.json({
    data: result.items,
    filters,
    meta: {
      currentPage: result.currentPage,
      totalItems: result.totalItems,
      totalPages: result.totalPages,
      perPage: parsedPerPage,
    },
  }, {
    headers: {
      "Cache-Control": `public, max-age=0, s-maxage=${PUBLIC_CATALOG_REVALIDATE}, stale-while-revalidate=${PUBLIC_CATALOG_REVALIDATE}`,
    },
  });
}
