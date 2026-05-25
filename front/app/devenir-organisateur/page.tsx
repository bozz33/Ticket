import { OrganizerLandingPage } from "@/components/static-pages/OrganizerLandingPage";
import { createMetadata } from "@/lib/metadata";

export const revalidate = 300;

export async function generateMetadata() {
  return createMetadata({
    title: "Devenir organisateur — Ticket",
    description: "Créez votre espace organisateur Ticket pour publier, vendre, suivre vos commandes et contrôler les accès.",
    path: "/devenir-organisateur",
  });
}

export default async function BecomeOrganizerPage() {
  return <OrganizerLandingPage />;
}
