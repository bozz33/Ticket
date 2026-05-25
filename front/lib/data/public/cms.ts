import type { FrontPageData, FrontPageIndexEntry, PlatformConfiguration } from "@/lib/types";
import {
  defaultMenus,
  defaultPlatformConfiguration,
  fetchJson,
  type PublicFrontPagePayload,
  type PublicFrontPagesIndexPayload,
  type PublicPlatformPayload,
  publicCmsCacheSeconds,
  rememberPublicData,
} from "./shared";

export async function getPlatformConfiguration(): Promise<PlatformConfiguration> {
  return rememberPublicData("platform-configuration", {}, publicCmsCacheSeconds, async () => {
    const payload = await fetchJson<PublicPlatformPayload>("/api/v1/public/platform/configuration", { revalidate: publicCmsCacheSeconds });

    return payload?.data
      ? {
          ...defaultPlatformConfiguration,
          ...payload.data,
          menus: {
            ...defaultMenus,
            ...(payload.data.menus ?? {}),
          },
          seo: {
            ...defaultPlatformConfiguration.seo,
            ...(payload.data.seo ?? {}),
            robots: {
              ...defaultPlatformConfiguration.seo?.robots,
              ...(payload.data.seo?.robots ?? {}),
            },
            openGraph: {
              ...defaultPlatformConfiguration.seo?.openGraph,
              ...(payload.data.seo?.openGraph ?? {}),
            },
            twitter: {
              ...defaultPlatformConfiguration.seo?.twitter,
              ...(payload.data.seo?.twitter ?? {}),
            },
            sitemap: {
              ...defaultPlatformConfiguration.seo?.sitemap,
              ...(payload.data.seo?.sitemap ?? {}),
            },
          },
          socialLinks: Array.isArray(payload.data.socialLinks) ? payload.data.socialLinks : defaultPlatformConfiguration.socialLinks,
          reassuranceItems: Array.isArray(payload.data.reassuranceItems) ? payload.data.reassuranceItems : defaultPlatformConfiguration.reassuranceItems,
          paymentMethods: Array.isArray(payload.data.paymentMethods) ? payload.data.paymentMethods : defaultPlatformConfiguration.paymentMethods,
          featureFlags: Array.isArray(payload.data.featureFlags) ? payload.data.featureFlags : defaultPlatformConfiguration.featureFlags,
          usersCount: typeof payload.data.usersCount === "number" ? payload.data.usersCount : defaultPlatformConfiguration.usersCount,
          languages: Array.isArray(payload.data.languages) ? payload.data.languages : defaultPlatformConfiguration.languages,
          defaultLanguage: payload.data.defaultLanguage ?? defaultPlatformConfiguration.defaultLanguage,
          translations: payload.data.translations && typeof payload.data.translations === "object" ? payload.data.translations : defaultPlatformConfiguration.translations,
        }
      : defaultPlatformConfiguration;
  });
}

export async function getFrontPageData(path: string): Promise<FrontPageData | null> {
  const normalizedPath = path.startsWith("/") ? path : `/${path}`;
  return rememberPublicData("front-page", { path: normalizedPath }, publicCmsCacheSeconds, async () => {
    const payload = await fetchJson<PublicFrontPagePayload>(
      `/api/v1/public/front/pages?path=${encodeURIComponent(normalizedPath)}`,
      { revalidate: publicCmsCacheSeconds },
    );

    return payload?.data ?? null;
  });
}

export async function getFrontPagesIndex(): Promise<FrontPageIndexEntry[]> {
  return rememberPublicData("front-pages-index", {}, publicCmsCacheSeconds, async () => {
    const payload = await fetchJson<PublicFrontPagesIndexPayload>("/api/v1/public/front/pages", { revalidate: publicCmsCacheSeconds });

    return payload?.data ?? [];
  });
}
