import Link from "next/link";

import { resolveSupportedLocale, translate, translateNavigationLink } from "@/lib/i18n/public-translations";
import { NavigationLink, PlatformConfiguration } from "@/lib/types";

function FooterIcon({ icon }: { icon?: unknown }) {
  const name = typeof icon === "string" ? icon : "";

  if (!name) {
    return null;
  }

  return <span aria-hidden="true" className="footer-link-icon">{name}</span>;
}

function ensureVerificationLink(links: NavigationLink[]): NavigationLink[] {
  return links.some((link) => link.href === "/verifier")
    ? links
    : [...links, { href: "/verifier", label: "Vérifier un ticket" }];
}

function renderFooterLink(link: NavigationLink, key: string) {
  const target = link.target || undefined;
  const isExternal = link.href.startsWith("http://") || link.href.startsWith("https://") || target === "_blank";

  if (isExternal) {
    return (
      <a href={link.href} key={key} rel={target === "_blank" ? "noreferrer" : undefined} target={target}>
        <FooterIcon icon={link.meta?.icon} />
        {link.label}
      </a>
    );
  }

  return (
    <Link href={link.href} key={key} target={target}>
      <FooterIcon icon={link.meta?.icon} />
      {link.label}
    </Link>
  );
}

export function Footer({ platform, locale: initialLocale }: { platform: PlatformConfiguration; locale?: string }) {
  const locale = resolveSupportedLocale(platform, initialLocale);
  const t = (key: string, fallback: string) => translate(platform, locale, key, fallback);
  const exploreLinks = platform.menus.footer_explore.length > 0
    ? platform.menus.footer_explore
    : [
        { href: "/evenements", label: "Evenements" },
        { href: "/formations", label: "Formations" },
        { href: "/stands", label: "Stands" },
        { href: "/crowdfunding", label: "Crowdfunding" },
      ];
  const platformLinks = ensureVerificationLink(platform.menus.footer_platform.length > 0
    ? platform.menus.footer_platform
    : [
        { href: "/categories", label: "Categories" },
        { href: "/a-propos", label: "A propos" },
        { href: "/devenir-organisateur", label: "Devenir organisateur" },
      ]);
  const bottomLinks = platform.menus.footer_bottom.length > 0
    ? platform.menus.footer_bottom
    : [
        { href: "/contact", label: "Contact" },
        { href: platform.accountUrl, label: "Panel user" },
        { href: platform.organizerCtaUrl, label: "Onboarding organisateur" },
      ];

  const translatedExploreLinks = exploreLinks.map((link) => translateNavigationLink(platform, locale, link));
  const translatedPlatformLinks = platformLinks.map((link) => translateNavigationLink(platform, locale, link));
  const translatedBottomLinks = bottomLinks.map((link) => translateNavigationLink(platform, locale, link));

  return (
    <footer className="site-footer">
      <div className="shell footer-grid">
        <div>
          <p className="eyebrow">{t("footer.eyebrow", "Portail public")}</p>
          <h2 className="footer-title">{platform.brandName}</h2>
          <p className="footer-copy">
            {platform.footerDescription}
          </p>
          {platform.socialLinks.length > 0 ? (
            <div className="footer-socials">
              {platform.socialLinks.map((social) => (
                <a href={social.url} key={social.label} rel="noreferrer" target="_blank">
                  {social.label}
                </a>
              ))}
            </div>
          ) : null}
        </div>

        <div>
          <h3 className="footer-heading">{t("footer.explore", "Explorer")}</h3>
          <ul className="footer-list">
            {translatedExploreLinks.map((link, index) => (
              <li key={`explore-${link.href}-${index}`}>
                {renderFooterLink(link, `explore-link-${link.href}-${index}`)}
              </li>
            ))}
          </ul>
        </div>

        <div>
          <h3 className="footer-heading">{t("footer.platform", "Plateforme")}</h3>
          <ul className="footer-list">
            {translatedPlatformLinks.map((link, index) => (
              <li key={`platform-${link.href}-${index}`}>
                {renderFooterLink(link, `platform-link-${link.href}-${index}`)}
              </li>
            ))}
          </ul>
        </div>

        <div>
          <h3 className="footer-heading">{t("footer.payment", "Paiement")}</h3>
          <ul className="footer-list">
            {platform.paymentMethods.map((method) => (
              <li key={method}>{method}</li>
            ))}
          </ul>
        </div>
      </div>
      <div className="shell footer-bottom">
        <p>
          {new Date().getFullYear()} {platform.brandName}. {t("footer.rights", "Tous droits réservés.")}
        </p>
        <div className="footer-bottom__links">
          {translatedBottomLinks.map((link, index) => renderFooterLink(link, `bottom-${link.href}-${index}`))}
        </div>
      </div>
    </footer>
  );
}
