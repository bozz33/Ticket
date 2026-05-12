import { ManagedFrontPageRoute, getManagedPageMetadata } from "@/components/ManagedFrontPageRoute";

export const revalidate = 300;

export async function generateMetadata() {
  return getManagedPageMetadata("/mentions-legales", {
    title: "Mentions légales — Ticket",
    description: "Mentions légales de la plateforme Ticket : éditeur, hébergeur, propriété intellectuelle et données personnelles.",
  });
}

export default async function MentionsLegalesPage() {
  return <ManagedFrontPageRoute path="/mentions-legales" />;
}
