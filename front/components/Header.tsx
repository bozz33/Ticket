"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { NavigationLink, PlatformConfiguration } from "@/lib/types";

function renderLink(link: NavigationLink, key: string, onClick?: () => void) {
  const target = link.target || undefined;
  const isExternal = link.href.startsWith("http://") || link.href.startsWith("https://") || target === "_blank";

  if (isExternal) {
    return (
      <a href={link.href} key={key} onClick={onClick} rel={target === "_blank" ? "noreferrer" : undefined} target={target}>
        {link.label}
      </a>
    );
  }

  return (
    <Link href={link.href} key={key} onClick={onClick} target={target}>
      {link.label}
    </Link>
  );
}

export function Header({ platform }: { platform: PlatformConfiguration }) {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isScrolled, setIsScrolled] = useState(false);
  const SCROLLED_ENTER_THRESHOLD = 88;
  const SCROLLED_EXIT_THRESHOLD = 16;
  const primaryLinks = platform.menus.header_primary.length > 0
    ? platform.menus.header_primary
    : [
        { href: "/", label: "Accueil" },
        { href: "/a-propos", label: "A propos" },
        { href: "/evenements", label: "Evenements" },
        { href: "/contact", label: "Contact" },
      ];
  const topbarLinks = platform.menus.header_utility.length > 0
    ? platform.menus.header_utility
    : [
        { href: "/a-propos", label: "A propos" },
        { href: "/remboursement", label: "Remboursement" },
        { href: "/faq", label: "FAQ" },
        { href: "/mentions-legales", label: "Mentions legales" },
      ];

  const closeMenu = () => setIsMenuOpen(false);

  useEffect(() => {
    let isTicking = false;

    const syncScrolledState = () => {
      const nextScrollY = window.scrollY || 0;

      setIsScrolled((current) => {
        if (current) {
          return nextScrollY > SCROLLED_EXIT_THRESHOLD;
        }

        return nextScrollY > SCROLLED_ENTER_THRESHOLD;
      });
    };

    const onScroll = () => {
      if (isTicking) {
        return;
      }

      isTicking = true;

      window.requestAnimationFrame(() => {
        syncScrolledState();
        isTicking = false;
      });
    };

    const onResize = () => { if (window.innerWidth > 760) setIsMenuOpen(false); };
    const onKeyDown = (e: KeyboardEvent) => { if (e.key === "Escape") setIsMenuOpen(false); };

    syncScrolledState();
    onResize();

    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onResize);
    window.addEventListener("keydown", onKeyDown);

    return () => {
      window.removeEventListener("scroll", onScroll);
      window.removeEventListener("resize", onResize);
      window.removeEventListener("keydown", onKeyDown);
    };
  }, []);

  return (
    <header
      className={`site-header${isScrolled ? " is-scrolled" : ""}${isMenuOpen ? " is-menu-open" : ""}`}
    >
      {/* ── Topbar ───────────────────────────────────────────── */}
      <div className="topbar">
        <div className="shell topbar__inner">

          {/* Gauche : email + téléphone + badge 24h */}
          <div className="topbar__contacts">
            <a className="topbar__contact-item" href={`mailto:${platform.supportEmail}`}>
              <svg aria-hidden="true" className="topbar__icon" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.9" viewBox="0 0 24 24">
                <path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" />
                <path d="m22 6-10 7L2 6" />
              </svg>
              {platform.supportEmail}
            </a>
            <span className="topbar__separator" aria-hidden="true">·</span>
            <a className="topbar__contact-item" href={`tel:${platform.supportPhone.replace(/\s/g, "")}`}>
              <svg aria-hidden="true" className="topbar__icon" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.9" viewBox="0 0 24 24">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13 19.79 19.79 0 0 1 1.6 4.41 2 2 0 0 1 3.58 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.16 6.16l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
              </svg>
              {platform.supportPhone}
            </a>
            {/* Badge disponibilité */}
            <span className="topbar__badge">
              <span className="topbar__badge-dot" aria-hidden="true" />
              Disponible 24h/24
            </span>
          </div>

          {/* Droite : liens légaux + paiement sécurisé */}
          <div className="topbar__meta">
            {topbarLinks.map((link, index) => renderLink(link, `topbar-${link.href}-${index}`))}
            <span className="topbar__secure">
              <svg aria-hidden="true" className="topbar__icon" fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.9" viewBox="0 0 24 24">
                <rect height="11" rx="2" width="14" x="5" y="11" />
                <path d="M8 11V7a4 4 0 0 1 8 0v4" />
              </svg>
              Paiement securise
            </span>
          </div>
        </div>
      </div>

      {/* ── Nav principale ───────────────────────────────────── */}
      <div className="nav-shell">
        <div className="shell nav">
          <Link className="brand" href="/" onClick={closeMenu}>
            {platform.logoUrl ? (
              <img alt={platform.brandName} className="brand__logo" src={platform.logoUrl} />
            ) : (
              <span className="brand__mark">T</span>
            )}
            <span className="brand__copy">
              <strong>{platform.brandName}</strong>
              <small>Public marketplace</small>
            </span>
          </Link>

          <button
            aria-expanded={isMenuOpen}
            aria-label={isMenuOpen ? "Fermer le menu" : "Ouvrir le menu"}
            className={`menu-toggle${isMenuOpen ? " is-open" : ""}`}
            onClick={() => setIsMenuOpen((v) => !v)}
            type="button"
          >
            <span className="menu-toggle__line" />
            <span className="menu-toggle__line" />
            <span className="menu-toggle__line" />
          </button>

          <div className={`nav__panel${isMenuOpen ? " is-open" : ""}`}>
            <nav aria-label="Navigation principale" className="nav__links">
              {primaryLinks.map((link, index) => renderLink(link, `primary-${link.href}-${index}`, closeMenu))}
            </nav>

            <div className="nav__actions">
              <Link
                className="button button--ghost button--ghost-on-dark"
                href="/compte"
                onClick={closeMenu}
              >
                Mon compte
              </Link>
              <Link className="button" href="/devenir-organisateur" onClick={closeMenu}>
                Devenir organisateur
              </Link>
            </div>
          </div>
        </div>
      </div>
    </header>
  );
}
