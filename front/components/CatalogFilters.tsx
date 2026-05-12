"use client";

import Link from "next/link";
import { useEffect, useState, useTransition } from "react";
import { useRouter } from "next/navigation";
import { buildSearchQuery } from "@/lib/utils";
import { ModuleRoute, SearchFilters } from "@/lib/types";

const AUTO_APPLY_DELAY_MS = 400;

/* ================================================================
   CatalogFilters — Barre horizontale style Boleto
   Corrections v2 :
   - Fond uniforme sur tous les champs (plus de fond doré sur PRIX)
   - Bouton "Rechercher" toujours visible
   - Champ "Tri" ajouté entre Prix et le bouton
   - Overflow contrôlé (nowrap → wrap sur mobile)
   - Icônes cohérentes
   ================================================================ */

const DEFAULT_MODULE_TABS: Array<{ value: ModuleRoute | "all"; label: string }> = [
  { value: "all", label: "Tous" },
  { value: "formations", label: "Formations" },
  { value: "stands", label: "Stands" },
  { value: "appels-a-projets", label: "Appels a projets" },
  { value: "crowdfunding", label: "Crowdfunding" },
];

const MODULE_ROUTES: ModuleRoute[] = ["evenements", "formations", "stands", "appels-a-projets", "crowdfunding"];

function isModuleRoute(value: string): value is ModuleRoute {
  return MODULE_ROUTES.includes(value as ModuleRoute);
}

/* ── Icônes inline ─────────────────────────────────────────────── */
function IconSearch() {
  return (
    <svg fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.9" viewBox="0 0 24 24">
      <circle cx="10.8" cy="10.8" r="7.3" />
      <path d="m16.2 16.2 4.3 4.3" />
    </svg>
  );
}

function IconGrid() {
  return (
    <svg fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.9" viewBox="0 0 24 24">
      <rect height="5" rx="1" width="5" x="5.5" y="5.5" />
      <rect height="5" rx="1" width="5" x="13.5" y="5.5" />
      <rect height="5" rx="1" width="5" x="5.5" y="13.5" />
      <rect height="5" rx="1" width="5" x="13.5" y="13.5" />
    </svg>
  );
}

function IconPin() {
  return (
    <svg fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.9" viewBox="0 0 24 24">
      <rect height="14" rx="2" width="16" x="4" y="6" />
      <path d="M8 3v6M16 3v6M4 11h16" />
    </svg>
  );
}

function IconTicket() {
  return (
    <svg fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.9" viewBox="0 0 24 24">
      <path d="M4.5 7.5A2.5 2.5 0 0 1 7 5h10a2.5 2.5 0 0 1 2.5 2.5v2.2a2.3 2.3 0 0 0 0 4.6v2.2A2.5 2.5 0 0 1 17 19H7a2.5 2.5 0 0 1-2.5-2.5v-2.2a2.3 2.3 0 0 0 0-4.6V7.5Z" />
    </svg>
  );
}

function IconSort() {
  return (
    <svg fill="none" stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.9" viewBox="0 0 24 24">
      <path d="M3 6h18M7 12h10M10 18h4" />
    </svg>
  );
}

/* ================================================================
   Composant
   ================================================================ */
