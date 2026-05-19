import type {
  FrontPageData,
  FrontPageSection,
  NavigationLink,
} from "@/lib/types";

export function getSectionsByType(page: FrontPageData, type: FrontPageSection["type"]): FrontPageSection[] {
  return page.sections.filter((section) => section.type === type);
}

export function getSetting(section: FrontPageSection | undefined, key: string): string {
  const value = section?.settings?.[key];

  return typeof value === "string" ? value : "";
}

export function getBooleanSetting(section: FrontPageSection | undefined, key: string): boolean {
  const value = section?.settings?.[key];

  if (typeof value === "boolean") {
    return value;
  }

  if (typeof value === "string") {
    return ["1", "true", "yes", "on"].includes(value.toLowerCase());
  }

  return false;
}

export function hasLinks(links: NavigationLink[]): boolean {
  return links.length > 0;
}
