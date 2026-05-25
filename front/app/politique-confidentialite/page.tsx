import { LegalPageView } from "@/components/static-pages/LegalPageView";
import { privacyPolicyContent } from "@/components/static-pages/legal-pages";
import { createMetadata } from "@/lib/metadata";

export const revalidate = 300;

export async function generateMetadata() {
  return createMetadata({
    title: "Politique de confidentialité — Ticket",
    description: privacyPolicyContent.description,
    path: "/politique-confidentialite",
  });
}

export default function PolitiqueConfidentialitePage() {
  return <LegalPageView content={privacyPolicyContent} />;
}
