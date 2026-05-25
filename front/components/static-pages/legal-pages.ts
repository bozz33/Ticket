export type LegalSection = {
  id: string;
  title: string;
  body?: string;
  items?: Array<{ label?: string; value?: string; title?: string; body?: string }>;
};

export type LegalRelatedLink = {
  href: string;
  title: string;
  body: string;
};

export type LegalPageContent = {
  eyebrow: string;
  title: string;
  description: string;
  updatedAt: string;
  sections: LegalSection[];
  relatedLinks?: LegalRelatedLink[];
};

export const privacyPolicyContent: LegalPageContent = {
  eyebrow: "Données personnelles",
  title: "Politique de confidentialité",
  description: "Cette page explique comment Ticket collecte, utilise, protège et conserve les données nécessaires aux achats, réservations et espaces organisateurs.",
  updatedAt: "Dernière mise à jour : mai 2026",
  sections: [
    {
      id: "responsable",
      title: "Responsable du traitement",
      body: "Ticket agit comme plateforme technique pour la publication, la réservation, le paiement, la vérification des reçus et le suivi des comptes utilisateurs.",
      items: [
        { label: "Contact", value: "support@ticket.africa" },
        { label: "Support", value: "+225 27 22 40 11 00" },
      ],
    },
    {
      id: "donnees",
      title: "Données traitées",
      body: "Les données traitées sont limitées aux informations utiles au service : identité, coordonnées, compte, historique de commandes, reçus, passes, notifications, demandes de remboursement et journaux techniques de sécurité.",
    },
    {
      id: "finalites",
      title: "Finalités",
      items: [
        { title: "Exécuter les commandes", body: "Créer les réservations, paiements, reçus, QR codes, passes et justificatifs liés aux achats." },
        { title: "Sécuriser la plateforme", body: "Prévenir la fraude, contrôler les accès, protéger les sessions et auditer les opérations sensibles." },
        { title: "Assister les utilisateurs", body: "Répondre aux demandes de support, remboursement, vérification de paiement et récupération de compte." },
      ],
    },
    {
      id: "conservation",
      title: "Conservation",
      body: "Les données de compte sont conservées tant que le compte est actif. Les données de transaction peuvent être conservées plus longtemps lorsque des obligations comptables, fiscales, de preuve ou de lutte contre la fraude l’exigent.",
    },
    {
      id: "droits",
      title: "Vos droits",
      body: "Vous pouvez demander l’accès, la rectification, la limitation, l’opposition ou la suppression de vos données lorsque la réglementation applicable le permet. Une demande peut être adressée au support Ticket.",
    },
    {
      id: "securite",
      title: "Sécurité",
      body: "Ticket applique des contrôles d’accès, une séparation stricte des espaces organisateurs, des jetons d’authentification, des vérifications serveur et des mesures de journalisation pour protéger les opérations sensibles.",
    },
  ],
};

