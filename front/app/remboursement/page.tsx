import { LegalPageView } from "@/components/static-pages/LegalPageView";
import { salesTermsContent } from "@/components/static-pages/legal-pages";
import { createMetadata } from "@/lib/metadata";

export const revalidate = 300;

export async function generateMetadata() {
  return createMetadata({
    title: "CGV et remboursements — Ticket",
    description: salesTermsContent.description,
    path: "/remboursement",
  });
}

export default async function RemboursementPage() {
  return <LegalPageView content={salesTermsContent} />;
}
