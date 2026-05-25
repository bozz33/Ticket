import { notFound } from "next/navigation";
import type { Metadata } from "next";

import { ManagedFrontPage } from "@/components/ManagedFrontPage";
import { createMetadata, safeJsonLd } from "@/lib/metadata";
import { getFrontPageData, getOrganizerHighlights, getPlatformConfiguration } from "@/lib/data/public";
import { getStaticPageHeroImage, StaticPageHeroKey } from "@/lib/utils";

function resolveFallbackHeroImage(path: string): string | undefined {
  const keyByPath: Partial<Record<string, StaticPageHeroKey>> = {
    "/a-propos": "a-propos",
    "/categories": "categories",
    "/contact": "contact",
    "/faq": "faq",
    "/mentions-legales": "mentions-legales",
    "/remboursement": "remboursement",
    "/devenir-organisateur": "devenir-organisateur",
  };

  const key = keyByPath[path];

  return key ? getStaticPageHeroImage(key) : undefined;
}

export async function getManagedPageMetadata(
  path: string,
  fallback: { title: string; description: string },
): Promise<Metadata> {
  const [platform, page] = await Promise.all([getPlatformConfiguration(), getFrontPageData(path)]);
  const pageSeo = page?.seo;
  const platformSeo = platform.seo;

  return createMetadata({
    title: pageSeo?.title || platformSeo?.defaultTitle || fallback.title,
    description: pageSeo?.description || platformSeo?.defaultDescription || fallback.description,
    path,
    image: pageSeo?.image || pageSeo?.og_image || platformSeo?.openGraph?.imageUrl || undefined,
    imageAlt: pageSeo?.og_image_alt || platformSeo?.openGraph?.imageAlt,
    keywords: pageSeo?.keywords?.length ? pageSeo.keywords : platformSeo?.keywords,
    canonicalUrl: pageSeo?.canonical_url || undefined,
    robotsIndex: pageSeo?.robots_index || platformSeo?.robots?.index,
    robotsFollow: pageSeo?.robots_follow || platformSeo?.robots?.follow,
    maxImagePreview: pageSeo?.max_image_preview || platformSeo?.robots?.maxImagePreview,
    ogTitle: pageSeo?.og_title || undefined,
    ogDescription: pageSeo?.og_description || undefined,
    ogType: pageSeo?.og_type || platformSeo?.openGraph?.type,
    twitterTitle: pageSeo?.twitter_title || undefined,
    twitterDescription: pageSeo?.twitter_description || undefined,
    twitterImage: pageSeo?.twitter_image || platformSeo?.twitter?.imageUrl || undefined,
    twitterCard: pageSeo?.twitter_card || platformSeo?.twitter?.card,
  });
}

export async function ManagedFrontPageRoute({
  path,
  includeOrganizers = false,
}: {
  path: string;
  includeOrganizers?: boolean;
}) {
  const [platform, page, organizers] = await Promise.all([
    getPlatformConfiguration(),
    getFrontPageData(path),
    includeOrganizers ? getOrganizerHighlights() : Promise.resolve([]),
  ]);

  if (!page) {
    notFound();
  }

  const structuredData = safeJsonLd(page.seo.structured_data_json);

  return (
    <>
      {structuredData ? (
        <script dangerouslySetInnerHTML={{ __html: structuredData }} type="application/ld+json" />
      ) : null}
      <ManagedFrontPage
        fallbackHeroImage={resolveFallbackHeroImage(path)}
        organizers={organizers}
        page={page}
        platform={platform}
      />
    </>
  );
}
