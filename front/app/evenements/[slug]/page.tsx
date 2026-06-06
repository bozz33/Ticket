import type { PublicContent } from "@/lib/types";
import { ModuleDetailView } from "@/components/route/DetailRouteViews";
import { getContentDetail, getRelatedContent } from "@/lib/data/public";
import { createMetadata } from "@/lib/metadata";

export const revalidate = 120;

export async function generateMetadata({
  params,
  searchParams,
}: {
  params: Promise<{ slug: string }>;
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const { slug } = await params;
  const rawSearchParams = await searchParams;
  const tenant = Array.isArray(rawSearchParams.tenant) ? rawSearchParams.tenant[0] : rawSearchParams.tenant;
  const item = await getContentDetail("evenements", slug, tenant);

  return createMetadata({
    title: item?.title ?? "Evenement",
    description: item?.summary ?? "Detail evenement",
    path: `/evenements/${slug}`,
    image: item?.coverImageUrl,
  });
}

function buildEventJsonLd(item: PublicContent, slug: string): Record<string, unknown> {
  const jsonLd: Record<string, unknown> = {
    "@context": "https://schema.org",
    "@type": "Event",
    name: item.title,
    description: item.summary,
    url: `/evenements/${slug}`,
    image: item.coverImageUrl || undefined,
    eventStatus: "https://schema.org/EventScheduled",
    eventAttendanceMode: item.format === "online"
      ? "https://schema.org/OnlineEventAttendanceMode"
      : "https://schema.org/OfflineEventAttendanceMode",
  };

  if (item.startsAt) jsonLd.startDate = item.startsAt;
  if (item.endsAt) jsonLd.endDate = item.endsAt;

  if (item.city) {
    jsonLd.location = {
      "@type": "Place",
      name: item.venueName ?? item.city,
      address: {
        "@type": "PostalAddress",
        addressLocality: item.city,
        addressCountry: item.country ?? undefined,
      },
    };
  }

  if (item.organizers.length > 0) {
    jsonLd.organizer = {
      "@type": "Organization",
      name: item.organizers[0].name,
    };
  }

  if (!item.isFree) {
    jsonLd.offers = {
      "@type": "Offer",
      price: item.priceFrom,
      priceCurrency: item.currency ?? "XOF",
      availability: (item.remainingSeats ?? 1) > 0
        ? "https://schema.org/InStock"
        : "https://schema.org/SoldOut",
    };
  }

  return jsonLd;
}

export default async function EventDetailPage({
  params,
  searchParams,
}: {
  params: Promise<{ slug: string }>;
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const { slug } = await params;
  const rawSearchParams = await searchParams;
  const tenant = Array.isArray(rawSearchParams.tenant) ? rawSearchParams.tenant[0] : rawSearchParams.tenant;
  const item = await getContentDetail("evenements", slug, tenant);
  const related = item ? await getRelatedContent(item) : [];

  return (
    <>
      {item ? (
        <script
          dangerouslySetInnerHTML={{ __html: JSON.stringify(buildEventJsonLd(item, slug)) }}
          type="application/ld+json"
        />
      ) : null}
      <ModuleDetailView item={item} related={related} />
    </>
  );
}
