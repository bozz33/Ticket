import type { PublicContent } from "@/lib/types";
import { formatDateRange } from "@/lib/utils";

import type { ModuleDefaultDetailContent } from "./types";

export function getStandDefaultContent(item: PublicContent): ModuleDefaultDetailContent {
  const dateLabel = formatDateRange(item);

  return {
    description:
      "Cette page présente un stand avec une trame permanente: visibilité, conditions d'occupation, règles de réservation et informations de contact restent stables jusqu'à modification depuis le panel super-admin.",
    sectionDescription: "Une base éditoriale permanente pour les stands, pensée pour la clarté commerciale.",
    program: [
      "Présentation du positionnement et des bénéfices de visibilité",
      "Conditions d'occupation et préparation logistique",
      "Confirmation centralisée avec reçu et pass associés",
    ],
    timeline: [
      { label: "Réservation ouverte", dateLabel: "Immédiat", description: "Les emplacements restent réservables tant que l'offre est active." },
      { label: "Mise en place", dateLabel, description: "Les informations pratiques suivent la date de présence annoncée sur la fiche." },
      { label: "Accès exposant", dateLabel: "Après confirmation", description: "Le panel acheteur centralise la commande, le reçu et le pass d'accès." },
    ],
    conditions: [
      "Chaque réservation est liée à un compte acheteur identifié.",
      "Les conditions d'installation et d'utilisation peuvent être précisées par l'organisateur.",
    ],
    requiredDocuments: [
      "Éléments d'identification ou de marque seulement si demandés par l'organisateur.",
      "Coordonnées valides pour la logistique et les confirmations.",
    ],
    faq: [
      { question: "Le stand est-il confirmé immédiatement ?", answer: "Oui, dès que la commande est validée côté plateforme." },
      { question: "Un pass est-il généré ?", answer: "Oui. Un pass d'accès est créé pour la réservation confirmée." },
    ],
    offerTitle: "Réservations et emplacements",
  };
}
