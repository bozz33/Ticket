import { NextRequest, NextResponse } from "next/server";

import { listContent } from "@/lib/data/catalog";
import { ModuleRoute, SearchFilters } from "@/lib/types";

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

  const filters: SearchFilters = {
    q: searchParams.get("q") ?? undefined,
    category: searchParams.get("category") ?? undefined,
    city: searchParams.get("city") ?? undefined,
    price: (searchParams.get("price") as SearchFilters["price"]) ?? "all",
    sort: (searchParams.get("sort") as SearchFilters["sort"]) ?? "popular",
    module: isModuleRoute(module) ? module : "all",
  };

  return NextResponse.json({
    data: listContent(filters),
    filters,
  });
}
