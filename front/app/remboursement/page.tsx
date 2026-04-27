import Link from "next/link";

/* ================================================================
   Politique de remboursement
   URL : /remboursement
   ================================================================ */

const toc = [
  { id: "introduction", label: "Introduction" },
  { id: "conditions", label: "Conditions générales" },
  { id: "annulation-organisateur", label: "Annulation par l'organisateur" },
  { id: "demande-acheteur", label: "Demande de l'acheteur" },
  { id: "delais", label: "Délais de traitement" },
  { id: "moyens", label: "Modes de remboursement" },
  { id: "exclusions", label: "Exclusions" },
  { id: "cas-speciaux", label: "Cas particuliers" },
  { id: "contestation", label: "Contestation & litige" },
  { id: "contact", label: "Nous contacter" },
];

export const metadata = {
  title: "Politique de remboursement — Ticket",
  description:
    "Consultez la politique de remboursement complète de la plateforme Ticket : conditions, délais et modalités de remboursement.",
};

export default function RemboursementPage() {
  return (
    <>
      {/* Hero */}
      <section className="inner-hero">
        <div className="shell">
          <nav className="breadcrumb" aria-label="Fil d'Ariane">
            <Link href="/">Accueil</Link>
            <span className="breadcrumb__sep" aria-hidden="true">›</span>
            <span className="breadcrumb__current">Politique de remboursement</span>
          </nav>
          <p className="inner-hero__eyebrow">Documents légaux</p>
          <h1>Politique de remboursement</h1>
          <p>
            Cette politique définit les conditions dans lesquelles un remboursement peut être
            accordé sur la plateforme Ticket. Nous nous engageons à traiter chaque demande
            avec équité et transparence.
          </p>
          <p className="inner-hero__meta">
            Dernière mise à jour : 1er avril 2025 · Applicable à compter du 1er mai 2025
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

            <section className="legal-section" id="introduction">
              <div className="legal-section__number">1</div>
              <h2>Introduction</h2>
              <div className="legal-infobox">
                <p>
                  <strong>Important :</strong> Cette politique s'applique aux achats effectués
                  directement sur la plateforme Ticket. Les conditions spécifiques à chaque
                  événement, indiquées sur la page de l'offre, peuvent compléter ou restreindre
                  les présentes dispositions.
                </p>
              </div>
              <p>
                La plateforme Ticket agit en qualité d'intermédiaire entre les acheteurs
                (utilisateurs finaux) et les organisateurs (tenants). À ce titre, la politique
                de remboursement repose sur un équilibre entre les droits des acheteurs et les
                engagements contractuels des organisateurs.
              </p>
              <p>
                En achetant un billet, une inscription ou une réservation sur Ticket, vous
                acceptez les conditions de la présente politique ainsi que celles définies par
                l'organisateur de l'événement.
              </p>
            </section>

            <section className="legal-section" id="conditions">
              <div className="legal-section__number">2</div>
              <h2>Conditions générales de remboursement</h2>
              <p>
                Un remboursement peut être accordé dans les situations suivantes :
              </p>
              <ul>
                <li>L'événement est annulé par l'organisateur.</li>
                <li>L'événement est reporté à une date que l'acheteur ne peut pas honorer.</li>
                <li>La politique de l'organisateur prévoit un droit de rétractation.</li>
                <li>Un incident technique imputable à la plateforme a empêché la livraison du billet.</li>
                <li>Le paiement a été débité sans confirmation de commande.</li>
              </ul>
              <p>
                Les remboursements ne sont pas automatiques. Chaque demande est examinée
                individuellement et soumise à validation selon le contexte.
              </p>

              <table className="legal-table">
                <thead>
                  <tr>
                    <th>Situation</th>
                    <th>Remboursement possible</th>
                    <th>Frais de service remboursés</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Annulation par l'organisateur</td>
                    <td>Oui — intégral</td>
                    <td>Oui</td>
                  </tr>
                  <tr>
                    <td>Report de l'événement</td>
                    <td>Selon politique organisateur</td>
                    <td>Selon décision</td>
                  </tr>
                  <tr>
                    <td>Demande acheteur (avant événement)</td>
                    <td>Selon politique organisateur</td>
                    <td>Non</td>
                  </tr>
                  <tr>
                    <td>Billet non utilisé (no-show)</td>
                    <td>Non</td>
                    <td>Non</td>
                  </tr>
                  <tr>
                    <td>Erreur de paiement / doublon</td>
                    <td>Oui — intégral</td>
                    <td>Oui</td>
                  </tr>
                </tbody>
              </table>
            </section>

            <section className="legal-section" id="annulation-organisateur">
              <div className="legal-section__number">3</div>
              <h2>Annulation par l'organisateur</h2>
              <p>
                En cas d'annulation définitive d'un événement par l'organisateur, Ticket s'engage à :
              </p>
              <ul>
                <li>Notifier tous les acheteurs par email dans un délai de 48 heures.</li>
                <li>Procéder au remboursement intégral du montant payé (billets + frais de service).</li>
                <li>Initier les remboursements dans un délai de 5 jours ouvrés suivant la notification.</li>
              </ul>
              <p>
                En cas de report de date, l'acheteur a le choix entre conserver son billet
                pour la nouvelle date ou demander un remboursement complet dans un délai de
                14 jours suivant l'annonce du report.
              </p>
              <div className="legal-infobox">
                <p>
                  <strong>Note :</strong> Si l'annulation est due à un cas de force majeure
                  (catastrophe naturelle, décision gouvernementale, etc.), les modalités de
                  remboursement sont définies conjointement avec l'organisateur et peuvent
                  différer des conditions standard.
                </p>
              </div>
            </section>

            <section className="legal-section" id="demande-acheteur">
              <div className="legal-section__number">4</div>
              <h2>Demande de remboursement par l'acheteur</h2>
              <p>
                Un acheteur peut demander un remboursement de sa propre initiative.
                L'acceptation de cette demande est soumise à la politique définie par
                l'organisateur pour l'événement concerné.
              </p>
              <h3 style={{ fontFamily: "var(--font-body)", fontSize: "1rem", fontWeight: 700, margin: "16px 0 8px" }}>
                Procédure à suivre
              </h3>
              <ol>
                <li>Connectez-vous à votre compte Ticket.</li>
                <li>Accédez à « Mes commandes » et sélectionnez la commande concernée.</li>
                <li>Cliquez sur « Demander un remboursement » si l'option est disponible.</li>
                <li>Sélectionnez le motif et soumettez votre demande.</li>
                <li>Vous recevez une confirmation par email dans les 24 heures.</li>
              </ol>
              <p>
                Si la fonctionnalité n'est pas disponible ou si l'événement est passé,
                contactez notre support à <a href="mailto:support@ticket.africa">support@ticket.africa</a>.
              </p>
              <div className="legal-alert">
                <p>
                  Les demandes de remboursement doivent être formulées avant la date de l'événement.
                  Aucun remboursement ne sera traité pour les billets non utilisés après
                  la tenue de l'événement (sauf annulation prouvée).
                </p>
              </div>
            </section>

            <section className="legal-section" id="delais">
              <div className="legal-section__number">5</div>
              <h2>Délais de traitement des remboursements</h2>
              <p>
                Une fois un remboursement approuvé, les délais de virement varient selon
                le mode de paiement initial :
              </p>
              <table className="legal-table">
                <thead>
                  <tr>
                    <th>Mode de paiement</th>
                    <th>Délai de remboursement</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Carte bancaire (Visa / Mastercard)</td>
                    <td>5 à 10 jours ouvrés</td>
                  </tr>
                  <tr>
                    <td>Orange Money</td>
                    <td>24 à 72 heures</td>
                  </tr>
                  <tr>
                    <td>MTN Mobile Money</td>
                    <td>24 à 72 heures</td>
                  </tr>
                  <tr>
                    <td>Wave</td>
                    <td>24 à 48 heures</td>
                  </tr>
                  <tr>
                    <td>Moov Money</td>
                    <td>24 à 72 heures</td>
                  </tr>
                </tbody>
              </table>
              <p>
                Ces délais sont donnés à titre indicatif et peuvent varier selon les
                établissements financiers concernés. Si vous n'avez pas reçu votre
                remboursement après ces délais, contactez notre support.
              </p>
            </section>

            <section className="legal-section" id="moyens">
              <div className="legal-section__number">6</div>
              <h2>Modes de remboursement</h2>
              <p>
                Les remboursements sont effectués par le même moyen de paiement que celui
                utilisé lors de l'achat. Il n'est pas possible de demander un remboursement
                sur un autre compte ou un autre moyen de paiement.
              </p>
              <p>
                À titre exceptionnel (ex. : fermeture du compte Mobile Money), un
                remboursement par virement bancaire peut être envisagé sur demande écrite
                auprès de notre équipe support, sous réserve de vérification d'identité.
              </p>
            </section>

            <section className="legal-section" id="exclusions">
              <div className="legal-section__number">7</div>
              <h2>Exclusions de remboursement</h2>
              <p>
                Les situations suivantes ne donnent pas lieu à remboursement :
              </p>
              <ul>
                <li>Billet perdu, volé ou non présenté à l'entrée de l'événement.</li>
                <li>Refus d'accès pour comportement inapproprié ou non-conformité aux règles de l'événement.</li>
                <li>Insatisfaction subjective liée au contenu de l'événement (programme, intervenants, etc.).</li>
                <li>Retard de l'acheteur entraînant un accès partiel à l'événement.</li>
                <li>Billet explicitement marqué comme non remboursable lors de l'achat.</li>
                <li>Demande formulée après la tenue de l'événement (hors annulation).</li>
              </ul>
            </section>

            <section className="legal-section" id="cas-speciaux">
              <div className="legal-section__number">8</div>
              <h2>Cas particuliers</h2>
              <h3 style={{ fontFamily: "var(--font-body)", fontSize: "1rem", fontWeight: 700, margin: "0 0 8px" }}>
                Formations et inscriptions
              </h3>
              <p>
                Pour les inscriptions à des formations, la politique de remboursement
                est définie par l'organisme de formation. Un délai de rétractation de
                14 jours est applicable pour les formations en ligne conformément à la
                réglementation en vigueur.
              </p>
              <h3 style={{ fontFamily: "var(--font-body)", fontSize: "1rem", fontWeight: 700, margin: "16px 0 8px" }}>
                Crowdfunding / Contributions
              </h3>
              <p>
                Les contributions à des campagnes de crowdfunding sont généralement non
                remboursables une fois la transaction confirmée, sauf si la campagne
                n'atteint pas son objectif et que l'organisateur a opté pour le modèle
                « tout ou rien ». Les conditions sont précisées sur chaque page de campagne.
              </p>
              <h3 style={{ fontFamily: "var(--font-body)", fontSize: "1rem", fontWeight: 700, margin: "16px 0 8px" }}>
                Réservations de stands
              </h3>
              <p>
                Les remboursements pour les réservations de stands sont soumis au contrat
                signé avec l'organisateur du salon. Des frais d'annulation progressifs
                peuvent s'appliquer selon la date de la demande.
              </p>
            </section>

            <section className="legal-section" id="contestation">
              <div className="legal-section__number">9</div>
              <h2>Contestation et résolution de litige</h2>
              <p>
                Si vous estimez que votre demande de remboursement a été traitée de
                manière incorrecte, vous disposez des recours suivants :
              </p>
              <ol>
                <li>
                  <strong>Escalade interne :</strong> Contactez notre équipe à{" "}
                  <a href="mailto:support@ticket.africa">support@ticket.africa</a> en
                  mentionnant « CONTESTATION REMBOURSEMENT » en objet.
                </li>
                <li>
                  <strong>Médiation :</strong> En l'absence de résolution satisfaisante
                  dans les 15 jours, vous pouvez faire appel à un médiateur de la
                  consommation compétent dans votre pays.
                </li>
                <li>
                  <strong>Chargeback :</strong> En dernier recours, vous pouvez initier
                  une contestation (chargeback) auprès de votre banque ou opérateur
                  Mobile Money pour les paiements injustifiés.
                </li>
              </ol>
            </section>

            <section className="legal-section" id="contact">
              <div className="legal-section__number">10</div>
              <h2>Nous contacter</h2>
              <p>
                Pour toute question relative à notre politique de remboursement ou pour
                soumettre une demande :
              </p>
              <ul>
                <li>
                  <strong>Email :</strong>{" "}
                  <a href="mailto:support@ticket.africa">support@ticket.africa</a>
                </li>
                <li>
                  <strong>Téléphone :</strong>{" "}
                  <a href="tel:+2252722401100">+225 27 22 40 11 00</a>{" "}
                  (disponible 24h/24, 7j/7)
                </li>
                <li>
                  <strong>Formulaire :</strong>{" "}
                  <Link href="/contact">Page de contact</Link>
                </li>
              </ul>
              <div className="legal-infobox">
                <p>
                  <strong>Délai de réponse :</strong> Notre équipe s'engage à répondre
                  à toute demande dans un délai maximum de 48 heures ouvrées.
                </p>
              </div>
            </section>

          </article>
        </div>
      </section>
    </>
  );
}
