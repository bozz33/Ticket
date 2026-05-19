import type { ModuleDefaultDetailContent } from "./types";

export function getCrowdfundingDefaultContent(): ModuleDefaultDetailContent {
  return {
    description:
      "Cette page présente une campagne avec des informations durables par défaut: objectif, logique de contribution, suivi de campagne et conditions de soutien restent stables tant qu'aucune mise à jour n'est faite côté super-admin.",
    sectionDescription: "Une base éditoriale permanente pour les campagnes et leurs parcours de contribution.",
    program: [
      "Présentation du projet et de son objectif de campagne",
      "Lecture des niveaux de contribution ou options de soutien",
      "Suivi du reçu et de la confirmation dans le panel acheteur",
    ],
    timeline: [
      { label: "Campagne en cours", dateLabel: "Ouvert", description: "Les contributions restent disponibles tant que la campagne est active." },
      { label: "Objectif et progression", dateLabel: "Suivi en temps réel", description: "La progression publique reste visible sur la fiche campagne." },
      { label: "Confirmation", dateLabel: "Après validation", description: "Chaque contribution confirmée génère un reçu et un suivi centralisé." },
    ],
    conditions: [
      "Chaque soutien confirmé reste attaché au compte acheteur utilisé.",
      "Les modalités précises peuvent être détaillées par l'organisateur si nécessaire.",
    ],
    requiredDocuments: ["Aucun document supplémentaire par défaut pour soutenir une campagne."],
    faq: [
      { question: "Le soutien génère-t-il un reçu ?", answer: "Oui, un reçu est généré pour chaque contribution confirmée." },
      { question: "Un pass est-il systématique ?", answer: "Non. Selon le type de campagne, la plateforme peut générer un pass d'achat générique ou seulement un reçu." },
    ],
    offerTitle: "Paliers et contributions",
  };
}
