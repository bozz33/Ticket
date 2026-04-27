import Link from "next/link";

import { getSpeakerHighlights } from "@/lib/data/public";

export const dynamic = "force-dynamic";

export default async function SpeakersPage() {
  const speakers = await getSpeakerHighlights();

  return (
    <>
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">Intervenants</p>
          <h1>Speakers, mentors et profils invites</h1>
          <p>
            Une lecture plus humaine du catalogue, inspiree des pages speaker et event line-up de
            Boleto.
          </p>
        </div>
      </section>

      <section className="section">
        <div className="shell speaker-grid">
          {speakers.map((speaker) => (
            <article className="speaker-spotlight" key={`${speaker.name}-${speaker.itemSlug}`}>
              <img alt={speaker.name} className="speaker-spotlight__image" src={speaker.imageUrl} />
              <div className="speaker-spotlight__body">
                <span className="badge">{speaker.category}</span>
                <h2>{speaker.name}</h2>
                <p>{speaker.role}</p>
                <div className="speaker-spotlight__meta">
                  <span>{speaker.city}</span>
                  <span>{speaker.itemTitle}</span>
                </div>
                <div className="speaker-spotlight__footer">
                  <Link className="publisher-pill" href={`/organisateurs/${speaker.organizerSlug}`}>
                    <img alt={speaker.organizerName} src={speaker.organizerLogoUrl} />
                    <span>{speaker.organizerName}</span>
                  </Link>
                  <Link className="button button--small" href={`/${speaker.itemModule}/${speaker.itemSlug}`}>
                    Voir le detail
                  </Link>
                </div>
              </div>
            </article>
          ))}
        </div>
      </section>
    </>
  );
}
