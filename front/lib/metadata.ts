import type { Metadata } from "next";

import { buildPublicUrl } from "@/lib/utils";

const siteUrl = process.env.NEXT_PUBLIC_SITE_URL || "http://127.0.0.1:3000";

export const metadataBase = new URL(siteUrl);

export function createMetadata({
  title,
  description,
  path,
  image,
  imageAlt,
  keywords,
  canonicalUrl,
  robotsIndex,
  robotsFollow,
  maxImagePreview,
  ogTitle,
  ogDescription,
  ogType,
  twitterTitle,
  twitterDescription,
  twitterImage,
  twitterCard,
}: {
  title: string;
  description: string;
  path: string;
  image?: string;
  imageAlt?: string | null;
  keywords?: string[];
  canonicalUrl?: string | null;
  robotsIndex?: string | null;
  robotsFollow?: string | null;
  maxImagePreview?: string | null;
  ogTitle?: string | null;
  ogDescription?: string | null;
  ogType?: string | null;
  twitterTitle?: string | null;
  twitterDescription?: string | null;
  twitterImage?: string | null;
  twitterCard?: string | null;
}): Metadata {
  const url = buildPublicUrl(path);
  const normalizedRobotsIndex = robotsIndex === "noindex" ? false : true;
  const normalizedRobotsFollow = robotsFollow === "nofollow" ? false : true;
  const normalizedImagePreview = maxImagePreview === "none" || maxImagePreview === "standard" ? maxImagePreview : "large";
  const metadataImage = image
    ? [
        {
          url: image,
          alt: imageAlt ?? undefined,
        },
      ]
    : undefined;

  return {
    title,
    description,
    keywords: keywords && keywords.length > 0 ? keywords : undefined,
    robots: {
      index: normalizedRobotsIndex,
      follow: normalizedRobotsFollow,
      googleBot: {
        index: normalizedRobotsIndex,
        follow: normalizedRobotsFollow,
        "max-image-preview": normalizedImagePreview,
      },
    },
    alternates: {
      canonical: canonicalUrl || path,
    },
    openGraph: {
      title: ogTitle || title,
      description: ogDescription || description,
      url,
      siteName: "Ticket",
      type: ogType === "article" || ogType === "profile" || ogType === "book" ? ogType : "website",
      images: metadataImage,
    },
    twitter: {
      card: twitterCard === "summary" ? "summary" : "summary_large_image",
      title: twitterTitle || ogTitle || title,
      description: twitterDescription || ogDescription || description,
      images: twitterImage ? [twitterImage] : image ? [image] : undefined,
    },
  };
}

export function safeJsonLd(value?: string | null): string | null {
  if (!value?.trim()) {
    return null;
  }

  try {
    return JSON.stringify(JSON.parse(value)).replace(/</g, "\\u003c");
  } catch {
    return null;
  }
}
