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
  const item = await getContentDetail("crowdfunding", slug);

  return createMetadata({
    title: item?.title ?? "Crowdfunding",
    description: item?.summary ?? "Detail campagne",
    path: `/crowdfunding/${slug}`,
    image: item?.coverImageUrl,
  });
}

export default async function CrowdfundingDetailPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const item = await getContentDetail("crowdfunding", slug);
  const related = item ? await getRelatedContent(item) : [];

  return <ModuleDetailView item={item} related={related} />;
}
