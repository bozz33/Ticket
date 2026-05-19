import type { FrontPageData, FrontPageIndexEntry, PlatformConfiguration } from "@/lib/types";
import {
  defaultMenus,
  defaultPlatformConfiguration,
  fetchJson,
  publicDataCacheSeconds,
  rememberPublicData,
  type PublicFrontPagePayload,
  type PublicFrontPagesIndexPayload,
  type PublicPlatformPayload,
} from "./shared";

export async function getPlatformConfiguration(): Promise<PlatformConfiguration> {
  return rememberPublicData("platform-configuration", {}, publicDataCacheSeconds, async () => {
    const payload = await fetchJson<PublicPlatformPayload>("/api/v1/public/platform/configuration");
    return payload?.data
      ? {
        ...defaultPlatformConfiguration,
        ...payload.data,
        menus: {
          ...defaultMenus,
          ...(payload.data.menus ?? {}),
        },
        socialLinks: Array.isArray(payload.data.socialLinks) ? payload.data.socialLinks : defaultPlatformConfiguration.socialLinks,
        reassuranceItems: Array.isArray(payload.data.reassuranceItems) ? payload.data.reassuranceItems : defaultPlatformConfiguration.reassuranceItems,
        paymentMethods: Array.isArray(payload.data.paymentMethods) ? payload.data.paymentMethods : defaultPlatformConfiguration.paymentMethods,
        featureFlags: Array.isArray(payload.data.featureFlags) ? payload.data.featureFlags : defaultPlatformConfiguration.featureFlags,
        usersCount: typeof payload.data.usersCount === "number" ? payload.data.usersCount : defaultPlatformConfiguration.usersCount,
      }
      : defaultPlatformConfiguration;
  });
}

export async function getFrontPageData(path: string): Promise<FrontPageData | null> {
  const normalizedPath = path.startsWith("/") ? path : `/${path}`;
  return rememberPublicData("front-page", { path: normalizedPath }, publicDataCacheSeconds, async () => {
    const payload = await fetchJson<PublicFrontPagePayload>(`/api/v1/public/front/pages?path=${encodeURIComponent(normalizedPath)}`);

    return payload?.data ?? null;
  });
}

export async function getFrontPagesIndex(): Promise<FrontPageIndexEntry[]> {
  const payload = await fetchJson<PublicFrontPagesIndexPayload>("/api/v1/public/front/pages");

  return payload?.data ?? [];
}
