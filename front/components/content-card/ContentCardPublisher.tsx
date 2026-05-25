import Link from "next/link";

import { OrganizerFollowPill } from "@/components/OrganizerFollowPill";
import type { PublicContent } from "@/lib/types";

import { defaultContentCardLabels } from "./labels";
import type { ContentCardLabels, ContentCardModel } from "./types";

type ContentCardPublisherProps = {
  accountAuthenticated?: boolean;
  accountSessionKey?: string;
  initialFollowing?: boolean;
  item: PublicContent;
  labels?: ContentCardLabels;
  model: ContentCardModel;
};

export function ContentCardPublisher({
  accountAuthenticated,
  accountSessionKey,
  initialFollowing,
  item,
  labels = defaultContentCardLabels,
  model,
}: ContentCardPublisherProps) {
  return (
    <div className="content-card__publisher">
      <Link className="content-card__publisher-main" href={model.organizerHref}>
        <img alt={model.publisherName} decoding="async" loading="lazy" src={model.publisherImage} />
        <span className="content-card__publisher-copy">
          <small>{labels.publishedBy}</small>
          <strong>{model.publisherName}</strong>
        </span>
      </Link>
      {item.organizerSlug ? (
        <OrganizerFollowPill
          initialAuthenticated={accountAuthenticated}
          initialFollowing={initialFollowing}
          accountSessionKey={accountSessionKey}
          followLabel={labels.followOrganizer}
          followedLabel={labels.organizerFollowed}
          organizerName={model.publisherName}
          slug={item.organizerSlug}
        />
      ) : null}
    </div>
  );
}
