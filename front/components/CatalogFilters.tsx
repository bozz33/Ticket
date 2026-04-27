"use client";

import { useEffect, useRef } from "react";

import { ModuleRoute, SearchFilters } from "@/lib/types";

/* ================================================================
   CatalogFilters — Barre de filtre horizontale style Boleto
   Remplace complètement l'ancien composant sidebar vertical
   ================================================================ */

const ALL_MODULES: Array<{ value: ModuleRoute | "all"; label: string }> = [
  { value: "all", label: "Tous" },
  { value: "evenements", label: "Evenements" },
  { value: "formations", label: "Formations" },
  { value: "stands", label: "Stands" },
  { value: "appels-a-projets", label: "Appels a projets" },
  { value: "crowdfunding", label: "Crowdfunding" },
];

/* SVG Icons — inline pour éviter les imports */
function IconSearch() {
  return (
    <svg
      aria-hidden="true"
      fill="none"
      stroke="currentColor"
      strokeLinecap="round"
      strokeLinejoin="round"
      strokeWidth="1.9"
      viewBox="0 0 24 24"
    >
      <circle cx="10.8" cy="10.8" r="7.3" />
      <path d="m16.2 16.2 4.3 4.3" />
    </svg>
  );
}

function IconCategory() {
  return (
    <svg
      aria-hidden="true"
      fill="none"
      stroke="currentColor"
      strokeLinecap="round"
      strokeLinejoin="round"
      strokeWidth="1.9"
      viewBox="0 0 24 24"
    >
      <rect height="5" rx="1" width="5" x="5.5" y="5.5" />
      <rect height="5" rx="1" width="5" x="13.5" y="5.5" />
      <rect height="5" rx="1" width="5" x="5.5" y="13.5" />
      <rect height="5" rx="1" width="5" x="13.5" y="13.5" />
    </svg>
  );
}

function IconLocation() {
  return (
    <svg
      aria-hidden="true"
      fill="none"
      stroke="currentColor"
      strokeLinecap="round"
      strokeLinejoin="round"
      strokeWidth="1.9"
      viewBox="0 0 24 24"
    >
      <path d="M19 10.4c0 5.1-7 10.1-7 10.1s-7-5-7-10.1A7 7 0 0 1 19 10.4Z" />
      <circle cx="12" cy="10.4" r="2.6" />
    </svg>
  );
}

function IconPrice() {
  return (
    <svg
      aria-hidden="true"
      fill="none"
      stroke="currentColor"
      strokeLinecap="round"
      strokeLinejoin="round"
      strokeWidth="1.9"
      viewBox="0 0 24 24"
    >
      <path d="M4.5 7.5A2.5 2.5 0 0 1 7 5h10a2.5 2.5 0 0 1 2.5 2.5v2.2a2.3 2.3 0 0 0 0 4.6v2.2A2.5 2.5 0 0 1 17 19H7a2.5 2.5 0 0 1-2.5-2.5v-2.2a2.3 2.3 0 0 0 0-4.6V7.5Z" />
    </svg>
  );
}

/* ================================================================
   Composant principal
   ================================================================ */
export function CatalogFilters({
  action,
  filters,
  categories,
  cities,
  includeModule = false,
  layout = "default",
}: {
  action: string;
  filters: SearchFilters;
  categories: string[];
  cities: string[];
  includeModule?: boolean;
  layout?: "default" | "wide";
}) {
  const currentModule = filters.module ?? "all";
  const formRef = useRef<HTMLFormElement | null>(null);
  const inputTimerRef = useRef<number | null>(null);
  const shouldAutoSubmit = true;

  useEffect(() => {
    return () => {
      if (inputTimerRef.current !== null) {
        window.clearTimeout(inputTimerRef.current);
      }
    };
  }, []);

  const submitNow = () => {
    formRef.current?.requestSubmit();
  };

  const scheduleSubmit = () => {
    if (!shouldAutoSubmit) {
      return;
    }

    if (inputTimerRef.current !== null) {
      window.clearTimeout(inputTimerRef.current);
    }

    inputTimerRef.current = window.setTimeout(() => {
      submitNow();
    }, 420);
  };

  return (
    <div className={`filter-bar-wrapper${layout === "wide" ? " filter-bar-wrapper--wide" : ""}`}>
      <div className="shell">
        <form
          action={action}
          className={`filter-bar${layout === "wide" ? " filter-bar--wide" : ""}`}
          method="get"
          ref={formRef}
        >
          <button aria-hidden="true" className="filter-bar__hidden-submit" tabIndex={-1} type="submit" />

          {/* ── Ligne 1 : onglets de module ─────────────────────── */}
          {includeModule && (
            <div className="filter-bar__tabs" role="tablist">
              {ALL_MODULES.map((mod) => (
                <button
                  aria-selected={currentModule === mod.value}
                  className={`filter-bar__tab${currentModule === mod.value ? " is-active" : ""}`}
                  key={mod.value}
                  name="module"
                  role="tab"
                  type="submit"
                  value={mod.value}
                >
                  {mod.label}
                </button>
              ))}
            </div>
          )}

          <input name="sort" type="hidden" value={filters.sort ?? "popular"} />

          {/* ── Ligne 2 : champs filtres ─────────────────────────── */}
          <div className="filter-bar__fields">
            {/* Recherche texte */}
            <div className="filter-bar__field filter-bar__field--search">
              <span className="filter-bar__field-icon" aria-hidden="true">
                <IconSearch />
              </span>
              <div className="filter-bar__field-stack">
                <span className="filter-bar__label">Recherche</span>
                <input
                  defaultValue={filters.q}
                  name="q"
                  onChange={shouldAutoSubmit ? scheduleSubmit : undefined}
                  placeholder="Concert, masterclass, stand..."
                  type="text"
                />
              </div>
            </div>

            {/* Catégorie */}
            <div className="filter-bar__field filter-bar__field--category">
              <span className="filter-bar__field-icon" aria-hidden="true">
                <IconCategory />
              </span>
              <div className="filter-bar__field-stack">
                <span className="filter-bar__label">Categorie</span>
                <select
                  defaultValue={filters.category ?? ""}
                  name="category"
                  onChange={shouldAutoSubmit ? submitNow : undefined}
                >
                  <option value="">Toutes</option>
                  {categories.map((cat) => (
                    <option key={cat} value={cat}>
                      {cat}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            {/* Ville */}
            <div className="filter-bar__field filter-bar__field--city">
              <span className="filter-bar__field-icon" aria-hidden="true">
                <IconLocation />
              </span>
              <div className="filter-bar__field-stack">
                <span className="filter-bar__label">Ville</span>
                <select
                  defaultValue={filters.city ?? ""}
                  name="city"
                  onChange={shouldAutoSubmit ? submitNow : undefined}
                >
                  <option value="">Toutes</option>
                  {cities.map((city) => (
                    <option key={city} value={city}>
                      {city}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            {/* Prix */}
            <div className="filter-bar__field filter-bar__field--price">
              <span className="filter-bar__field-icon" aria-hidden="true">
                <IconPrice />
              </span>
              <div className="filter-bar__field-stack">
                <span className="filter-bar__label">Prix</span>
                <select
                  defaultValue={filters.price ?? "all"}
                  name="price"
                  onChange={shouldAutoSubmit ? submitNow : undefined}
                >
                  <option value="all">Tous</option>
                  <option value="free">Gratuit</option>
                  <option value="paid">Payant</option>
                </select>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  );
}
