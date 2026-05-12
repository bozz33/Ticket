import { ModuleDetailView } from "@/components/RouteViews";
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
  const item = await getContentDetail("appels-a-projets", slug, tenant);

  return createMetadata({
    title: item?.title ?? "Appel a projets",
    description: item?.summary ?? "Detail appel a projets",
    path: `/appels-a-projets/${slug}`,
    image: item?.coverImageUrl,
  });
}

export default async function CallDetailPage({
  params,
  searchParams,
}: {
  params: Promise<{ slug: string }>;
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const { slug } = await params;
  const rawSearchParams = await searchParams;
  const tenant = Array.isArray(rawSearchParams.tenant) ? rawSearchParams.tenant[0] : rawSearchParams.tenant;
  const item = await getContentDetail("appels-a-projets", slug, tenant);
  const related = item ? await getRelatedContent(item) : [];

  return <ModuleDetailView item={item} related={related} />;
}
