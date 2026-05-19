import { ABOUT_FLOW_STEPS, ABOUT_VALUES } from "./constants";

export function AboutFlowSection() {
  return (
    <section className="section">
      <div className="shell about-flow-grid">
        <article className="detail-block">
          <p className="eyebrow">Fonctionnement</p>
          <h2>Comment une publication devient une réservation, un achat ou une candidature</h2>
          <div className="timeline timeline--compact">
            {ABOUT_FLOW_STEPS.map((step) => (
              <article className="timeline__item" key={step.index}>
                <p>{step.index}</p>
                <h3>{step.title}</h3>
                <span>{step.body}</span>
              </article>
            ))}
          </div>
        </article>

        <article className="detail-block">
          <p className="eyebrow">Nos engagements</p>
          <h2>Ce qu&apos;on cherche à rendre meilleur à chaque itération</h2>
          <div className="about-values-grid">
            {ABOUT_VALUES.map((value) => (
              <article className="about-value-card" key={value.title}>
                <h3>{value.title}</h3>
                <p>{value.body}</p>
              </article>
            ))}
          </div>
        </article>
      </div>
    </section>
  );
}
