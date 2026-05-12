import { ManagedFrontPageRoute, getManagedPageMetadata } from "@/components/ManagedFrontPageRoute";

export const revalidate = 300;

export async function generateMetadata() {
  return getManagedPageMetadata("/faq", {
    title: "FAQ — Ticket",
    description: "Retrouvez les réponses essentielles sur les commandes, paiements, remboursements et le compte acheteur.",
  });
}

export default async function FaqPage() {
  return <ManagedFrontPageRoute path="/faq" />;
}
