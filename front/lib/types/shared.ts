export type ModuleRoute =
  | "evenements"
  | "formations"
  | "stands"
  | "appels-a-projets"
  | "crowdfunding";

export type SortOption = "popular" | "recent" | "price";

export interface SearchFilters {
  module?: ModuleRoute | "all";
  q?: string;
  category?: string;
  city?: string;
  dateFrom?: string;
  dateTo?: string;
  price?: "all" | "free" | "paid";
  sort?: SortOption;
  page?: number;
}

export interface OfferTier {
  id: string;
  title: string;
  subtitle?: string;
  price: number;
  currency: string;
  remaining?: number;
  quantityLabel?: string;
  ctaLabel: string;
  perks: string[];
  source?: "offer" | "event_ticket";
  ticketId?: string | null;
  availabilityStatus?: "available" | "low_stock" | "sold_out" | "sales_not_started" | "sales_ended" | "inactive";
  availabilityLabel?: string;
  isAvailable?: boolean;
  isSoldOut?: boolean;
}

export interface TimelineEntry {
  label: string;
  dateLabel: string;
  description: string;
}

export interface PersonEntry {
  name: string;
  role: string;
  imageUrl: string;
}

export interface FaqEntry {
  question: string;
  answer: string;
}

export interface StatEntry {
  label: string;
  value: string;
}
