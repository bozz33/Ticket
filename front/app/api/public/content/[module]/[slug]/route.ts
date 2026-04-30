import { NextResponse } from "next/server";

import { findContent, listRelatedContent } from "@/lib/data/catalog";
import { ModuleRoute } from "@/lib/types";

function isModuleRoute(value: string): value is ModuleRoute {
  return (
    value === "evenements" ||
    value === "formations" ||
    value === "stands" ||
    value === "appels-a-projets" ||
    value === "crowdfunding"
  );
}

export async function GET(
  _request: Request,
  context: { params: Promise<{ module: string; slug: string }> },
) {
  const { module, slug } = await context.params;

  if (!isModuleRoute(module)) {
    return NextResponse.json({ message: "Module not found." }, { status: 404 });
  }

  const item = await findContent(module, slug);

  if (!item) {
    return NextResponse.json({ message: "Content not found." }, { status: 404 });
  }

  const related = await listRelatedContent(item);

  return NextResponse.json({
    data: item,
    related,
  });
}
