import type { ModuleRoute } from "@/lib/types";

export const AUTO_APPLY_DELAY_MS = 400;

export const DEFAULT_MODULE_TABS: Array<{ value: ModuleRoute | "all"; label: string }> = [
  { value: "all", label: "Tous" },
  { value: "formations", label: "Formations" },
  { value: "stands", label: "Stands" },
  { value: "appels-a-projets", label: "Appels a projets" },
  { value: "crowdfunding", label: "Crowdfunding" },
];

const MODULE_ROUTES: ModuleRoute[] = ["evenements", "formations", "stands", "appels-a-projets", "crowdfunding"];

export function isModuleRoute(value: string): value is ModuleRoute {
  return MODULE_ROUTES.includes(value as ModuleRoute);
}
