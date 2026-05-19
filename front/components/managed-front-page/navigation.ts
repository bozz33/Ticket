import type { NavigationLink, PlatformConfiguration } from "@/lib/types";

import { hasLinks } from "./helpers";

export function fallbackPrimaryLinks(platform: PlatformConfiguration): NavigationLink[] {
  return hasLinks(platform.menus.header_primary)
    ? platform.menus.header_primary
    : [
        { href: "/", label: "Accueil" },
        { href: "/a-propos", label: "A propos" },
        { href: "/evenements", label: "Evenements" },
        { href: "/contact", label: "Contact" },
      ];
}

export function fallbackUtilityLinks(platform: PlatformConfiguration): NavigationLink[] {
  return hasLinks(platform.menus.header_utility)
    ? platform.menus.header_utility
    : [
        { href: "/a-propos", label: "A propos" },
        { href: "/remboursement", label: "Remboursement" },
        { href: "/faq", label: "FAQ" },
        { href: "/mentions-legales", label: "Mentions legales" },
      ];
}
