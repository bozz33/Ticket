"use client";

import Link from "next/link";
import { useDeferredValue, useEffect, useState } from "react";

import { ModuleRoute, SearchSuggestion } from "@/lib/types";
import { getModuleMeta } from "@/lib/utils";

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
        <path d="M5.5 5.5h5v5h-5z" />
        <path d="M13.5 5.5h5v5h-5z" />
        <path d="M5.5 13.5h5v5h-5z" />
        <path d="M13.5 13.5h5v5h-5z" />
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

export function HeroSearch({ reassuranceItems }: { reassuranceItems: string[] }) {
  const [query, setQuery] = useState("");
  const [module, setModule] = useState<ModuleRoute | "all">("all");
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
    params.set("module", module);

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
  }, [deferredQuery, module]);

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
                    {getModuleMeta(suggestion.module).title} - {suggestion.category} -{" "}
                    {suggestion.city}
                  </span>
                </Link>
              ))}
            </div>
          ) : null}
        </div>
        <div className="hero-search__field-stack">
          <label htmlFor="hero-module">
            <span className="hero-search__field-icon">
              <SearchGlyph name="module" />
            </span>
            <span>Module</span>
          </label>
          <select
            defaultValue="all"
            id="hero-module"
            name="module"
            onChange={(event) => {
              setModule(event.target.value as ModuleRoute | "all");
            }}
          >
            <option value="all">Tous</option>
            <option value="evenements">Evenements</option>
            <option value="formations">Formations</option>
            <option value="stands">Stands</option>
            <option value="appels-a-projets">Appels a projets</option>
            <option value="crowdfunding">Crowdfunding</option>
          </select>
        </div>
        <button className="button" type="submit">
          Rechercher
        </button>
      </div>
      <div className="hero-search__trust">
        {reassuranceItems.map((item) => (
          <span key={item}>{item}</span>
        ))}
      </div>
    </form>
  );
}
