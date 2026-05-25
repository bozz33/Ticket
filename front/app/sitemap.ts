import type { MetadataRoute } from "next";

import { getAllContent, getFrontPagesIndex, getOrganizerHighlights, getPlatformConfiguration } from "@/lib/data/public";
import { metadataBase } from "@/lib/metadata";

const baseUrl = metadataBase.toString().replace(/\/$/, "");

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const platform = await getPlatformConfiguration();
  const sitemapSettings = platform.seo?.sitemap;

  if (sitemapSettings?.enabled === false) {
    return [];
  }

  const staticRoutes = [
    "/",
    "/evenements",
    "/formations",
    "/stands",
    "/appels-a-projets",
    "/crowdfunding",
    "/categories",
    "/compte",
    "/politique-confidentialite",
    "/conditions-generales-de-vente",
    "/conditions-generales-utilisation",
  ].map((path) => ({
    url: `${baseUrl}${path}`,
    lastModified: new Date(),
  }));

  const [contentItems, organizers, frontPages] = await Promise.all([
    sitemapSettings?.includeCatalog === false ? Promise.resolve([]) : getAllContent(),
    sitemapSettings?.includeOrganizers === false ? Promise.resolve([]) : getOrganizerHighlights(),
    sitemapSettings?.includeFrontPages === false ? Promise.resolve([]) : getFrontPagesIndex(),
  ]);

  const cmsRoutes = frontPages
    .filter((page) => page.path !== "/")
    .map((page) => ({
      url: `${baseUrl}${page.path}`,
      lastModified: new Date(page.updated_at || page.published_at || Date.now()),
    }));

  const contentRoutes = contentItems.map((item) => ({
    url: `${baseUrl}/${item.module}/${item.slug}`,
    lastModified: new Date(item.publishedAt),
  }));

  const organizerRoutes = organizers.map(({ organizer }) => ({
    url: `${baseUrl}/organisateurs/${organizer.slug}`,
    lastModified: new Date(),
  }));

  return [...staticRoutes, ...cmsRoutes, ...contentRoutes, ...organizerRoutes];
}
