import Link from "next/link";

import { NavigationLink, PlatformConfiguration } from "@/lib/types";

function renderFooterLink(link: NavigationLink, key: string) {
  const target = link.target || undefined;
  const isExternal = link.href.startsWith("http://") || link.href.startsWith("https://") || target === "_blank";

  if (isExternal) {
    return (
      <a href={link.href} key={key} rel={target === "_blank" ? "noreferrer" : undefined} target={target}>
        {link.label}
      </a>
    );
  }

  return (
    <Link href={link.href} key={key} target={target}>
      {link.label}
    </Link>
  );
}

export function Footer({ platform }: { platform: PlatformConfiguration }) {
  const exploreLinks = platform.menus.footer_explore.length > 0
    ? platform.menus.footer_explore
    : [
        { href: "/evenements", label: "Evenements" },
        { href: "/formations", label: "Formations" },
        { href: "/stands", label: "Stands" },
        { href: "/crowdfunding", label: "Crowdfunding" },
      ];
  const platformLinks = platform.menus.footer_platform.length > 0
    ? platform.menus.footer_platform
    : [
        { href: "/categories", label: "Categories" },
        { href: "/a-propos", label: "A propos" },
        { href: "/devenir-organisateur", label: "Devenir organisateur" },
      ];
  const bottomLinks = platform.menus.footer_bottom.length > 0
    ? platform.menus.footer_bottom
    : [
        { href: "/contact", label: "Contact" },
        { href: platform.accountUrl, label: "Panel user" },
        { href: platform.organizerCtaUrl, label: "Onboarding organisateur" },
      ];

  return (
    <footer className="site-footer">
      <div className="shell footer-grid">
        <div>
          <p className="eyebrow">Portail public</p>
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
          <h3 className="footer-heading">Explorer</h3>
          <ul className="footer-list">
            {exploreLinks.map((link, index) => (
              <li key={`explore-${link.href}-${index}`}>
                {renderFooterLink(link, `explore-link-${link.href}-${index}`)}
              </li>
            ))}
          </ul>
        </div>

        <div>
          <h3 className="footer-heading">Plateforme</h3>
          <ul className="footer-list">
            {platformLinks.map((link, index) => (
              <li key={`platform-${link.href}-${index}`}>
                {renderFooterLink(link, `platform-link-${link.href}-${index}`)}
              </li>
            ))}
          </ul>
        </div>

        <div>
          <h3 className="footer-heading">Paiement</h3>
          <ul className="footer-list">
            {platform.paymentMethods.map((method) => (
              <li key={method}>{method}</li>
            ))}
          </ul>
        </div>
      </div>
      <div className="shell footer-bottom">
        <p>
          {new Date().getFullYear()} {platform.brandName}. Tous droits reserves.
        </p>
        <div className="footer-bottom__links">
          {bottomLinks.map((link, index) => renderFooterLink(link, `bottom-${link.href}-${index}`))}
        </div>
      </div>
    </footer>
  );
}
