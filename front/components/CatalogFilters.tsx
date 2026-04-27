import { ModuleRoute, SearchFilters } from "@/lib/types";
import { getModuleMeta } from "@/lib/utils";

export function CatalogFilters({
  action,
  filters,
  categories,
  cities,
  includeModule = false,
}: {
  action: string;
  filters: SearchFilters;
  categories: string[];
  cities: string[];
  includeModule?: boolean;
}) {
  return (
    <form action={action} className="filters-panel" method="get">
      <div className="filters-panel__group">
        <label htmlFor="q">Recherche</label>
        <input defaultValue={filters.q} id="q" name="q" placeholder="Titre, ville, categorie" />
      </div>

      {includeModule ? (
        <div className="filters-panel__group">
          <label htmlFor="module">Module</label>
          <select defaultValue={filters.module ?? "all"} id="module" name="module">
            <option value="all">Tous</option>
            {(
              [
                "evenements",
                "formations",
                "stands",
                "appels-a-projets",
                "crowdfunding",
              ] as ModuleRoute[]
            ).map((module) => (
              <option key={module} value={module}>
                {getModuleMeta(module).title}
              </option>
            ))}
          </select>
        </div>
      ) : null}

      <div className="filters-panel__group">
        <label htmlFor="category">Categorie</label>
        <select defaultValue={filters.category ?? ""} id="category" name="category">
          <option value="">Toutes</option>
          {categories.map((category) => (
            <option key={category} value={category}>
              {category}
            </option>
          ))}
        </select>
      </div>

      <div className="filters-panel__group">
        <label htmlFor="city">Ville</label>
        <select defaultValue={filters.city ?? ""} id="city" name="city">
          <option value="">Toutes</option>
          {cities.map((city) => (
            <option key={city} value={city}>
              {city}
            </option>
          ))}
        </select>
      </div>

      <div className="filters-panel__group">
        <label htmlFor="price">Prix</label>
        <select defaultValue={filters.price ?? "all"} id="price" name="price">
          <option value="all">Tous</option>
          <option value="free">Gratuit</option>
          <option value="paid">Payant</option>
        </select>
      </div>

      <div className="filters-panel__group">
        <label htmlFor="sort">Tri</label>
        <select defaultValue={filters.sort ?? "popular"} id="sort" name="sort">
          <option value="popular">Populaire</option>
          <option value="recent">Recent</option>
          <option value="price">Prix</option>
        </select>
      </div>

      <button className="button button--full" type="submit">
        Appliquer
      </button>
    </form>
  );
}
