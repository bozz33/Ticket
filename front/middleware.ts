import { type NextRequest, NextResponse } from "next/server";

const PROTECTED = /^\/compte(?!\/connexion)(\/|$)/;

export function middleware(request: NextRequest): NextResponse {
  const { pathname } = request.nextUrl;

  if (!PROTECTED.test(pathname)) {
    return NextResponse.next();
  }

  const token = request.cookies.get("_account_token")?.value;

  if (!token) {
    const loginUrl = new URL("/compte/connexion", request.url);
    loginUrl.searchParams.set("redirect", pathname);
    return NextResponse.redirect(loginUrl);
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/compte/:path*"],
};
