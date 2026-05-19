"use client";

import type { Dispatch, SetStateAction } from "react";

import type { SearchFilters } from "@/lib/types";

import { IconGrid, IconPin, IconSearch, IconSort, IconTicket } from "./icons";
import type { PriceFilter } from "./types";

type CatalogFilterFieldsProps = {
  categories: string[];
  category: string;
  dateFrom: string;
  dateTo: string;
  filters: SearchFilters;
  price: PriceFilter;
  query: string;
  searchPlaceholder: string;
  showSortField: boolean;
  showSubmitButton: boolean;
  navigateWithFilters: (patch?: Partial<SearchFilters>) => void;
  setCategory: Dispatch<SetStateAction<string>>;
  setDateFrom: Dispatch<SetStateAction<string>>;
  setDateTo: Dispatch<SetStateAction<string>>;
  setPrice: Dispatch<SetStateAction<PriceFilter>>;
  setQuery: Dispatch<SetStateAction<string>>;
};

export function CatalogFilterFields({
  categories,
  category,
  dateFrom,
  dateTo,
  filters,
  price,
  query,
  searchPlaceholder,
  showSortField,
  showSubmitButton,
  navigateWithFilters,
  setCategory,
  setDateFrom,
  setDateTo,
  setPrice,
  setQuery,
}: CatalogFilterFieldsProps) {
  return (
    <div className="fbar-fields">
      <div className="fbar-field fbar-field--search">
        <div className="fbar-field__icon">
          <IconSearch />
        </div>
        <div className="fbar-field__stack">
          <span className="fbar-field__label">Recherche</span>
          <input
            aria-label="Recherche"
            className="fbar-field__input"
            name="q"
            onChange={(event) => setQuery(event.currentTarget.value)}
            placeholder={searchPlaceholder}
            type="text"
            value={query}
          />
        </div>
      </div>

      <div className="fbar-divider" aria-hidden="true" />

      <div className="fbar-field">
        <div className="fbar-field__icon">
          <IconGrid />
        </div>
        <div className="fbar-field__stack">
          <span className="fbar-field__label">Catégorie</span>
          <select
            aria-label="Catégorie"
            className="fbar-field__input"
            name="category"
            onChange={(event) => {
              setCategory(event.currentTarget.value);
              navigateWithFilters({ category: event.currentTarget.value });
            }}
            value={category}
          >
            <option value="">Toutes</option>
            {categories.map((cat) => (
              <option key={cat} value={cat}>
                {cat}
              </option>
            ))}
          </select>
        </div>
      </div>

      <div className="fbar-divider" aria-hidden="true" />

      <div className="fbar-field">
        <div className="fbar-field__icon">
          <IconPin />
        </div>
        <div className="fbar-field__stack">
          <span className="fbar-field__label">Date</span>
          <div className="fbar-date-range">
            <input
              aria-label="Date de début"
              className="fbar-field__input fbar-date-range__input"
              name="date_from"
              onChange={(event) => {
                setDateFrom(event.currentTarget.value);
              }}
              type="date"
              value={dateFrom}
            />
            <input
              aria-label="Date de fin"
              className="fbar-field__input fbar-date-range__input"
              name="date_to"
              onChange={(event) => {
                setDateTo(event.currentTarget.value);
              }}
              type="date"
              value={dateTo}
            />
          </div>
        </div>
      </div>

      <div className="fbar-divider" aria-hidden="true" />

      <div className="fbar-field">
        <div className="fbar-field__icon">
          <IconTicket />
        </div>
        <div className="fbar-field__stack">
          <span className="fbar-field__label">Prix</span>
          <select
            aria-label="Prix"
            className="fbar-field__input"
            name="price"
            onChange={(event) => {
              const nextPrice = event.currentTarget.value as PriceFilter;
              setPrice(nextPrice);
              navigateWithFilters({ price: nextPrice });
            }}
            value={price}
          >
            <option value="all">Tous</option>
            <option value="free">Gratuit</option>
            <option value="paid">Payant</option>
          </select>
        </div>
      </div>

      {showSortField && <div className="fbar-divider" aria-hidden="true" />}

      {showSortField && (
        <div className="fbar-field">
          <div className="fbar-field__icon">
            <IconSort />
          </div>
          <div className="fbar-field__stack">
            <span className="fbar-field__label">Tri</span>
            <select
              aria-label="Tri"
              className="fbar-field__input"
              defaultValue={filters.sort ?? "popular"}
              name="sort"
              onChange={(event) => event.currentTarget.form?.requestSubmit()}
            >
              <option value="popular">Populaire</option>
              <option value="recent">Récent</option>
              <option value="price">Prix</option>
            </select>
          </div>
        </div>
      )}

      {showSubmitButton && (
        <button className="fbar-submit" type="submit" aria-label="Lancer la recherche">
          <IconSearch />
          <span>Rechercher</span>
        </button>
      )}
    </div>
  );
}
