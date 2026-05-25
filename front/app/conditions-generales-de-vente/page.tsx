import { LegalPageView } from "@/components/static-pages/LegalPageView";
import { salesTermsContent } from "@/components/static-pages/legal-pages";
import { createMetadata } from "@/lib/metadata";

export const revalidate = 300;

export async function generateMetadata() {
  return createMetadata({
    title: "Conditions générales de vente — Ticket",
    description: salesTermsContent.description,
    path: "/conditions-generales-de-vente",
  });
}

export default function ConditionsGeneralesDeVentePage() {
  return <LegalPageView content={salesTermsContent} />;
}
