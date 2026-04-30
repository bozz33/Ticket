import { ModuleRoute, SearchFilters } from "@/lib/types";

/* ================================================================
   CatalogFilters — Barre horizontale style Boleto
   Corrections v2 :
   - Fond uniforme sur tous les champs (plus de fond doré sur PRIX)
   - Bouton "Rechercher" toujours visible
   - Champ "Tri" ajouté entre Prix et le bouton
   - Overflow contrôlé (nowrap → wrap sur mobile)
   - Icônes cohérentes
   ================================================================ */

const ALL_MODULES: Array<{ value: ModuleRoute | "all"; label: string }> = [
  { value: "all", label: "Tous" },
  { value: "evenements", label: "Evenements" },
  { value: "formations", label: "Formations" },
  { value: "stands", label: "Stands" },
  { value: "appels-a-projets", label: "Appels a projets" },
  { value: "crowdfunding", label: "Crowdfunding" },
];

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
      <path d="M19 10.4c0 5.1-7 10.1-7 10.1s-7-5-7-10.1A7 7 0 0 1 19 10.4Z" />
      <circle cx="12" cy="10.4" r="2.6" />
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
  layout,
}: {
  action: string;
  filters: SearchFilters;
  categories: string[];
  cities: string[];
  includeModule?: boolean;
  layout?: string;
}) {
  const currentModule = filters.module ?? "all";

  return (
    <div className="filter-bar-wrapper">
      <div className="shell">
        <form action={action} className="filter-bar-form" method="get">

          {/* ── Onglets module (si recherche globale) ────────── */}
          {includeModule && (
            <>
              <div className="fbar-tabs" role="tablist">
                {ALL_MODULES.map((mod) => (
                  <button
                    className={`fbar-tab${currentModule === mod.value ? " is-active" : ""}`}
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
              {filters.q && <input name="q" type="hidden" value={filters.q} />}
              {filters.category && <input name="category" type="hidden" value={filters.category} />}
              {filters.city && <input name="city" type="hidden" value={filters.city} />}
            </>
          )}

          {/* ── Champs de filtre ──────────────────────────────── */}
          <div className="fbar-fields">

            {/* Recherche */}
            <div className="fbar-field fbar-field--search">
              <div className="fbar-field__icon"><IconSearch /></div>
              <div className="fbar-field__stack">
                <span className="fbar-field__label">Recherche</span>
                <input
                  className="fbar-field__input"
                  defaultValue={filters.q}
                  name="q"
                  placeholder="Concert, masterclass, stand..."
                  type="text"
                />
              </div>
            </div>

            <div className="fbar-divider" aria-hidden="true" />

            {/* Catégorie */}
            <div className="fbar-field">
              <div className="fbar-field__icon"><IconGrid /></div>
              <div className="fbar-field__stack">
                <span className="fbar-field__label">Categorie</span>
                <select className="fbar-field__input" defaultValue={filters.category ?? ""} name="category">
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
                <span className="fbar-field__label">Ville</span>
                <select className="fbar-field__input" defaultValue={filters.city ?? ""} name="city">
                  <option value="">Toutes</option>
                  {cities.map((city) => (
                    <option key={city} value={city}>{city}</option>
                  ))}
                </select>
              </div>
            </div>

            <div className="fbar-divider" aria-hidden="true" />

            {/* Prix */}
            <div className="fbar-field">
              <div className="fbar-field__icon"><IconTicket /></div>
              <div className="fbar-field__stack">
                <span className="fbar-field__label">Prix</span>
                <select className="fbar-field__input" defaultValue={filters.price ?? "all"} name="price">
                  <option value="all">Tous</option>
                  <option value="free">Gratuit</option>
                  <option value="paid">Payant</option>
                </select>
              </div>
            </div>

            <div className="fbar-divider" aria-hidden="true" />

            {/* Tri */}
            <div className="fbar-field">
              <div className="fbar-field__icon"><IconSort /></div>
              <div className="fbar-field__stack">
                <span className="fbar-field__label">Tri</span>
                <select className="fbar-field__input" defaultValue={filters.sort ?? "popular"} name="sort">
                  <option value="popular">Populaire</option>
                  <option value="recent">Recent</option>
                  <option value="price">Prix</option>
                </select>
              </div>
            </div>

            {/* Bouton Rechercher */}
            <button className="fbar-submit" type="submit" aria-label="Lancer la recherche">
              <IconSearch />
              <span>Rechercher</span>
            </button>
          </div>

        </form>
      </div>
    </div>
  );
}
