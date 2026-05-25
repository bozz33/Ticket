import type { NavigationLink, PlatformConfiguration } from "@/lib/types";

export const PUBLIC_LOCALE_COOKIE = "ticket_locale";

const FALLBACK_TRANSLATIONS: Record<string, Record<string, string>> = {
  fr: {
    "common.view_all": "Tout voir",
    "content_card.date": "Date",
    "content_card.follow_organizer": "Suivre l'orga",
    "content_card.free": "Gratuit",
    "content_card.location": "Lieu",
    "content_card.cta.appels_a_projets": "Candidater",
    "content_card.cta.crowdfunding": "Contribuer",
    "content_card.cta.evenements": "Acheter",
    "content_card.cta.formations": "S'inscrire",
    "content_card.cta.stands": "Réserver",
    "content_card.module.appels_a_projets": "Appels à projets",
    "content_card.module.crowdfunding": "Crowdfunding",
    "content_card.module.evenements": "Événements",
    "content_card.module.formations": "Formations",
    "content_card.module.stands": "Stands",
    "content_card.organizer_followed": "Orga suivie",
    "content_card.paid": "Payant",
    "content_card.price_from": "À partir de",
    "content_card.published_by": "Publié par",
    "content_card.remaining_seats": "{count} restantes",
    "content_card.seats": "Places",
    "content_card.selection": "Sélection",
    "content_card.sold_out": "Épuisé",
    "content_card.trending": "Tendance",
    "footer.explore": "Explorer",
    "footer.eyebrow": "Portail public",
    "footer.platform": "Plateforme",
    "footer.payment": "Paiement",
    "footer.rights": "Tous droits réservés.",
    "header.available": "Disponible 24h/24",
    "header.secure_payment": "Paiement sécurisé",
    "home.featured.description": "Une vitrine riche et visuelle, avec des cartes denses et des CTA directs.",
    "home.featured.eyebrow": "Sélection éditée",
    "home.featured.title": "À la une",
    "home.hero.body": "Un catalogue premium pour billets, formations, stands, candidatures et campagnes, avec des parcours d'achat clairs et une mise en avant forte des organisateurs.",
    "home.hero.eyebrow": "Marketplace publique",
    "home.hero.image_alt": "Scène premium et public pendant un événement",
    "home.hero.primary_cta": "Vérifier un ticket",
    "home.hero.secondary_cta": "Publier sur la plateforme",
    "home.hero.title": "Des expériences à réserver, soutenir ou rejoindre.",
    "home.organizers.description": "Chaque organisateur peut être valorisé comme une vraie page publique.",
    "home.organizers.eyebrow": "Organisateurs",
    "home.organizers.title": "Profils publics mis en avant",
    "home.popular.description": "Les contenus qui ont reçu le plus de mentions j'aime cette semaine.",
    "home.popular.eyebrow": "Tendances",
    "home.popular.title": "Populaires cette semaine",
    "home.stats.items": "Contenus publiés",
    "home.stats.organizers": "Organisateurs",
    "home.stats.users": "Utilisateurs",
    "listing.empty_body": "Élargissez la recherche ou revenez au catalogue complet.",
    "listing.empty_content_body": "Modifiez vos critères ou explorez les catalogues par module.",
    "listing.empty_content_title": "Aucun contenu ne correspond à ces filtres.",
    "listing.reset": "Réinitialiser",
    "listing.reset_filters": "Réinitialiser les filtres",
    "listing.results": "résultats",
    "nav.about": "À propos",
    "nav.account": "Mon compte",
    "nav.categories": "Catégories",
    "nav.contact": "Contact",
    "nav.events": "Événements",
    "nav.faq": "FAQ",
    "nav.home": "Accueil",
    "nav.legal": "Mentions légales",
    "nav.organizer": "Devenir organisateur",
    "nav.refunds": "CGV & remboursements",
    "nav.verify_ticket": "Vérifier un ticket",
  },
  en: {
    "common.view_all": "View all",
    "content_card.date": "Date",
    "content_card.follow_organizer": "Follow organizer",
    "content_card.free": "Free",
    "content_card.location": "Location",
    "content_card.cta.appels_a_projets": "Apply",
    "content_card.cta.crowdfunding": "Contribute",
    "content_card.cta.evenements": "Buy",
    "content_card.cta.formations": "Register",
    "content_card.cta.stands": "Book",
    "content_card.module.appels_a_projets": "Calls for projects",
    "content_card.module.crowdfunding": "Crowdfunding",
    "content_card.module.evenements": "Events",
    "content_card.module.formations": "Training",
    "content_card.module.stands": "Stands",
    "content_card.organizer_followed": "Organizer followed",
    "content_card.paid": "Paid",
    "content_card.price_from": "From",
    "content_card.published_by": "Published by",
    "content_card.remaining_seats": "{count} left",
    "content_card.seats": "Seats",
    "content_card.selection": "Selected",
    "content_card.sold_out": "Sold out",
    "content_card.trending": "Trending",
    "footer.explore": "Explore",
    "footer.eyebrow": "Public portal",
    "footer.platform": "Platform",
    "footer.payment": "Payment",
    "footer.rights": "All rights reserved.",
    "header.available": "Available 24/7",
    "header.secure_payment": "Secure payment",
    "home.featured.description": "A rich visual showcase with dense cards and direct calls to action.",
    "home.featured.eyebrow": "Editor's picks",
    "home.featured.title": "Featured",
    "home.hero.body": "A premium catalogue for tickets, training, stands, applications and campaigns, with clear purchase paths and strong organizer visibility.",
    "home.hero.eyebrow": "Public marketplace",
    "home.hero.image_alt": "Premium crowd scene during an event",
    "home.hero.primary_cta": "Verify a ticket",
    "home.hero.secondary_cta": "Publish on the platform",
    "home.hero.title": "Book, support or join memorable experiences.",
    "home.organizers.description": "Each organizer can be showcased as a real public profile.",
    "home.organizers.eyebrow": "Organizers",
    "home.organizers.title": "Featured public profiles",
    "home.popular.description": "The content with the most likes this week.",
    "home.popular.eyebrow": "Trending",
    "home.popular.title": "Popular this week",
    "home.stats.items": "Published content",
    "home.stats.organizers": "Organizers",
    "home.stats.users": "Users",
    "listing.empty_body": "Broaden your search or return to the full catalogue.",
    "listing.empty_content_body": "Change your filters or explore the catalogue by module.",
    "listing.empty_content_title": "No content matches these filters.",
    "listing.reset": "Reset",
    "listing.reset_filters": "Reset filters",
    "listing.results": "results",
    "nav.about": "About",
    "nav.account": "My account",
    "nav.categories": "Categories",
    "nav.contact": "Contact",
    "nav.events": "Events",
    "nav.faq": "FAQ",
    "nav.home": "Home",
    "nav.legal": "Legal notice",
    "nav.organizer": "Become an organizer",
    "nav.refunds": "Terms & refunds",
    "nav.verify_ticket": "Verify a ticket",
  },
};

