import Link from "next/link";

import { OrganizerFollowPill } from "@/components/OrganizerFollowPill";
import type { PublicContent } from "@/lib/types";

import type { ContentCardModel } from "./types";

type ContentCardPublisherProps = {
  accountAuthenticated?: boolean;
  item: PublicContent;
  model: ContentCardModel;
};

export function ContentCardPublisher({ accountAuthenticated, item, model }: ContentCardPublisherProps) {
  return (
    <div className="content-card__publisher">
      <Link className="content-card__publisher-main" href={model.organizerHref}>
        <img alt={model.publisherName} decoding="async" loading="lazy" src={model.publisherImage} />
        <span className="content-card__publisher-copy">
          <small>Publié par</small>
          <strong>{model.publisherName}</strong>
        </span>
      </Link>
      {item.organizerSlug ? (
        <OrganizerFollowPill initialAuthenticated={accountAuthenticated} slug={item.organizerSlug} />
      ) : null}
    </div>
  );
}
