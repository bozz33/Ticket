import { NextResponse } from "next/server";

const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") ?? (process.env.NODE_ENV === "development" ? "http://127.0.0.1:8000" : "");
const PUBLIC_PLATFORM_REVALIDATE = 120;

export async function GET() {
  if (!apiBaseUrl) {
    return NextResponse.json({ message: "API base URL is not configured." }, { status: 500 });
  }

  try {
    const response = await fetch(`${apiBaseUrl}/api/v1/public/platform/configuration`, {
      cache: "force-cache",
      next: { revalidate: PUBLIC_PLATFORM_REVALIDATE },
      headers: {
        Accept: "application/json",
      },
    });
    const payload = await response.json();

    return NextResponse.json(payload, {
      status: response.status,
      headers: {
        "Cache-Control": `public, max-age=0, s-maxage=${PUBLIC_PLATFORM_REVALIDATE}, stale-while-revalidate=${PUBLIC_PLATFORM_REVALIDATE}`,
      },
    });
  } catch {
    return NextResponse.json({ message: "Unable to reach backend." }, { status: 503 });
  }
}
