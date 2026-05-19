import { ABOUT_CAPABILITIES } from "./constants";

export function AboutCapabilitiesSection() {
  return (
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
          {ABOUT_CAPABILITIES.map((capability) => (
            <article className="about-capability-card" key={capability.badge}>
              <span className="badge">{capability.badge}</span>
              <h3>{capability.title}</h3>
              <p>{capability.body}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
