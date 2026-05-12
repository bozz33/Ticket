import { NextResponse, type NextRequest } from "next/server";

import { getContentDetail, getRelatedContent } from "@/lib/data/public";
import { ModuleRoute } from "@/lib/types";

const PUBLIC_CONTENT_REVALIDATE = 120;

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
  request: NextRequest,
  context: { params: Promise<unknown> },
) {
  const { module, slug } = (await context.params) as { module: string; slug: string };
  const tenant = request.nextUrl.searchParams.get("tenant") ?? undefined;

  if (!isModuleRoute(module)) {
    return NextResponse.json({ message: "Module not found." }, { status: 404 });
  }

  const item = await getContentDetail(module, slug, tenant);

  if (!item) {
    return NextResponse.json({ message: "Content not found." }, { status: 404 });
  }
  const related = await getRelatedContent(item);

  return NextResponse.json({
    data: item,
    related,
  }, {
    headers: {
      "Cache-Control": `public, max-age=0, s-maxage=${PUBLIC_CONTENT_REVALIDATE}, stale-while-revalidate=${PUBLIC_CONTENT_REVALIDATE}`,
    },
  });
}
