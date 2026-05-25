import type { Metadata, Viewport } from "next";
import { cookies } from "next/headers";
import type { ReactNode } from "react";

import { BackToTopButton } from "@/components/BackToTopButton";
import { Footer } from "@/components/Footer";
import { Header } from "@/components/Header";
import { ObservabilityReporter } from "@/components/ObservabilityReporter";
import { ServiceWorkerRegister } from "@/components/ServiceWorkerRegister";
import { getPlatformConfiguration } from "@/lib/data/public";
import { PUBLIC_LOCALE_COOKIE, resolveSupportedLocale } from "@/lib/i18n/public-translations";
import { createMetadata, metadataBase, safeJsonLd } from "@/lib/metadata";

import "./globals.css";

export async function generateMetadata(): Promise<Metadata> {
  const platform = await getPlatformConfiguration();
  const faviconUrl = platform.faviconUrl || "/icon-192.png";
  const appleTouchIconUrl = platform.appleTouchIconUrl || faviconUrl;
  const baseMetadata = createMetadata({
    title: platform.seo?.defaultTitle || platform.brandName || "Ticket",
    description:
      platform.seo?.defaultDescription || "Portail public unifie pour billetterie, reservations, paiements et contenus multi-modules.",
    path: "/",
    image: platform.seo?.openGraph?.imageUrl || undefined,
    imageAlt: platform.seo?.openGraph?.imageAlt,
    keywords: platform.seo?.keywords,
    canonicalUrl: platform.seo?.canonicalUrl,
    robotsIndex: platform.seo?.robots?.index,
    robotsFollow: platform.seo?.robots?.follow,
    maxImagePreview: platform.seo?.robots?.maxImagePreview,
    ogTitle: platform.seo?.openGraph?.title,
    ogDescription: platform.seo?.openGraph?.description,
    ogType: platform.seo?.openGraph?.type,
    twitterTitle: platform.seo?.twitter?.title,
    twitterDescription: platform.seo?.twitter?.description,
    twitterImage: platform.seo?.twitter?.imageUrl,
    twitterCard: platform.seo?.twitter?.card,
  });

  return {
    ...baseMetadata,
    metadataBase,
    applicationName: platform.brandName || "Ticket",
    manifest: "/manifest.webmanifest",
    appleWebApp: {
      capable: true,
      statusBarStyle: "default",
      title: platform.brandName || "Ticket",
    },
    icons: {
      icon: [
        { url: "/icon.svg", type: "image/svg+xml" },
        { url: faviconUrl, sizes: "192x192", type: "image/png" },
        { url: faviconUrl, sizes: "512x512", type: "image/png" },
      ],
      apple: [{ url: appleTouchIconUrl, sizes: "180x180", type: "image/png" }],
    },
    formatDetection: {
      email: false,
      address: false,
      telephone: false,
    },
  };
}

export const viewport: Viewport = {
  width: "device-width",
  initialScale: 1,
  viewportFit: "cover",
  themeColor: "#d39a36",
};

export default async function RootLayout({
  children,
}: Readonly<{
  children: ReactNode;
}>) {
  const platform = await getPlatformConfiguration();
  const cookieStore = await cookies();
  const locale = resolveSupportedLocale(platform, cookieStore.get(PUBLIC_LOCALE_COOKIE)?.value);
  const structuredData = safeJsonLd(platform.seo?.structuredDataJson);

  return (
    <html data-scroll-behavior="smooth" lang={locale}>
      <body>
        {structuredData ? <script dangerouslySetInnerHTML={{ __html: structuredData }} type="application/ld+json" /> : null}
        <ObservabilityReporter />
        <ServiceWorkerRegister />
        <Header locale={locale} platform={platform} />
        <main>{children}</main>
        <BackToTopButton />
        <Footer locale={locale} platform={platform} />
      </body>
    </html>
  );
}
