import { NextRequest, NextResponse } from "next/server";

import { listSearchSuggestions } from "@/lib/data/catalog";
import { ModuleRoute } from "@/lib/types";

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

  return NextResponse.json({
    data: listSearchSuggestions(query, isModuleRoute(module) ? module : "all"),
  });
}
