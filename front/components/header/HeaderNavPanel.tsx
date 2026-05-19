import Link from "next/link";

import type { NavigationLink } from "@/lib/types";

import { HeaderLink } from "./HeaderLink";

type HeaderNavPanelProps = {
  isMenuOpen: boolean;
  primaryLinks: NavigationLink[];
  onNavigate: () => void;
};

export function HeaderNavPanel({ isMenuOpen, primaryLinks, onNavigate }: HeaderNavPanelProps) {
  return (
    <div className={`nav__panel${isMenuOpen ? " is-open" : ""}`}>
      <nav aria-label="Navigation principale" className="nav__links">
        {primaryLinks.map((link, index) => (
          <HeaderLink link={link} key={`primary-${link.href}-${index}`} onClick={onNavigate} />
        ))}
      </nav>

      <div className="nav__actions">
        <Link className="button button--ghost button--ghost-on-dark" href="/compte" onClick={onNavigate}>
          Mon compte
        </Link>
        <Link className="button" href="/devenir-organisateur" onClick={onNavigate}>
          Devenir organisateur
        </Link>
      </div>
    </div>
  );
}
