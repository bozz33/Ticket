import type { MetadataRoute } from "next";

import { metadataBase } from "@/lib/metadata";

export default function robots(): MetadataRoute.Robots {
  return {
    rules: {
      userAgent: "*",
      allow: "/",
    },
    sitemap: `${metadataBase.toString().replace(/\/$/, "")}/sitemap.xml`,
  };
}
