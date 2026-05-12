"use client";

import { useMemo } from "react";

export function AccountTablePagination({
  page,
  pageSize,
  total,
  onPageChange,
}: {
  page: number;
  pageSize: number;
  total: number;
  onPageChange: (page: number) => void;
}) {
  const totalPages = Math.max(1, Math.ceil(total / pageSize));
  const pages = useMemo(() => {
    const start = Math.max(1, page - 1);
    const end = Math.min(totalPages, start + 2);
    const normalizedStart = Math.max(1, end - 2);

    return Array.from(
      { length: end - normalizedStart + 1 },
      (_, index) => normalizedStart + index,
    );
  }, [page, totalPages]);

  if (totalPages <= 1) {
    return null;
  }

  const startItem = total === 0 ? 0 : (page - 1) * pageSize + 1;
  const endItem = Math.min(total, page * pageSize);

  return (
    <div className="ac-table-pagination">
      <p className="ac-table-pagination__summary">
        {startItem}-{endItem} sur {total}
      </p>
      <div className="ac-table-pagination__controls">
        <button
          className="ac-table-pagination__button"
          disabled={page <= 1}
          onClick={() => onPageChange(page - 1)}
          type="button"
        >
          Precedent
        </button>
        <div className="ac-table-pagination__pages">
          {pages.map((pageNumber) => (
            <button
              className={`ac-table-pagination__page${pageNumber === page ? " is-active" : ""}`}
              key={pageNumber}
              onClick={() => onPageChange(pageNumber)}
              type="button"
            >
              {pageNumber}
            </button>
          ))}
        </div>
        <button
          className="ac-table-pagination__button"
          disabled={page >= totalPages}
          onClick={() => onPageChange(page + 1)}
          type="button"
        >
          Suivant
        </button>
      </div>
    </div>
  );
}
