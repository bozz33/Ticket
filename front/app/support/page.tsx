import { getPlatformConfiguration } from "@/lib/data/public";

export const dynamic = "force-dynamic";

export default async function SupportPage() {
  const platform = await getPlatformConfiguration();

  return (
    <>
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">Support</p>
          <h1>Centre d'aide public</h1>
          <p>Reassurance checkout, paiement, confirmations et assistance organisateur.</p>
        </div>
      </section>

      <section className="section">
        <div className="shell support-grid">
          <article className="explore-tile">
            <h2>Besoin d'assistance</h2>
            <p>Contact prioritaire pour les visiteurs et acheteurs avant et apres paiement.</p>
            <p>{platform.supportEmail}</p>
            <p>{platform.supportPhone}</p>
          </article>
          <article className="explore-tile">
            <h2>Questions frequentes</h2>
            <ul className="bullet-list">
              <li>Ou retrouver ma confirmation ?</li>
              <li>Quels moyens de paiement sont acceptes ?</li>
              <li>Comment contacter un organisateur ?</li>
              <li>Comment acceder a mon espace utilisateur ?</li>
            </ul>
          </article>
          <article className="explore-tile">
            <h2>Garanties</h2>
            <ul className="bullet-list">
              {platform.reassuranceItems.map((item) => (
                <li key={item}>{item}</li>
              ))}
            </ul>
          </article>
        </div>
      </section>
    </>
  );
}
