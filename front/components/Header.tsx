"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { PlatformConfiguration } from "@/lib/types";

const primaryLinks = [
  { href: "/evenements", label: "Evenements" },
  { href: "/formations", label: "Formations" },
  { href: "/stands", label: "Stands" },
  { href: "/appels-a-projets", label: "Appels a projets" },
  { href: "/crowdfunding", label: "Crowdfunding" },
  { href: "/categories", label: "Categories" },
];

const utilityLinks = [
  { href: "/villes", label: "Villes" },
  { href: "/intervenants", label: "Intervenants" },
  { href: "/a-propos", label: "A propos" },
  { href: "/support", label: "Support" },
];

export function Header({ platform }: { platform: PlatformConfiguration }) {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isScrolled, setIsScrolled] = useState(false);

  const closeMenu = () => {
    setIsMenuOpen(false);
  };

  useEffect(() => {
    const onScroll = () => {
      setIsScrolled(window.scrollY > 18);
    };

    const onResize = () => {
      if (window.innerWidth > 760) {
        setIsMenuOpen(false);
      }
    };

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        setIsMenuOpen(false);
      }
    };

    onScroll();
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
      <div className="topbar">
        <div className="shell topbar__inner">
          <div className="topbar__contacts">
            <p>{platform.supportEmail}</p>
            <span>{platform.supportPhone}</span>
          </div>
          <div className="topbar__meta">
            {utilityLinks.map((link) => (
              <Link href={link.href} key={link.href}>
                {link.label}
              </Link>
            ))}
            <span>Paiement securise</span>
          </div>
        </div>
      </div>
      <div className="nav-shell">
        <div className="shell nav">
          <Link className="brand" href="/" onClick={closeMenu}>
            <span className="brand__mark">T</span>
            <span className="brand__copy">
              <strong>{platform.brandName}</strong>
              <small>Public marketplace</small>
            </span>
          </Link>

          <button
            aria-expanded={isMenuOpen}
            aria-label={isMenuOpen ? "Fermer le menu" : "Ouvrir le menu"}
            className={`menu-toggle${isMenuOpen ? " is-open" : ""}`}
            onClick={() => {
              setIsMenuOpen((current) => !current);
            }}
            type="button"
          >
            <span className="menu-toggle__label">{isMenuOpen ? "Fermer" : "Menu"}</span>
          </button>

          <div className={`nav__panel${isMenuOpen ? " is-open" : ""}`}>
            <nav aria-label="Navigation principale" className="nav__links">
              {primaryLinks.map((link) => (
                <Link key={link.href} href={link.href} onClick={closeMenu}>
                  {link.label}
                </Link>
              ))}
              {utilityLinks.map((link) => (
                <Link className="nav__mobile-only" href={link.href} key={link.href} onClick={closeMenu}>
                  {link.label}
                </Link>
              ))}
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
