"use client";

import type { PlatformConfiguration } from "@/lib/types";

import { getHeaderPrimaryLinks, getHeaderTopbarLinks } from "./header/config";
import { HeaderBrand } from "./header/HeaderBrand";
import { HeaderNavPanel } from "./header/HeaderNavPanel";
import { HeaderTopbar } from "./header/HeaderTopbar";
import { MenuToggle } from "./header/MenuToggle";
import { useHeaderBehavior } from "./header/useHeaderBehavior";

export function Header({ platform, locale }: { platform: PlatformConfiguration; locale?: string }) {
  const { closeMenu, isMenuOpen, isReady, isScrolled, toggleMenu } = useHeaderBehavior();
  const primaryLinks = getHeaderPrimaryLinks(platform);
  const topbarLinks = getHeaderTopbarLinks(platform);

  return (
    <header className={`site-header${isScrolled ? " is-scrolled" : ""}${isMenuOpen ? " is-menu-open" : ""}`}>
      <HeaderTopbar locale={locale} platform={platform} topbarLinks={topbarLinks} />

      <div className="nav-shell">
        <div className="shell nav">
          <HeaderBrand platform={platform} onClick={closeMenu} />
          <MenuToggle disabled={!isReady} isMenuOpen={isMenuOpen} onToggle={toggleMenu} />
          <HeaderNavPanel
            isMenuOpen={isMenuOpen}
            locale={locale}
            onNavigate={closeMenu}
            platform={platform}
            primaryLinks={primaryLinks}
          />
        </div>
      </div>
    </header>
  );
}
