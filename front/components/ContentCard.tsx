import { ContentCardBody } from "./content-card/ContentCardBody";
import { ContentCardMedia } from "./content-card/ContentCardMedia";
import { ContentCardPublisher } from "./content-card/ContentCardPublisher";
import { buildContentCardModel } from "./content-card/helpers";
import type { ContentCardProps } from "./content-card/types";

export function ContentCard({ item, accountAuthenticated, initialLiked }: ContentCardProps) {
  const model = buildContentCardModel(item);

  return (
    <article className={`content-card${model.showMedia ? "" : " content-card--textual"}`}>
      <div className={`content-card__stage${model.showMedia ? "" : " content-card__stage--textual"}`}>
        <ContentCardMedia
          accountAuthenticated={accountAuthenticated}
          initialLiked={initialLiked}
          item={item}
          model={model}
        />
      </div>

      <ContentCardBody item={item} model={model} />
      <ContentCardPublisher accountAuthenticated={accountAuthenticated} item={item} model={model} />
    </article>
  );
}
