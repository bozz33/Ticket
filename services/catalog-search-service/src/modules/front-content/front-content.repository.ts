export interface FrontContentRepository {
  menus(locale: string): Promise<Record<string, unknown>[]>;
  pages(locale: string): Promise<Record<string, unknown>[]>;
  page(path: string, locale: string): Promise<Record<string, unknown> | null>;
}

export const FRONT_CONTENT_REPOSITORY = Symbol('FRONT_CONTENT_REPOSITORY');
