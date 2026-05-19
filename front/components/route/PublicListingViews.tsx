import Link from "next/link";

import { CatalogFilters } from "@/components/CatalogFilters";
import { ContentCard } from "@/components/ContentCard";
import { getLikeRenderingContext } from "@/components/route/content-engagement";
import { Pagination } from "@/components/route/Pagination";
import type { FrontPageData, FrontPageSection, PublicContent, SearchFilters } from "@/lib/types";

function getFrontSection(page: FrontPageData | null | undefined, type: FrontPageSection["type"]): FrontPageSection | undefined {
  return page?.sections.find((section) => section.type === type);
}

function sectionText(value: string | null | undefined, fallback: string): string {
  return value && value.trim().length > 0 ? value : fallback;
}

export async function ModuleListingView({
  page,
  module,
  title,
  singular,
  description,
  heroImageUrl,
  items,
  filters,
  currentPage,
  totalItems,
  totalPages,
  categories,
  cities,
}: {
  page?: FrontPageData | null;
  module: PublicContent["module"];
  title: string;
  singular: string;
  description: string;
  heroImageUrl: string;
  items: PublicContent[];
  filters: SearchFilters;
  currentPage: number;
  totalItems: number;
  totalPages: number;
  categories: string[];
  cities: string[];
}) {
  const hero = getFrontSection(page, "hero");
  const moduleHrefMode = module === "evenements" ? "query" : "route";
  const { accountAuthenticated, likeSummaries } = await getLikeRenderingContext(items);

  return (
    <>
      <section className="page-hero">
        <img alt={sectionText(hero?.title, title)} className="page-hero__image" src={sectionText(hero?.image_url, heroImageUrl)} />
        <div className="shell page-hero__content">
          <p className="eyebrow">{sectionText(hero?.eyebrow, "Catalogue public")}</p>
          <h1>{sectionText(hero?.title, title)}</h1>
          <p>{sectionText(hero?.body, description)}</p>
        </div>
      </section>

      <CatalogFilters
        action={`/${module}`}
        categories={categories}
        cities={cities}
        filters={filters}
        includeModule
        moduleHrefMode={moduleHrefMode}
      />

      <section className="listing-section">
        <div className="shell">
          <div className="listing-top-bar">
            <p className="listing-count">
              <strong>{totalItems}</strong> resultats
            </p>
          </div>

          {items.length > 0 ? (
            <>
              <div className="card-grid card-grid--three">
                {items.map((item) => (
                  <ContentCard
                    accountAuthenticated={accountAuthenticated}
                    initialLiked={likeSummaries[item.slug]?.liked}
                    item={item}
                    key={item.id}
                  />
                ))}
              </div>
              <Pagination
                basePath={`/${module}`}
                currentPage={currentPage}
                filters={filters}
                totalPages={totalPages}
              />
            </>
          ) : (
            <div className="empty-state">
              <h3>Aucun {singular} ne correspond a ces filtres.</h3>
              <p>Elargissez la recherche ou revenez au catalogue complet.</p>
              <Link className="button" href={`/${module}`}>
                Reinitialiser les filtres
              </Link>
            </div>
          )}
        </div>
      </section>
    </>
  );
}

export async function SearchResultsView({
  page,
  items,
  filters,
  currentPage,
  totalItems,
  totalPages,
  categories,
  cities,
}: {
  page?: FrontPageData | null;
  items: PublicContent[];
  filters: SearchFilters;
  currentPage: number;
  totalItems: number;
  totalPages: number;
  categories: string[];
  cities: string[];
}) {
  const hero = getFrontSection(page, "hero");
  const { accountAuthenticated, likeSummaries } = await getLikeRenderingContext(items);

  return (
    <>
      <section className="page-hero page-hero--compact">
        {hero?.image_url ? <img alt={sectionText(hero.title, "Recherche")} className="page-hero__image" src={hero.image_url} /> : null}
        <div className="shell page-hero__content">
          <p className="eyebrow">{sectionText(hero?.eyebrow, "Recherche globale")}</p>
          <h1>{sectionText(hero?.title, "Tout le catalogue public")}</h1>
          <p>{sectionText(hero?.body, "Une seule recherche pour tous les modules et tous les organisateurs.")}</p>
        </div>
      </section>

      <CatalogFilters
        action="/recherche"
        categories={categories}
        cities={cities}
        filters={filters}
        includeModule
        layout="wide"
      />

      <section className="listing-section">
        <div className="shell">
          <div className="listing-top-bar">
            <p className="listing-count">
              <strong>{totalItems}</strong> resultats
            </p>
          </div>

          {items.length > 0 ? (
            <>
              <div className="card-grid card-grid--three">
                {items.map((item) => (
                  <ContentCard
                    accountAuthenticated={accountAuthenticated}
                    initialLiked={likeSummaries[item.slug]?.liked}
                    item={item}
                    key={item.id}
                  />
                ))}
              </div>
              <Pagination
                basePath="/recherche"
                currentPage={currentPage}
                filters={filters}
                totalPages={totalPages}
              />
            </>
          ) : (
            <div className="empty-state">
              <h3>Aucun contenu ne correspond a ces filtres.</h3>
              <p>Modifiez vos criteres ou explorez les catalogues par module.</p>
              <Link className="button" href="/recherche">
                Reinitialiser
              </Link>
            </div>
          )}
        </div>
      </section>
    </>
  );
}
