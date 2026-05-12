import { ManagedFrontPageRoute, getManagedPageMetadata } from "@/components/ManagedFrontPageRoute";

export const revalidate = 300;

export async function generateMetadata() {
  return getManagedPageMetadata("/contact", {
    title: "Contact & Support — Ticket",
    description: "Contactez l'équipe Ticket : support, partenariats et questions organisateurs.",
  });
}

export default async function ContactPage() {
  return <ManagedFrontPageRoute path="/contact" />;
}
