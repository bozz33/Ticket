import Link from "next/link";

export function HeroSearch({ categories }: { categories: string[] }) {
  const quickLinks = [
    { href: "/formations", label: "Formations" },
    { href: "/stands", label: "Stands" },
    { href: "/appels-a-projets", label: "Appels a projets" },
    { href: "/crowdfunding", label: "Crowdfunding" },
  ];

  return (
    <aside className="hero-search">
      <div className="hero-search__head">
        <p>Marketplace public</p>
        <h2>Trouvez rapidement un contenu</h2>
      </div>

      <div className="hero-search__tabs">
        {quickLinks.map((link) => (
          <Link href={link.href} key={link.href}>
            {link.label}
          </Link>
        ))}
      </div>

      <form action="/recherche" className="hero-search__fields" method="get">
        <div className="hero-search__field-stack">
          <label htmlFor="hero-search-q">
            <span className="hero-search__field-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="7" />
                <path d="m20 20-3.5-3.5" />
              </svg>
            </span>
            Rechercher
          </label>
          <input id="hero-search-q" name="q" placeholder="Evenement, formation, stand..." type="search" />
        </div>

        <div className="hero-search__field-stack">
          <label htmlFor="hero-search-category">
            <span className="hero-search__field-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24">
                <path d="M4 7h16" />
                <path d="M7 12h10" />
                <path d="M10 17h4" />
              </svg>
            </span>
            Categorie
          </label>
          <select defaultValue="" id="hero-search-category" name="category">
            <option value="">Toutes les categories</option>
            {categories.map((category) => (
              <option key={category} value={category}>
                {category}
              </option>
            ))}
          </select>
        </div>

        <button className="button" type="submit">
          Explorer
        </button>
      </form>
    </aside>
  );
}
