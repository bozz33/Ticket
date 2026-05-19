import Link from "next/link";

import type { SearchFilters } from "@/lib/types";
import { buildSearchQuery } from "@/lib/utils";

export function Pagination({
  basePath,
  currentPage,
  filters,
  totalPages,
}: {
  basePath: string;
  currentPage: number;
  filters: SearchFilters;
  totalPages: number;
}) {
  if (totalPages <= 1) return null;

  const windowStart = Math.max(1, currentPage - 1);
  const windowEnd = Math.min(totalPages, windowStart + 3);
  const firstPage = Math.max(1, windowEnd - 3);
  const pageNumbers = Array.from(
    { length: windowEnd - firstPage + 1 },
    (_, index) => firstPage + index,
  );
  const buildHref = (page: number) =>
    `${basePath}${buildSearchQuery({
      ...filters,
      page,
    })}`;

  return (
    <nav aria-label="Navigation pages" className="pagination">
      {currentPage > 1 ? (
        <Link className="pagination__btn" href={buildHref(currentPage - 1)}>
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M15 18l-6-6 6-6" />
          </svg>
          Precedent
        </Link>
      ) : (
        <span className="pagination__btn is-disabled">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M15 18l-6-6 6-6" />
          </svg>
          Precedent
        </span>
      )}

      {firstPage > 1 ? (
        <>
          <Link className="pagination__btn" href={buildHref(1)}>
            1
          </Link>
          <span className="pagination__ellipsis">...</span>
        </>
      ) : null}

      {pageNumbers.map((page) => (
        <Link
          className={`pagination__btn${page === currentPage ? " active" : ""}`}
          href={buildHref(page)}
          key={page}
        >
          {page}
        </Link>
      ))}

      {windowEnd < totalPages ? (
        <>
          <span className="pagination__ellipsis">...</span>
          <Link className="pagination__btn" href={buildHref(totalPages)}>
            {totalPages}
          </Link>
        </>
      ) : null}

      {currentPage < totalPages ? (
        <Link className="pagination__btn" href={buildHref(currentPage + 1)}>
          Suivant
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M9 18l6-6-6-6" />
          </svg>
        </Link>
      ) : (
        <span className="pagination__btn is-disabled">
          Suivant
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M9 18l6-6-6-6" />
          </svg>
        </span>
      )}
    </nav>
  );
}
