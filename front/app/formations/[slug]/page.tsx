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
  const item = await getContentDetail("formations", slug);

  return createMetadata({
    title: item?.title ?? "Formation",
    description: item?.summary ?? "Detail formation",
    path: `/formations/${slug}`,
    image: item?.coverImageUrl,
  });
}

export default async function FormationDetailPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const item = await getContentDetail("formations", slug);
  const related = item ? await getRelatedContent(item) : [];

  return <ModuleDetailView item={item} related={related} />;
}
