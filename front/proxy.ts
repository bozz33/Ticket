import { NextRequest, NextResponse } from "next/server";
import { isMutationMethod, validateMutationOrigin } from "@/lib/request-security";

const AUTH_TOKEN_COOKIE = "_account_token";
const TENANT_COOKIE = "_account_tenant";
const authPages = new Set([
  "/compte/connexion",
  "/compte/inscription",
  "/compte/reinitialisation",
  "/compte/reinitialisation/nouveau",
]);

function applySecurityHeaders(response: NextResponse, pathname: string) {
  if (pathname.startsWith("/compte") || pathname.startsWith("/api/account") || pathname.startsWith("/api/onboarding") || pathname.startsWith("/api/public/call-for-projects")) {
    response.headers.set("Cache-Control", "no-store, no-cache, must-revalidate, proxy-revalidate");
    response.headers.set("Pragma", "no-cache");
    response.headers.set("Expires", "0");
    response.headers.set("Vary", "Origin");
    response.headers.set("X-Request-Id", crypto.randomUUID());
  }

  return response;
}

export function proxy(request: NextRequest) {
  const { pathname, search } = request.nextUrl;
  const token = request.cookies.get(AUTH_TOKEN_COOKIE)?.value;
  const currentTenant = request.cookies.get(TENANT_COOKIE)?.value?.trim() ?? "";
  const requestedTenant = request.nextUrl.searchParams.get("tenant")?.trim() ?? "";
  const allowExplicitAuthFlow = Boolean(
    request.nextUrl.searchParams.get("redirect")
    || request.nextUrl.searchParams.get("reason")
    || request.nextUrl.searchParams.get("tenant")
    || request.nextUrl.searchParams.get("reset")
    || request.nextUrl.searchParams.get("verified"),
  );
  const isTenantSwitch = Boolean(requestedTenant && requestedTenant !== currentTenant);
  const isAccountArea = pathname === "/compte" || pathname.startsWith("/compte/");
  const isAuthPage = authPages.has(pathname);

  if ((pathname.startsWith("/api/account") || pathname.startsWith("/api/onboarding") || pathname.startsWith("/api/public/call-for-projects")) && isMutationMethod(request.method)) {
    const originError = validateMutationOrigin(request);

    if (originError) {
      return applySecurityHeaders(originError, pathname);
    }
  }

  if (isAccountArea && !isAuthPage && !token) {
    const loginUrl = new URL("/compte/connexion", request.url);
    const redirectTarget = `${pathname}${search}`;

    if (redirectTarget !== "/compte/connexion") {
      loginUrl.searchParams.set("redirect", redirectTarget);
    }

    return applySecurityHeaders(NextResponse.redirect(loginUrl), pathname);
  }

  if (isAuthPage && token && !allowExplicitAuthFlow && !isTenantSwitch) {
    return applySecurityHeaders(NextResponse.redirect(new URL("/compte", request.url)), pathname);
  }

  return applySecurityHeaders(NextResponse.next(), pathname);
}

export const config = {
  matcher: ["/compte/:path*", "/api/account/:path*", "/api/onboarding/:path*", "/api/public/call-for-projects/:path*"],
};
