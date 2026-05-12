import { ManagedFrontPageRoute, getManagedPageMetadata } from "@/components/ManagedFrontPageRoute";

/* ================================================================
   Politique de remboursement
   URL : /remboursement
   ================================================================ */

export const revalidate = 300;

export async function generateMetadata() {
  return getManagedPageMetadata("/remboursement", {
    title: "Politique de remboursement — Ticket",
    description: "Conditions, délais et modalités de remboursement applicables sur la plateforme Ticket.",
  });
}

export default async function RemboursementPage() {
  return <ManagedFrontPageRoute path="/remboursement" />;
}
