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
  const item = await getContentDetail("crowdfunding", slug, tenant);

  return createMetadata({
    title: item?.title ?? "Crowdfunding",
    description: item?.summary ?? "Detail campagne",
    path: `/crowdfunding/${slug}`,
    image: item?.coverImageUrl,
  });
}

function buildCrowdfundingJsonLd(item: PublicContent, slug: string): Record<string, unknown> {
  const jsonLd: Record<string, unknown> = {
    "@context": "https://schema.org",
    "@type": "CreativeWork",
    name: item.title,
    description: item.summary,
    url: `/crowdfunding/${slug}`,
    image: item.coverImageUrl || undefined,
  };

  if (item.organizers.length > 0) {
    jsonLd.author = {
      "@type": "Organization",
      name: item.organizers[0].name,
    };
  }

  if (item.progressTarget) {
    jsonLd.funding = {
      "@type": "MonetaryAmount",
      value: item.progressTarget,
      currency: item.currency ?? "XOF",
    };
  }

  if (item.deadlineAt) jsonLd.expires = item.deadlineAt;

  return jsonLd;
}

export default async function CrowdfundingDetailPage({
  params,
  searchParams,
}: {
  params: Promise<{ slug: string }>;
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const { slug } = await params;
  const rawSearchParams = await searchParams;
  const tenant = Array.isArray(rawSearchParams.tenant) ? rawSearchParams.tenant[0] : rawSearchParams.tenant;
  const item = await getContentDetail("crowdfunding", slug, tenant);
  const related = item ? await getRelatedContent(item) : [];

  return (
    <>
      {item ? (
        <script
          dangerouslySetInnerHTML={{ __html: JSON.stringify(buildCrowdfundingJsonLd(item, slug)) }}
          type="application/ld+json"
        />
      ) : null}
      <ModuleDetailView item={item} related={related} />
    </>
  );
}
