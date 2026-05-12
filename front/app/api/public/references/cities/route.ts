import { type NextRequest, NextResponse } from "next/server";

const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL?.replace(/\/$/, "") ?? (process.env.NODE_ENV === "development" ? "http://127.0.0.1:8000" : "");
const PUBLIC_CITIES_REVALIDATE = 300;

export async function GET(request: NextRequest) {
  if (!apiBaseUrl) {
    return NextResponse.json({ message: "API base URL is not configured." }, { status: 500 });
  }

  const country = request.nextUrl.searchParams.get("country")?.trim() ?? "";
  const q = request.nextUrl.searchParams.get("q")?.trim() ?? "";
  const limit = request.nextUrl.searchParams.get("limit")?.trim() ?? "";
  const params = new URLSearchParams();

  if (country) {
    params.set("country", country);
  }

  if (q) {
    params.set("q", q);
  }

  if (limit) {
    params.set("limit", limit);
  }

  const query = params.toString() ? `?${params.toString()}` : "";

  try {
    const response = await fetch(`${apiBaseUrl}/api/v1/public/references/cities${query}`, {
      cache: "force-cache",
      next: { revalidate: PUBLIC_CITIES_REVALIDATE },
      headers: {
        Accept: "application/json",
      },
    });
    const payload = await response.json();

    return NextResponse.json(payload, {
      status: response.status,
      headers: {
        "Cache-Control": `public, max-age=0, s-maxage=${PUBLIC_CITIES_REVALIDATE}, stale-while-revalidate=${PUBLIC_CITIES_REVALIDATE}`,
      },
    });
  } catch {
    return NextResponse.json({ message: "Unable to reach backend." }, { status: 503 });
  }
}
