import type { FrontPageData, FrontPageSection } from "@/lib/types";

export function getFrontSection(
  page: FrontPageData | null | undefined,
  type: FrontPageSection["type"],
  key?: string,
): FrontPageSection | undefined {
  return page?.sections.find((section) => section.type === type && (!key || section.key === key));
}

export function sectionText(value: string | null | undefined, fallback: string): string {
  return value && value.trim().length > 0 ? value : fallback;
}

export function sectionStats(
  section: FrontPageSection | undefined,
  fallback: Array<{ label: string; value: string }>,
): Array<{ label: string; value: string }> {
  if (!section || section.items.length === 0) {
    return fallback;
  }

  return section.items
    .map((item, index) => {
      const value = sectionText(item.value, "");

      return {
        label: sectionText(item.label ?? item.title, fallback[index]?.label ?? ""),
        value: value === "Dynamique" ? (fallback[index]?.value ?? "") : value,
      };
    })
    .filter((item) => item.label && item.value);
}