export function CatalogFilters({
  action,
  filters,
  categories,
  cities,
  includeModule = false,
  layout = "default",
  moduleHrefMode = "route",
  moduleTabs = DEFAULT_MODULE_TABS,
  theme = "default",
  forcedModule,
  searchPlaceholder = "Concert, masterclass, stand...",
}: {
  action: string;
  filters: SearchFilters;
  categories: string[];
  cities: string[];
  includeModule?: boolean;
  layout?: "default" | "wide";
  moduleHrefMode?: "route" | "query";
  moduleTabs?: Array<{ value: ModuleRoute | "all"; label: string }>;
  theme?: "default" | "organizer" | "organizer-event";
  forcedModule?: ModuleRoute;
  searchPlaceholder?: string;
}) {
  const isGlobalSearch = action === "/recherche";
  const baseModule = action.replace(/^\//, "") as ModuleRoute;
  const queryDrivenModule = (((filters.module === "all" || !filters.module || (isGlobalSearch && filters.module === "evenements")) ? "all" : filters.module) as ModuleRoute | "all");
  const isDedicatedModuleRoute = isModuleRoute(baseModule);
  const currentModule = forcedModule ?? (isGlobalSearch
    ? queryDrivenModule
    : moduleHrefMode === "query"
      ? queryDrivenModule
      : ((isDedicatedModuleRoute ? (baseModule === "evenements" ? "all" : baseModule) : queryDrivenModule) as ModuleRoute | "all"));
  const showSortField = false;
  const showSubmitButton = false;
  const router = useRouter();
  const [isPending, startTransition] = useTransition();
  const [query, setQuery] = useState(filters.q ?? "");
  const [category, setCategory] = useState(filters.category ?? "");
  const [dateFrom, setDateFrom] = useState(filters.dateFrom ?? "");
  const [dateTo, setDateTo] = useState(filters.dateTo ?? "");
  const [price, setPrice] = useState<"all" | "free" | "paid">(filters.price ?? "all");

  const navigateWithFilters = (patch: Partial<SearchFilters> = {}) => {
    const nextFilters: SearchFilters = {
      ...filters,
      q: patch.q ?? query,
      category: patch.category ?? category,
      dateFrom: patch.dateFrom ?? dateFrom,
      dateTo: patch.dateTo ?? dateTo,
      price: patch.price ?? price,
      sort: filters.sort ?? "popular",
      page: 1,
    };

    const normalizedFilters: SearchFilters = {
      ...nextFilters,
      q: nextFilters.q?.trim() ? nextFilters.q.trim() : undefined,
      category: nextFilters.category?.trim() ? nextFilters.category.trim() : undefined,
      dateFrom: nextFilters.dateFrom?.trim() ? nextFilters.dateFrom.trim() : undefined,
      dateTo: nextFilters.dateTo?.trim() ? nextFilters.dateTo.trim() : undefined,
      price: nextFilters.price ?? "all",
      sort: nextFilters.sort ?? "popular",
      module: forcedModule ?? (isGlobalSearch || moduleHrefMode === "query" ? currentModule : undefined),
      page: 1,
    };

    startTransition(() => {
      router.replace(`${action}${buildSearchQuery(normalizedFilters)}`, { scroll: false });
    });
  };

  const buildModuleHref = (moduleValue: ModuleRoute | "all") => {
    if (forcedModule) {
      return `${action}${buildSearchQuery({
        ...filters,
        module: forcedModule,
        page: 1,
      })}`;
    }

    if (isGlobalSearch) {
      return `/recherche${buildSearchQuery({
        ...filters,
        module: moduleValue,
        page: 1,
      })}`;
    }

    if (moduleHrefMode === "query") {
      return `${action}${buildSearchQuery({
        ...filters,
        module: moduleValue,
        page: 1,
      })}`;
    }

    if (moduleValue === "all") {
      return "/recherche";
    }

    if (moduleValue === baseModule) {
      return `/${baseModule}`;
    }

    return `/${moduleValue}`;
  };

  useEffect(() => {
    setQuery(filters.q ?? "");
    setCategory(filters.category ?? "");
    setDateFrom(filters.dateFrom ?? "");
    setDateTo(filters.dateTo ?? "");
    setPrice(filters.price ?? "all");
  }, [filters.category, filters.dateFrom, filters.dateTo, filters.price, filters.q]);

  useEffect(() => {
    const normalizedCurrentQuery = (filters.q ?? "").trim();
    const normalizedNextQuery = query.trim();

    if (normalizedCurrentQuery === normalizedNextQuery) {
      return;
    }

    const timeout = window.setTimeout(() => {
      navigateWithFilters({ q: query });
    }, AUTO_APPLY_DELAY_MS);

    return () => {
      window.clearTimeout(timeout);
    };
  }, [filters.q, query]);

  useEffect(() => {
    const normalizedCurrentDateFrom = (filters.dateFrom ?? "").trim();
    const normalizedCurrentDateTo = (filters.dateTo ?? "").trim();
    const normalizedNextDateFrom = dateFrom.trim();
    const normalizedNextDateTo = dateTo.trim();

    if (normalizedCurrentDateFrom === normalizedNextDateFrom && normalizedCurrentDateTo === normalizedNextDateTo) {
      return;
    }

    const timeout = window.setTimeout(() => {
      navigateWithFilters({ dateFrom, dateTo });
    }, AUTO_APPLY_DELAY_MS);

    return () => {
      window.clearTimeout(timeout);
    };
  }, [dateFrom, dateTo, filters.dateFrom, filters.dateTo]);

  return (
    <div className={`filter-bar-wrapper${layout === "wide" ? " filter-bar-wrapper--wide" : ""}${theme === "organizer" ? " filter-bar-wrapper--organizer" : ""}${theme === "organizer-event" ? " filter-bar-wrapper--organizer-event" : ""}`}>
      <div className="shell">
        <form action={action} className="filter-bar-form" method="get" onSubmit={(event) => {
          event.preventDefault();
          navigateWithFilters();
        }}>
          {(forcedModule || isGlobalSearch || moduleHrefMode === "query") && currentModule !== "all" ? (
            <input name="module" type="hidden" value={currentModule} />
          ) : null}

          {/* ── Onglets module (si recherche globale) ────────── */}
          {includeModule && (
            <>
              <div className="fbar-tabs" role="tablist">
                {moduleTabs.map((mod) => (
                  <Link
                    className={`fbar-tab${currentModule === mod.value ? " is-active" : ""}`}
                    href={buildModuleHref(mod.value)}
                    key={mod.value}
                    role="tab"
                    scroll={false}
                  >
                    {mod.label}
                  </Link>
                ))}
              </div>
            </>
          )}
          {!showSortField && <input name="sort" type="hidden" value={filters.sort ?? "popular"} />}
          {isPending ? <p className="fbar-status">Mise à jour…</p> : null}

          {/* ── Champs de filtre ──────────────────────────────── */}
          <div className="fbar-fields">

            {/* Recherche */}
            <div className="fbar-field fbar-field--search">
              <div className="fbar-field__icon"><IconSearch /></div>
              <div className="fbar-field__stack">
                <span className="fbar-field__label">Recherche</span>
                <input
                  className="fbar-field__input"
                  name="q"
                  onChange={(event) => setQuery(event.currentTarget.value)}
                  placeholder={searchPlaceholder}
                  type="text"
                  value={query}
                />
              </div>
            </div>

            <div className="fbar-divider" aria-hidden="true" />

            {/* Catégorie */}
            <div className="fbar-field">
              <div className="fbar-field__icon"><IconGrid /></div>
              <div className="fbar-field__stack">
                <span className="fbar-field__label">Categorie</span>
                <select
                  className="fbar-field__input"
                  name="category"
                  onChange={(event) => {
                    setCategory(event.currentTarget.value);
                    navigateWithFilters({ category: event.currentTarget.value });
                  }}
                  value={category}
                >
                  <option value="">Toutes</option>
                  {categories.map((cat) => (
                    <option key={cat} value={cat}>{cat}</option>
                  ))}
                </select>
              </div>
            </div>

            <div className="fbar-divider" aria-hidden="true" />

            {/* Ville */}
            <div className="fbar-field">
              <div className="fbar-field__icon"><IconPin /></div>
              <div className="fbar-field__stack">
                <span className="fbar-field__label">Date</span>
                <div className="fbar-date-range">
                  <input
                    className="fbar-field__input fbar-date-range__input"
                    name="date_from"
                    onChange={(event) => {
                      setDateFrom(event.currentTarget.value);
                    }}
                    type="date"
                    value={dateFrom}
                  />
                  <input
                    className="fbar-field__input fbar-date-range__input"
                    name="date_to"
                    onChange={(event) => {
                      setDateTo(event.currentTarget.value);
                    }}
                    type="date"
                    value={dateTo}
                  />
                </div>
              </div>
            </div>

            <div className="fbar-divider" aria-hidden="true" />

            {/* Prix */}
            <div className="fbar-field">
              <div className="fbar-field__icon"><IconTicket /></div>
              <div className="fbar-field__stack">
                <span className="fbar-field__label">Prix</span>
                <select
                  className="fbar-field__input"
                  name="price"
                  onChange={(event) => {
                    const nextPrice = event.currentTarget.value as "all" | "free" | "paid";
                    setPrice(nextPrice);
                    navigateWithFilters({ price: nextPrice });
                  }}
                  value={price}
                >
                  <option value="all">Tous</option>
                  <option value="free">Gratuit</option>
                  <option value="paid">Payant</option>
                </select>
              </div>
            </div>

            {showSortField && <div className="fbar-divider" aria-hidden="true" />}

            {showSortField && (
              <div className="fbar-field">
                <div className="fbar-field__icon"><IconSort /></div>
                <div className="fbar-field__stack">
                  <span className="fbar-field__label">Tri</span>
                  <select className="fbar-field__input" defaultValue={filters.sort ?? "popular"} name="sort" onChange={(event) => event.currentTarget.form?.requestSubmit()}>
                    <option value="popular">Populaire</option>
                    <option value="recent">Recent</option>
                    <option value="price">Prix</option>
                  </select>
                </div>
              </div>
            )}

            {showSubmitButton && (
              <button className="fbar-submit" type="submit" aria-label="Lancer la recherche">
                <IconSearch />
                <span>Rechercher</span>
              </button>
            )}
          </div>
 
        </form>
      </div>
    </div>
  );
}
