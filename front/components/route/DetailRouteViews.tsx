import Link from "next/link";
import { notFound } from "next/navigation";

import { ContentCard } from "@/components/ContentCard";
import { getLikeRenderingContext } from "@/components/route/content-engagement";
import { SectionHeader } from "@/components/route/SectionHeader";
import { contentEngagementKey, organizerFollowKey } from "@/lib/engagement";
import type { PublicContent } from "@/lib/types";
import { formatDateRange, resolveImageSrc } from "@/lib/utils";

import { DetailBlocks } from "./detail/DetailBlocks";
import { StickySummary } from "./detail/StickySummary";

export async function ModuleDetailView({
  item,
  related,
}: {
  item: PublicContent | null;
  related: PublicContent[];
}) {
  if (!item) {
    notFound();
  }

  const coverImage = resolveImageSrc(item.coverImageUrl);
  const organizerImage = resolveImageSrc(item.organizers[0]?.imageUrl, item.coverImageUrl);
  const organizerName = item.organizers[0]?.name ?? "Organisateur";
  const {
    accountAuthenticated,
    accountSessionKey,
    followSummaries,
    likeSummaries,
  } = await getLikeRenderingContext([item, ...related]);
  const itemLikeSummary = likeSummaries[contentEngagementKey(item)];

  return (
    <>
      <section className="detail-hero">
        {coverImage ? <img alt={item.title} className="detail-hero__image" src={coverImage} /> : null}
        <div className="shell detail-hero__content">
          <div className="detail-hero__copy">
            <div className="content-card__badges">
              <span className="badge">{item.category}</span>
              {item.badges.map((badge) => (
                <span className="badge badge--muted" key={badge}>
                  {badge}
                </span>
              ))}
            </div>
            <p className="eyebrow">{item.eyebrow}</p>
            <h1>{item.title}</h1>
            <p className="hero__lede">{item.summary}</p>
            <div className="detail-hero__facts">
              <span>{formatDateRange(item)}</span>
              <span>
                {item.city}, {item.country}
              </span>
            </div>
            <Link
              className="publisher-pill publisher-pill--dark"
              href={`/organisateurs/${item.organizerSlug}`}
            >
              {organizerImage ? <img alt={organizerName} src={organizerImage} /> : null}
              <span>
                <small>Organisateur</small>
                <strong>{organizerName}</strong>
              </span>
            </Link>
          </div>
        </div>
      </section>

      {item.gallery.filter((image) => image?.trim()).length > 0 ? (
        <section className="section section--light section--tight">
          <div className="shell gallery-strip">
            {item.gallery
              .filter((image) => image?.trim())
              .slice(0, 3)
              .map((image) => (
                <article className="gallery-strip__item" key={image}>
                  <img alt={item.title} decoding="async" loading="lazy" src={image} />
                </article>
              ))}
          </div>
        </section>
      ) : null}

      <section className="section">
        <div className="shell detail-layout">
          <div>
            <DetailBlocks item={item} />
          </div>
          <StickySummary
            accountAuthenticated={accountAuthenticated}
            accountSessionKey={accountSessionKey}
            initialLiked={accountAuthenticated === true ? itemLikeSummary?.liked ?? false : undefined}
            initialLikes={itemLikeSummary?.likes ?? item.likesCount}
            item={item}
          />
        </div>
      </section>

      <section className="section section--light">
        <div className="shell">
          <SectionHeader
            action={
              <Link className="button button--ghost" href={`/${item.module}`}>
                Retour au catalogue
              </Link>
            }
            eyebrow="A decouvrir aussi"
            title={`Autres ${item.moduleTitle.toLowerCase()}`}
          />
          <div className="card-grid card-grid--three">
            {related.map((candidate) => {
              const likeSummary = likeSummaries[contentEngagementKey(candidate)];
              const followSummary = followSummaries[organizerFollowKey(candidate.organizerSlug)];

              return (
                <ContentCard
                  accountAuthenticated={accountAuthenticated}
                  accountSessionKey={accountSessionKey}
                  initialFollowing={accountAuthenticated === true ? followSummary?.following ?? false : undefined}
                  initialLiked={accountAuthenticated === true ? likeSummary?.liked ?? false : undefined}
                  initialLikes={likeSummary?.likes ?? candidate.likesCount}
                  item={candidate}
                  key={candidate.id}
                />
              );
            })}
          </div>
        </div>
      </section>
    </>
  );
}
