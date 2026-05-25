"use client";

import { useRouter } from "next/navigation";
import { useEffect, useMemo, useState } from "react";

import type { PlatformConfiguration } from "@/lib/types";
import {
  displayLocaleCode,
  PUBLIC_LOCALE_COOKIE,
  resolveSupportedLocale,
  translate,
} from "@/lib/i18n/public-translations";

type LanguageSwitcherProps = {
  platform: PlatformConfiguration;
  initialLocale?: string;
  locale?: string;
  onLocaleChange?: (locale: string) => void;
};

export function usePublicLocale(platform: PlatformConfiguration, initialLocale?: string) {
  const router = useRouter();
  const fallbackLocale = useMemo(() => resolveSupportedLocale(platform, initialLocale), [initialLocale, platform]);
  const [locale, setLocale] = useState(fallbackLocale);

  useEffect(() => {
    const storedLocale = window.localStorage.getItem(PUBLIC_LOCALE_COOKIE);
    const nextLocale = resolveSupportedLocale(platform, storedLocale || initialLocale || fallbackLocale);

    setLocale(nextLocale);
  }, [fallbackLocale, initialLocale, platform]);

  function updateLocale(nextLocale: string) {
    const normalizedLocale = resolveSupportedLocale(platform, nextLocale);

    setLocale(normalizedLocale);
    window.localStorage.setItem(PUBLIC_LOCALE_COOKIE, normalizedLocale);
    document.cookie = `${PUBLIC_LOCALE_COOKIE}=${encodeURIComponent(normalizedLocale)}; path=/; max-age=31536000; samesite=lax`;

    if (normalizedLocale !== locale) {
      router.refresh();
    }
  }

  return { locale, setLocale: updateLocale, t: (key: string, fallback: string) => translate(platform, locale, key, fallback) };
}

export function LanguageSwitcher({ platform, initialLocale, locale: controlledLocale, onLocaleChange }: LanguageSwitcherProps) {
  const localState = usePublicLocale(platform, initialLocale);
  const locale = controlledLocale ?? localState.locale;
  const setLocale = onLocaleChange ?? localState.setLocale;

  if (platform.languages.length <= 1) {
    return null;
  }

  return (
    <label className="language-switcher">
      <span className="sr-only">Langue</span>
      <span aria-hidden="true" className="language-switcher__icon">
        <svg fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" />
          <path d="M2 12h20" />
          <path d="M12 2a15.3 15.3 0 0 1 0 20" />
          <path d="M12 2a15.3 15.3 0 0 0 0 20" />
        </svg>
      </span>
      <select
        aria-label="Changer de langue"
        onChange={(event) => setLocale(event.target.value)}
        value={locale}
      >
        {platform.languages.map((language) => (
          <option key={language.code} value={language.code}>
            {displayLocaleCode(language.code)}
          </option>
        ))}
      </select>
    </label>
  );
}
