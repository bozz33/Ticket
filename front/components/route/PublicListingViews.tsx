import Link from "next/link";
import { cookies } from "next/headers";

import { CatalogFilters } from "@/components/CatalogFilters";
import { ContentCard } from "@/components/ContentCard";
import { buildContentCardLabels } from "@/components/content-card/labels";
import { getLikeRenderingContext } from "@/components/route/content-engagement";
import { Pagination } from "@/components/route/Pagination";
import { getPlatformConfiguration } from "@/lib/data/public";
import { contentEngagementKey, organizerFollowKey } from "@/lib/engagement";
import { PUBLIC_LOCALE_COOKIE, resolveSupportedLocale, translate } from "@/lib/i18n/public-translations";
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
  const platform = await getPlatformConfiguration();
  const cookieStore = await cookies();
  const locale = resolveSupportedLocale(platform, cookieStore.get(PUBLIC_LOCALE_COOKIE)?.value);
  const t = (key: string, fallback: string) => translate(platform, locale, key, fallback);
  const contentCardLabels = buildContentCardLabels(t);
  const {
    accountAuthenticated,
    accountSessionKey,
    followSummaries,
    likeSummaries,
  } = await getLikeRenderingContext(items);

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
      />

      <section className="listing-section">
        <div className="shell">
          <div className="listing-top-bar">
            <p className="listing-count">
              <strong>{totalItems}</strong> {t("listing.results", "resultats")}
            </p>
          </div>

          {items.length > 0 ? (
            <>
              <div className="card-grid card-grid--three">
                {items.map((item) => {
                  const likeSummary = likeSummaries[contentEngagementKey(item)];
                  const followSummary = followSummaries[organizerFollowKey(item.organizerSlug)];

                  return (
                    <ContentCard
                      accountAuthenticated={accountAuthenticated}
                      accountSessionKey={accountSessionKey}
                      initialFollowing={accountAuthenticated === true ? followSummary?.following ?? false : undefined}
                      initialLiked={accountAuthenticated === true ? likeSummary?.liked ?? false : undefined}
                      initialLikes={likeSummary?.likes ?? item.likesCount}
                      item={item}
                      key={item.id}
                      labels={contentCardLabels}
                    />
                  );
                })}
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
              <h3>{t("listing.empty_title", `Aucun ${singular} ne correspond a ces filtres.`)}</h3>
              <p>{t("listing.empty_body", "Elargissez la recherche ou revenez au catalogue complet.")}</p>
              <Link className="button" href={`/${module}`}>
                {t("listing.reset_filters", "Reinitialiser les filtres")}
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
  const platform = await getPlatformConfiguration();
  const cookieStore = await cookies();
  const locale = resolveSupportedLocale(platform, cookieStore.get(PUBLIC_LOCALE_COOKIE)?.value);
  const t = (key: string, fallback: string) => translate(platform, locale, key, fallback);
  const contentCardLabels = buildContentCardLabels(t);
  const {
    accountAuthenticated,
    accountSessionKey,
    followSummaries,
    likeSummaries,
  } = await getLikeRenderingContext(items);

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
              <strong>{totalItems}</strong> {t("listing.results", "resultats")}
            </p>
          </div>

          {items.length > 0 ? (
            <>
              <div className="card-grid card-grid--three">
                {items.map((item) => {
                  const likeSummary = likeSummaries[contentEngagementKey(item)];
                  const followSummary = followSummaries[organizerFollowKey(item.organizerSlug)];

                  return (
                    <ContentCard
                      accountAuthenticated={accountAuthenticated}
                      accountSessionKey={accountSessionKey}
                      initialFollowing={accountAuthenticated === true ? followSummary?.following ?? false : undefined}
                      initialLiked={accountAuthenticated === true ? likeSummary?.liked ?? false : undefined}
                      initialLikes={likeSummary?.likes ?? item.likesCount}
                      item={item}
                      key={item.id}
                      labels={contentCardLabels}
                    />
                  );
                })}
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
              <h3>{t("listing.empty_content_title", "Aucun contenu ne correspond a ces filtres.")}</h3>
              <p>{t("listing.empty_content_body", "Modifiez vos criteres ou explorez les catalogues par module.")}</p>
              <Link className="button" href="/recherche">
                {t("listing.reset", "Reinitialiser")}
              </Link>
            </div>
          )}
        </div>
      </section>
    </>
  );
}
