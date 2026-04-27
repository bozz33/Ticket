import { ModuleDetailView } from "@/components/RouteViews";
import { getContentDetail, getRelatedContent } from "@/lib/data/public";
import { createMetadata } from "@/lib/metadata";

export const dynamic = "force-dynamic";

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const item = await getContentDetail("evenements", slug);

  return createMetadata({
    title: item?.title ?? "Evenement",
    description: item?.summary ?? "Detail evenement",
    path: `/evenements/${slug}`,
    image: item?.coverImageUrl,
  });
}

export default async function EventDetailPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const item = await getContentDetail("evenements", slug);
  const related = item ? await getRelatedContent(item) : [];

  return <ModuleDetailView item={item} related={related} />;
}
