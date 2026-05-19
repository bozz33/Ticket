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
  { href: "/a-propos", label: "À propos" },
  { href: "/remboursement", label: "Remboursement" },
  { href: "/faq", label: "FAQ" },
  { href: "/mentions-legales", label: "Mentions légales" },
];

export function getHeaderPrimaryLinks(platform: PlatformConfiguration) {
  return platform.menus.header_primary.length > 0 ? platform.menus.header_primary : DEFAULT_PRIMARY_LINKS;
}

export function getHeaderTopbarLinks(platform: PlatformConfiguration) {
  return platform.menus.header_utility.length > 0 ? platform.menus.header_utility : DEFAULT_TOPBAR_LINKS;
}
