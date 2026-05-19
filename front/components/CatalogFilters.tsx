"use client";

import { CatalogFilterFields } from "./catalog-filters/CatalogFilterFields";
import { DEFAULT_MODULE_TABS } from "./catalog-filters/config";
import { ModuleTabs } from "./catalog-filters/ModuleTabs";
import type { CatalogFiltersProps } from "./catalog-filters/types";
import { useCatalogFilters } from "./catalog-filters/useCatalogFilters";

export function CatalogFilters({
  action,
  filters,
  categories,
  cities: _cities,
  includeModule = false,
  layout = "default",
  moduleHrefMode = "route",
  moduleTabs = DEFAULT_MODULE_TABS,
  theme = "default",
  forcedModule,
  searchPlaceholder = "Concert, masterclass, stand...",
}: CatalogFiltersProps) {
  const showSortField = false;
  const showSubmitButton = false;
  const filtersController = useCatalogFilters({ action, filters, forcedModule, moduleHrefMode });
  const {
    buildModuleHref,
    category,
    currentModule,
    dateFrom,
    dateTo,
    isGlobalSearch,
    isPending,
    navigateWithFilters,
    price,
    query,
    setCategory,
    setDateFrom,
    setDateTo,
    setPrice,
    setQuery,
  } = filtersController;

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
            <ModuleTabs
              buildModuleHref={buildModuleHref}
              currentModule={currentModule}
              moduleTabs={moduleTabs}
            />
          )}
          {!showSortField && <input name="sort" type="hidden" value={filters.sort ?? "popular"} />}
          {isPending ? <p className="fbar-status">Mise à jour…</p> : null}

          <CatalogFilterFields
            categories={categories}
            category={category}
            dateFrom={dateFrom}
            dateTo={dateTo}
            filters={filters}
            navigateWithFilters={navigateWithFilters}
            price={price}
            query={query}
            searchPlaceholder={searchPlaceholder}
            setCategory={setCategory}
            setDateFrom={setDateFrom}
            setDateTo={setDateTo}
            setPrice={setPrice}
            setQuery={setQuery}
            showSortField={showSortField}
            showSubmitButton={showSubmitButton}
          />
 
        </form>
      </div>
    </div>
  );
}
