import { ContentCardBody } from "./content-card/ContentCardBody";
import { ContentCardMedia } from "./content-card/ContentCardMedia";
import { ContentCardPublisher } from "./content-card/ContentCardPublisher";
import { buildContentCardModel } from "./content-card/helpers";
import { defaultContentCardLabels } from "./content-card/labels";
import type { ContentCardProps } from "./content-card/types";

export function ContentCard({
  item,
  accountAuthenticated,
  accountSessionKey,
  initialFollowing,
  initialLiked,
  initialLikes,
  labels = defaultContentCardLabels,
}: ContentCardProps) {
  const model = buildContentCardModel(item, labels);

  return (
    <article className={`content-card${model.showMedia ? "" : " content-card--textual"}`}>
      <div className={`content-card__stage${model.showMedia ? "" : " content-card__stage--textual"}`}>
        <ContentCardMedia
          accountAuthenticated={accountAuthenticated}
          accountSessionKey={accountSessionKey}
          initialLiked={initialLiked}
          initialLikes={initialLikes}
          item={item}
          labels={labels}
          model={model}
        />
      </div>

      <ContentCardBody item={item} labels={labels} model={model} />
      <ContentCardPublisher
        accountAuthenticated={accountAuthenticated}
        accountSessionKey={accountSessionKey}
        initialFollowing={initialFollowing}
        item={item}
        labels={labels}
        model={model}
      />
    </article>
  );
}
