import type { FaqEntry, TimelineEntry } from "@/lib/types";

export type ModuleDefaultDetailContent = {
  description: string;
  sectionDescription: string;
  program: string[];
  timeline: TimelineEntry[];
  conditions: string[];
  requiredDocuments: string[];
  faq: FaqEntry[];
  offerTitle: string;
};