export const salesTermsContent: LegalPageContent = {
  eyebrow: "Vente et remboursement",
  title: "Conditions générales de vente et remboursement",
  description: "Ces conditions encadrent les achats, réservations, paiements, reçus, passes, contributions, demandes de remboursement et responsabilités liées aux services Ticket.",
  updatedAt: "Dernière mise à jour : mai 2026",
  sections: [
    {
      id: "introduction",
      title: "Introduction",
      body: "Les présentes conditions générales de vente et de remboursement définissent les règles applicables aux transactions réalisées sur la plateforme Ticket. Elles s'appliquent aux visiteurs, acheteurs, participants, contributeurs, organisateurs et, plus généralement, à toute personne utilisant la plateforme pour réserver, acheter, contribuer ou gérer un contenu public.",
    },
    {
      id: "definitions",
      title: "Définitions",
      items: [
        { title: "Plateforme", body: "Le site, les interfaces, les API et les outils Ticket permettant de publier, vendre, réserver, payer, contrôler et suivre des contenus publics." },
        { title: "Organisateur", body: "La personne physique ou morale qui publie un événement, une formation, un stand, un appel à projets, une campagne ou une offre assimilée." },
        { title: "Acheteur ou participant", body: "Toute personne qui réserve, achète, reçoit un pass, dépose une candidature ou participe à une contribution via Ticket." },
        { title: "Ticket, pass ou reçu", body: "Le justificatif numérique lié à une commande, pouvant contenir une référence, un statut, un QR code et des informations de vérification." },
      ],
    },
    {
      id: "objet",
      title: "Objet du contrat",
      body: "Ticket met à disposition une infrastructure technique permettant aux organisateurs de publier des contenus et aux utilisateurs de réserver ou payer des billets d'événements, inscriptions de formation, stands, candidatures, contributions ou services assimilés. Ticket agit comme intermédiaire technique et outil de gestion, sauf mention contraire prévue dans un contrat spécifique.",
    },
    {
      id: "acceptation",
      title: "Acceptation des conditions",
      body: "Toute commande, réservation gratuite, contribution, demande de candidature ou utilisation d'un service payant entraîne l'acceptation pleine et entière des présentes conditions. L'utilisateur confirme avoir pris connaissance du prix, des frais, des conditions d'accès, des restrictions éventuelles et des règles de remboursement avant validation.",
    },
    {
      id: "services",
      title: "Services proposés",
      items: [
        { title: "Billetterie d'événements", body: "Les tickets donnent accès à un événement publié par un organisateur, dans la limite des stocks, catégories, dates et conditions d'accès indiqués sur la page publique." },
        { title: "Formations et inscriptions", body: "Les inscriptions peuvent être gratuites ou payantes et peuvent donner lieu à un pass, une confirmation, une attestation ou un reçu selon la configuration du contenu." },
        { title: "Stands et réservations", body: "Les offres de stands permettent de réserver un emplacement ou un service associé à un salon, marché, foire ou événement professionnel." },
        { title: "Appels à projets", body: "Les candidatures peuvent être gratuites ou payantes. Les documents, délais, critères et modalités de sélection restent définis par l'organisateur." },
        { title: "Contributions et crowdfunding", body: "Les contributions correspondent à un soutien volontaire ou à une participation financière à une campagne. Elles ne créent pas nécessairement un droit d'accès à un événement." },
      ],
    },
    {
      id: "organisateur",
      title: "Rôle et responsabilité de l'organisateur",
      body: "L'organisateur reste seul responsable des informations publiées, des prix, des capacités, de la disponibilité des offres, du déroulement des événements, de l'accueil du public, des conditions de sécurité, des autorisations administratives, des reports, annulations et engagements pris envers les participants.",
    },
    {
      id: "securisation",
      title: "Sécurisation et conformité",
      items: [
        { title: "Vérifications", body: "Ticket peut vérifier l'identité d'un organisateur, demander des pièces complémentaires, contrôler une campagne ou suspendre une publication lorsqu'un risque de fraude, de non-conformité ou d'abus est identifié." },
        { title: "Traçabilité", body: "Les transactions, reçus, passes, demandes de remboursement, connexions et actions sensibles peuvent être journalisés afin d'assurer la preuve, la sécurité et la conformité." },
        { title: "Paiements sécurisés", body: "Les paiements sont traités via des partenaires spécialisés. Ticket ne conserve pas directement les données complètes de carte bancaire." },
      ],
    },
    {
      id: "zone",
      title: "Zone géographique, devise et horaires",
      body: "Les services peuvent être accessibles dans plusieurs pays selon les organisateurs, devises, moyens de paiement et règles locales disponibles. Les dates, horaires et fuseaux affichés doivent être vérifiés par l'utilisateur avant validation, l'heure officielle restant celle communiquée par l'organisateur sur la fiche concernée.",
    },
    {
      id: "prix",
      title: "Prix et frais",
      body: "Les prix des offres sont fixés par l'organisateur. Des frais de service, de paiement, de plateforme ou de traitement peuvent être ajoutés selon le moyen de paiement, le pays, le type de contenu ou le contrat organisateur. Le montant total à payer est affiché avant validation. Une modification de prix ne s'applique pas aux commandes déjà confirmées.",
    },
    {
      id: "commission",
      title: "Commissions et reversements organisateur",
      body: "Ticket peut percevoir une commission ou des frais techniques en contrepartie de la mise à disposition de la plateforme, des outils de paiement, de contrôle, de suivi et de support. Les fonds collectés pour le compte d'un organisateur peuvent être reversés après déduction des frais applicables, sous réserve des vérifications nécessaires, des délais bancaires et des conditions du contrat organisateur.",
    },
    {
      id: "commande",
      title: "Commande et confirmation",
      body: "L'utilisateur sélectionne une offre, indique la quantité souhaitée, renseigne les informations demandées puis valide la commande ou la réservation. Une commande payante est confirmée après validation effective du paiement. Une réservation gratuite est confirmée après acceptation serveur. La confirmation électronique constitue la preuve de la transaction.",
    },
    {
      id: "paiement",
      title: "Modalités de paiement",
      body: "Les paiements peuvent être réalisés par carte bancaire, mobile money ou tout autre moyen proposé au moment de la commande. Les moyens disponibles varient selon le pays, le partenaire financier et le type d'offre. En cas d'échec, d'expiration, de rejet ou d'abandon du paiement, la commande peut rester en attente ou être annulée automatiquement.",
    },
    {
      id: "livraison",
      title: "Mise à disposition des reçus, tickets et passes",
      body: "Après confirmation, l'utilisateur peut recevoir un reçu, un ticket, un pass, une confirmation de contribution ou un justificatif de candidature. Ces documents peuvent être accessibles depuis le compte acheteur, par lien de vérification ou par notification. L'utilisateur doit conserver ses références et vérifier l'exactitude de ses coordonnées.",
    },
    {
      id: "qr",
      title: "QR codes, contrôle et validité",
      body: "Les tickets, passes et reçus peuvent comporter un QR code unique. Chaque QR code est lié à une commande, un pass ou une vérification précise. Toute reproduction, altération, revente abusive, falsification ou tentative d'utilisation multiple peut entraîner un refus d'accès, une annulation de pass ou des vérifications complémentaires.",
    },
    {
      id: "annulation-client",
      title: "Annulation demandée par l'utilisateur",
      body: "Une annulation demandée par l'utilisateur n'est possible que si la politique de l'organisateur, le type d'offre, le délai restant avant le début du service et le statut de la commande le permettent. Lorsque l'offre est personnalisée, datée, consommée, déjà contrôlée ou explicitement non remboursable, la demande peut être refusée.",
    },
    {
      id: "annulation-organisateur",
      title: "Annulation, report ou modification par l'organisateur",
      body: "En cas d'annulation, de report ou de modification substantielle d'un événement ou service, Ticket applique les informations transmises par l'organisateur et les règles affichées sur la plateforme. Les utilisateurs peuvent recevoir un remboursement, un avoir, un report de pass ou une information de suivi selon le cas.",
    },
    {
      id: "remboursements",
      title: "Règles de remboursement",
      items: [
        { title: "Demande depuis le compte", body: "L'acheteur peut déposer une demande depuis son panel lorsque la commande est éligible. Il doit indiquer le motif et fournir les éléments utiles à l'instruction." },
        { title: "Instruction", body: "La demande est analysée selon le statut de la commande, la date de l'événement, la politique organisateur, le moyen de paiement, les contrôles déjà effectués et les informations fournies." },
        { title: "Montant remboursé", body: "Le remboursement peut être total ou partiel. Les frais techniques, frais de paiement, commissions de prestataire ou frais déjà consommés peuvent être exclus sauf incident technique, doublon avéré ou décision contraire." },
        { title: "Support de remboursement", body: "Selon la configuration, le remboursement peut être effectué vers le moyen de paiement initial, sous forme d'avoir, de crédit portefeuille ou par une procédure manuelle validée par le support." },
        { title: "Délais", body: "Les délais dépendent du moyen de paiement, des partenaires financiers, du pays, des contrôles de conformité et du volume de demandes à traiter." },
      ],
    },
    {
      id: "contributions",
      title: "Cotisations, contributions et crowdfunding",
      body: "Les contributions volontaires, dons, cotisations ou soutiens à une campagne ne sont pas automatiquement remboursables après paiement. Lorsque la campagne est annulée, frauduleuse, non conforme ou suspendue par décision de Ticket, de l'organisateur ou d'une autorité compétente, un remboursement ou une mesure de compensation peut être étudié.",
    },
    {
      id: "retractation",
      title: "Droit de rétractation et exceptions",
      body: "Pour les services datés, billets de spectacle, activités de loisirs, événements, formations programmées ou prestations exécutées à une date déterminée, le droit de rétractation peut être exclu ou limité par la réglementation applicable. Lorsque le droit de rétractation s'applique, ses conditions sont précisées avant la validation de la commande.",
    },
    {
      id: "preuve",
      title: "Preuve, facturation et reçus",
      body: "Les références de commande, reçus, journaux de transaction, confirmations de paiement, notifications et statuts enregistrés par Ticket constituent des éléments de preuve. L'utilisateur peut accéder à ses reçus depuis son compte lorsque la commande est rattachée à une identité vérifiable.",
    },
    {
      id: "fraude",
      title: "Fraude, abus et suspension",
      body: "Ticket peut refuser, suspendre ou annuler une transaction lorsqu'un risque de fraude, de doublon abusif, d'utilisation illicite, de revente non autorisée, de contournement du paiement ou d'atteinte au service est détecté. Des justificatifs peuvent être demandés avant toute régularisation.",
    },
    {
      id: "responsabilite",
      title: "Responsabilité",
      body: "Ticket fournit une plateforme technique et ne se substitue pas à l'organisateur pour l'exécution matérielle de l'événement ou du service. Ticket ne peut être tenu responsable des informations erronées fournies par l'organisateur, des changements de programme, incidents sur site, conditions d'accès, décisions de sécurité, indisponibilités indépendantes de sa volonté ou cas de force majeure. La responsabilité éventuelle de Ticket est limitée au montant effectivement payé pour la transaction concernée, sauf disposition impérative contraire.",
    },
    {
      id: "donnees",
      title: "Données personnelles",
      body: "Les données nécessaires à la commande, au paiement, à la vérification, au remboursement, au support et à la lutte contre la fraude sont traitées conformément à la politique de confidentialité de Ticket. Certaines informations peuvent être communiquées à l'organisateur ou aux prestataires de paiement lorsque cela est nécessaire à l'exécution du service.",
    },
    {
      id: "support",
      title: "Réclamations et service client",
      body: "Toute question relative à une commande, un reçu, un pass, une contribution, une candidature ou un remboursement doit être adressée au support Ticket avec la référence concernée, le nom utilisé lors de la commande, le moyen de paiement et les éléments utiles à l'analyse.",
    },
    {
      id: "litiges",
      title: "Droit applicable et litiges",
      body: "Les présentes conditions sont interprétées selon le droit applicable à l'entité exploitant la plateforme et aux opérations concernées. Avant toute action contentieuse, l'utilisateur, l'organisateur et Ticket s'efforcent de rechercher une solution amiable. Les juridictions compétentes sont déterminées selon les règles légales et contractuelles applicables.",
    },
    {
      id: "dispositions",
      title: "Dispositions finales",
      body: "Si une clause est déclarée nulle ou inapplicable, les autres clauses restent valables. Le fait pour Ticket de ne pas appliquer immédiatement une clause ne constitue pas une renonciation. Ticket peut mettre à jour les présentes conditions afin de tenir compte des évolutions légales, techniques, financières ou opérationnelles.",
    },
    {
      id: "contact",
      title: "Contact",
      items: [
        { label: "Support", value: "support@ticket.africa" },
        { label: "Téléphone", value: "+225 27 22 40 11 00" },
        { label: "Objet conseillé", value: "Référence commande, reçu, pass, événement ou campagne concernée" },
      ],
    },
  ],
};

