import type { MetadataRoute } from "next";

import { mockContent, mockOrganizers } from "@/lib/data/mock";
import { metadataBase } from "@/lib/metadata";

const baseUrl = metadataBase.toString().replace(/\/$/, "");

export default function sitemap(): MetadataRoute.Sitemap {
  const staticRoutes = [
    "/",
    "/evenements",
    "/formations",
    "/stands",
    "/appels-a-projets",
    "/crowdfunding",
    "/recherche",
    "/categories",
    "/villes",
    "/intervenants",
    "/a-propos",
    "/support",
    "/devenir-organisateur",
    "/compte",
  ].map((path) => ({
    url: `${baseUrl}${path}`,
    lastModified: new Date(),
  }));

  const contentRoutes = mockContent.map((item) => ({
    url: `${baseUrl}/${item.module}/${item.slug}`,
    lastModified: new Date(item.publishedAt),
  }));

  const organizerRoutes = mockOrganizers.map((organizer) => ({
    url: `${baseUrl}/organisateurs/${organizer.slug}`,
    lastModified: new Date(),
  }));

  return [...staticRoutes, ...contentRoutes, ...organizerRoutes];
}
