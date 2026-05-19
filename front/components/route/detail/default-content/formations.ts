import type { PublicContent } from "@/lib/types";
import { formatDateRange } from "@/lib/utils";

import type { ModuleDefaultDetailContent } from "./types";

export function getFormationDefaultContent(item: PublicContent): ModuleDefaultDetailContent {
  const dateLabel = formatDateRange(item);

  return {
    description:
      "Cette page présente une formation avec un cadre stable: objectifs, déroulé, conditions d'accès et informations pratiques restent homogènes tant qu'aucune personnalisation spécifique n'est faite côté super-admin.",
    sectionDescription: "Une base éditoriale permanente pour les formations, avec un rendu sobre et durable.",
    program: [
      "Présentation du programme et des objectifs pédagogiques",
      "Informations pratiques sur le format, la durée et le niveau attendu",
      "Accès à la confirmation, au reçu et au pass après validation",
    ],
    timeline: [
      { label: "Ouverture des inscriptions", dateLabel: "Immédiat", description: "Les réservations restent disponibles tant que l'offre est active." },
      { label: "Session planifiée", dateLabel, description: "La session suit les dates publiées sur la fiche formation." },
      { label: "Accès participant", dateLabel: "Après confirmation", description: "Chaque inscrit retrouve son reçu et son pass dans son panel acheteur." },
    ],
    conditions: [
      "Une seule commande valide ouvre l'accès à l'inscription correspondante.",
      "Les informations du profil acheteur servent de référence pour la commande et le reçu.",
    ],
    requiredDocuments: [
      "Justificatif complémentaire uniquement si demandé par l'organisateur.",
      "Coordonnées acheteur à jour pour les confirmations et rappels.",
    ],
    faq: [
      { question: "L'inscription est-elle nominative ?", answer: "Oui. Les confirmations, reçus et passes restent rattachés au compte acheteur connecté." },
      { question: "Le reçu est-il disponible après paiement ?", answer: "Oui. Il est consultable, imprimable et téléchargeable depuis le panel acheteur." },
    ],
    offerTitle: "Formules, inscriptions ou packs",
  };
}
