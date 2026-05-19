import Link from "next/link";

import type { AccountAccessPass } from "@/lib/types";

import { PASS_TYPE_LABELS, formatPassDate } from "./helpers";

type PassInfoPanelProps = {
  pass: AccountAccessPass;
};

export function PassInfoPanel({ pass }: PassInfoPanelProps) {
  const isUsed = pass.status === "used";
  const isRevoked = pass.status === "revoked";

  return (
    <div className="ac-detail-panel">
      <div className="ac-detail-panel__head">
        <span className="ac-detail-panel__title">Informations du pass</span>
      </div>
      <div className="ac-detail-panel__body">
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">Type</span>
          <span className="ac-detail-row__value">{PASS_TYPE_LABELS[pass.type]}</span>
        </div>
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">Porteur</span>
          <span className="ac-detail-row__value">{pass.holder_name ?? "—"}</span>
        </div>
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">E-mail porteur</span>
          <span className="ac-detail-row__value">{pass.holder_email ?? "—"}</span>
        </div>
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">Téléphone</span>
          <span className="ac-detail-row__value">{pass.order?.buyer_phone ?? "—"}</span>
        </div>
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">Créé le</span>
          <span className="ac-detail-row__value">{formatPassDate(pass.created_at)}</span>
        </div>
        {pass.expires_at ? (
          <div className="ac-detail-row">
            <span className="ac-detail-row__label">Expire le</span>
            <span className="ac-detail-row__value">{formatPassDate(pass.expires_at)}</span>
          </div>
        ) : null}
        {isUsed ? (
          <div className="ac-detail-row">
            <span className="ac-detail-row__label">Utilisé le</span>
            <span className="ac-detail-row__value">{formatPassDate(pass.used_at)}</span>
          </div>
        ) : null}
        {isRevoked ? (
          <>
            <div className="ac-detail-row">
              <span className="ac-detail-row__label">Révoqué le</span>
              <span className="ac-detail-row__value">{formatPassDate(pass.revoked_at)}</span>
            </div>
            {pass.revocation_reason ? (
              <div className="ac-detail-row">
                <span className="ac-detail-row__label">Motif</span>
                <span className="ac-detail-row__value">{pass.revocation_reason}</span>
              </div>
            ) : null}
          </>
        ) : null}
        {pass.order ? (
          <div className="ac-detail-row">
            <span className="ac-detail-row__label">Commande</span>
            <Link
              href={`/compte/commandes/${pass.order.reference}`}
              className="ac-detail-row__value"
              style={{ textDecoration: "underline" }}
            >
              {pass.order.reference}
            </Link>
          </div>
        ) : null}
      </div>
    </div>
  );
}
