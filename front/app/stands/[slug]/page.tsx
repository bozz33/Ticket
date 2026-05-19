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
  const item = await getContentDetail("stands", slug, tenant);

  return createMetadata({
    title: item?.title ?? "Stand",
    description: item?.summary ?? "Detail stand",
    path: `/stands/${slug}`,
    image: item?.coverImageUrl,
  });
}

export default async function StandDetailPage({
  params,
  searchParams,
}: {
  params: Promise<{ slug: string }>;
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const { slug } = await params;
  const rawSearchParams = await searchParams;
  const tenant = Array.isArray(rawSearchParams.tenant) ? rawSearchParams.tenant[0] : rawSearchParams.tenant;
  const item = await getContentDetail("stands", slug, tenant);
  const related = item ? await getRelatedContent(item) : [];

  return <ModuleDetailView item={item} related={related} />;
}
