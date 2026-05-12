import { NextRequest, NextResponse } from "next/server";

import { getAllContent } from "@/lib/data/public";
import { ModuleRoute, SearchFilters } from "@/lib/types";

const PUBLIC_CATALOG_REVALIDATE = 120;

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

  const filters: SearchFilters = {
    q: searchParams.get("q") ?? undefined,
    category: searchParams.get("category") ?? undefined,
    city: searchParams.get("city") ?? undefined,
    price: (searchParams.get("price") as SearchFilters["price"]) ?? "all",
    sort: (searchParams.get("sort") as SearchFilters["sort"]) ?? "popular",
    module: isModuleRoute(module) ? module : "all",
    page: page ? Number(page) : undefined,
  };
  const data = await getAllContent(filters);

  return NextResponse.json({
    data,
    filters,
  }, {
    headers: {
      "Cache-Control": `public, max-age=0, s-maxage=${PUBLIC_CATALOG_REVALIDATE}, stale-while-revalidate=${PUBLIC_CATALOG_REVALIDATE}`,
    },
  });
}
