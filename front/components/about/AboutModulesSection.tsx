import Link from "next/link";

import type { PublicContentSummary } from "@/lib/types";

type AboutModulesSectionProps = {
  moduleCards: PublicContentSummary["moduleCards"];
};

export function AboutModulesSection({ moduleCards }: AboutModulesSectionProps) {
  return (
    <section className="section section--light">
      <div className="shell">
        <div className="section-header">
          <div>
            <p className="eyebrow">Modules</p>
            <h2>Une plateforme unique, avec des expériences spécialisées par module</h2>
            <p className="section-copy">
              Tous les modules partagent une même base de qualité visuelle, mais gardent leurs propres offres, leurs
              règles métier et leurs parcours publics.
            </p>
          </div>
        </div>

        <div className="module-overview-grid module-overview-grid--rich">
          {moduleCards.map((card) => (
            <article className="module-overview-card" key={card.module}>
              <img alt={card.title} className="module-overview-card__image" src={card.image} />
              <div className="module-overview-card__body">
                <span className="badge">
                  {card.count} publication{card.count > 1 ? "s" : ""}
                </span>
                <h3>{card.title}</h3>
                <p>{card.description}</p>
                <Link className="button button--ghost" href={card.href}>
                  Explorer le module
                </Link>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
