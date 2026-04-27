import Link from "next/link";

import { getCityOverview } from "@/lib/data/public";

export const dynamic = "force-dynamic";

export default async function CitiesPage() {
  const cities = await getCityOverview();

  return (
    <>
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">Destinations</p>
          <h1>Villes</h1>
          <p>Un autre point d'entree fort pour les visiteurs qui cherchent d'abord un lieu.</p>
        </div>
      </section>

      <section className="section section--light">
        <div className="shell tile-grid">
          {cities.map((entry) => (
            <article className="explore-tile" key={entry.city}>
              <span className="badge">{entry.count} contenus</span>
              <h2>{entry.city}</h2>
              <p>{entry.sample?.category ?? "Catalogue public"}</p>
              <Link className="button button--ghost" href={`/recherche?city=${encodeURIComponent(entry.city)}`}>
                Voir les contenus
              </Link>
            </article>
          ))}
        </div>
      </section>
    </>
  );
}
