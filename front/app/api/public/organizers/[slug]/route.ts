import { NextResponse, type NextRequest } from "next/server";

import { getOrganizerBySlug } from "@/lib/data/public";

const PUBLIC_ORGANIZER_REVALIDATE = 120;

export async function GET(
  _request: NextRequest,
  context: { params: Promise<unknown> },
) {
  const { slug } = (await context.params) as { slug: string };
  const payload = await getOrganizerBySlug(slug);

  if (!payload) {
    return NextResponse.json({ message: "Organizer not found." }, { status: 404 });
  }

  return NextResponse.json({
    data: payload.organizer,
    items: payload.items,
  }, {
    headers: {
      "Cache-Control": `public, max-age=0, s-maxage=${PUBLIC_ORGANIZER_REVALIDATE}, stale-while-revalidate=${PUBLIC_ORGANIZER_REVALIDATE}`,
    },
  });
}
