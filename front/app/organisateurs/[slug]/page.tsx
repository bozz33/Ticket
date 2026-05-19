import { OrganizerView } from "@/components/route/OrganizerRouteViews";
import { getOrganizerBySlug, getOrganizerCatalogPageData } from "@/lib/data/public";
import { createMetadata } from "@/lib/metadata";

export const revalidate = 120;

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const payload = await getOrganizerBySlug(slug);

  return createMetadata({
    title: payload?.organizer.name ?? "Organisateur",
    description: payload?.organizer.tagline ?? "Profil organisateur public",
    path: `/organisateurs/${slug}`,
    image: payload?.organizer.bannerUrl,
  });
}

export default async function OrganizerDetailPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const payload = await getOrganizerCatalogPageData(slug, { module: "evenements" }, 12);

  return (
    <OrganizerView
      categories={payload?.categories ?? []}
      cities={payload?.cities ?? []}
      currentPage={payload?.currentPage ?? 1}
      filters={payload?.filters ?? { module: "evenements" }}
      items={payload?.items ?? []}
      organizer={payload?.organizer ?? null}
      stats={payload?.stats ?? { total: 0, free: 0, paid: 0, byModule: {} }}
      totalItems={payload?.totalItems ?? 0}
      totalPages={payload?.totalPages ?? 0}
    />
  );
}
