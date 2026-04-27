import Link from "next/link";

/* ================================================================
   Contact & Support
   URL : /contact  (remplace /support/page.tsx)
   ================================================================ */

export const metadata = {
  title: "Contact & Support — Ticket",
  description: "Contactez l'équipe Ticket : support, partenariats et questions organisateurs. Disponibles 24h/24.",
};

export default function ContactPage() {
  return (
    <>
      {/* Hero */}
      <section className="inner-hero">
        <div className="shell">
          <nav className="breadcrumb" aria-label="Fil d'Ariane">
            <Link href="/">Accueil</Link>
            <span className="breadcrumb__sep" aria-hidden="true">›</span>
            <span className="breadcrumb__current">Contact</span>
          </nav>
          <p className="inner-hero__eyebrow">Support client</p>
          <h1>Nous sommes la pour vous</h1>
          <p>
            Une question, un problème ou une demande de partenariat ? Notre equipe est
            disponible 24h/24 pour vous accompagner.
          </p>
        </div>
      </section>

      {/* ── Section principale ──────────────────────────────── */}
      <section className="section">
        <div className="shell">
          <div className="contact-grid">

            {/* Colonne gauche : canaux de contact */}
            <div>
              <div className="contact-card" style={{ marginBottom: "22px" }}>
                <p className="eyebrow">Canaux disponibles</p>
                <h2 style={{ margin: "0 0 20px", fontFamily: "var(--font-display)", fontSize: "1.3rem" }}>
                  Comment nous joindre
                </h2>

                <div className="contact-channel">
                  <div className="contact-channel__icon">
                    <svg viewBox="0 0 24 24"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="m22 6-10 7L2 6"/></svg>
                  </div>
                  <div>
                    <p className="contact-channel__label">Email support</p>
                    <p className="contact-channel__value">
                      <a href="mailto:support@ticket.africa">support@ticket.africa</a>
                    </p>
                    <p className="contact-channel__note">Reponse sous 24-48h ouvrées</p>
                  </div>
                </div>

                <div className="contact-channel">
                  <div className="contact-channel__icon">
                    <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13 19.79 19.79 0 0 1 1.6 4.41 2 2 0 0 1 3.58 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.16 6.16l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                  </div>
                  <div>
                    <p className="contact-channel__label">Telephone</p>
                    <p className="contact-channel__value">
                      <a href="tel:+2252722401100">+225 27 22 40 11 00</a>
                    </p>
                    <p className="contact-channel__note">Disponible 24h/24, 7j/7</p>
                  </div>
                </div>

                <div className="contact-channel">
                  <div className="contact-channel__icon" style={{ background: "rgba(37,211,102,0.1)", color: "#25d366" }}>
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
                  </div>
                  <div>
                    <p className="contact-channel__label">WhatsApp</p>
                    <p className="contact-channel__value">
                      <a href="https://wa.me/2252722401100" target="_blank" rel="noreferrer">Ouvrir la conversation</a>
                    </p>
                    <p className="contact-channel__note">Reponses rapides en journee</p>
                  </div>
                </div>

                <div className="contact-channel">
                  <div className="contact-channel__icon">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                  </div>
                  <div>
                    <p className="contact-channel__label">Horaires</p>
                    <p className="contact-channel__value">24h/24 — 7j/7</p>
                    <p className="contact-channel__note">Support email et telephonique</p>
                  </div>
                </div>
              </div>

              {/* Délais de réponse */}
              <div className="contact-card">
                <p className="eyebrow">SLA — Engagements</p>
                <h2 style={{ margin: "0 0 18px", fontFamily: "var(--font-display)", fontSize: "1.15rem" }}>
                  Nos délais de réponse
                </h2>
                {[
                  { label: "Urgence (paiement débité, billet manquant)", delay: "< 2 heures", color: "#1c9b4d" },
                  { label: "Remboursement en cours", delay: "< 24 heures", color: "var(--accent-strong)" },
                  { label: "Demande générale", delay: "< 48 heures", color: "var(--text-soft)" },
                  { label: "Partenariat organisateur", delay: "< 5 jours", color: "var(--text-soft)" },
                ].map((item) => (
                  <div
                    key={item.label}
                    style={{ display: "flex", justifyContent: "space-between", alignItems: "center", padding: "10px 0", borderBottom: "1px solid rgba(13,20,28,0.06)" }}
                  >
                    <span style={{ fontSize: "0.86rem", color: "var(--text-soft)", maxWidth: "65%" }}>{item.label}</span>
                    <span style={{ fontSize: "0.82rem", fontWeight: 800, color: item.color, whiteSpace: "nowrap" }}>{item.delay}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* Colonne droite : formulaire */}
            <div className="contact-card">
              <p className="eyebrow">Formulaire de contact</p>
              <h2 style={{ margin: "0 0 6px", fontFamily: "var(--font-display)", fontSize: "1.3rem" }}>
                Envoyez-nous un message
              </h2>
              <p style={{ margin: "0 0 24px", color: "var(--text-soft)", fontSize: "0.9rem" }}>
                Decrivez votre situation en detail pour un traitement plus rapide.
              </p>

              <form className="contact-form-group" onSubmit={(e) => e.preventDefault()}>
                <div className="contact-form-row">
                  <label className="contact-form-label">
                    Prénom *
                    <input placeholder="Votre prénom" required type="text" />
                  </label>
                  <label className="contact-form-label">
                    Nom *
                    <input placeholder="Votre nom" required type="text" />
                  </label>
                </div>

                <div className="contact-form-row">
                  <label className="contact-form-label">
                    Email *
                    <input placeholder="vous@exemple.com" required type="email" />
                  </label>
                  <label className="contact-form-label">
                    Téléphone
                    <input placeholder="+225 ..." type="tel" />
                  </label>
                </div>

                <label className="contact-form-label">
                  Sujet *
                  <select required>
                    <option value="">-- Sélectionnez un sujet --</option>
                    <option value="commande">Problème de commande</option>
                    <option value="paiement">Problème de paiement</option>
                    <option value="remboursement">Demande de remboursement</option>
                    <option value="billet">Billet non reçu</option>
                    <option value="organisateur">Devenir organisateur</option>
                    <option value="technique">Problème technique</option>
                    <option value="autre">Autre</option>
                  </select>
                </label>

                <label className="contact-form-label">
                  Référence de commande <span style={{ fontWeight: 500, color: "var(--text-soft)" }}>(optionnel)</span>
                  <input placeholder="Ex : CMD-2025-00123" type="text" />
                </label>

                <label className="contact-form-label">
                  Message *
                  <textarea
                    placeholder="Décrivez votre situation en détail (événement concerné, date, problème rencontré...)..."
                    required
                    rows={5}
                  />
                </label>

                <div style={{ display: "flex", alignItems: "flex-start", gap: "10px" }}>
                  <input id="rgpd" type="checkbox" style={{ marginTop: "4px", width: "auto", minHeight: "auto" }} required />
                  <label htmlFor="rgpd" style={{ fontSize: "0.82rem", color: "var(--text-soft)", cursor: "pointer" }}>
                    J'accepte que mes données soient traitées conformément à la{" "}
                    <Link href="/mentions-legales#donnees" style={{ color: "var(--teal)" }}>
                      politique de confidentialité
                    </Link>
                    .
                  </label>
                </div>

                <button className="button button--full" type="submit" style={{ minHeight: "52px", fontSize: "0.96rem" }}>
                  Envoyer le message
                </button>

                <p style={{ margin: 0, fontSize: "0.78rem", color: "var(--text-soft)", textAlign: "center" }}>
                  Réponse garantie sous 48h ouvrées · Données sécurisées
                </p>
              </form>
            </div>
          </div>
        </div>
      </section>

      {/* ── FAQ rapide ────────────────────────────────────────── */}
      <section className="section section--light">
        <div className="shell">
          <div className="section-header">
            <div>
              <p className="eyebrow">Aide rapide</p>
              <h2>Questions fréquentes</h2>
            </div>
            <Link className="button button--ghost" href="/faq">
              Voir toute la FAQ
            </Link>
          </div>

          <div className="support-grid">
            {[
              {
                title: "Billet non reçu",
                body: "Vérifiez votre dossier spam puis connectez-vous à votre compte. Si le problème persiste, contactez-nous avec votre référence de commande.",
                link: "/faq#commandes",
              },
              {
                title: "Remboursement",
                body: "Le remboursement dépend de la politique de l'organisateur. En cas d'annulation par l'organisateur, le remboursement est automatique et intégral.",
                link: "/remboursement",
              },
              {
                title: "Problème de paiement",
                body: "Si votre compte a été débité sans confirmation, attendez 15 minutes puis contactez-nous immédiatement avec la référence de la transaction.",
                link: "/faq#paiement",
              },
            ].map((item) => (
              <article className="explore-tile" key={item.title}>
                <h2 style={{ margin: 0, fontSize: "1.1rem" }}>{item.title}</h2>
                <p style={{ margin: 0 }}>{item.body}</p>
                <Link className="button button--ghost" href={item.link}>
                  En savoir plus
                </Link>
              </article>
            ))}
          </div>
        </div>
      </section>
    </>
  );
}
