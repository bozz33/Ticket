import type { Metadata, Viewport } from "next";
import type { ReactNode } from "react";

import { BackToTopButton } from "@/components/BackToTopButton";
import { Footer } from "@/components/Footer";
import { Header } from "@/components/Header";
import { ServiceWorkerRegister } from "@/components/ServiceWorkerRegister";
import { getPlatformConfiguration } from "@/lib/data/public";
import { metadataBase } from "@/lib/metadata";

import "./globals.css";

export const metadata: Metadata = {
  metadataBase,
  title: "Ticket",
  description: "Portail public unifie pour billetterie, formations, stands, appels a projets et crowdfunding.",
  applicationName: "Ticket",
  manifest: "/manifest.webmanifest",
  appleWebApp: {
    capable: true,
    statusBarStyle: "default",
    title: "Ticket",
  },
  icons: {
    icon: [
      { url: "/icon.svg", type: "image/svg+xml" },
      { url: "/icon-192.png", sizes: "192x192", type: "image/png" },
      { url: "/icon-512.png", sizes: "512x512", type: "image/png" },
    ],
    apple: [{ url: "/icon-192.png", sizes: "192x192", type: "image/png" }],
  },
  formatDetection: {
    email: false,
    address: false,
    telephone: false,
  },
};

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

  return (
    <html data-scroll-behavior="smooth" lang="fr">
      <body>
        <ServiceWorkerRegister />
        <Header platform={platform} />
        <main>{children}</main>
        <BackToTopButton />
        <Footer platform={platform} />
      </body>
    </html>
  );
}
