import { notFound } from "next/navigation";
import type { Metadata } from "next";

import { ManagedFrontPage } from "@/components/ManagedFrontPage";
import { createMetadata } from "@/lib/metadata";
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
  const page = await getFrontPageData(path);

  return createMetadata({
    title: page?.seo.title || fallback.title,
    description: page?.seo.description || fallback.description,
    path,
    image: page?.seo.image || undefined,
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

  return (
    <ManagedFrontPage
      fallbackHeroImage={resolveFallbackHeroImage(path)}
      organizers={organizers}
      page={page}
      platform={platform}
    />
  );
}
