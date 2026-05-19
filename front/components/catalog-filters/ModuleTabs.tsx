import Link from "next/link";

import type { ModuleRoute } from "@/lib/types";

import type { ModuleTab } from "./types";

type ModuleTabsProps = {
  currentModule: ModuleRoute | "all";
  moduleTabs: ModuleTab[];
  buildModuleHref: (moduleValue: ModuleRoute | "all") => string;
};

export function ModuleTabs({ buildModuleHref, currentModule, moduleTabs }: ModuleTabsProps) {
  return (
    <div className="fbar-tabs" role="tablist">
      {moduleTabs.map((mod) => (
        <Link
          className={`fbar-tab${currentModule === mod.value ? " is-active" : ""}`}
          href={buildModuleHref(mod.value)}
          key={mod.value}
          role="tab"
          scroll={false}
        >
          {mod.label}
        </Link>
      ))}
    </div>
  );
}
