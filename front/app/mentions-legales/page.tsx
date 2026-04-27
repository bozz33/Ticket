import Link from "next/link";

/* ================================================================
   Mentions légales
   URL : /mentions-legales
   ================================================================ */

export const metadata = {
  title: "Mentions légales — Ticket",
  description: "Mentions légales de la plateforme Ticket : éditeur, hébergeur, propriété intellectuelle et données personnelles.",
};

const toc = [
  { id: "editeur", label: "Éditeur du site" },
  { id: "hebergeur", label: "Hébergeur" },
  { id: "objet", label: "Objet de la plateforme" },
  { id: "acces", label: "Accès au service" },
  { id: "propriete", label: "Propriété intellectuelle" },
  { id: "donnees", label: "Données personnelles & RGPD" },
  { id: "cookies", label: "Cookies" },
  { id: "responsabilite", label: "Responsabilité" },
  { id: "liens", label: "Liens hypertextes" },
  { id: "droit", label: "Droit applicable" },
  { id: "contact", label: "Contact" },
];

export default function MentionsLegalesPage() {
  return (
    <>
      {/* Hero */}
      <section className="inner-hero">
        <div className="shell">
          <nav className="breadcrumb" aria-label="Fil d'Ariane">
            <Link href="/">Accueil</Link>
            <span className="breadcrumb__sep" aria-hidden="true">›</span>
            <span className="breadcrumb__current">Mentions légales</span>
          </nav>
          <p className="inner-hero__eyebrow">Documents légaux</p>
          <h1>Mentions légales</h1>
          <p>
            Conformément aux dispositions légales en vigueur, vous trouverez ci-dessous
            l'ensemble des informations légales relatives à la plateforme Ticket.
          </p>
          <p className="inner-hero__meta">
            Dernière mise à jour : 1er avril 2025
          </p>
        </div>
      </section>

      {/* Corps */}
      <section>
        <div className="shell legal-layout">

          {/* Sommaire */}
          <aside>
            <nav className="legal-toc" aria-label="Sommaire">
              <h3>Sommaire</h3>
              <ol className="legal-toc__list">
                {toc.map((item) => (
                  <li className="legal-toc__item" key={item.id}>
                    <a href={`#${item.id}`}>{item.label}</a>
                  </li>
                ))}
              </ol>
            </nav>
          </aside>

          {/* Contenu */}
          <article className="legal-content">

            <section className="legal-section" id="editeur">
              <div className="legal-section__number">1</div>
              <h2>Éditeur du site</h2>
              <p>Le site web accessible à l'adresse <strong>ticket.africa</strong> est édité par :</p>
              <table className="legal-table">
                <tbody>
                  <tr><td><strong>Raison sociale</strong></td><td>Ticket Africa SAS</td></tr>
                  <tr><td><strong>Forme juridique</strong></td><td>Société par Actions Simplifiée</td></tr>
                  <tr><td><strong>Capital social</strong></td><td>10 000 000 XOF</td></tr>
                  <tr><td><strong>Siège social</strong></td><td>Plateau, Avenue Marchand — Abidjan, Côte d'Ivoire</td></tr>
                  <tr><td><strong>RCCM</strong></td><td>CI-ABJ-2024-B-12345</td></tr>
                  <tr><td><strong>N° contribuable</strong></td><td>2024123456789</td></tr>
                  <tr><td><strong>Directeur de publication</strong></td><td>[Nom du responsable légal]</td></tr>
                  <tr><td><strong>Email</strong></td><td>contact@ticket.africa</td></tr>
                  <tr><td><strong>Téléphone</strong></td><td>+225 27 22 40 11 00</td></tr>
                </tbody>
              </table>
            </section>

            <section className="legal-section" id="hebergeur">
              <div className="legal-section__number">2</div>
              <h2>Hébergeur</h2>
              <p>Le site est hébergé par :</p>
              <table className="legal-table">
                <tbody>
                  <tr><td><strong>Société</strong></td><td>Amazon Web Services (AWS)</td></tr>
                  <tr><td><strong>Adresse</strong></td><td>410 Terry Ave N, Seattle, WA 98109, États-Unis</td></tr>
                  <tr><td><strong>Région de données</strong></td><td>Europe (Paris) — eu-west-3</td></tr>
                  <tr><td><strong>Site web</strong></td><td>aws.amazon.com</td></tr>
                </tbody>
              </table>
            </section>

            <section className="legal-section" id="objet">
              <div className="legal-section__number">3</div>
              <h2>Objet de la plateforme</h2>
              <p>
                Ticket est une plateforme numérique multi-organisateurs dédiée à la gestion
                et à la commercialisation d'événements, formations, stands d'exposition,
                appels à projets et campagnes de financement participatif en Afrique.
              </p>
              <p>
                La plateforme met en relation des organisateurs (professionnels et associations)
                avec des utilisateurs finaux souhaitant acheter des billets, s'inscrire à des
                formations, réserver des espaces ou contribuer à des projets.
              </p>
              <p>
                Ticket agit en qualité d'intermédiaire technique et commercial et ne peut
                être tenu responsable du contenu des événements proposés par les organisateurs.
              </p>
            </section>

            <section className="legal-section" id="acces">
              <div className="legal-section__number">4</div>
              <h2>Accès au service</h2>
              <p>
                L'accès au catalogue public de la plateforme est libre et gratuit. La création
                d'un compte utilisateur est requise pour effectuer un achat, une inscription ou
                une contribution.
              </p>
              <p>
                Ticket se réserve le droit de suspendre l'accès au service pour maintenance,
                mise à jour ou en cas d'utilisation abusive, sans préavis ni indemnisation.
              </p>
              <div className="legal-infobox">
                <p>
                  <strong>Âge minimum :</strong> L'utilisation de la plateforme est réservée
                  aux personnes majeures (18 ans et plus) ou aux mineurs agissant sous
                  la supervision d'un représentant légal.
                </p>
              </div>
            </section>

            <section className="legal-section" id="propriete">
              <div className="legal-section__number">5</div>
              <h2>Propriété intellectuelle</h2>
              <p>
                L'ensemble des éléments constituant la plateforme Ticket (logo, charte graphique,
                textes, images, interfaces, code source) est la propriété exclusive de
                Ticket Africa SAS ou de ses partenaires et est protégé par les lois relatives
                à la propriété intellectuelle.
              </p>
              <p>
                Toute reproduction, représentation, modification, publication ou adaptation,
                totale ou partielle, de ces éléments sans autorisation préalable écrite est
                strictement interdite et peut donner lieu à des poursuites judiciaires.
              </p>
              <p>
                Les contenus publiés par les organisateurs (descriptions d'événements, images,
                logos) restent la propriété de leurs auteurs. En les publiant sur Ticket,
                les organisateurs concèdent à la plateforme un droit d'utilisation non exclusif
                aux fins d'affichage et de promotion.
              </p>
            </section>

            <section className="legal-section" id="donnees">
              <div className="legal-section__number">6</div>
              <h2>Données personnelles & RGPD</h2>
              <p>
                Ticket collecte et traite des données personnelles dans le cadre de la
                fourniture de ses services. Ces traitements sont effectués dans le respect
                du Règlement Général sur la Protection des Données (RGPD) et des lois
                locales applicables.
              </p>

              <h3 style={{ fontFamily: "var(--font-body)", fontSize: "1rem", fontWeight: 700, margin: "16px 0 8px" }}>
                Données collectées
              </h3>
              <ul>
                <li>Données d'identification : nom, prénom, email, numéro de téléphone.</li>
                <li>Données de transaction : historique des achats, moyens de paiement (non stockés en clair).</li>
                <li>Données de navigation : adresses IP, cookies techniques.</li>
                <li>Données de compte : préférences, historique des billets.</li>
              </ul>

              <h3 style={{ fontFamily: "var(--font-body)", fontSize: "1rem", fontWeight: 700, margin: "16px 0 8px" }}>
                Finalités du traitement
              </h3>
              <ul>
                <li>Gestion des comptes et authentification.</li>
                <li>Traitement des commandes et émission des billets.</li>
                <li>Gestion du service après-vente et des remboursements.</li>
                <li>Communications transactionnelles (confirmations, rappels).</li>
                <li>Communications marketing (avec consentement).</li>
                <li>Amélioration de la plateforme et analyses statistiques.</li>
              </ul>

              <h3 style={{ fontFamily: "var(--font-body)", fontSize: "1rem", fontWeight: 700, margin: "16px 0 8px" }}>
                Vos droits
              </h3>
              <p>Conformément au RGPD, vous disposez des droits suivants :</p>
              <ul>
                <li><strong>Droit d'accès</strong> — consulter vos données personnelles.</li>
                <li><strong>Droit de rectification</strong> — corriger des informations inexactes.</li>
                <li><strong>Droit à l'effacement</strong> — demander la suppression de vos données.</li>
                <li><strong>Droit d'opposition</strong> — s'opposer à certains traitements.</li>
                <li><strong>Droit à la portabilité</strong> — recevoir vos données dans un format standard.</li>
                <li><strong>Droit à la limitation</strong> — restreindre temporairement un traitement.</li>
              </ul>
              <p>
                Pour exercer ces droits, contactez notre délégué à la protection des données :
                <a href="mailto:dpo@ticket.africa"> dpo@ticket.africa</a>.
              </p>

              <h3 style={{ fontFamily: "var(--font-body)", fontSize: "1rem", fontWeight: 700, margin: "16px 0 8px" }}>
                Conservation des données
              </h3>
              <table className="legal-table">
                <thead>
                  <tr><th>Type de données</th><th>Durée de conservation</th></tr>
                </thead>
                <tbody>
                  <tr><td>Données de compte actif</td><td>Durée du compte + 3 ans</td></tr>
                  <tr><td>Données de transaction</td><td>10 ans (obligation légale)</td></tr>
                  <tr><td>Logs de connexion</td><td>12 mois</td></tr>
                  <tr><td>Données marketing</td><td>3 ans après dernier contact</td></tr>
                </tbody>
              </table>
            </section>

            <section className="legal-section" id="cookies">
              <div className="legal-section__number">7</div>
              <h2>Cookies</h2>
              <p>
                La plateforme Ticket utilise des cookies pour assurer son bon fonctionnement
                et améliorer l'expérience utilisateur.
              </p>
              <table className="legal-table">
                <thead>
                  <tr><th>Type</th><th>Finalité</th><th>Durée</th></tr>
                </thead>
                <tbody>
                  <tr><td>Essentiels</td><td>Session, authentification, panier</td><td>Session</td></tr>
                  <tr><td>Fonctionnels</td><td>Préférences langue et devise</td><td>1 an</td></tr>
                  <tr><td>Analytiques</td><td>Statistiques d'audience (anonymisées)</td><td>13 mois</td></tr>
                  <tr><td>Marketing</td><td>Publicité ciblée (avec consentement)</td><td>13 mois</td></tr>
                </tbody>
              </table>
              <p>
                Vous pouvez gérer vos préférences en matière de cookies via les paramètres
                de votre navigateur ou le gestionnaire de consentement disponible sur la
                plateforme.
              </p>
            </section>

            <section className="legal-section" id="responsabilite">
              <div className="legal-section__number">8</div>
              <h2>Limitation de responsabilité</h2>
              <p>
                Ticket met tout en œuvre pour assurer la disponibilité et la sécurité de
                la plateforme. Cependant, sa responsabilité ne saurait être engagée en cas
                de :
              </p>
              <ul>
                <li>Interruption temporaire du service pour maintenance.</li>
                <li>Panne technique indépendante de la volonté de la plateforme.</li>
                <li>Informations erronées publiées par les organisateurs.</li>
                <li>Non-tenue ou modification d'un événement par l'organisateur.</li>
                <li>Force majeure ou événement extérieur imprévisible.</li>
              </ul>
              <p>
                La responsabilité de Ticket est limitée au montant des sommes effectivement
                versées par l'utilisateur pour la transaction concernée.
              </p>
            </section>

            <section className="legal-section" id="liens">
              <div className="legal-section__number">9</div>
              <h2>Liens hypertextes</h2>
              <p>
                La plateforme peut contenir des liens vers des sites tiers. Ticket n'exerce
                aucun contrôle sur ces sites et décline toute responsabilité quant à leur
                contenu, leur disponibilité ou leur politique de confidentialité.
              </p>
              <p>
                La création de liens hypertextes vers la plateforme Ticket doit faire l'objet
                d'une autorisation préalable écrite.
              </p>
            </section>

            <section className="legal-section" id="droit">
              <div className="legal-section__number">10</div>
              <h2>Droit applicable et juridiction</h2>
              <p>
                Les présentes mentions légales sont régies par le droit ivoirien. En cas de
                litige, les parties s'engagent à rechercher une solution amiable avant tout
                recours judiciaire.
              </p>
              <p>
                À défaut d'accord amiable, tout litige relatif à l'utilisation de la plateforme
                sera soumis à la compétence exclusive des tribunaux d'Abidjan, Côte d'Ivoire.
              </p>
            </section>

            <section className="legal-section" id="contact">
              <div className="legal-section__number">11</div>
              <h2>Contact</h2>
              <p>Pour toute question relative aux présentes mentions légales :</p>
              <ul>
                <li><strong>Email général :</strong> <a href="mailto:contact@ticket.africa">contact@ticket.africa</a></li>
                <li><strong>DPO (données personnelles) :</strong> <a href="mailto:dpo@ticket.africa">dpo@ticket.africa</a></li>
                <li><strong>Support :</strong> <a href="mailto:support@ticket.africa">support@ticket.africa</a></li>
                <li><strong>Téléphone :</strong> <a href="tel:+2252722401100">+225 27 22 40 11 00</a></li>
              </ul>
              <div className="legal-infobox">
                <p>
                  <strong>Signalement d'abus :</strong> Pour signaler un contenu illégal ou
                  abusif sur la plateforme, envoyez un email à{" "}
                  <a href="mailto:abuse@ticket.africa">abuse@ticket.africa</a>.
                </p>
              </div>
            </section>

          </article>
        </div>
      </section>
    </>
  );
}
