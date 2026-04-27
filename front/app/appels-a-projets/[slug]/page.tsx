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
  const item = await getContentDetail("appels-a-projets", slug);

  return createMetadata({
    title: item?.title ?? "Appel a projets",
    description: item?.summary ?? "Detail appel a projets",
    path: `/appels-a-projets/${slug}`,
    image: item?.coverImageUrl,
  });
}

export default async function CallDetailPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const item = await getContentDetail("appels-a-projets", slug);
  const related = item ? await getRelatedContent(item) : [];

  return <ModuleDetailView item={item} related={related} />;
}
