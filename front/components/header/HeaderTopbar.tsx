"use client";

import { translateNavigationLink } from "@/lib/i18n/public-translations";
import type { NavigationLink, PlatformConfiguration } from "@/lib/types";

import { HeaderLink } from "./HeaderLink";
import { usePublicLocale } from "./LanguageSwitcher";

type HeaderTopbarProps = {
  locale?: string;
  platform: PlatformConfiguration;
  topbarLeftLinks: NavigationLink[];
  topbarLinks: NavigationLink[];
};

function TopbarIcon({ icon }: { icon?: unknown }) {
  const name = typeof icon === "string" ? icon : "";

  if (name === "mail") {
    return (
      <svg aria-hidden="true" className="topbar__icon" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.9" viewBox="0 0 24 24">
        <path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" />
        <path d="m22 6-10 7L2 6" />
      </svg>
    );
  }

  if (name === "phone") {
    return (
      <svg aria-hidden="true" className="topbar__icon" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.9" viewBox="0 0 24 24">
        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13 19.79 19.79 0 0 1 1.6 4.41 2 2 0 0 1 3.58 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.16 6.16l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
      </svg>
    );
  }

  if (name === "shield" || name === "lock") {
    return (
      <svg aria-hidden="true" className="topbar__icon" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.9" viewBox="0 0 24 24">
        <rect height="11" rx="2" width="14" x="5" y="11" />
        <path d="M8 11V7a4 4 0 0 1 8 0v4" />
      </svg>
    );
  }

  if (name === "status") {
    return (
      <svg aria-hidden="true" className="topbar__icon topbar__icon--status" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.9" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="8" />
        <path d="m8.8 12.3 2.1 2.1 4.5-5" />
      </svg>
    );
  }

  if (name === "globe") {
    return (
      <svg aria-hidden="true" className="topbar__icon" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.9" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="9" />
        <path d="M3 12h18" />
        <path d="M12 3a14 14 0 0 1 0 18" />
        <path d="M12 3a14 14 0 0 0 0 18" />
      </svg>
    );
  }

  if (name === "user" || name === "organizer") {
    return (
      <svg aria-hidden="true" className="topbar__icon" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.9" viewBox="0 0 24 24">
        <circle cx="12" cy="8" r="4" />
        <path d="M4.5 21a7.5 7.5 0 0 1 15 0" />
      </svg>
    );
  }

  if (name === "ticket") {
    return (
      <svg aria-hidden="true" className="topbar__icon" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.9" viewBox="0 0 24 24">
        <path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4V8Z" />
        <path d="M9 9h6" />
        <path d="M9 15h6" />
      </svg>
    );
  }

  if (name === "link") {
    return (
      <svg aria-hidden="true" className="topbar__icon" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.9" viewBox="0 0 24 24">
        <path d="M10 13a5 5 0 0 0 7.1 0l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1" />
        <path d="M14 11a5 5 0 0 0-7.1 0l-2 2A5 5 0 0 0 12 20.1l1.1-1.1" />
      </svg>
    );
  }

  return null;
}

export function HeaderTopbar({ locale: initialLocale, platform, topbarLeftLinks, topbarLinks }: HeaderTopbarProps) {
  const { locale } = usePublicLocale(platform, initialLocale);
  const translatedTopbarLeftLinks = topbarLeftLinks.map((link) => translateNavigationLink(platform, locale, link));
  const translatedTopbarLinks = topbarLinks.map((link) => translateNavigationLink(platform, locale, link));

  return (
    <div className="topbar">
      <div className="shell topbar__inner">
        <div className="topbar__contacts">
          {translatedTopbarLeftLinks.map((link, index) => (
            <HeaderLink className="topbar__link" icon={<TopbarIcon icon={link.meta?.icon} />} link={link} key={`topbar-left-${link.href}-${index}`} />
          ))}
        </div>

        <div className="topbar__meta">
          {translatedTopbarLinks.map((link, index) => (
            <HeaderLink className="topbar__link" icon={<TopbarIcon icon={link.meta?.icon} />} link={link} key={`topbar-${link.href}-${index}`} />
          ))}
        </div>
      </div>
    </div>
  );
}
