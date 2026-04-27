"use client";

import Link from "next/link";
import { useDeferredValue, useEffect, useState } from "react";

import { SearchSuggestion } from "@/lib/types";
import { getModuleMeta } from "@/lib/utils";

/* ================================================================
   HeroSearch — Composant amélioré
   Changement : suppression des trust badges du bas (.hero-search__trust)
   ================================================================ */

const moduleTabs = [
  { href: "/evenements", label: "Evenements" },
  { href: "/formations", label: "Formations" },
  { href: "/stands", label: "Stands" },
  { href: "/appels-a-projets", label: "Appels a projets" },
  { href: "/crowdfunding", label: "Crowdfunding" },
];

function SearchGlyph({ name }: { name: "search" | "module" }) {
  if (name === "module") {
    return (
      <svg aria-hidden="true" viewBox="0 0 24 24">
        <rect height="5" rx="1" width="5" x="5.5" y="5.5" />
        <rect height="5" rx="1" width="5" x="13.5" y="5.5" />
        <rect height="5" rx="1" width="5" x="5.5" y="13.5" />
        <rect height="5" rx="1" width="5" x="13.5" y="13.5" />
      </svg>
    );
  }

  return (
    <svg aria-hidden="true" viewBox="0 0 24 24">
      <path d="M10.8 18.1a7.3 7.3 0 1 1 0-14.6 7.3 7.3 0 0 1 0 14.6Z" />
      <path d="m16.2 16.2 4.3 4.3" />
    </svg>
  );
}

export function HeroSearch({ categories }: { categories: string[] }) {
  const [query, setQuery] = useState("");
  const [suggestions, setSuggestions] = useState<SearchSuggestion[]>([]);
  const [loading, setLoading] = useState(false);
  const deferredQuery = useDeferredValue(query);

  useEffect(() => {
    const search = deferredQuery.trim();

    if (search.length < 2) {
      setSuggestions([]);
      setLoading(false);
      return;
    }

    const controller = new AbortController();
    const params = new URLSearchParams();

    params.set("q", search);

    setLoading(true);

    fetch(`/api/public/search/suggestions?${params.toString()}`, {
      signal: controller.signal,
      headers: {
        Accept: "application/json",
      },
    })
      .then(async (response) => {
        if (!response.ok) {
          throw new Error("Unable to load suggestions.");
        }

        return response.json() as Promise<{ data?: SearchSuggestion[] }>;
      })
      .then((payload) => {
        setSuggestions(payload.data ?? []);
      })
      .catch((error: unknown) => {
        if (error instanceof Error && error.name === "AbortError") {
          return;
        }

        setSuggestions([]);
      })
      .finally(() => {
        setLoading(false);
      });

    return () => {
      controller.abort();
    };
  }, [deferredQuery]);

  return (
    <form action="/recherche" className="hero-search" method="get">
      <div className="hero-search__head">
        <p>Bienvenue sur Ticket</p>
        <h2>Que recherchez-vous ?</h2>
      </div>
      <div className="hero-search__tabs">
        {moduleTabs.map((tab) => (
          <Link href={tab.href} key={tab.href}>
            {tab.label}
          </Link>
        ))}
      </div>
      <div className="hero-search__fields">
        <div className="hero-search__field-stack">
          <label htmlFor="hero-q">
            <span className="hero-search__field-icon">
              <SearchGlyph name="search" />
            </span>
            <span>Recherche</span>
          </label>
          <input
            id="hero-q"
            name="q"
            onChange={(event) => {
              setQuery(event.target.value);
            }}
            placeholder="Concert, masterclass, stand, programme..."
            value={query}
          />
          {loading || suggestions.length > 0 ? (
            <div className="hero-search__suggestions">
              {loading ? <span className="hero-search__hint">Suggestions en cours...</span> : null}
              {suggestions.map((suggestion) => (
                <Link
                  className="hero-search__suggestion"
                  href={suggestion.href}
                  key={suggestion.href}
                >
                  <strong>{suggestion.title}</strong>
                  <span>
                    {getModuleMeta(suggestion.module).title} — {suggestion.category} —{" "}
                    {suggestion.city}
                  </span>
                </Link>
              ))}
            </div>
          ) : null}
        </div>
        <div className="hero-search__field-stack hero-search__field-stack--category">
          <label htmlFor="hero-category">
            <span className="hero-search__field-icon">
              <SearchGlyph name="module" />
            </span>
            <span>Categorie</span>
          </label>
          <select defaultValue="" id="hero-category" name="category">
            <option value="">Toutes les categories</option>
            {categories.map((category) => (
              <option key={category} value={category}>
                {category}
              </option>
            ))}
          </select>
        </div>
        <button className="button hero-search__submit" type="submit">
          Rechercher
        </button>
      </div>
      {/*
        ⚠️  Trust badges supprimés (demande utilisateur — image 3)
        Les éléments .hero-search__trust ont été retirés volontairement.
        Ils sont aussi masqués via globals-patch.css : .hero-search__trust { display: none }
      */}
    </form>
  );
}
