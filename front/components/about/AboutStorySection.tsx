import type { PublicContentSummary } from "@/lib/types";

type AboutStorySectionProps = {
  galleryImages: string[];
  heroImage: string;
  summary: PublicContentSummary;
};

export function AboutStorySection({ galleryImages, heroImage, summary }: AboutStorySectionProps) {
  return (
    <section className="section">
      <div className="shell about-story-grid">
        <div className="about-story-copy">
          <div className="section-header">
            <div>
              <p className="eyebrow">Notre mission</p>
              <h2>Donner aux organisations une présence publique qui inspire confiance et convertit vraiment</h2>
              <p className="section-copy">
                Ticket n&apos;est pas seulement une vitrine. La plateforme doit permettre à une organisation de présenter
                clairement son activité, publier ses contenus, vendre ou réserver proprement, recevoir des
                candidatures, et suivre tout cela dans un environnement maîtrisé.
              </p>
            </div>
          </div>

          <div className="about-stat-grid">
            <article>
              <strong>{summary.totalItems}</strong>
              <span>contenus publics publiés</span>
            </article>
            <article>
              <strong>{summary.activeCategories}</strong>
              <span>catégories actives</span>
            </article>
            <article>
              <strong>{summary.activeCities}</strong>
              <span>villes visibles</span>
            </article>
            <article>
              <strong>{summary.activeCountries}</strong>
              <span>pays représentés</span>
            </article>
          </div>

          <div className="prose">
            <p>
              L&apos;objectif est simple : éviter les catalogues froids, les fiches incomplètes, les tunnels d&apos;achat
              fragiles et les profils organisateurs qui ne rassurent personne. On veut une plateforme qui tienne autant
              en communication qu&apos;en exploitation.
            </p>
            <p>
              Côté super-admin, les équipes pilotent la publication, les pages front, les frais, les remboursements et
              l&apos;observabilité. Côté organisateur, chaque tenant dispose d&apos;un panel de gestion orienté métier pour ses
              contenus, ses offres, ses accès et ses demandes.
            </p>
          </div>
        </div>

        <aside className="about-story-media">
          <div className="about-story-media__stack">
            <img
              alt="Scène et public en événement"
              className="about-story-media__hero"
              src={galleryImages[0] || heroImage}
            />
            <div className="about-story-media__mosaic">
              {galleryImages.slice(1, 4).map((image, index) => (
                <img alt={`Aperçu Ticket ${index + 1}`} key={image} src={image} />
              ))}
            </div>
            <div className="about-story-media__note">
              <strong>Marketplace multi-modules</strong>
              <span>
                Une seule plateforme pour les événements, les formations, les stands, les candidatures et les campagnes.
              </span>
            </div>
          </div>
        </aside>
      </div>
    </section>
  );
}
