import { OrganizerProfile, PlatformConfiguration, PublicContent } from "@/lib/types";

export const mockPlatformConfiguration: PlatformConfiguration = {
  brandName: process.env.NEXT_PUBLIC_PLATFORM_NAME || "Ticket",
  supportEmail: "support@ticket.africa",
  supportPhone: "+225 27 22 40 11 00",
  currencyCode: "XOF",
  accountUrl: process.env.NEXT_PUBLIC_ACCOUNT_URL || "http://127.0.0.1:8000/user",
  organizerCtaUrl:
    process.env.NEXT_PUBLIC_ORGANIZER_CTA_URL || "http://127.0.0.1:8000/tenant/register",
  reassuranceItems: [
    "Paiement securise et verification serveur",
    "Billets et confirmations centralises",
    "Parcours mobile optimise jusqu'au checkout",
  ],
  paymentMethods: ["Carte bancaire", "Mobile Money", "Paystack"],
  featureFlags: ["public_catalog", "checkout", "organizer_profiles"],
};

export const mockOrganizers: OrganizerProfile[] = [
  {
    slug: "lagoon-live",
    name: "Lagoon Live",
    legalName: "Lagoon Live Events",
    tagline: "Experiences culturelles et concerts premium en Afrique de l'Ouest.",
    description:
      "Lagoon Live produit des concerts, conferences et formats immersifs avec une execution orientée public, image et conversion.",
    city: "Abidjan",
    country: "Cote d'Ivoire",
    verified: true,
    followers: 18420,
    accentColor: "#d98c2b",
    logoUrl:
      "https://images.unsplash.com/photo-1614680376573-df3480f0c6ff?auto=format&fit=crop&w=320&q=80",
    bannerUrl:
      "https://images.unsplash.com/photo-1503095396549-807759245b35?auto=format&fit=crop&w=1600&q=80",
    websiteUrl: "https://example.com/lagoon-live",
    supportEmail: "hello@lagoonlive.africa",
    supportPhone: "+225 07 08 09 10 11",
    socialLinks: [
      { label: "Instagram", url: "https://instagram.com" },
      { label: "Facebook", url: "https://facebook.com" },
      { label: "LinkedIn", url: "https://linkedin.com" },
    ],
  },
  {
    slug: "atelier-neo",
    name: "Atelier Neo",
    legalName: "Atelier Neo Learning",
    tagline: "Formations, bootcamps et programmes certifiants pour equipes et createurs.",
    description:
      "Atelier Neo conçoit des formations tres operationnelles autour de la production evenementielle, du marketing et de la monetisation.",
    city: "Dakar",
    country: "Senegal",
    verified: true,
    followers: 9630,
    accentColor: "#0f8b8d",
    logoUrl:
      "https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=320&q=80",
    bannerUrl:
      "https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=1600&q=80",
    websiteUrl: "https://example.com/atelier-neo",
    supportEmail: "academy@atelierneo.africa",
    supportPhone: "+221 33 400 10 10",
    socialLinks: [
      { label: "Instagram", url: "https://instagram.com" },
      { label: "YouTube", url: "https://youtube.com" },
      { label: "LinkedIn", url: "https://linkedin.com" },
    ],
  },
  {
    slug: "impact-foundry",
    name: "Impact Foundry",
    legalName: "Impact Foundry Network",
    tagline: "Programmes d'innovation, expo business et financement de projets a impact.",
    description:
      "Impact Foundry accompagne des organisateurs, incubateurs et associations sur les programmes a candidatures, les espaces expo et les collectes financees.",
    city: "Yaounde",
    country: "Cameroun",
    verified: false,
    followers: 7420,
    accentColor: "#264653",
    logoUrl:
      "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=320&q=80",
    bannerUrl:
      "https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1600&q=80",
    websiteUrl: "https://example.com/impact-foundry",
    supportEmail: "contact@impactfoundry.africa",
    supportPhone: "+237 222 30 12 12",
    socialLinks: [
      { label: "Facebook", url: "https://facebook.com" },
      { label: "X", url: "https://x.com" },
      { label: "LinkedIn", url: "https://linkedin.com" },
    ],
  },
];

