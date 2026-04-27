import type { Metadata } from "next";
import { Fraunces, Manrope } from "next/font/google";
import type { ReactNode } from "react";

import { Footer } from "@/components/Footer";
import { Header } from "@/components/Header";
import { getPlatformConfiguration } from "@/lib/data/public";
import { metadataBase } from "@/lib/metadata";

import "./globals.css";

const bodyFont = Manrope({
  subsets: ["latin"],
  variable: "--font-body",
});

const displayFont = Fraunces({
  subsets: ["latin"],
  variable: "--font-display",
});

export const metadata: Metadata = {
  metadataBase,
  title: "Ticket",
  description: "Portail public unifie pour billetterie, formations, stands, appels a projets et crowdfunding.",
};

export default async function RootLayout({
  children,
}: Readonly<{
  children: ReactNode;
}>) {
  const platform = await getPlatformConfiguration();

  return (
    <html data-scroll-behavior="smooth" lang="fr">
      <body className={`${bodyFont.variable} ${displayFont.variable}`}>
        <Header platform={platform} />
        <main>{children}</main>
        <Footer platform={platform} />
      </body>
    </html>
  );
}
