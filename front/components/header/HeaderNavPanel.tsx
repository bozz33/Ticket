import Link from "next/link";

import type { NavigationLink, PlatformConfiguration } from "@/lib/types";
import { translateNavigationLink } from "@/lib/i18n/public-translations";

import { HeaderLink } from "./HeaderLink";
import { LanguageSwitcher, usePublicLocale } from "./LanguageSwitcher";

type HeaderNavPanelProps = {
  isMenuOpen: boolean;
  locale?: string;
  platform: PlatformConfiguration;
  primaryLinks: NavigationLink[];
  onNavigate: () => void;
};

export function HeaderNavPanel({ isMenuOpen, locale: initialLocale, platform, primaryLinks, onNavigate }: HeaderNavPanelProps) {
  const { locale, setLocale, t } = usePublicLocale(platform, initialLocale);
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
        <Link className="button button--ghost button--ghost-on-dark" href="/compte" onClick={onNavigate}>
          {t("nav.account", "Mon compte")}
        </Link>
        <Link className="button" href="/devenir-organisateur" onClick={onNavigate}>
          {t("nav.organizer", "Devenir organisateur")}
        </Link>
      </div>
    </div>
  );
}
