"use client";

import { useCallback, useEffect, useState, useTransition } from "react";
import { useRouter } from "next/navigation";

import { buildSearchQuery } from "@/lib/utils";
import type { ModuleRoute, SearchFilters } from "@/lib/types";

import { AUTO_APPLY_DELAY_MS, isModuleRoute } from "./config";
import type { ModuleHrefMode, PriceFilter } from "./types";

type UseCatalogFiltersInput = {
  action: string;
  filters: SearchFilters;
  forcedModule?: ModuleRoute;
  moduleHrefMode: ModuleHrefMode;
};

export function useCatalogFilters({
  action,
  filters,
  forcedModule,
  moduleHrefMode,
}: UseCatalogFiltersInput) {
  const router = useRouter();
  const [isPending, startTransition] = useTransition();
  const [query, setQuery] = useState(filters.q ?? "");
  const [category, setCategory] = useState(filters.category ?? "");
  const [dateFrom, setDateFrom] = useState(filters.dateFrom ?? "");
  const [dateTo, setDateTo] = useState(filters.dateTo ?? "");
  const [price, setPrice] = useState<PriceFilter>(filters.price ?? "all");

  const isGlobalSearch = action === "/recherche";
  const baseModule = action.replace(/^\//, "") as ModuleRoute;
  const queryDrivenModule = (filters.module === "all" || !filters.module || (isGlobalSearch && filters.module === "evenements")
    ? "all"
    : filters.module) as ModuleRoute | "all";
  const isDedicatedModuleRoute = isModuleRoute(baseModule);
  const currentModule =
    forcedModule ??
    (isGlobalSearch
      ? queryDrivenModule
      : moduleHrefMode === "query"
        ? queryDrivenModule
        : ((isDedicatedModuleRoute ? (baseModule === "evenements" ? "all" : baseModule) : queryDrivenModule) as
            | ModuleRoute
            | "all"));

  const navigateWithFilters = useCallback(
    (patch: Partial<SearchFilters> = {}) => {
      const nextFilters: SearchFilters = {
        ...filters,
        q: patch.q ?? query,
        category: patch.category ?? category,
        dateFrom: patch.dateFrom ?? dateFrom,
        dateTo: patch.dateTo ?? dateTo,
        price: patch.price ?? price,
        sort: filters.sort ?? "popular",
        page: 1,
      };

      const normalizedFilters: SearchFilters = {
        ...nextFilters,
        q: nextFilters.q?.trim() ? nextFilters.q.trim() : undefined,
        category: nextFilters.category?.trim() ? nextFilters.category.trim() : undefined,
        dateFrom: nextFilters.dateFrom?.trim() ? nextFilters.dateFrom.trim() : undefined,
        dateTo: nextFilters.dateTo?.trim() ? nextFilters.dateTo.trim() : undefined,
        price: nextFilters.price ?? "all",
        sort: nextFilters.sort ?? "popular",
        module: forcedModule ?? (isGlobalSearch || moduleHrefMode === "query" ? currentModule : undefined),
        page: 1,
      };

      startTransition(() => {
        router.replace(`${action}${buildSearchQuery(normalizedFilters)}`, { scroll: false });
      });
    },
    [
      action,
      category,
      currentModule,
      dateFrom,
      dateTo,
      filters,
      forcedModule,
      isGlobalSearch,
      moduleHrefMode,
      price,
      query,
      router,
    ],
  );

  const buildModuleHref = useCallback(
    (moduleValue: ModuleRoute | "all") => {
      if (forcedModule) {
        return `${action}${buildSearchQuery({
          ...filters,
          module: forcedModule,
          page: 1,
        })}`;
      }

      if (isGlobalSearch) {
        return `/recherche${buildSearchQuery({
          ...filters,
          module: moduleValue,
          page: 1,
        })}`;
      }

      if (moduleHrefMode === "query") {
        return `${action}${buildSearchQuery({
          ...filters,
          module: moduleValue,
          page: 1,
        })}`;
      }

      if (moduleValue === "all") {
        return "/recherche";
      }

      if (moduleValue === baseModule) {
        return `/${baseModule}`;
      }

      return `/${moduleValue}`;
    },
    [action, baseModule, filters, forcedModule, isGlobalSearch, moduleHrefMode],
  );

  useEffect(() => {
    setQuery(filters.q ?? "");
    setCategory(filters.category ?? "");
    setDateFrom(filters.dateFrom ?? "");
    setDateTo(filters.dateTo ?? "");
    setPrice(filters.price ?? "all");
  }, [filters.category, filters.dateFrom, filters.dateTo, filters.price, filters.q]);

  useEffect(() => {
    const normalizedCurrentQuery = (filters.q ?? "").trim();
    const normalizedNextQuery = query.trim();

    if (normalizedCurrentQuery === normalizedNextQuery) {
      return;
    }

    const timeout = window.setTimeout(() => {
      navigateWithFilters({ q: query });
    }, AUTO_APPLY_DELAY_MS);

    return () => {
      window.clearTimeout(timeout);
    };
  }, [filters.q, navigateWithFilters, query]);

  useEffect(() => {
    const normalizedCurrentDateFrom = (filters.dateFrom ?? "").trim();
    const normalizedCurrentDateTo = (filters.dateTo ?? "").trim();
    const normalizedNextDateFrom = dateFrom.trim();
    const normalizedNextDateTo = dateTo.trim();

    if (normalizedCurrentDateFrom === normalizedNextDateFrom && normalizedCurrentDateTo === normalizedNextDateTo) {
      return;
    }

    const timeout = window.setTimeout(() => {
      navigateWithFilters({ dateFrom, dateTo });
    }, AUTO_APPLY_DELAY_MS);

    return () => {
      window.clearTimeout(timeout);
    };
  }, [dateFrom, dateTo, filters.dateFrom, filters.dateTo, navigateWithFilters]);

  return {
    buildModuleHref,
    category,
    currentModule,
    dateFrom,
    dateTo,
    isGlobalSearch,
    isPending,
    navigateWithFilters,
    price,
    query,
    setCategory,
    setDateFrom,
    setDateTo,
    setPrice,
    setQuery,
  };
}
