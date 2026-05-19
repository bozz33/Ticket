"use client";

import { useEffect, useState } from "react";

import type {
  CallForProjectApplicationField,
  PublicReferenceCity,
  PublicReferenceCountry,
} from "@/lib/types";

import type { CityPayload, CountryPayload, FormValue } from "./types";

export function useReferenceCountries(): PublicReferenceCountry[] {
  const [countries, setCountries] = useState<PublicReferenceCountry[]>([]);

  useEffect(() => {
    let isMounted = true;

    async function loadCountries() {
      try {
        const response = await fetch("/api/public/references/countries", {
          headers: { Accept: "application/json" },
        });
        const payload = (await response.json().catch(() => null)) as CountryPayload | null;

        if (!response.ok || !payload?.data || !isMounted) {
          return;
        }

        setCountries(payload.data);
      } catch {}
    }

    void loadCountries();

    return () => {
      isMounted = false;
    };
  }, []);

  return countries;
}

export function useCitySearch(
  visibleFields: CallForProjectApplicationField[],
  values: Record<string, FormValue>,
  citySearchInput: Record<string, string>,
) {
  const [citySearchResults, setCitySearchResults] = useState<Record<string, PublicReferenceCity[]>>({});
  const [loadingCitySearch, setLoadingCitySearch] = useState<Record<string, boolean>>({});

  useEffect(() => {
    const controllers: AbortController[] = [];
    const timeouts: number[] = [];
    const cityFields = visibleFields.filter((field) => field.type === "city" && field.country_field);

    for (const field of cityFields) {
      const countryCode = String(values[field.country_field ?? ""] ?? "").toUpperCase();
      const query = (citySearchInput[field.key] ?? "").trim();

      if (!countryCode || query.length < 2) {
        setLoadingCitySearch((current) => ({ ...current, [field.key]: false }));
        setCitySearchResults((current) => ({ ...current, [field.key]: [] }));
        continue;
      }

      const controller = new AbortController();
      controllers.push(controller);
      setLoadingCitySearch((current) => ({ ...current, [field.key]: true }));

      const timeout = window.setTimeout(() => {
        void fetch(`/api/public/references/cities?country=${encodeURIComponent(countryCode)}&q=${encodeURIComponent(query)}&limit=25`, {
          headers: { Accept: "application/json" },
          signal: controller.signal,
        })
          .then(async (response) => {
            const payload = (await response.json().catch(() => null)) as CityPayload | null;

            if (!response.ok || !payload?.data) {
              return;
            }

            setCitySearchResults((current) => ({ ...current, [field.key]: payload.data ?? [] }));
          })
          .catch(() => {})
          .finally(() => {
            setLoadingCitySearch((current) => ({ ...current, [field.key]: false }));
          });
      }, 250);

      timeouts.push(timeout);
    }

    return () => {
      controllers.forEach((controller) => controller.abort());
      timeouts.forEach((timeout) => window.clearTimeout(timeout));
    };
  }, [citySearchInput, values, visibleFields]);

  return {
    citySearchResults,
    loadingCitySearch,
    setCitySearchResults,
  };
}
