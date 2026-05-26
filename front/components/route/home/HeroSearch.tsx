import Link from "next/link";

import type { PlatformConfiguration } from "@/lib/types";
import { translate } from "@/lib/i18n/public-translations";

export function HeroSearch({ categories, locale, platform }: { categories: string[]; locale: string; platform: PlatformConfiguration }) {
  const t = (key: string, fallback: string) => translate(platform, locale, key, fallback);
  const quickLinks = [
    { href: "/formations", label: t("content_card.module.formations", "Formations") },
    { href: "/stands", label: t("content_card.module.stands", "Stands") },
    { href: "/appels-a-projets", label: t("content_card.module.appels_a_projets", "Appels à projets") },
    { href: "/crowdfunding", label: t("content_card.module.crowdfunding", "Crowdfunding") },
  ];

  return (
    <aside className="hero-search">
      <div className="hero-search__head">
        <p>{t("home.search.eyebrow", "Marketplace public")}</p>
        <h2>{t("home.search.title", "Trouvez rapidement un contenu")}</h2>
      </div>

      <div className="hero-search__tabs">
        {quickLinks.map((link) => (
          <Link href={link.href} key={link.href}>
            {link.label}
          </Link>
        ))}
      </div>

      <form action="/recherche" className="hero-search__fields" method="get">
        <div className="hero-search__field-stack">
          <label htmlFor="hero-search-q">
            <span className="hero-search__field-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="7" />
                <path d="m20 20-3.5-3.5" />
              </svg>
            </span>
            {t("home.search.query_label", "Rechercher")}
          </label>
          <input id="hero-search-q" name="q" placeholder={t("home.search.query_placeholder", "Événement, formation, stand...")} type="search" />
        </div>

        <div className="hero-search__field-stack">
          <label htmlFor="hero-search-category">
            <span className="hero-search__field-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24">
                <path d="M4 7h16" />
                <path d="M7 12h10" />
                <path d="M10 17h4" />
              </svg>
            </span>
            {t("home.search.category_label", "Catégorie")}
          </label>
          <select defaultValue="" id="hero-search-category" name="category">
            <option value="">{t("home.search.all_categories", "Toutes les catégories")}</option>
            {categories.map((category) => (
              <option key={category} value={category}>
                {category}
              </option>
            ))}
          </select>
        </div>

        <button className="button" type="submit">
          {t("home.search.submit", "Explorer")}
        </button>
      </form>
    </aside>
  );
}
