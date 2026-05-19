import Link from "next/link";

import type { PublicPassVerification } from "@/lib/types";

import { PUBLIC_PASS_STATUS_COPY, PUBLIC_PASS_STATUS_LABELS } from "./helpers";

type PassVerificationDecisionPanelProps = {
  pass: PublicPassVerification;
};

export function PassVerificationDecisionPanel({ pass }: PassVerificationDecisionPanelProps) {
  return (
    <div className="ac-detail-panel">
      <div className="ac-detail-panel__head">
        <span className="ac-detail-panel__title">Décision</span>
      </div>
      <div className="ac-detail-panel__body">
        <div className="ac-qr-status">
          <div
            className={`ac-qr-status__icon ${
              pass.status === "active"
                ? "ac-qr-status__icon--ok"
                : pass.status === "used"
                  ? "ac-qr-status__icon--warn"
                  : "ac-qr-status__icon--bad"
            }`}
          >
            <svg
              fill="none"
              height="28"
              stroke="currentColor"
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="2"
              viewBox="0 0 24 24"
              width="28"
            >
              {pass.status === "active" ? (
                <path d="M20 7 9.5 17.5 4 12" />
              ) : pass.status === "used" ? (
                <>
                  <circle cx="12" cy="12" r="10" />
                  <path d="M12 8v4" />
                  <path d="M12 16h.01" />
                </>
              ) : (
                <>
                  <circle cx="12" cy="12" r="10" />
                  <line x1="15" y1="9" x2="9" y2="15" />
                  <line x1="9" y1="9" x2="15" y2="15" />
                </>
              )}
            </svg>
          </div>
          <p className="ac-qr-status__title">{PUBLIC_PASS_STATUS_LABELS[pass.status]}</p>
          <p className="ac-qr-status__sub">{PUBLIC_PASS_STATUS_COPY[pass.status]}</p>
        </div>

        <div style={{ marginTop: "20px" }}>
          <Link href="/" className="button button--full">
            Retour à l&apos;accueil
          </Link>
        </div>
      </div>
    </div>
  );
}
