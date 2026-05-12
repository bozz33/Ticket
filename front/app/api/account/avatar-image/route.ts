import { NextResponse } from "next/server";

import { getAuthToken, requireTenantSlug } from "@/lib/auth";
import { getAccountAvatarResponse } from "@/lib/data/account";

export async function GET() {
  const token = await getAuthToken();

  if (!token) {
    return new NextResponse(null, { status: 401 });
  }

  let tenantSlug = "";

  try {
    tenantSlug = await requireTenantSlug();
  } catch {
    return new NextResponse(null, { status: 404 });
  }

  const response = await getAccountAvatarResponse(tenantSlug, token);

  if (!response) {
    return new NextResponse(null, { status: 404 });
  }

  return new NextResponse(await response.arrayBuffer(), {
    headers: {
      "Cache-Control": "private, max-age=300",
      "Content-Type": response.headers.get("content-type") ?? "image/jpeg",
    },
  });
}
