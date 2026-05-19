import { type NextRequest } from "next/server";

import { getEventLikeStatus, mutateEventLike } from "@/lib/server/engagement-proxy";

type EventLikeContext = { params: Promise<{ tenant: string; slug: string }> };

export async function GET(request: NextRequest, context: EventLikeContext) {
  return getEventLikeStatus(request, context);
}

export async function POST(request: NextRequest, context: EventLikeContext) {
  return mutateEventLike(request, context, "POST");
}

export async function DELETE(request: NextRequest, context: EventLikeContext) {
  return mutateEventLike(request, context, "DELETE");
}
