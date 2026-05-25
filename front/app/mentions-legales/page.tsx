import { LegalNoticePageView } from "@/components/static-pages/LegalPageView";
import { legalNoticeContent } from "@/components/static-pages/legal-pages";
import { createMetadata } from "@/lib/metadata";

export const revalidate = 300;

export async function generateMetadata() {
  return createMetadata({
    title: "Mentions légales — Ticket",
    description: legalNoticeContent.description,
    path: "/mentions-legales",
  });
}

export default async function MentionsLegalesPage() {
  return <LegalNoticePageView content={legalNoticeContent} />;
}
