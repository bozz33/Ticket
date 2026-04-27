import { ModuleRoute, PublicContent, SearchFilters, SortOption } from "@/lib/types";

export const moduleLabels: Record<
  ModuleRoute,
  {
    title: string;
    singular: string;
    cta: string;
    description: string;
    heroImageUrl: string;
  }
> = {
  evenements: {
    title: "Evenements",
    singular: "evenement",
    cta: "Acheter",
    description:
      "Concerts, conferences et experiences live publies par les organisations de la plateforme.",
    heroImageUrl:
      "https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?auto=format&fit=crop&w=1600&q=80",
  },
  formations: {
    title: "Formations",
    singular: "formation",
    cta: "S'inscrire",
    description:
      "Sessions, masterclass et ateliers avec des parcours clairs jusqu'a l'inscription.",
    heroImageUrl:
      "https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1600&q=80",
  },
  stands: {
    title: "Stands",
    singular: "stand",
    cta: "Reserver",
    description:
      "Catalogues d'exposition et offres de reservation pour salons, foires et showcases.",
    heroImageUrl:
      "https://images.unsplash.com/photo-1511578314322-379afb476865?auto=format&fit=crop&w=1600&q=80",
  },
  "appels-a-projets": {
    title: "Appels a projets",
    singular: "appel",
    cta: "Candidater",
    description:
      "Programmes, concours et appels a candidatures avec conditions, calendrier et pieces requises.",
    heroImageUrl:
      "https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=1600&q=80",
  },
  crowdfunding: {
    title: "Crowdfunding",
    singular: "campagne",
    cta: "Contribuer",
    description:
      "Campagnes financieres publiees par les organisateurs avec progression, paliers et impact attendu.",
    heroImageUrl:
      "https://images.unsplash.com/photo-1556740749-887f6717d7e4?auto=format&fit=crop&w=1600&q=80",
  },
};

export function getModuleMeta(module: ModuleRoute) {
  return moduleLabels[module];
}

export function formatMoney(amount: number, currency: string): string {
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency,
    maximumFractionDigits: 0,
  }).format(amount);
}

export function formatDateLabel(value?: string): string {
  if (!value) {
    return "Date a venir";
  }

  return new Intl.DateTimeFormat("fr-FR", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date(value));
}

export function formatDateRange(item: PublicContent): string {
  if (item.startsAt && item.endsAt) {
    return `${formatDateLabel(item.startsAt)} - ${formatDateLabel(item.endsAt)}`;
  }

  return formatDateLabel(item.startsAt ?? item.deadlineAt);
}

export function buildPublicUrl(path: string): string {
  const baseUrl = (process.env.NEXT_PUBLIC_SITE_URL || "http://127.0.0.1:3000").replace(/\/$/, "");

  return `${baseUrl}${path.startsWith("/") ? path : `/${path}`}`;
}

export function buildSearchQuery(filters: SearchFilters): string {
  const params = new URLSearchParams();

  Object.entries(filters).forEach(([key, value]) => {
    if (typeof value === "string" && value.trim() !== "") {
      params.set(key, value);
    }
  });

  const query = params.toString();

  return query.length > 0 ? `?${query}` : "";
}

export function normalizeSearchParams(
  searchParams: Record<string, string | string[] | undefined>,
): SearchFilters {
  const getValue = (key: string) => {
    const value = searchParams[key];

    return Array.isArray(value) ? value[0] : value;
  };

  const sort = getValue("sort");

  return {
    module: getValue("module") as SearchFilters["module"],
    q: getValue("q"),
    category: getValue("category"),
    city: getValue("city"),
    price: (getValue("price") as SearchFilters["price"]) ?? "all",
    sort: isSortOption(sort) ? sort : "popular",
  };
}

function isSortOption(value?: string): value is SortOption {
  return value === "popular" || value === "recent" || value === "price";
}

export function matchesFilters(item: PublicContent, filters: SearchFilters): boolean {
  const q = filters.q?.trim().toLowerCase();
  const category = filters.category?.trim().toLowerCase();
  const city = filters.city?.trim().toLowerCase();

  if (filters.module && filters.module !== "all" && item.module !== filters.module) {
    return false;
  }

  if (
    q &&
    ![
      item.title,
      item.summary,
      item.description,
      item.category,
      item.city,
      item.country,
    ]
      .join(" ")
      .toLowerCase()
      .includes(q)
  ) {
    return false;
  }

  if (category && item.category.toLowerCase() !== category) {
    return false;
  }

  if (city && item.city.toLowerCase() !== city) {
    return false;
  }

  if (filters.price === "free" && !item.isFree) {
    return false;
  }

  if (filters.price === "paid" && item.isFree) {
    return false;
  }

  return true;
}

export function sortItems(items: PublicContent[], sort: SortOption = "popular"): PublicContent[] {
  const copy = [...items];

  if (sort === "price") {
    return copy.sort((left, right) => left.priceFrom - right.priceFrom);
  }

  if (sort === "recent") {
    return copy.sort(
      (left, right) =>
        new Date(right.publishedAt).getTime() - new Date(left.publishedAt).getTime(),
    );
  }

  return copy.sort((left, right) => Number(right.popular) - Number(left.popular));
}

export function uniqueValues(items: PublicContent[], field: keyof Pick<PublicContent, "category" | "city">) {
  return [...new Set(items.map((item) => item[field]))].sort((left, right) =>
    left.localeCompare(right, "fr"),
  );
}
