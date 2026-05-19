import type { NavigationLink, PlatformConfiguration } from "@/lib/types";

import { HeaderLink } from "./HeaderLink";

type HeaderTopbarProps = {
  platform: PlatformConfiguration;
  topbarLinks: NavigationLink[];
};

export function HeaderTopbar({ platform, topbarLinks }: HeaderTopbarProps) {
  return (
    <div className="topbar">
      <div className="shell topbar__inner">
        <div className="topbar__contacts">
          <a className="topbar__contact-item" href={`mailto:${platform.supportEmail}`}>
            <svg
              aria-hidden="true"
              className="topbar__icon"
              fill="none"
              stroke="currentColor"
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="1.9"
              viewBox="0 0 24 24"
            >
              <path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" />
              <path d="m22 6-10 7L2 6" />
            </svg>
            {platform.supportEmail}
          </a>
          <span className="topbar__separator" aria-hidden="true">
            ·
          </span>
          <a className="topbar__contact-item" href={`tel:${platform.supportPhone.replace(/\s/g, "")}`}>
            <svg
              aria-hidden="true"
              className="topbar__icon"
              fill="none"
              stroke="currentColor"
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="1.9"
              viewBox="0 0 24 24"
            >
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13 19.79 19.79 0 0 1 1.6 4.41 2 2 0 0 1 3.58 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.16 6.16l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
            </svg>
            {platform.supportPhone}
          </a>
          <span className="topbar__badge">
            <span className="topbar__badge-dot" aria-hidden="true" />
            Disponible 24h/24
          </span>
        </div>

        <div className="topbar__meta">
          {topbarLinks.map((link, index) => (
            <HeaderLink link={link} key={`topbar-${link.href}-${index}`} />
          ))}
          <span className="topbar__secure">
            <svg
              aria-hidden="true"
              className="topbar__icon"
              fill="none"
              stroke="currentColor"
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="1.9"
              viewBox="0 0 24 24"
            >
              <rect height="11" rx="2" width="14" x="5" y="11" />
              <path d="M8 11V7a4 4 0 0 1 8 0v4" />
            </svg>
            Paiement sécurisé
          </span>
        </div>
      </div>
    </div>
  );
}
