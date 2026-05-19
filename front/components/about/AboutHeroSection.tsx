import type { PlatformConfiguration, PublicContentSummary } from "@/lib/types";

import type { AboutHeroData } from "./types";

type AboutHeroSectionProps = {
  hero: AboutHeroData;
  heroImage: string;
  highlightedOrganizerCount: number;
  platform: PlatformConfiguration;
  summary: PublicContentSummary;
};

export function AboutHeroSection({
  hero,
  heroImage,
  highlightedOrganizerCount,
  platform,
  summary,
}: AboutHeroSectionProps) {
  return (
    <section className="page-hero">
      <img alt={hero?.title ?? "À propos de Ticket"} className="page-hero__image" src={heroImage} />
      <div className="shell page-hero__content">
        <p className="eyebrow">{hero?.eyebrow ?? "À propos de la plateforme"}</p>
        <h1>
          {hero?.title ??
            `Une plateforme publique pensée pour vendre, candidater, réserver et publier avec ${platform.brandName}`}
        </h1>
        <p>
          {hero?.body ??
            "Ticket réunit la billetterie, les formations, les stands, les appels à projets et le crowdfunding dans une expérience publique cohérente, plus crédible pour les acheteurs et plus exploitable pour les organisateurs."}
        </p>
        <div className="page-hero__pills">
          <span>{summary.totalItems} contenus publics</span>
          <span>{highlightedOrganizerCount} organisations visibles</span>
          <span>{summary.offerCount} offres publiées</span>
        </div>
      </div>
    </section>
  );
}
