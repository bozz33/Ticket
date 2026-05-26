import Link from "next/link";

import type { NavigationLink, PlatformConfiguration } from "@/lib/types";
import { translateNavigationLink } from "@/lib/i18n/public-translations";

import { HeaderLink } from "./HeaderLink";
import { LanguageSwitcher, usePublicLocale } from "./LanguageSwitcher";

type HeaderNavPanelProps = {
  actionLinks: NavigationLink[];
  isMenuOpen: boolean;
  locale?: string;
  platform: PlatformConfiguration;
  primaryLinks: NavigationLink[];
  onNavigate: () => void;
};

export function HeaderNavPanel({ actionLinks, isMenuOpen, locale: initialLocale, platform, primaryLinks, onNavigate }: HeaderNavPanelProps) {
  const { locale, setLocale } = usePublicLocale(platform, initialLocale);
  const translatedActionLinks = actionLinks.map((link) => translateNavigationLink(platform, locale, link));
  const translatedPrimaryLinks = primaryLinks.map((link) => translateNavigationLink(platform, locale, link));

  return (
    <div className={`nav__panel${isMenuOpen ? " is-open" : ""}`}>
      <nav aria-label="Navigation principale" className="nav__links" id="primary-navigation">
        {translatedPrimaryLinks.map((link, index) => (
          <HeaderLink link={link} key={`primary-${link.href}-${index}`} onClick={onNavigate} />
        ))}
      </nav>

      <div className="nav__actions">
        <LanguageSwitcher initialLocale={initialLocale} locale={locale} onLocaleChange={setLocale} platform={platform} />
        {translatedActionLinks.map((link, index) => {
          const variant = typeof link.meta?.variant === "string" ? link.meta.variant : index === 0 ? "ghost" : "primary";

          return (
            <Link className={variant === "ghost" ? "button button--ghost button--ghost-on-dark" : "button"} href={link.href} key={`header-action-${link.href}-${index}`} onClick={onNavigate}>
              {link.label}
            </Link>
          );
        })}
      </div>
    </div>
  );
}
