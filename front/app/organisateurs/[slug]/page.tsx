import { OrganizerView } from "@/components/RouteViews";
import { getOrganizerBySlug } from "@/lib/data/public";
import { createMetadata } from "@/lib/metadata";

export const dynamic = "force-dynamic";

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
  const payload = await getOrganizerBySlug(slug);

  return <OrganizerView items={payload?.items ?? []} organizer={payload?.organizer ?? null} />;
}
