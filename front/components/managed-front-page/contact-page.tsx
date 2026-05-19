import type { FrontPageSection } from "@/lib/types";

import { getSetting } from "./helpers";

export function renderContactComposite(
  channels: FrontPageSection | undefined,
  form: FrontPageSection | undefined,
) {
  if (!channels && !form) {
    return null;
  }

  const subjectOptions = form?.items ?? [];

  return (
    <section className="section">
      <div className="shell">
        <div className="contact-grid">
          <div>
            {channels ? (
              <div className="contact-card" style={{ marginBottom: "22px" }}>
                {channels.eyebrow ? <p className="eyebrow">{channels.eyebrow}</p> : null}
                {channels.title ? (
                  <h2 style={{ margin: "0 0 20px", fontFamily: "var(--font-display)", fontSize: "1.3rem" }}>
                    {channels.title}
                  </h2>
                ) : null}
                {channels.items.map((item, index) => (
                  <div className="contact-channel" key={`${channels.key}-${index}`}>
                    <div className="contact-channel__icon">
                      <span style={{ fontSize: "0.9rem", fontWeight: 800 }}>
                        {(item.icon || item.label || "i").slice(0, 2).toUpperCase()}
                      </span>
                    </div>
                    <div>
                      {item.label ? <p className="contact-channel__label">{item.label}</p> : null}
                      {item.href ? (
                        <p className="contact-channel__value">
                          <a href={item.href} rel={item.href.startsWith("http") ? "noreferrer" : undefined} target={item.href.startsWith("http") ? "_blank" : undefined}>
                            {item.value || item.href}
                          </a>
                        </p>
                      ) : item.value ? <p className="contact-channel__value">{item.value}</p> : null}
                      {item.body ? <p className="contact-channel__note">{item.body}</p> : null}
                    </div>
                  </div>
                ))}
              </div>
            ) : null}
          </div>

          <div className="contact-card">
            {form?.eyebrow ? <p className="eyebrow">{form.eyebrow}</p> : null}
            {form?.title ? (
              <h2 style={{ margin: "0 0 6px", fontFamily: "var(--font-display)", fontSize: "1.3rem" }}>
                {form.title}
              </h2>
            ) : null}
            {form?.body ? (
              <p style={{ margin: "0 0 24px", color: "var(--text-soft)", fontSize: "0.9rem" }}>{form.body}</p>
            ) : null}

            <form className="contact-form-group">
              <div className="contact-form-row">
                <label className="contact-form-label" htmlFor="contact-first-name">
                  Prénom *
                  <input id="contact-first-name" placeholder="Votre prénom" required type="text" />
                </label>
                <label className="contact-form-label" htmlFor="contact-last-name">
                  Nom *
                  <input id="contact-last-name" placeholder="Votre nom" required type="text" />
                </label>
              </div>

              <div className="contact-form-row">
                <label className="contact-form-label" htmlFor="contact-email">
                  Email *
                  <input id="contact-email" placeholder="vous@exemple.com" required type="email" />
                </label>
                <label className="contact-form-label" htmlFor="contact-phone">
                  Téléphone
                  <input id="contact-phone" placeholder="+225 ..." type="tel" />
                </label>
              </div>

              <label className="contact-form-label" htmlFor="contact-subject">
                Sujet *
                <select id="contact-subject" required>
                  <option value="">-- Sélectionnez un sujet --</option>
                  {subjectOptions.map((item, index) => (
                    <option key={`${form?.key}-subject-${index}`} value={item.label || item.title || `option-${index}`}>
                      {item.title || item.label}
                    </option>
                  ))}
                </select>
              </label>

              <label className="contact-form-label" htmlFor="contact-order-reference">
                Référence de commande
                <input id="contact-order-reference" placeholder="Ex : CMD-2025-00123" type="text" />
              </label>

              <label className="contact-form-label" htmlFor="contact-message">
                Message *
                <textarea id="contact-message" placeholder="Décrivez votre situation en détail..." required rows={5} />
              </label>

              <button className="button button--full" type="button" style={{ minHeight: "52px", fontSize: "0.96rem" }}>
                Envoyer le message
              </button>

              {getSetting(form, "success_note") ? (
                <p style={{ margin: 0, fontSize: "0.78rem", color: "var(--text-soft)", textAlign: "center" }}>
                  {getSetting(form, "success_note")}
                </p>
              ) : null}
            </form>
          </div>
        </div>
      </div>
    </section>
  );
}