export const usageTermsContent: LegalPageContent = {
  eyebrow: "Règles d’utilisation",
  title: "Conditions générales d’utilisation",
  description: "Ces règles définissent les conditions d’accès aux services Ticket pour les visiteurs, acheteurs et organisateurs.",
  updatedAt: "Dernière mise à jour : mai 2026",
  sections: [
    {
      id: "acces",
      title: "Accès au service",
      body: "La consultation du catalogue est ouverte au public. Certaines actions, comme l’achat, le suivi de commande ou la gestion organisateur, nécessitent un compte ou une session sécurisée.",
    },
    {
      id: "compte",
      title: "Compte utilisateur",
      body: "L’utilisateur doit fournir des informations exactes, maintenir la confidentialité de ses identifiants et signaler toute utilisation non autorisée de son compte.",
    },
    {
      id: "organisateurs",
      title: "Espace organisateur",
      body: "L’organisateur reste responsable des informations publiées, des capacités, des prix, des conditions d’accès, du déroulement de ses contenus et de la conformité de son activité.",
    },
    {
      id: "interdictions",
      title: "Usages interdits",
      items: [
        { title: "Fraude", body: "Tentative de duplication de billet, contournement du paiement, usurpation ou usage d’un QR code non autorisé." },
        { title: "Atteinte au service", body: "Action visant à perturber la plateforme, contourner les limites techniques ou accéder à des données qui ne vous appartiennent pas." },
        { title: "Contenu illicite", body: "Publication de contenus trompeurs, illicites, discriminatoires, dangereux ou portant atteinte aux droits de tiers." },
      ],
    },
    {
      id: "disponibilite",
      title: "Disponibilité",
      body: "Ticket s’efforce d’assurer un service stable, mais peut procéder à des maintenances, corrections ou restrictions temporaires pour des raisons techniques, réglementaires ou de sécurité.",
    },
    {
      id: "evolution",
      title: "Évolution des conditions",
      body: "Les présentes conditions peuvent être mises à jour. Les utilisateurs sont invités à les consulter régulièrement.",
    },
  ],
};

