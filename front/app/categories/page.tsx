import Link from "next/link";

import { getManagedPageMetadata } from "@/components/ManagedFrontPageRoute";
import { getCategoryOverview, getFrontPageData } from "@/lib/data/public";
import { getStaticPageHeroImage } from "@/lib/utils";

export const revalidate = 120;
export const generateMetadata = () => getManagedPageMetadata("/categories", {
  title: "Categories | Ticket",
  description: "Explorez les categories du catalogue public.",
});

export default async function CategoriesPage() {
  const [categories, page] = await Promise.all([
    getCategoryOverview(),
    getFrontPageData("/categories"),
  ]);
  const hero = page?.sections.find((section) => section.type === "hero");

  return (
    <>
      <section className="page-hero page-hero--compact">
        <img
          alt={hero?.title ?? "Categories"}
          className="page-hero__image"
          src={hero?.image_url || getStaticPageHeroImage("categories")}
        />
        <div className="shell page-hero__content">
          <p className="eyebrow">{hero?.eyebrow || "Navigation"}</p>
          <h1>{hero?.title || "Categories"}</h1>
          <p>{hero?.body || "Entrees editoriales pour accelerer la decouverte sur le portail public."}</p>
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
