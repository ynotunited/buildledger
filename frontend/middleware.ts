import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

const AUTH_COOKIE = "buildledger_session";
const PUBLIC_PATHS = new Set([
  "/",
  "/login",
  "/register",
  "/forgot-password",
  "/reset-password",
  "/verify-email",
  "/auth/callback",
  "/privacy-policy",
  "/terms-of-use",
  "/data-compliance",
  "/ip-infringement",
]);

function isPublicPath(pathname: string) {
  return PUBLIC_PATHS.has(pathname) || pathname.startsWith("/contracts/sign/");
}

export function middleware(request: NextRequest) {
  const { pathname, search } = request.nextUrl;
  const hasAuthCookie =
    !!request.cookies.get(AUTH_COOKIE)?.value ||
    !!request.cookies.get("laravel-session")?.value ||
    !!request.cookies.get("buildledger_auth")?.value;

  if (pathname.startsWith("/_next") || pathname.includes(".")) {
    return NextResponse.next();
  }

  if (!isPublicPath(pathname) && !hasAuthCookie) {
    const loginUrl = new URL("/login", request.url);
    loginUrl.searchParams.set("next", `${pathname}${search}`);
    return NextResponse.redirect(loginUrl);
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/((?!api|_next/static|_next/image|favicon.ico).*)"],
};