export const legalNoticeContent: LegalPageContent = {
  eyebrow: "Informations légales",
  title: "Mentions légales",
  description: "Retrouvez les informations d'identification, les règles de publication et les documents contractuels associés à la plateforme Ticket.",
  updatedAt: "Dernière mise à jour : mai 2026",
  relatedLinks: [
    {
      href: "/politique-confidentialite",
      title: "Politique de confidentialité",
      body: "Traitement des données personnelles, droits des utilisateurs et mesures de sécurité.",
    },
    {
      href: "/conditions-generales-de-vente",
      title: "Conditions générales de vente",
      body: "Transactions, paiements, reçus, remboursements et responsabilités liées aux achats.",
    },
    {
      href: "/conditions-generales-utilisation",
      title: "Conditions générales d'utilisation",
      body: "Règles d'accès à la plateforme, comptes utilisateurs, organisateurs et usages interdits.",
    },
  ],
  sections: [
    {
      id: "introduction",
      title: "Introduction",
      body: "Ticket est une startup numérique légalement structurée autour d'une plateforme de billetterie, réservation, paiement, vérification de reçus et gestion de contenus publics. Cette page présente les informations essentielles permettant d'identifier l'éditeur de la plateforme et d'accéder aux documents contractuels applicables.",
    },
    {
      id: "editeur",
      title: "Éditeur de la plateforme",
      items: [
        { label: "Nom commercial", value: "Ticket" },
        { label: "Statut", value: "Startup / entreprise numérique exploitant une plateforme SaaS de billetterie et de réservation" },
        { label: "Activité", value: "Plateforme de billetterie, réservations, paiements et gestion de contenus publics" },
        { label: "Contact", value: "support@ticket.africa" },
        { label: "Téléphone", value: "+225 27 22 40 11 00" },
      ],
    },
    {
      id: "propriete",
      title: "Propriété intellectuelle",
      body: "Les interfaces, textes, marques, visuels, données structurées et éléments techniques de Ticket sont protégés. Toute reproduction non autorisée est interdite.",
    },
    {
      id: "publication",
      title: "Publication et responsabilités",
      body: "Les organisateurs restent responsables des contenus qu'ils publient, des informations relatives aux événements, formations, stands, campagnes ou appels à projets, ainsi que du respect des lois, autorisations et obligations applicables à leurs activités.",
    },
    {
      id: "signalement",
      title: "Signalement et contact",
      body: "Toute demande relative à un contenu, une commande, une donnée personnelle, une propriété intellectuelle ou une réclamation peut être adressée au support Ticket avec les références utiles au traitement de la demande.",
    },
  ],
};
