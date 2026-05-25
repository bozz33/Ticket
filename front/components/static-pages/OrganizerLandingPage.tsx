import Link from "next/link";

import { getStaticPageHeroImage } from "@/lib/utils";

const features = [
  {
    title: "Billetterie et quotas",
    body: "Créez des tickets gratuits ou payants, définissez les capacités, limites par acheteur, catégories, disponibilités et badges de stock.",
  },
  {
    title: "Paiements et reçus",
    body: "Encaissez en ligne, générez les reçus, suivez les commandes et gardez une trace claire des remboursements.",
  },
  {
    title: "Catalogue public",
    body: "Chaque contenu profite d’une fiche publique, de filtres, d’un checkout et d’un parcours acheteur cohérent.",
  },
  {
    title: "Contrôle d’accès",
    body: "Les passes et QR codes permettent de vérifier les entrées et de limiter les duplications ou usages frauduleux.",
  },
  {
    title: "Modules métier",
    body: "Événements, formations, stands, appels à projets et crowdfunding gardent leurs particularités sans casser l’expérience.",
  },
  {
    title: "Backoffice organisateur",
    body: "Chaque organisateur dispose d’un espace isolé pour piloter ses contenus, commandes, reçus, passes et demandes.",
  },
];

const steps = [
  "Créez votre espace organisateur et complétez les informations de votre structure.",
  "Publiez un premier contenu avec visuels, prix, quotas et conditions.",
  "Suivez les ventes, les réservations, les reçus et les passes depuis votre panel.",
  "Contrôlez les accès, traitez les demandes et analysez les performances.",
];

export function OrganizerLandingPage() {
  const heroImage = getStaticPageHeroImage("devenir-organisateur");

  return (
    <>
      <section className="page-hero organizer-landing-hero">
        <img alt="Organisateurs préparant un événement" className="page-hero__image" src={heroImage} />
        <div className="shell page-hero__content">
          <p className="eyebrow">Devenir organisateur</p>
          <h1>Publiez, vendez et contrôlez vos expériences depuis un espace professionnel.</h1>
          <p>
            Ticket accompagne les organisateurs qui veulent une billetterie claire, un paiement fiable, des reçus propres,
            des QR codes vérifiables et un vrai suivi après achat.
          </p>
          <div className="organizer-landing-hero__actions">
            <Link className="button" href="/devenir-organisateur/inscription">Créer mon espace</Link>
            <Link className="button button--ghost-light" href="/contact">Parler à l’équipe</Link>
          </div>
        </div>
      </section>

      <section className="section">
        <div className="shell about-platform-grid">
          <article className="detail-block">
            <p className="eyebrow">Pourquoi Ticket</p>
            <h2>Une plateforme pensée pour les ventes réelles, pas seulement pour publier une affiche.</h2>
            <p className="section-copy">
              Un organisateur a besoin de gérer les prix, stocks, paiements, reçus, entrées, remboursements et messages
              acheteurs dans un même environnement. Ticket assemble ces éléments dans un parcours modulaire et robuste.
            </p>
          </article>
          <aside className="sticky-panel">
            <div className="sticky-panel__price">
              <span>Modèle simple</span>
              <strong>Sans frais fixe au lancement</strong>
            </div>
            <ul className="facts-list">
              <li><strong>Modules</strong><span>Événements, formations, stands, candidatures, campagnes</span></li>
              <li><strong>Paiement</strong><span>Carte bancaire et Mobile Money selon configuration</span></li>
              <li><strong>Documents</strong><span>Reçus A4, passes, QR codes et historique</span></li>
            </ul>
          </aside>
        </div>
      </section>

      <section className="section section--light">
        <div className="shell">
          <div className="section-header">
            <div>
              <p className="eyebrow">Fonctionnalités</p>
              <h2>Tout ce qu’il faut pour gérer avant, pendant et après la vente</h2>
            </div>
          </div>
          <div className="support-grid">
            {features.map((feature) => (
              <article className="explore-tile" key={feature.title}>
                <h3>{feature.title}</h3>
                <p>{feature.body}</p>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section className="section">
        <div className="shell about-flow-grid">
          <article className="detail-block">
            <p className="eyebrow">Mise en route</p>
            <h2>Un parcours simple, mais une architecture prête pour la croissance.</h2>
            <ol className="legal-toc__list organizer-landing-steps">
              {steps.map((step, index) => (
                <li key={step}><strong>{index + 1}.</strong> {step}</li>
              ))}
            </ol>
          </article>
          <article className="detail-block">
            <p className="eyebrow">Accompagnement</p>
            <h2>Vous gardez la maîtrise de vos contenus.</h2>
            <p>
              Le panel organisateur permet de centraliser les informations opérationnelles, mais chaque fiche publique reste lisible
              pour l’acheteur : dates, lieu, prix, conditions, disponibilité et prochaine action.
            </p>
            <Link className="button" href="/devenir-organisateur/inscription">Démarrer maintenant</Link>
          </article>
        </div>
      </section>
    </>
  );
}
