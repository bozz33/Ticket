import Link from "next/link";

import { getManagedPageMetadata } from "@/components/ManagedFrontPageRoute";
import {
  getFrontPageData,
  getOrganizerHighlights,
  getPlatformConfiguration,
  getPublicContentSummary,
} from "@/lib/data/public";
import { getStaticPageHeroImage } from "@/lib/utils";

export const revalidate = 120;
export const generateMetadata = () =>
  getManagedPageMetadata("/a-propos", {
    title: "A propos | Ticket",
    description:
      "Découvrez Ticket, une plateforme publique pensée pour publier, réserver, acheter, candidater et piloter des contenus multi-modules.",
  });

const VALUES = [
  {
    title: "Clarté publique",
    body: "Chaque fiche doit être compréhensible dès le premier écran: contenu, prix, dates, organisateur, conditions et prochaine action.",
  },
  {
    title: "Conversion propre",
    body: "Le catalogue, le checkout, les reçus, les passes et les confirmations sont pensés comme un seul parcours, pas comme des blocs isolés.",
  },
  {
    title: "Pilotage organisateur",
    body: "Les entreprises et organisations publient, suivent leurs offres, leurs candidatures, leurs ventes et leurs accès depuis un panel robuste.",
  },
];

export default async function AboutPage() {
  const [platform, page, organizers, summary] = await Promise.all([
    getPlatformConfiguration(),
    getFrontPageData("/a-propos"),
    getOrganizerHighlights(),
    getPublicContentSummary(),
  ]);

  const hero = page?.sections.find((section) => section.type === "hero");
  const heroImage = hero?.image_url || getStaticPageHeroImage("a-propos");
  const galleryImages = summary.galleryImages;
  const highlightedOrganizers = organizers.slice(0, 6);
  const moduleCards = summary.moduleCards;

  return (
    <>
      <section className="page-hero">
        <img
          alt={hero?.title ?? "À propos de Ticket"}
          className="page-hero__image"
          src={heroImage}
        />
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
            <span>{highlightedOrganizers.length} organisations visibles</span>
            <span>{summary.offerCount} offres publiées</span>
          </div>
        </div>
      </section>

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

      <section className="section section--light">
        <div className="shell">
          <div className="section-header">
            <div>
              <p className="eyebrow">Ce que la plateforme apporte</p>
              <h2>Un cadre plus sérieux pour la découverte, la conversion et le suivi</h2>
              <p className="section-copy">
                Les grandes plateformes de billetterie et de publication ne se contentent pas d&apos;héberger du contenu.
                Elles structurent la confiance, l&apos;intention et l&apos;après-achat. C&apos;est cette logique qu&apos;on applique ici.
              </p>
            </div>
          </div>

          <div className="about-capability-grid">
            <article className="about-capability-card">
              <span className="badge">Découverte</span>
              <h3>Un catalogue lisible dès le premier regard</h3>
              <p>
                Filtres clairs, cartes plus denses, pages module dédiées et navigation unifiée entre tous les types de contenus.
              </p>
            </article>
            <article className="about-capability-card">
              <span className="badge">Conversion</span>
              <h3>Des parcours propres jusqu&apos;à l&apos;achat ou la candidature</h3>
              <p>
                Checkout, demandes, reçus, passes, candidatures et confirmations suivent une logique commune et plus robuste.
              </p>
            </article>
            <article className="about-capability-card">
              <span className="badge">Exploitation</span>
              <h3>Un vrai pilotage côté super-admin et côté tenant</h3>
              <p>
                Pages front, SEO, frais, remboursements, accès, candidatures, offres et profils publics restent configurables.
              </p>
            </article>
          </div>
        </div>
      </section>

      <section className="section">
        <div className="shell about-trust-grid">
          <article className="detail-block">
            <p className="eyebrow">Ils publient déjà</p>
            <h2>Des organisations visibles, pas juste des contenus isolés</h2>
            <p className="section-copy">
              La plateforme met aussi en valeur les structures qui publient. Chaque organisateur peut avoir sa page
              publique, sa bannière, sa description, ses liens, son compteur d&apos;abonnés et la liste de ses contenus.
            </p>
            <div className="about-logo-cloud">
              {highlightedOrganizers.map(({ organizer }) => (
                <Link className="about-logo-tile" href={`/organisateurs/${organizer.slug}`} key={organizer.slug}>
                  <img alt={organizer.name} src={organizer.logoUrl} />
                  <span>{organizer.name}</span>
                </Link>
              ))}
            </div>
          </article>

          <aside className="detail-block about-platform-stats">
            <p className="eyebrow">En bref</p>
            <h2>Le socle opérationnel de Ticket</h2>
            <div className="organizer-profile__hero-stats organizer-profile__hero-stats--compact">
              <div>
                <strong>{platform.usersCount.toLocaleString("fr-FR")}</strong>
                <span>utilisateurs suivis</span>
              </div>
              <div>
                <strong>{summary.offerCount}</strong>
                <span>offres publiées</span>
              </div>
              <div>
                <strong>{summary.freeItems}</strong>
                <span>accès gratuits</span>
              </div>
              <div>
                <strong>{summary.paidItems}</strong>
                <span>contenus monétisés</span>
              </div>
            </div>
          </aside>
        </div>
      </section>

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
                  <span className="badge">{card.count} publication{card.count > 1 ? "s" : ""}</span>
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

      <section className="section">
        <div className="shell about-flow-grid">
          <article className="detail-block">
            <p className="eyebrow">Fonctionnement</p>
            <h2>Comment une publication devient une réservation, un achat ou une candidature</h2>
            <div className="timeline timeline--compact">
              <article className="timeline__item">
                <p>01</p>
                <h3>Publier</h3>
                <span>L&apos;organisateur construit sa fiche, ses visuels, ses offres, ses règles publiques et ses pièces éventuelles.</span>
              </article>
              <article className="timeline__item">
                <p>02</p>
                <h3>Découvrir et comparer</h3>
                <span>L&apos;acheteur explore le catalogue global ou les pages module, puis affine sa lecture avec les filtres publics.</span>
              </article>
              <article className="timeline__item">
                <p>03</p>
                <h3>Convertir</h3>
                <span>Le checkout, la demande ou la candidature se poursuivent avec confirmation, reçu, accès et historique utilisateur.</span>
              </article>
            </div>
          </article>

          <article className="detail-block">
            <p className="eyebrow">Nos engagements</p>
            <h2>Ce qu&apos;on cherche à rendre meilleur à chaque itération</h2>
            <div className="about-values-grid">
              {VALUES.map((value) => (
                <article className="about-value-card" key={value.title}>
                  <h3>{value.title}</h3>
                  <p>{value.body}</p>
                </article>
              ))}
            </div>
          </article>
        </div>
      </section>

      <section className="section section--light">
        <div className="shell about-cta-band">
          <div>
            <p className="eyebrow">Aller plus loin</p>
            <h2>Publier, organiser, convertir et suivre sur une seule plateforme</h2>
            <p className="section-copy">
              Ticket est pensé pour les organisations qui veulent une présence publique plus sérieuse, et pour les
              acheteurs qui attendent un parcours plus clair, du catalogue jusqu&apos;au reçu ou au pass d&apos;accès.
            </p>
          </div>

          <div className="about-cta-band__actions">
            <Link className="button" href="/recherche">
              Explorer le catalogue
            </Link>
            <Link className="button button--ghost" href="/devenir-organisateur">
              Devenir organisateur
            </Link>
            <Link className="button button--ghost" href="/contact">
              Parler au support
            </Link>
          </div>
        </div>
      </section>
    </>
  );
}
