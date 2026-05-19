import Link from "next/link";

import type { AccountOrder } from "@/lib/types";

import { PASS_TYPE_LABELS } from "./helpers";

type OrderAccessPassesPanelProps = {
  passes: AccountOrder["access_passes"];
};

export function OrderAccessPassesPanel({ passes }: OrderAccessPassesPanelProps) {
  if (passes.length === 0) {
    return null;
  }

  return (
    <div className="ac-detail-panel" style={{ marginTop: "24px" }}>
      <div className="ac-detail-panel__head">
        <span className="ac-detail-panel__title">Passes d&apos;accès ({passes.length})</span>
      </div>
      <div className="ac-detail-panel__body">
        <div className="ac-list">
          {passes.map((pass) => (
            <Link
              className="ac-card"
              href={`/compte/passes/${pass.public_id}`}
              key={pass.id}
              style={{ padding: "14px 16px" }}
            >
              <div className="ac-card__icon ac-card__icon--teal">
                <svg
                  fill="none"
                  height="18"
                  stroke="currentColor"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth="2"
                  viewBox="0 0 24 24"
                  width="18"
                >
                  <rect width="5" height="5" x="3" y="3" rx="1" />
                  <rect width="5" height="5" x="16" y="3" rx="1" />
                  <rect width="5" height="5" x="3" y="16" rx="1" />
                  <path d="M21 16h-3a2 2 0 0 0-2 2v3" />
                  <path d="M21 21v.01" />
                </svg>
              </div>
              <div className="ac-card__body">
                <p className="ac-card__title">{pass.holder_name ?? "Porteur anonyme"}</p>
                <p className="ac-card__meta">
                  {PASS_TYPE_LABELS[pass.type] ?? pass.type}
                  <span className="ac-card__meta-sep" />
                  <span className="ac-detail-row__value--mono" style={{ fontSize: "0.78rem" }}>
                    {pass.access_code.slice(0, 12)}…
                  </span>
                </p>
              </div>
              <span className={`ac-badge ac-badge--${pass.status}`}>
                <span className="ac-badge__dot" />
                {pass.status}
              </span>
              <svg
                className="ac-card__arrow"
                fill="none"
                height="16"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="2"
                viewBox="0 0 24 24"
                width="16"
              >
                <path d="m9 18 6-6-6-6" />
              </svg>
            </Link>
          ))}
        </div>
      </div>
    </div>
  );
}
