import { NextResponse } from "next/server";

import { findOrganizer } from "@/lib/data/catalog";

export async function GET(
  _request: Request,
  context: { params: Promise<{ slug: string }> },
) {
  const { slug } = await context.params;
  const payload = findOrganizer(slug);

  if (!payload) {
    return NextResponse.json({ message: "Organizer not found." }, { status: 404 });
  }

  return NextResponse.json({
    data: payload.organizer,
    items: payload.items,
  });
}
