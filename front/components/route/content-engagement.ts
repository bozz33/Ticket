import type { PublicContent } from "@/lib/types";
import { getAuthToken, getMarketplaceAuthTokenForTenant } from "@/lib/auth";
import { getContentLikeSummaries } from "@/lib/data/public/catalog";
import { getOrganizerFollowSummaries } from "@/lib/data/public/organizers";
import { organizerFollowKey } from "@/lib/engagement";
import { createHash } from "node:crypto";

export type ContentLikeSummaryMap = Record<string, { liked: boolean; likes: number }>;
export type EventLikeSummaryMap = ContentLikeSummaryMap;
export type OrganizerFollowSummaryMap = Record<string, { following: boolean; followers: number }>;
const RENDERING_CONTEXT_AUTH_TIMEOUT_MS = 2500;

export async function getLikeRenderingContext(items: PublicContent[]): Promise<{
  accountAuthenticated?: boolean;
  accountSessionKey?: string;
  followSummaries: OrganizerFollowSummaryMap;
  likeSummaries: ContentLikeSummaryMap;
}> {
  const hasLikeableContent = items.some((item) => Boolean(item.organizerSlug) && Boolean(item.slug));

  if (!hasLikeableContent) {
    return {
      followSummaries: {},
      likeSummaries: {},
    };
  }

  const token = await getAuthToken();

  if (!token) {
    return {
      accountAuthenticated: undefined,
      followSummaries: {},
      likeSummaries: {},
    };
  }

  const tenantTokens = await getTenantTokens(items);
  const [likeSummaries, followSummaries] = await Promise.all([
    getContentLikeSummaries(items, tenantTokens),
    getOrganizerFollowSummaries(items, tenantTokens),
  ]);

  return {
    accountAuthenticated: true,
    accountSessionKey: createEngagementSessionKey(token),
    followSummaries,
    likeSummaries,
  };
}

function createEngagementSessionKey(token: string): string {
  return createHash("sha256").update(token).digest("base64url").slice(0, 24);
}

async function getTenantTokens(items: PublicContent[]): Promise<Record<string, string>> {
  const tenants = Array.from(
    new Set(items.map((item) => organizerFollowKey(item.organizerSlug)).filter((tenant) => tenant.length > 0)),
  );
  const entries = await Promise.all(
    tenants.map(async (tenant) => [
      tenant,
      await getMarketplaceAuthTokenForTenant(tenant, {
        persist: true,
        timeoutMs: RENDERING_CONTEXT_AUTH_TIMEOUT_MS,
      }),
    ] as const),
  );

  return Object.fromEntries(entries.filter(([, token]) => typeof token === "string" && token.length > 0)) as Record<string, string>;
}