export const mockContent: PublicContent[] = [
  {
    id: "evt-jazz-nights",
    module: "evenements",
    slug: "abidjan-jazz-nights-2026",
    title: "Abidjan Jazz Nights 2026",
    eyebrow: "Concert premium",
    summary: "Trois soirees live entre jazz, soul et food court au bord de la lagune.",
    description:
      "Abidjan Jazz Nights rassemble des artistes live, un espace VIP, des experiences partenaires et une billetterie simple jusqu'au paiement. La page est pensee pour rassurer vite: date, lieu, prix et tickets sont visibles sans friction.",
    category: "Musique",
    city: "Abidjan",
    country: "Cote d'Ivoire",
    venueName: "Palais de la Culture",
    address: "Treichville, Abidjan",
    format: "presentiel",
    startsAt: "2026-06-19T19:30:00+00:00",
    endsAt: "2026-06-21T23:00:00+00:00",
    publishedAt: "2026-04-10T10:00:00+00:00",
    coverImageUrl:
      "https://images.unsplash.com/photo-1501386761578-eac5c94b800a?auto=format&fit=crop&w=1200&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1511379938547-c1f69419868d?auto=format&fit=crop&w=900&q=80",
      "https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?auto=format&fit=crop&w=900&q=80",
      "https://images.unsplash.com/photo-1503095396549-807759245b35?auto=format&fit=crop&w=900&q=80",
    ],
    priceFrom: 15000,
    currency: "XOF",
    isFree: false,
    publicStatus: "published",
    featured: true,
    popular: true,
    badges: ["Top ventes", "Edition 2026"],
    highlights: ["3 jours", "12 artistes", "Zone VIP"],
    organizerSlug: "lagoon-live",
    organizers: [
      {
        name: "Lagoon Live",
        role: "Organisateur",
        imageUrl:
          "https://images.unsplash.com/photo-1614680376573-df3480f0c6ff?auto=format&fit=crop&w=240&q=80",
      },
    ],
    speakers: [
      {
        name: "Awa Cissoko",
        role: "Artiste invitee",
        imageUrl:
          "https://images.unsplash.com/photo-1488426862026-3ee34a7d66df?auto=format&fit=crop&w=240&q=80",
      },
      {
        name: "Kader Moss",
        role: "Band leader",
        imageUrl:
          "https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=240&q=80",
      },
    ],
    stats: [
      { label: "Places deja reservees", value: "2 480" },
      { label: "Taux de remplissage", value: "74%" },
      { label: "Partenaires", value: "9" },
    ],
    tiers: [
      {
        id: "general",
        title: "Pass general",
        subtitle: "Acces 1 jour",
        price: 15000,
        currency: "XOF",
        remaining: 380,
        quantityLabel: "tickets",
        ctaLabel: "Acheter",
        perks: ["Acces concert", "Food court", "Zone assise libre"],
      },
      {
        id: "weekend",
        title: "Pass weekend",
        subtitle: "Acces 3 jours",
        price: 35000,
        currency: "XOF",
        remaining: 120,
        quantityLabel: "passes",
        ctaLabel: "Reserver",
        perks: ["Acces 3 jours", "Fast lane", "Goodies"],
      },
      {
        id: "vip",
        title: "Pass VIP Lounge",
        subtitle: "Hospitality + service",
        price: 75000,
        currency: "XOF",
        remaining: 44,
        quantityLabel: "passes",
        ctaLabel: "Choisir",
        perks: ["Lounge premium", "Service a table", "Parking reserve"],
      },
    ],
    timeline: [
      {
        label: "Ouverture des portes",
        dateLabel: "19 juin - 18:00",
        description: "Controle d'acces, accueil et activation food court.",
      },
      {
        label: "Live session 1",
        dateLabel: "19 juin - 20:30",
        description: "Set d'ouverture avec artistes invites.",
      },
      {
        label: "Jam finale",
        dateLabel: "21 juin - 22:00",
        description: "Cloture et set collaboratif.",
      },
    ],
    faq: [
      {
        question: "Les billets sont-ils remboursables ?",
        answer: "Le remboursement depend de la politique de l'organisateur et du type de billet choisi.",
      },
      {
        question: "Puis-je transferer mon billet ?",
        answer: "Oui, avant la cloture des ventes, selon verification du compte acheteur.",
      },
    ],
    program: [
      "Concerts live et rencontres artistes",
      "Food court et zone partenaires",
      "Activation VIP et experiences premium",
    ],
    conditions: [
      "Billet numerique obligatoire a l'entree",
      "Verification d'identite possible pour les passes VIP",
      "Les horaires peuvent etre ajustes par l'organisateur",
    ],
    requiredDocuments: [],
    capacity: 3400,
    remainingSeats: 544,
  },
  {
    id: "evt-tech-summit",
    module: "evenements",
    slug: "sommet-tech-afrique-2026",
    title: "Sommet Tech Afrique 2026",
    eyebrow: "Conference",
    summary: "Keynotes, networking et sessions produit pour founders, ops et equipes innovation.",
    description:
      "Le sommet croise les codes de l'evenement premium et du catalogue transactionnel: hero immersif, tickets lisibles, speakers et agenda directement visibles.",
    category: "Conference",
    city: "Dakar",
    country: "Senegal",
    venueName: "Centre des Congres de Diamniadio",
    address: "Diamniadio, Dakar",
    format: "hybride",
    startsAt: "2026-07-08T08:30:00+00:00",
    endsAt: "2026-07-09T18:30:00+00:00",
    publishedAt: "2026-04-14T09:00:00+00:00",
    coverImageUrl:
      "https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=1200&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1515169067868-5387ec356754?auto=format&fit=crop&w=900&q=80",
      "https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=900&q=80",
      "https://images.unsplash.com/photo-1475721027785-f74eccf877e2?auto=format&fit=crop&w=900&q=80",
    ],
    priceFrom: 25000,
    currency: "XOF",
    isFree: false,
    publicStatus: "published",
    featured: true,
    popular: false,
    badges: ["Nouveau", "Hybride"],
    highlights: ["2 jours", "40 speakers", "12 ateliers"],
    organizerSlug: "lagoon-live",
    organizers: [
      {
        name: "Lagoon Live",
        role: "Production",
        imageUrl:
          "https://images.unsplash.com/photo-1614680376573-df3480f0c6ff?auto=format&fit=crop&w=240&q=80",
      },
    ],
    speakers: [
      {
        name: "Mariam Ndiaye",
        role: "Product lead",
        imageUrl:
          "https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=240&q=80",
      },
      {
        name: "Leo Mvondo",
        role: "Investor",
        imageUrl:
          "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=240&q=80",
      },
    ],
    stats: [
      { label: "Entreprises presentes", value: "120" },
      { label: "Participants attendus", value: "1 500" },
      { label: "Workshops", value: "12" },
    ],
    tiers: [
      {
        id: "standard",
        title: "Pass standard",
        price: 25000,
        currency: "XOF",
        remaining: 640,
        ctaLabel: "Acheter",
        perks: ["Keynotes", "Expo hall", "Networking"],
      },
      {
        id: "pro",
        title: "Pass pro",
        price: 55000,
        currency: "XOF",
        remaining: 210,
        ctaLabel: "Choisir",
        perks: ["Ateliers", "Networking lounge", "Replay 30 jours"],
      },
    ],
    timeline: [
      {
        label: "Ouverture",
        dateLabel: "8 juillet - 08:30",
        description: "Accueil et badge pickup.",
      },
      {
        label: "Ateliers produit",
        dateLabel: "8 juillet - 14:00",
        description: "Tracks growth, data et operations.",
      },
      {
        label: "Investor room",
        dateLabel: "9 juillet - 15:30",
        description: "Rencontres sur rendez-vous.",
      },
    ],
    faq: [
      {
        question: "Le pass en ligne donne-t-il acces au replay ?",
        answer: "Oui, pendant 30 jours apres l'evenement pour les tickets concernes.",
      },
    ],
    program: [
      "Keynotes et panels",
      "Demo zone startups",
      "Sessions networking ciblees",
    ],
    conditions: [
      "Badge nominatif",
      "Acces en ligne associe a l'adresse email de commande",
    ],
    requiredDocuments: [],
    capacity: 1500,
    remainingSeats: 850,
  },
  {
    id: "trn-ops-bootcamp",
    module: "formations",
    slug: "bootcamp-production-evenementielle",
    title: "Bootcamp Production Evenementielle",
    eyebrow: "Formation intensive",
    summary: "Un programme court pour structurer planning, billetterie, ops terrain et reporting.",
    description:
      "Ce bootcamp a ete pense comme une page detail de formation claire: programme, intervenants, capacite, format hybride et inscription directe.",
    category: "Production",
    city: "Dakar",
    country: "Senegal",
    venueName: "Atelier Neo Campus",
    address: "Point E, Dakar",
    format: "hybride",
    startsAt: "2026-05-18T09:00:00+00:00",
    endsAt: "2026-05-21T16:30:00+00:00",
    publishedAt: "2026-04-17T12:00:00+00:00",
    coverImageUrl:
      "https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=80",
      "https://images.unsplash.com/photo-1516321497487-e288fb19713f?auto=format&fit=crop&w=900&q=80",
    ],
    priceFrom: 180000,
    currency: "XOF",
    isFree: false,
    publicStatus: "published",
    featured: true,
    popular: true,
    badges: ["Places limitees", "Certifiante"],
    highlights: ["4 jours", "Cas pratiques", "Hybride"],
    organizerSlug: "atelier-neo",
    organizers: [
      {
        name: "Atelier Neo",
        role: "Academie",
        imageUrl:
          "https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=240&q=80",
      },
    ],
    speakers: [
      {
        name: "Sarah Beye",
        role: "Directrice pedagogique",
        imageUrl:
          "https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=240&q=80",
      },
      {
        name: "David Kouassi",
        role: "Ops lead",
        imageUrl:
          "https://images.unsplash.com/photo-1504257432389-52343af06ae3?auto=format&fit=crop&w=240&q=80",
      },
    ],
    stats: [
      { label: "Participants max", value: "32" },
      { label: "Taux de completion", value: "96%" },
      { label: "Cas traites", value: "7" },
    ],
    tiers: [
      {
        id: "campus",
        title: "Acces campus",
        price: 180000,
        currency: "XOF",
        remaining: 10,
        ctaLabel: "S'inscrire",
        perks: ["Sessions en presentiel", "Workbook", "Certification"],
      },
      {
        id: "hybrid",
        title: "Acces hybride",
        price: 130000,
        currency: "XOF",
        remaining: 18,
        ctaLabel: "Choisir",
        perks: ["Live video", "Replay", "Templates ops"],
      },
    ],
    timeline: [
      {
        label: "Jour 1",
        dateLabel: "18 mai - 09:00",
        description: "Cadre de production et planning maitre.",
      },
      {
        label: "Jour 2",
        dateLabel: "19 mai - 09:00",
        description: "Billetterie, pricing et funnel.",
      },
      {
        label: "Jour 4",
        dateLabel: "21 mai - 13:00",
        description: "Ops terrain, reporting et rendu final.",
      },
    ],
    faq: [
      {
        question: "La formation est-elle accessible a distance ?",
        answer: "Oui, via le pass hybride, avec replay et support de cours.",
      },
    ],
    program: [
      "Structurer un runbook evenementiel",
      "Configurer l'offre, les prix et le checkout",
      "Piloter l'equipe terrain et les prestataires",
      "Suivre ventes, scans et incidents",
    ],
    conditions: [
      "Niveau debutant a intermediaire accepte",
      "Ordinateur recommande pour les ateliers",
    ],
    requiredDocuments: [],
    capacity: 32,
    remainingSeats: 28,
  },
  {
    id: "trn-growth-masterclass",
    module: "formations",
    slug: "masterclass-growth-ticketing",
    title: "Masterclass Growth Ticketing",
    eyebrow: "Masterclass",
    summary: "Optimiser acquisition, conversion et relance panier pour des campagnes de vente plus rentables.",
    description:
      "Une page detail plus business, proche d'un usage SaaS/B2C: promesse claire, module visible, structure dense et ergonomique.",
    category: "Marketing",
    city: "Abidjan",
    country: "Cote d'Ivoire",
    venueName: "Studio Ticket Lab",
    address: "Cocody, Abidjan",
    format: "online",
    startsAt: "2026-06-03T17:00:00+00:00",
    endsAt: "2026-06-03T20:00:00+00:00",
    publishedAt: "2026-04-19T14:00:00+00:00",
    coverImageUrl:
      "https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1200&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1557804506-669a67965ba0?auto=format&fit=crop&w=900&q=80",
    ],
    priceFrom: 45000,
    currency: "XOF",
    isFree: false,
    publicStatus: "published",
    featured: false,
    popular: true,
    badges: ["En ligne"],
    highlights: ["3 heures", "Replay", "Templates"],
    organizerSlug: "atelier-neo",
    organizers: [
      {
        name: "Atelier Neo",
        role: "Academie",
        imageUrl:
          "https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=240&q=80",
      },
    ],
    speakers: [
      {
        name: "Aminata Faye",
        role: "Growth advisor",
        imageUrl:
          "https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?auto=format&fit=crop&w=240&q=80",
      },
    ],
    stats: [
      { label: "Cas etudies", value: "12" },
      { label: "Replay", value: "60 jours" },
      { label: "Templates", value: "8" },
    ],
    tiers: [
      {
        id: "remote",
        title: "Acces live",
        price: 45000,
        currency: "XOF",
        remaining: 80,
        ctaLabel: "S'inscrire",
        perks: ["Live Zoom", "Replay 60 jours", "Checklist conversion"],
      },
    ],
    timeline: [
      {
        label: "Session live",
        dateLabel: "3 juin - 17:00",
        description: "Funnel, offres et activation relance.",
      },
    ],
    faq: [
      {
        question: "Y a-t-il une attestation ?",
        answer: "Oui, une attestation numerique de participation est remise apres la session.",
      },
    ],
    program: [
      "Concevoir une offre lisible",
      "Structurer acquisition et retargeting",
      "Mesurer panier moyen et revenu net",
    ],
    conditions: ["Connexion internet stable requise"],
    requiredDocuments: [],
    capacity: 100,
    remainingSeats: 80,
  },
  {
    id: "std-agro-expo",
    module: "stands",
    slug: "agro-expo-abidjan-stands-2026",
    title: "Agro Expo Abidjan - Reservation de stands",
    eyebrow: "Salon expo",
    summary: "Reservez votre emplacement, comparez les packs et finalisez votre dossier exposant.",
    description:
      "La detail page de stand met en avant plan, quotas, services inclus et statut de disponibilite pour guider une reservation B2B claire.",
    category: "Exposition",
    city: "Abidjan",
    country: "Cote d'Ivoire",
    venueName: "Parc des Expositions",
    address: "Port-Bouet, Abidjan",
    format: "presentiel",
    startsAt: "2026-09-15T08:00:00+00:00",
    endsAt: "2026-09-18T18:00:00+00:00",
    publishedAt: "2026-04-15T11:00:00+00:00",
    coverImageUrl:
      "https://images.unsplash.com/photo-1511795409834-432f7b1728f2?auto=format&fit=crop&w=1200&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=900&q=80",
      "https://images.unsplash.com/photo-1505236858219-8359eb29e329?auto=format&fit=crop&w=900&q=80",
    ],
    priceFrom: 350000,
    currency: "XOF",
    isFree: false,
    publicStatus: "published",
    featured: true,
    popular: true,
    badges: ["B2B", "Disponibilites limitees"],
    highlights: ["120 stands", "4 jours", "Plan expo"],
    organizerSlug: "impact-foundry",
    organizers: [
      {
        name: "Impact Foundry",
        role: "Curateur salon",
        imageUrl:
          "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=240&q=80",
      },
    ],
    speakers: [],
    stats: [
      { label: "Exposants attendus", value: "120" },
      { label: "Visiteurs", value: "8 000" },
      { label: "Secteurs", value: "14" },
    ],
    tiers: [
      {
        id: "standard-9m2",
        title: "Stand 9 m2",
        price: 350000,
        currency: "XOF",
        remaining: 28,
        ctaLabel: "Reserver",
        perks: ["Signaletique", "1 prise", "2 badges exposants"],
      },
      {
        id: "premium-18m2",
        title: "Stand 18 m2",
        price: 680000,
        currency: "XOF",
        remaining: 9,
        ctaLabel: "Choisir",
        perks: ["Angle visible", "Mobilier", "4 badges exposants"],
      },
    ],
    timeline: [
      {
        label: "Cloture early booking",
        dateLabel: "30 juin",
        description: "Tarif preferentiel et choix d'emplacement prioritaire.",
      },
      {
        label: "Remise dossiers",
        dateLabel: "31 juillet",
        description: "Validation signaletique et informations exposant.",
      },
    ],
    faq: [
      {
        question: "Puis-je payer en deux fois ?",
        answer: "Oui, selon le pack choisi et apres validation organisateur.",
      },
    ],
    program: [
      "Hall principal et zones thematiques",
      "Rencontres B2B et prises de rendez-vous",
      "Visibilite sur le catalogue public du salon",
    ],
    conditions: [
      "Validation finale reservee a l'organisateur",
      "Documents administratifs exposes requis avant l'ouverture",
    ],
    requiredDocuments: [
      "Registre de commerce",
      "Logo HD",
      "Fiche exposant",
    ],
  },
  {
    id: "std-demo-pods",
    module: "stands",
    slug: "tech-expo-demo-pods",
    title: "Tech Expo Demo Pods",
    eyebrow: "Reservation startup",
    summary: "Pods compacts et premium pour demos produit, rencontres investisseurs et activation marque.",
    description:
      "Une offre stand plus compacte, tres proche des besoins startup, avec lecture directe des prix, quotas et avantages inclus.",
    category: "Innovation",
    city: "Yaounde",
    country: "Cameroun",
    venueName: "Impact Arena",
    address: "Bastos, Yaounde",
    format: "presentiel",
    startsAt: "2026-10-05T09:00:00+00:00",
    endsAt: "2026-10-06T19:00:00+00:00",
    publishedAt: "2026-04-22T08:00:00+00:00",
    coverImageUrl:
      "https://images.unsplash.com/photo-1528605248644-14dd04022da1?auto=format&fit=crop&w=1200&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=900&q=80",
    ],
    priceFrom: 220000,
    currency: "XOF",
    isFree: false,
    publicStatus: "published",
    featured: false,
    popular: false,
    badges: ["Startup friendly"],
    highlights: ["2 jours", "Demo area", "Networking"],
    organizerSlug: "impact-foundry",
    organizers: [
      {
        name: "Impact Foundry",
        role: "Organisateur",
        imageUrl:
          "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=240&q=80",
      },
    ],
    speakers: [],
    stats: [
      { label: "Pods restants", value: "17" },
      { label: "Investisseurs invites", value: "24" },
      { label: "Sessions demo", value: "18" },
    ],
    tiers: [
      {
        id: "pod-basic",
        title: "Demo pod basic",
        price: 220000,
        currency: "XOF",
        remaining: 17,
        ctaLabel: "Reserver",
        perks: ["1 comptoir", "1 ecran", "2 badges"],
      },
    ],
    timeline: [
      {
        label: "Selection des pods",
        dateLabel: "15 septembre",
        description: "Attribution selon ordre de reservation confirme.",
      },
    ],
    faq: [],
    program: ["Pitch zones", "Meet investors", "Media walk"],
    conditions: ["Offre reservee aux startups et PME selectionnees"],
    requiredDocuments: ["Pitch deck", "Logo", "Contact commercial"],
  },
  {
    id: "call-femtech-impact",
    module: "appels-a-projets",
    slug: "appel-a-projets-femtech-impact-2026",
    title: "Appel a projets FemTech Impact 2026",
    eyebrow: "Programme d'innovation",
    summary: "Subventions, mentorat et demo day pour startups en sante, inclusion et parcours feminin.",
    description:
      "La page detail met l'accent sur la clarte documentaire: eligibilite, calendrier, frais et pieces a fournir sont directement visibles.",
    category: "Innovation",
    city: "Yaounde",
    country: "Cameroun",
    deadlineAt: "2026-06-28T23:59:00+00:00",
    publishedAt: "2026-04-11T08:00:00+00:00",
    coverImageUrl:
      "https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1200&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=900&q=80",
    ],
    priceFrom: 10000,
    currency: "XOF",
    isFree: false,
    publicStatus: "published",
    featured: true,
    popular: true,
    badges: ["Ouvert", "Selection"],
    highlights: ["Subvention", "Mentorat", "Demo day"],
    organizerSlug: "impact-foundry",
    organizers: [
      {
        name: "Impact Foundry",
        role: "Programme lead",
        imageUrl:
          "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=240&q=80",
      },
    ],
    speakers: [
      {
        name: "Nadine Bella",
        role: "Mentor innovation",
        imageUrl:
          "https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=240&q=80",
      },
    ],
    stats: [
      { label: "Projets retenus", value: "12" },
      { label: "Montant max", value: "5 M XOF" },
      { label: "Mentors", value: "15" },
    ],
    tiers: [
      {
        id: "application",
        title: "Frais de dossier",
        price: 10000,
        currency: "XOF",
        ctaLabel: "Candidater",
        perks: ["Depot de dossier", "Acces portail candidat"],
      },
    ],
    timeline: [
      {
        label: "Ouverture",
        dateLabel: "11 avril",
        description: "Publication officielle et demarrage des candidatures.",
      },
      {
        label: "Cloture",
        dateLabel: "28 juin",
        description: "Depot final des dossiers.",
      },
      {
        label: "Pitch day",
        dateLabel: "21 juillet",
        description: "Presentation du shortlist.",
      },
    ],
    faq: [
      {
        question: "Une startup en pre-revenu peut-elle candidater ?",
        answer: "Oui, si elle repond aux criteres d'eligibilite et au stade demande.",
      },
    ],
    program: [
      "Subvention de lancement",
      "Mentorat produit et go-to-market",
      "Visibilite media et investisseur",
    ],
    conditions: [
      "Au moins une fondatrice ou cofondatrice",
      "Prototype fonctionnel recommande",
      "Projet deployable en Afrique francophone",
    ],
    requiredDocuments: [
      "Pitch deck",
      "Business model",
      "Piece d'identite de la fondatrice",
    ],
  },
  {
    id: "call-crea-jeunes",
    module: "appels-a-projets",
    slug: "crea-jeunes-festival-lab",
    title: "Crea Jeunes Festival Lab",
    eyebrow: "Selection creative",
    summary: "Programme d'accompagnement pour jeunes structures culturelles et equipes de programmation.",
    description:
      "Un appel a projets plus culturel, avec frais optionnels, calendrier detaille et logique de candidature fluide.",
    category: "Culture",
    city: "Abidjan",
    country: "Cote d'Ivoire",
    deadlineAt: "2026-05-30T23:59:00+00:00",
    publishedAt: "2026-04-21T16:00:00+00:00",
    coverImageUrl:
      "https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?auto=format&fit=crop&w=1200&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80",
    ],
    priceFrom: 0,
    currency: "XOF",
    isFree: true,
    publicStatus: "published",
    featured: false,
    popular: false,
    badges: ["Gratuit"],
    highlights: ["Mentorat", "Selection finale"],
    organizerSlug: "lagoon-live",
    organizers: [
      {
        name: "Lagoon Live",
        role: "Curation",
        imageUrl:
          "https://images.unsplash.com/photo-1614680376573-df3480f0c6ff?auto=format&fit=crop&w=240&q=80",
      },
    ],
    speakers: [],
    stats: [
      { label: "Structures retenues", value: "8" },
      { label: "Accompagnement", value: "6 semaines" },
    ],
    tiers: [
      {
        id: "free-application",
        title: "Depot de candidature",
        price: 0,
        currency: "XOF",
        ctaLabel: "Postuler",
        perks: ["Formulaire", "Suivi email", "Acces shortlist"],
      },
    ],
    timeline: [
      {
        label: "Depot",
        dateLabel: "Jusqu'au 30 mai",
        description: "Candidatures en ligne.",
      },
      {
        label: "Resultats",
        dateLabel: "10 juin",
        description: "Communication des structures retenues.",
      },
    ],
    faq: [],
    program: ["Mentorat", "Production", "Pitch final"],
    conditions: ["Structure jeune ou projet en lancement"],
    requiredDocuments: ["Presentation du projet", "Budget simplifie"],
  },
  {
    id: "crowd-salle-polyvalente",
    module: "crowdfunding",
    slug: "rehabilitation-salle-polyvalente",
    title: "Rehabilitation d'une salle polyvalente",
    eyebrow: "Campagne solidaire",
    summary: "Financer la remise a niveau d'un espace communautaire pour culture, formation et jeunesse.",
    description:
      "Le format crowdfunding reprend la logique detail produit, mais remplace les tickets par des paliers, une jauge de progression et des contreparties.",
    category: "Impact social",
    city: "Bouake",
    country: "Cote d'Ivoire",
    deadlineAt: "2026-07-30T23:59:00+00:00",
    publishedAt: "2026-04-12T10:00:00+00:00",
    coverImageUrl:
      "https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=1200&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1469571486292-b53601020a01?auto=format&fit=crop&w=900&q=80",
      "https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=900&q=80",
    ],
    priceFrom: 5000,
    currency: "XOF",
    isFree: false,
    publicStatus: "published",
    featured: true,
    popular: true,
    badges: ["Impact", "En cours"],
    highlights: ["Objectif communautaire", "Transparence", "Contreparties"],
    organizerSlug: "impact-foundry",
    organizers: [
      {
        name: "Impact Foundry",
        role: "Porteur",
        imageUrl:
          "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=240&q=80",
      },
    ],
    speakers: [],
    stats: [
      { label: "Contributeurs", value: "186" },
      { label: "Progression", value: "68%" },
      { label: "Jours restants", value: "95" },
    ],
    tiers: [
      {
        id: "supporter",
        title: "Contribution soutien",
        price: 5000,
        currency: "XOF",
        ctaLabel: "Contribuer",
        perks: ["Remerciement public"],
      },
      {
        id: "builder",
        title: "Contribution batisseur",
        price: 25000,
        currency: "XOF",
        ctaLabel: "Choisir",
        perks: ["Remerciement", "Invitation inauguration"],
      },
      {
        id: "partner",
        title: "Contribution partenaire",
        price: 100000,
        currency: "XOF",
        ctaLabel: "Soutenir",
        perks: ["Logo partenaire", "Invitation", "Mention speciale"],
      },
    ],
    timeline: [
      {
        label: "Lancement",
        dateLabel: "12 avril",
        description: "Ouverture de la campagne.",
      },
      {
        label: "Milestone 50%",
        dateLabel: "18 mai",
        description: "Validation de la phase gros oeuvre.",
      },
      {
        label: "Cloture",
        dateLabel: "30 juillet",
        description: "Arret de la collecte et bilan public.",
      },
    ],
    faq: [
      {
        question: "Les contributions sont-elles remboursables ?",
        answer: "Les contributions sont volontaires, sauf incident de paiement ou annulation conforme a la politique affichee.",
      },
    ],
    program: [
      "Refection toiture et electricite",
      "Mise en conformite acces",
      "Equipement mobilier et scene",
    ],
    conditions: ["Les contreparties sont livrées selon disponibilite et calendrier"],
    requiredDocuments: [],
    progressCurrent: 13600000,
    progressTarget: 20000000,
    backers: 186,
  },
  {
    id: "crowd-bourse-creative",
    module: "crowdfunding",
    slug: "bourse-creative-100-jeunes",
    title: "Bourse creative pour 100 jeunes",
    eyebrow: "Campagne education",
    summary: "Soutenir 100 jeunes createurs avec materiel, mentoring et acces a des ateliers specialises.",
    description:
      "Cette campagne combine narration, progression, objectifs et contreparties pour encourager une conversion rapide sans perdre la confiance.",
    category: "Education",
    city: "Yaounde",
    country: "Cameroun",
    deadlineAt: "2026-08-15T23:59:00+00:00",
    publishedAt: "2026-04-23T09:30:00+00:00",
    coverImageUrl:
      "https://images.unsplash.com/photo-1529390079861-591de354faf5?auto=format&fit=crop&w=1200&q=80",
    gallery: [
      "https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=80",
    ],
    priceFrom: 10000,
    currency: "XOF",
    isFree: false,
    publicStatus: "published",
    featured: false,
    popular: false,
    badges: ["Nouveau"],
    highlights: ["Mentorat", "Equipement", "Ateliers"],
    organizerSlug: "impact-foundry",
    organizers: [
      {
        name: "Impact Foundry",
        role: "Porteur",
        imageUrl:
          "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=240&q=80",
      },
    ],
    speakers: [],
    stats: [
      { label: "Beneficiaires", value: "100" },
      { label: "Objectif", value: "12 M XOF" },
    ],
    tiers: [
      {
        id: "starter",
        title: "Pack soutien",
        price: 10000,
        currency: "XOF",
        ctaLabel: "Contribuer",
        perks: ["Rapport d'impact"],
      },
      {
        id: "mentor",
        title: "Pack mentor",
        price: 50000,
        currency: "XOF",
        ctaLabel: "Soutenir",
        perks: ["Rapport", "Invitation demo day"],
      },
    ],
    timeline: [
      {
        label: "Lancement",
        dateLabel: "23 avril",
        description: "Mise en ligne et activations.",
      },
    ],
    faq: [],
    program: ["Achat materiel", "Mentoring", "Ateliers de restitution"],
    conditions: [],
    requiredDocuments: [],
    progressCurrent: 2100000,
    progressTarget: 12000000,
    backers: 48,
  },
];
