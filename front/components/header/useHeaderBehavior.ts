"use client";

import { useEffect, useState } from "react";

import { SCROLLED_ENTER_THRESHOLD, SCROLLED_EXIT_THRESHOLD } from "./config";

export function useHeaderBehavior() {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isScrolled, setIsScrolled] = useState(false);
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

  return {
    closeMenu,
    isMenuOpen,
    isScrolled,
    toggleMenu: () => setIsMenuOpen((value) => !value),
  };
}
