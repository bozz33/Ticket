import { NextRequest, NextResponse } from "next/server";

import { getSearchSuggestions } from "@/lib/data/public";
import { ModuleRoute } from "@/lib/types";

const PUBLIC_SEARCH_REVALIDATE = 60;

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
  const query = searchParams.get("q") ?? "";
  const module = searchParams.get("module");
  const suggestions = await getSearchSuggestions(query, isModuleRoute(module) ? module : "all");

  return NextResponse.json({
    data: suggestions,
  }, {
    headers: {
      "Cache-Control": `public, max-age=0, s-maxage=${PUBLIC_SEARCH_REVALIDATE}, stale-while-revalidate=${PUBLIC_SEARCH_REVALIDATE}`,
    },
  });
}
