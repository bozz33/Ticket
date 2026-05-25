import { LegalPageView } from "@/components/static-pages/LegalPageView";
import { usageTermsContent } from "@/components/static-pages/legal-pages";
import { createMetadata } from "@/lib/metadata";

export const revalidate = 300;

export async function generateMetadata() {
  return createMetadata({
    title: "Conditions générales d’utilisation — Ticket",
    description: usageTermsContent.description,
    path: "/conditions-generales-utilisation",
  });
}

export default function ConditionsGeneralesUtilisationPage() {
  return <LegalPageView content={usageTermsContent} />;
}
