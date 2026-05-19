import Link from "next/link";

import { SectionHeader } from "@/components/route/SectionHeader";
import type { PublicContent } from "@/lib/types";
import { formatMoney } from "@/lib/utils";

import { getModuleDefaultDetailContent } from "./default-content";

export function DetailBlocks({ item }: { item: PublicContent }) {
  const defaults = getModuleDefaultDetailContent(item);
  const description = item.module === "evenements" ? item.description : defaults.description;
  const program = item.module === "evenements" ? item.program : defaults.program;
  const timeline = item.module === "evenements" ? item.timeline : defaults.timeline;
  const conditions = item.module === "evenements" ? item.conditions : defaults.conditions;
  const requiredDocuments = item.module === "evenements" ? item.requiredDocuments : defaults.requiredDocuments;
  const faq = item.module === "evenements" ? item.faq : defaults.faq;
  const checkoutSuffix = item.organizerSlug
    ? `?offer=%s&tenant=${encodeURIComponent(item.organizerSlug)}`
    : "?offer=%s";

  return (
    <>
      <section className="detail-block">
        <SectionHeader
          eyebrow="Presentation"
          title={item.summary || item.title}
          description={defaults.sectionDescription}
        />
        <div className="prose">
          <p>{description}</p>
          <ul className="bullet-list">
            {program.map((entry) => (
              <li key={entry}>{entry}</li>
            ))}
          </ul>
        </div>
      </section>

      {item.module === "appels-a-projets" && item.applicationForm ? (
        <section className="detail-block">
          <SectionHeader eyebrow="Candidature" title="Deposer votre dossier" />
          <div className="prose">
            <p>{item.applicationForm.description ?? "Le formulaire public de candidature inclut les informations personnelles, la localisation, le numero avec indicatif et les pieces jointes requises."}</p>
            <Link className="button" href={`/appels-a-projets/${item.slug}/postuler`}>
              {item.applicationForm.submit_label ?? "Soumettre ma candidature"}
            </Link>
          </div>
        </section>
      ) : null}

      {item.tiers.length > 0 ? (
        <section className="detail-block">
          <SectionHeader eyebrow="Offres" title={defaults.offerTitle} />
          <div className="offer-grid">
            {item.tiers.map((tier) => (
              <article className="offer-card" key={tier.id}>
                <div>
                  <h3>{tier.title}</h3>
                  {tier.subtitle ? <p>{tier.subtitle}</p> : null}
                </div>
                <strong>{tier.price === 0 ? "Gratuit" : formatMoney(tier.price, tier.currency)}</strong>
                <ul className="bullet-list">
                  {tier.perks.map((perk) => (
                    <li key={perk}>{perk}</li>
                  ))}
                </ul>
                <Link
                  className="button button--full"
                  href={`/checkout/${item.module}/${item.slug}${checkoutSuffix.replace("%s", encodeURIComponent(tier.id))}`}
                >
                  {tier.ctaLabel}
                </Link>
              </article>
            ))}
          </div>
        </section>
      ) : null}

      {item.speakers.length > 0 ? (
        <section className="detail-block detail-block--speakers">
          <SectionHeader eyebrow="Equipe" title="Intervenants et profils clefs" />
          <div className="people-grid">
            {item.speakers.map((speaker) => (
              <article className="person-card" key={speaker.name}>
                <img alt={speaker.name} decoding="async" loading="lazy" src={speaker.imageUrl} />
                <h3>{speaker.name}</h3>
                <p>{speaker.role}</p>
              </article>
            ))}
          </div>
        </section>
      ) : null}

      <section className="detail-block">
        <SectionHeader eyebrow="Calendrier" title="Moments importants" />
        <div className="timeline">
          {timeline.map((entry) => (
            <article className="timeline__item" key={`${entry.label}-${entry.dateLabel}`}>
              <p>{entry.dateLabel}</p>
              <h3>{entry.label}</h3>
              <span>{entry.description}</span>
            </article>
          ))}
        </div>
      </section>

      {conditions.length > 0 || requiredDocuments.length > 0 ? (
        <section className="detail-block">
          <div className="two-column-text">
            <div>
              <SectionHeader eyebrow="Conditions" title="Regles et eligibilite" />
              <ul className="bullet-list">
                {conditions.map((condition) => (
                  <li key={condition}>{condition}</li>
                ))}
              </ul>
            </div>
            <div>
              <SectionHeader eyebrow="Pieces" title="Documents a prevoir" />
              {requiredDocuments.length > 0 ? (
                <ul className="bullet-list">
                  {requiredDocuments.map((document) => (
                    <li key={document}>{document}</li>
                  ))}
                </ul>
              ) : (
                <p className="section-copy">Aucun document obligatoire supplementaire.</p>
              )}
            </div>
          </div>
        </section>
      ) : null}

      {faq.length > 0 ? (
        <section className="detail-block">
          <SectionHeader eyebrow="FAQ" title="Questions frequentes" />
          <div className="faq-list">
            {faq.map((entry) => (
              <article className="faq-item" key={entry.question}>
                <h3>{entry.question}</h3>
                <p>{entry.answer}</p>
              </article>
            ))}
          </div>
        </section>
      ) : null}
    </>
  );
}