const NAVIGATION_KEYS: Array<[string, string]> = [
  ["/", "nav.home"],
  ["/a-propos", "nav.about"],
  ["/evenements", "nav.events"],
  ["/contact", "nav.contact"],
  ["/verifier", "nav.verify_ticket"],
  ["/remboursement", "nav.refunds"],
  ["/conditions-generales-de-vente", "nav.refunds"],
  ["/faq", "nav.faq"],
  ["/mentions-legales", "nav.legal"],
  ["/categories", "nav.categories"],
  ["/devenir-organisateur", "nav.organizer"],
];

export function displayLocaleCode(code: string): string {
  return code.toLowerCase() === "en" ? "AN" : code.toUpperCase();
}

export function defaultLocale(platform: PlatformConfiguration): string {
  return platform.defaultLanguage?.code
    || platform.languages.find((language) => language.is_default)?.code
    || platform.languages[0]?.code
    || "fr";
}

export function resolveSupportedLocale(platform: PlatformConfiguration, locale?: string | null): string {
  const normalized = (locale || "").toLowerCase();

  return platform.languages.some((language) => language.code.toLowerCase() === normalized)
    ? normalized
    : defaultLocale(platform);
}

export function translate(platform: PlatformConfiguration, locale: string, key: string, fallback: string): string {
  const currentLocale = resolveSupportedLocale(platform, locale);
  const fallbackLocale = defaultLocale(platform);

  return platform.translations?.[currentLocale]?.[key]
    ?? FALLBACK_TRANSLATIONS[currentLocale]?.[key]
    ?? platform.translations?.[fallbackLocale]?.[key]
    ?? FALLBACK_TRANSLATIONS[fallbackLocale]?.[key]
    ?? fallback;
}

export function translateNavigationLink(platform: PlatformConfiguration, locale: string, link: NavigationLink): NavigationLink {
  const normalizedHref = link.href.split("#")[0]?.split("?")[0] || link.href;
  const key = NAVIGATION_KEYS.find(([href]) => href === normalizedHref)?.[1];

  return key ? { ...link, label: translate(platform, locale, key, link.label) } : link;
}
