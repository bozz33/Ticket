import Link from "next/link";

import { getCategoryOverview } from "@/lib/data/public";

export const dynamic = "force-dynamic";

export default async function CategoriesPage() {
  const categories = await getCategoryOverview();

  return (
    <>
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">Navigation</p>
          <h1>Categories</h1>
          <p>Entrees editoriales pour accelerer la decouverte sur le portail public.</p>
        </div>
      </section>

      <section className="section">
        <div className="shell tile-grid">
          {categories.map((entry) => (
            <article className="explore-tile" key={entry.category}>
              <span className="badge">{entry.count} contenus</span>
              <h2>{entry.category}</h2>
              <p>{entry.sample?.summary ?? "Selection dynamique du catalogue"}</p>
              <Link className="button button--ghost" href={`/recherche?category=${encodeURIComponent(entry.category)}`}>
                Explorer
              </Link>
            </article>
          ))}
        </div>
      </section>
    </>
  );
}
