import { normalizeTenantSlug } from "@/lib/tenant";
import type { PublicContent } from "@/lib/types";

export function contentEngagementKey(item: Pick<PublicContent, "module" | "organizerSlug" | "slug">): string {
  const tenantSlug = normalizeTenantSlug(item.organizerSlug);
  const contentSlug = item.slug.trim();

  return tenantSlug && contentSlug ? `${tenantSlug}:${item.module}:${contentSlug}` : "";
}

export function organizerFollowKey(slug: string): string {
  return normalizeTenantSlug(slug);
}
