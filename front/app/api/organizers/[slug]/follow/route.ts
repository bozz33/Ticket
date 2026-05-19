import { type NextRequest } from "next/server";

import { getOrganizerFollowStatus, mutateOrganizerFollow } from "@/lib/server/engagement-proxy";

type OrganizerFollowContext = { params: Promise<{ slug: string }> };

export async function GET(request: NextRequest, context: OrganizerFollowContext) {
  return getOrganizerFollowStatus(request, context);
}

export async function POST(request: NextRequest, context: OrganizerFollowContext) {
  return mutateOrganizerFollow(request, context, "POST");
}

export async function DELETE(request: NextRequest, context: OrganizerFollowContext) {
  return mutateOrganizerFollow(request, context, "DELETE");
}
