import type { MetadataRoute } from "next";

import { getPlatformConfiguration } from "@/lib/data/public";
import { metadataBase } from "@/lib/metadata";

export default async function robots(): Promise<MetadataRoute.Robots> {
  const platform = await getPlatformConfiguration();
  const rules = platform.seo?.robots;

  return {
    rules: {
      userAgent: "*",
      allow: rules?.allowPaths?.length ? rules.allowPaths : "/",
      disallow: rules?.disallowPaths?.length ? rules.disallowPaths : undefined,
    },
    sitemap: `${metadataBase.toString().replace(/\/$/, "")}/sitemap.xml`,
  };
}
