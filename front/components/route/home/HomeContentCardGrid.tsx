import { ContentCard } from "@/components/ContentCard";
import type { ContentCardLabels } from "@/components/content-card/types";
import { contentEngagementKey, organizerFollowKey } from "@/lib/engagement";
import type { PublicContent } from "@/lib/types";

import type { ContentLikeSummaryMap, OrganizerFollowSummaryMap } from "../content-engagement";

type HomeContentCardGridProps = {
  accountAuthenticated?: boolean;
  accountSessionKey?: string;
  followSummaries: OrganizerFollowSummaryMap;
  items: PublicContent[];
  labels: ContentCardLabels;
  likeSummaries: ContentLikeSummaryMap;
};

export function HomeContentCardGrid({
  accountAuthenticated,
  accountSessionKey,
  followSummaries,
  items,
  labels,
  likeSummaries,
}: HomeContentCardGridProps) {
  return (
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
            labels={labels}
          />
        );
      })}
    </div>
  );
}
