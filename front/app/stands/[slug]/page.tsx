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
  const item = await getContentDetail("stands", slug);

  return createMetadata({
    title: item?.title ?? "Stand",
    description: item?.summary ?? "Detail stand",
    path: `/stands/${slug}`,
    image: item?.coverImageUrl,
  });
}

export default async function StandDetailPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const item = await getContentDetail("stands", slug);
  const related = item ? await getRelatedContent(item) : [];

  return <ModuleDetailView item={item} related={related} />;
}
