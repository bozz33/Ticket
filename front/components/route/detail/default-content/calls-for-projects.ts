import type { PublicContent } from "@/lib/types";
import { formatDateLabel } from "@/lib/utils";

import type { ModuleDefaultDetailContent } from "./types";

export function getCallForProjectsDefaultContent(item: PublicContent): ModuleDefaultDetailContent {
  return {
    description:
      "Cette page garde un socle permanent pour les appels à projets: objectifs, cadre de soumission, documents attendus et logique de traitement restent lisibles et homogènes, sauf personnalisation par le super-admin.",
    sectionDescription: "Une base éditoriale permanente pour cadrer les candidatures et éviter les pages trop fragiles.",
    program: [
      "Lecture du cadre de candidature et des conditions générales",
      "Préparation des pièces demandées et des informations du porteur",
      "Soumission depuis le formulaire public relié au tenant organisateur",
    ],
    timeline: [
      { label: "Candidatures ouvertes", dateLabel: item.applicationOpensAt ? formatDateLabel(item.applicationOpensAt) : "Ouvert", description: "Le formulaire public reste disponible pendant la période de soumission." },
      { label: "Clôture", dateLabel: item.deadlineAt ? formatDateLabel(item.deadlineAt) : "À confirmer", description: "Aucune nouvelle soumission n'est acceptée après la date limite." },
      { label: "Instruction", dateLabel: "Après soumission", description: "Les candidatures restent visibles dans le panel organisateur du tenant concerné." },
    ],
    conditions: [
      "Le dossier doit être soumis depuis le formulaire public prévu pour l'appel.",
      "Les informations d'identité et de contact doivent être cohérentes et exploitables.",
    ],
    requiredDocuments: [
      "Pièces listées dans le formulaire public si elles sont requises.",
      "Justificatifs additionnels seulement si l'organisateur les demande.",
    ],
    faq: [
      { question: "Faut-il être connecté pour candidater ?", answer: "Le formulaire public peut être ouvert au public, mais la plateforme peut imposer un compte acheteur prêt pour les actions sensibles." },
      { question: "La soumission remonte-t-elle chez l'organisateur ?", answer: "Oui. Les candidatures sont visibles dans le panel organisateur du tenant qui a publié l'appel." },
    ],
    offerTitle: "Candidatures et options",
  };
}
