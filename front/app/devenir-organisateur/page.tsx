import { ManagedFrontPageRoute, getManagedPageMetadata } from "@/components/ManagedFrontPageRoute";

export const revalidate = 300;

export async function generateMetadata() {
  return getManagedPageMetadata("/devenir-organisateur", {
    title: "Devenir organisateur — Ticket",
    description: "Publiez et vendez sur la plateforme Ticket avec un backoffice tenant et un front public unifié.",
  });
}

export default async function BecomeOrganizerPage() {
  return <ManagedFrontPageRoute path="/devenir-organisateur" />;
}
