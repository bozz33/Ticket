import { type NextRequest } from "next/server";

import { getContentLikeStatus, mutateContentLike } from "@/lib/server/engagement/content-like";

type ContentLikeContext = { params: Promise<{ tenant: string; module: string; slug: string }> };

export async function GET(request: NextRequest, context: ContentLikeContext) {
  return getContentLikeStatus(request, context);
}

export async function POST(request: NextRequest, context: ContentLikeContext) {
  return mutateContentLike(request, context, "POST");
}

export async function DELETE(request: NextRequest, context: ContentLikeContext) {
  return mutateContentLike(request, context, "DELETE");
}
