import type { PublicContent } from "@/lib/types";

import { getCallForProjectsDefaultContent } from "./default-content/calls-for-projects";
import { getCrowdfundingDefaultContent } from "./default-content/crowdfunding";
import { getFormationDefaultContent } from "./default-content/formations";
import { getStandDefaultContent } from "./default-content/stands";
import type { ModuleDefaultDetailContent } from "./default-content/types";

export function getModuleDefaultDetailContent(item: PublicContent): ModuleDefaultDetailContent {
  switch (item.module) {
    case "formations":
      return getFormationDefaultContent(item);
    case "stands":
      return getStandDefaultContent(item);
    case "appels-a-projets":
      return getCallForProjectsDefaultContent(item);
    case "crowdfunding":
      return getCrowdfundingDefaultContent();
    default:
      return {
        description: item.description,
        sectionDescription: "Une lecture directe du contenu, puis des blocs adaptés selon le module.",
        program: item.program,
        timeline: item.timeline,
        conditions: item.conditions,
        requiredDocuments: item.requiredDocuments,
        faq: item.faq,
        offerTitle: "Tickets, options ou paliers",
      };
  }
}
