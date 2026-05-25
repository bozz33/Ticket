import { ContentCard } from "@/components/ContentCard";
import { Pagination } from "@/components/route/Pagination";
import { SectionHeader } from "@/components/route/SectionHeader";
import type { ContentLikeSummaryMap, OrganizerFollowSummaryMap } from "@/components/route/content-engagement";
import { contentEngagementKey, organizerFollowKey } from "@/lib/engagement";
import type { PublicContent, SearchFilters } from "@/lib/types";

import type { OrganizerViewOrganizer } from "./types";

type OrganizerCatalogSectionProps = {
  accountAuthenticated?: boolean;
  accountSessionKey?: string;
  currentPage: number;
  followSummaries: OrganizerFollowSummaryMap;
  filters: SearchFilters;
  items: PublicContent[];
  likeSummaries: ContentLikeSummaryMap;
  organizer: OrganizerViewOrganizer;
  totalItems: number;
  totalPages: number;
};

export function OrganizerCatalogSection({
  accountAuthenticated,
  accountSessionKey,
  currentPage,
  followSummaries,
  filters,
  items,
  likeSummaries,
  organizer,
  totalItems,
  totalPages,
}: OrganizerCatalogSectionProps) {
  return (
    <section className="section section--light">
      <div className="shell">
        <SectionHeader
          eyebrow="Catalogue"
          title="Événements de l'organisation"
          description="Cette vue affiche directement tous les événements publics publiés par cette organisation."
        />
        <div className="listing-top-bar organizer-profile__catalog-meta">
          <p className="listing-count organizer-profile__catalog-count">
            <strong>{totalItems}</strong> événements publics
          </p>
          <p className="organizer-profile__catalog-note">
            Tous les événements affichés ici proviennent de {organizer.name}.
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
                  />
                );
              })}
            </div>
            <Pagination
              basePath={`/organisateurs/${organizer.slug}`}
              currentPage={currentPage}
              filters={filters}
              totalPages={totalPages}
            />
          </>
        ) : (
          <div className="empty-state">
            <h3>Aucun événement public n&apos;est disponible pour cette organisation.</h3>
            <p>Revenez plus tard pour découvrir les prochains événements publiés.</p>
          </div>
        )}
      </div>
    </section>
  );
}
