import { mockContent, mockOrganizers } from "@/lib/data/mock";
import {
  ModuleRoute,
  OrganizerProfile,
  PublicContent,
  SearchFilters,
  SearchSuggestion,
} from "@/lib/types";
import { matchesFilters, sortItems, uniqueValues } from "@/lib/utils";

export function listContent(filters: SearchFilters = {}): PublicContent[] {
  return sortItems(mockContent.filter((item) => matchesFilters(item, filters)), filters.sort);
}

export function listFeaturedContent(): PublicContent[] {
  return mockContent.filter((item) => item.featured).slice(0, 6);
}

export function listPopularContent(): PublicContent[] {
  return mockContent.filter((item) => item.popular).slice(0, 6);
}

export function findContent(module: ModuleRoute, slug: string): PublicContent | null {
  return mockContent.find((item) => item.module === module && item.slug === slug) ?? null;
}

export function listRelatedContent(item: PublicContent): PublicContent[] {
  return mockContent
    .filter(
      (candidate) =>
        candidate.id !== item.id &&
        (candidate.organizerSlug === item.organizerSlug || candidate.category === item.category),
    )
    .slice(0, 3);
}

export function findOrganizer(
  slug: string,
): { organizer: OrganizerProfile; items: PublicContent[] } | null {
  const organizer = mockOrganizers.find((entry) => entry.slug === slug);

  if (!organizer) {
    return null;
  }

  return {
    organizer,
    items: mockContent.filter((item) => item.organizerSlug === slug),
  };
}

export function findOrganizerProfile(slug: string): OrganizerProfile | null {
  return mockOrganizers.find((entry) => entry.slug === slug) ?? null;
}

export function listOrganizerHighlights() {
  return mockOrganizers.map((organizer) => ({
    organizer,
    items: mockContent.filter((item) => item.organizerSlug === organizer.slug).slice(0, 3),
  }));
}

export function listSpeakerHighlights() {
  return mockContent.flatMap((item) =>
    item.speakers.map((speaker) => {
      const organizer = findOrganizerProfile(item.organizerSlug);

      return {
        ...speaker,
        itemTitle: item.title,
        itemSlug: item.slug,
        itemModule: item.module,
        category: item.category,
        city: item.city,
        organizerSlug: item.organizerSlug,
        organizerName: organizer?.name ?? item.organizers[0]?.name ?? "Organisateur",
        organizerLogoUrl: organizer?.logoUrl ?? item.organizers[0]?.imageUrl ?? speaker.imageUrl,
      };
    }),
  );
}

export function listCategoryOverview() {
  const values = uniqueValues(mockContent, "category");

  return values.map((category) => ({
    category,
    count: mockContent.filter((item) => item.category === category).length,
    sample: mockContent.find((item) => item.category === category) ?? null,
  }));
}

export function listCityOverview() {
  const values = uniqueValues(mockContent, "city");

  return values.map((city) => ({
    city,
    count: mockContent.filter((item) => item.city === city).length,
    sample: mockContent.find((item) => item.city === city) ?? null,
  }));
}

export function listReferenceFilters() {
  const items = listContent();

  return {
    categories: uniqueValues(items, "category"),
    cities: uniqueValues(items, "city"),
  };
}

export function listSearchSuggestions(
  query: string,
  module?: ModuleRoute | "all",
  limit = 6,
): SearchSuggestion[] {
  const q = query.trim().toLowerCase();

  if (q.length < 2) {
    return [];
  }

  return mockContent
    .filter((item) => (module && module !== "all" ? item.module === module : true))
    .filter((item) =>
      [item.title, item.summary, item.category, item.city, item.country]
        .join(" ")
        .toLowerCase()
        .includes(q),
    )
    .slice(0, limit)
    .map((item) => ({
      title: item.title,
      href: `/${item.module}/${item.slug}`,
      module: item.module,
      city: item.city,
      category: item.category,
    }));
}
