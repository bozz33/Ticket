import type { NavigationLink, PlatformConfiguration } from "@/lib/types";

export const SCROLLED_ENTER_THRESHOLD = 88;
export const SCROLLED_EXIT_THRESHOLD = 16;

const DEFAULT_PRIMARY_LINKS: NavigationLink[] = [
  { href: "/", label: "Accueil" },
  { href: "/a-propos", label: "À propos" },
  { href: "/evenements", label: "Événements" },
  { href: "/contact", label: "Contact" },
];

const DEFAULT_TOPBAR_LINKS: NavigationLink[] = [
  { href: "/verifier", label: "Vérifier un ticket" },
  { href: "/a-propos", label: "À propos" },
  { href: "/remboursement", label: "CGV & remboursements" },
  { href: "/faq", label: "FAQ" },
  { href: "/mentions-legales", label: "Mentions légales" },
];

const DEFAULT_TOPBAR_LEFT_LINKS: NavigationLink[] = [
  { href: "mailto:support@ticket.africa", label: "support@ticket.africa", meta: { icon: "mail" } },
  { href: "tel:+2252722401100", label: "+225 27 22 40 11 00", meta: { icon: "phone" } },
  { href: "#availability", label: "Disponible 24h/24", meta: { icon: "status" } },
];

const DEFAULT_ACTION_LINKS: NavigationLink[] = [
  { href: "/compte", label: "Mon compte", meta: { variant: "ghost" } },
  { href: "/devenir-organisateur", label: "Devenir organisateur", meta: { variant: "primary" } },
];

export function getHeaderPrimaryLinks(platform: PlatformConfiguration) {
  return platform.menus.header_primary.length > 0 ? platform.menus.header_primary : DEFAULT_PRIMARY_LINKS;
}

export function getHeaderTopbarLinks(platform: PlatformConfiguration) {
  return platform.menus.header_top_right.length > 0
    ? platform.menus.header_top_right
    : platform.menus.header_utility.length > 0
      ? platform.menus.header_utility
      : DEFAULT_TOPBAR_LINKS;
}

export function getHeaderTopbarLeftLinks(platform: PlatformConfiguration) {
  return platform.menus.header_top_left.length > 0 ? platform.menus.header_top_left : DEFAULT_TOPBAR_LEFT_LINKS;
}

export function getHeaderActionLinks(platform: PlatformConfiguration) {
  return platform.menus.header_actions.length > 0 ? platform.menus.header_actions : DEFAULT_ACTION_LINKS;
}
