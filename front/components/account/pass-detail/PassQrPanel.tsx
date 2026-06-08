"use client";

import dynamic from "next/dynamic";

import type { AccountAccessPass } from "@/lib/types";

import { PASS_STATUS_LABELS, formatPassDate } from "./helpers";

const QrCode = dynamic(() => import("@/components/QrCode").then((m) => m.QrCode), {
  ssr: false,
  loading: () => <div className="ac-qr-wrap ac-qr-wrap--loading" aria-hidden="true" />,
});

type PassQrPanelProps = {
  pass: AccountAccessPass;
  qrPayload: string;
};

export function PassQrPanel({ pass, qrPayload }: PassQrPanelProps) {
  const isConsumable = pass.status === "active";
  const isUsed = pass.status === "used";
  const isRevoked = pass.status === "revoked";

  return (
    <div className="ac-qr-panel">
      {isConsumable ? (
        <>
          <p className="ac-qr-panel__label">Code QR d&apos;accès</p>
          <div className="ac-qr-wrap">
            <QrCode value={qrPayload} />
          </div>
          <p className="ac-qr-code-text">{pass.access_code.slice(0, 16)}…</p>
        </>
      ) : isUsed ? (
        <>
          <p className="ac-qr-panel__label">Pass utilisé</p>
          <div className="ac-qr-status">
            <div className="ac-qr-status__icon ac-qr-status__icon--warn">
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
                <path d="M20 12V22H4V12" />
                <path d="M22 7H2v5h20V7z" />
                <path d="M12 22V7" />
                <path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z" />
                <path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z" />
              </svg>
            </div>
            <p className="ac-qr-status__title">Déjà scanné</p>
            <p className="ac-qr-status__sub">Ce pass a été utilisé le {formatPassDate(pass.used_at)}.</p>
          </div>
        </>
      ) : (
        <>
          <p className="ac-qr-panel__label">Pass non disponible</p>
          <div className="ac-qr-status">
            <div className="ac-qr-status__icon ac-qr-status__icon--bad">
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
                <circle cx="12" cy="12" r="10" />
                <line x1="15" y1="9" x2="9" y2="15" />
                <line x1="9" y1="9" x2="15" y2="15" />
              </svg>
            </div>
            <p className="ac-qr-status__title">{PASS_STATUS_LABELS[pass.status]}</p>
            {isRevoked && pass.revocation_reason ? (
              <p className="ac-qr-status__sub">{pass.revocation_reason}</p>
            ) : null}
          </div>
        </>
      )}
    </div>
  );
}
