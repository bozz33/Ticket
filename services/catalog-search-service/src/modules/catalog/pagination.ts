import { Pagination } from './catalog.types';

export function normalizePagination(page: unknown, perPage: unknown): Pagination {
  const normalizedPage = positiveInt(page, 1);
  const normalizedPerPage = Math.min(48, positiveInt(perPage, 12));

  return { page: normalizedPage, perPage: normalizedPerPage };
}

function positiveInt(value: unknown, fallback: number): number {
  const parsed = Number.parseInt(String(value ?? ''), 10);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
}
