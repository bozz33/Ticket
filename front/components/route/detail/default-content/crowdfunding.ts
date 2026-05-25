import type { ModuleDefaultDetailContent } from "./types";

export function getCrowdfundingDefaultContent(): ModuleDefaultDetailContent {
  return {
    description:
      "Cette page présente une campagne avec des informations durables par défaut: objectif, logique de contribution, suivi de campagne et conditions de soutien restent stables tant qu'aucune mise à jour n'est faite côté super-admin.",
    sectionDescription: "Une base éditoriale permanente pour les campagnes et leurs parcours de contribution.",
    program: [
      "Présentation du projet et de son objectif de campagne",
      "Choix d'un palier ou saisie d'un montant libre",
      "Paiement public sans obligation de connexion acheteur",
    ],
    timeline: [
      { label: "Campagne en cours", dateLabel: "Ouvert", description: "Les contributions restent disponibles tant que la campagne est active." },
      { label: "Objectif et progression", dateLabel: "Suivi en temps réel", description: "La progression publique reste visible sur la fiche campagne." },
      { label: "Confirmation", dateLabel: "Après validation", description: "Chaque contribution confirmée met à jour la progression de la campagne." },
    ],
    conditions: [
      "Chaque soutien confirmé est enregistré comme contribution de campagne.",
      "Les modalités précises peuvent être détaillées par l'organisateur si nécessaire.",
    ],
    requiredDocuments: ["Aucun document supplémentaire par défaut pour soutenir une campagne."],
    faq: [
      { question: "Le soutien nécessite-t-il un compte ?", answer: "Non, une contribution peut être réglée sans compte acheteur." },
      { question: "Un pass est-il généré ?", answer: "Non, une contribution crowdfunding n'ouvre pas de pass d'accès." },
    ],
    offerTitle: "Paliers et contributions",
  };
}
