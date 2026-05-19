import type { PublicContent } from "@/lib/types";

export type EventLikeSummaryMap = Record<string, { liked: boolean; likes: number }>;

export async function getLikeRenderingContext(items: PublicContent[]): Promise<{
  accountAuthenticated?: boolean;
  likeSummaries: EventLikeSummaryMap;
}> {
  const hasLikeableEvents = items.some((item) => item.module === "evenements" && Boolean(item.organizerSlug) && Boolean(item.slug));

  if (!hasLikeableEvents) {
    return {
      likeSummaries: {},
    };
  }

  return {
    likeSummaries: {},
  };
}
