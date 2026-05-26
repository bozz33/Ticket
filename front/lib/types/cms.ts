export type FrontMenuLocation =
  | "header_top_left"
  | "header_top_right"
  | "header_primary"
  | "header_utility"
  | "header_actions"
  | "footer_explore"
  | "footer_platform"
  | "footer_bottom";

export interface NavigationLink {
  label: string;
  href: string;
  target?: string;
  meta?: Record<string, unknown>;
}

export type FrontPageSectionType =
  | "hero"
  | "split_overview"
  | "feature_grid"
  | "metrics"
  | "faq"
  | "contact_channels"
  | "contact_form"
  | "organizer_highlights"
  | "legal_article"
  | "onboarding_form";

export interface FrontPageSectionItem {
  title?: string;
  label?: string;
  value?: string;
  body?: string;
  href?: string;
  icon?: string;
  image_url?: string;
  meta?: Record<string, unknown>;
}

export interface FrontPageSection {
  key: string;
  type: FrontPageSectionType;
  title?: string | null;
  eyebrow?: string | null;
  body?: string | null;
  image_url?: string | null;
  primary_cta?: {
    label?: string | null;
    url?: string | null;
  };
  secondary_cta?: {
    label?: string | null;
    url?: string | null;
  };
  items: FrontPageSectionItem[];
  settings: Record<string, unknown>;
}

export interface FrontPageData {
  key: string;
  title: string;
  path: string;
  slug: string;
  template: string;
  seo: {
    title: string;
    description: string;
    image?: string | null;
    keywords?: string[];
    canonical_url?: string | null;
    robots_index?: string | null;
    robots_follow?: string | null;
    max_image_preview?: string | null;
    og_title?: string | null;
    og_description?: string | null;
    og_type?: string | null;
    og_image?: string | null;
    og_image_alt?: string | null;
    twitter_title?: string | null;
    twitter_description?: string | null;
    twitter_image?: string | null;
    twitter_card?: string | null;
    structured_data_json?: string | null;
  };
  meta: Record<string, unknown>;
  sections: FrontPageSection[];
}

export interface FrontPageIndexEntry {
  key: string;
  title: string;
  path: string;
  updated_at?: string | null;
  published_at?: string | null;
}
