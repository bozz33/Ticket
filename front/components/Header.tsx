"use client";

import type { PlatformConfiguration } from "@/lib/types";

import { getHeaderActionLinks, getHeaderPrimaryLinks, getHeaderTopbarLeftLinks, getHeaderTopbarLinks } from "./header/config";
import { HeaderBrand } from "./header/HeaderBrand";
import { HeaderNavPanel } from "./header/HeaderNavPanel";
import { HeaderTopbar } from "./header/HeaderTopbar";
import { MenuToggle } from "./header/MenuToggle";
import { useHeaderBehavior } from "./header/useHeaderBehavior";

export function Header({ platform, locale }: { platform: PlatformConfiguration; locale?: string }) {
  const { closeMenu, isMenuOpen, isReady, isScrolled, toggleMenu } = useHeaderBehavior();
  const primaryLinks = getHeaderPrimaryLinks(platform);
  const topbarLinks = getHeaderTopbarLinks(platform);
  const topbarLeftLinks = getHeaderTopbarLeftLinks(platform);
  const actionLinks = getHeaderActionLinks(platform);

  return (
    <header className={`site-header${isScrolled ? " is-scrolled" : ""}${isMenuOpen ? " is-menu-open" : ""}`}>
      <HeaderTopbar locale={locale} platform={platform} topbarLeftLinks={topbarLeftLinks} topbarLinks={topbarLinks} />

      <div className="nav-shell">
        <div className="shell nav">
          <HeaderBrand platform={platform} onClick={closeMenu} />
          <MenuToggle disabled={!isReady} isMenuOpen={isMenuOpen} onToggle={toggleMenu} />
          <HeaderNavPanel
            isMenuOpen={isMenuOpen}
            locale={locale}
            onNavigate={closeMenu}
            platform={platform}
            actionLinks={actionLinks}
            primaryLinks={primaryLinks}
          />
        </div>
      </div>
    </header>
  );
}
