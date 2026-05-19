import type { ModuleRoute, SearchFilters } from "@/lib/types";

export type CatalogFilterLayout = "default" | "wide";
export type CatalogFilterTheme = "default" | "organizer" | "organizer-event";
export type ModuleHrefMode = "route" | "query";
export type ModuleTab = { value: ModuleRoute | "all"; label: string };
export type PriceFilter = "all" | "free" | "paid";

export type CatalogFiltersProps = {
  action: string;
  filters: SearchFilters;
  categories: string[];
  cities: string[];
  includeModule?: boolean;
  layout?: CatalogFilterLayout;
  moduleHrefMode?: ModuleHrefMode;
  moduleTabs?: ModuleTab[];
  theme?: CatalogFilterTheme;
  forcedModule?: ModuleRoute;
  searchPlaceholder?: string;
};
