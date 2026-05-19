import type { PublicPassVerification } from "@/lib/types";

import { PUBLIC_PASS_STATUS_LABELS, formatPublicPassDate } from "./helpers";

type PassVerificationInfoPanelProps = {
  pass: PublicPassVerification;
  statusTone: string;
};

export function PassVerificationInfoPanel({ pass, statusTone }: PassVerificationInfoPanelProps) {
  return (
    <div className="ac-detail-panel">
      <div className="ac-detail-panel__head">
        <span className="ac-detail-panel__title">Informations du pass</span>
      </div>
      <div className="ac-detail-panel__body">
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">Type</span>
          <span className="ac-detail-row__value">{pass.type_label}</span>
        </div>
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">Statut</span>
          <span className={`ac-badge ac-badge--${statusTone}`}>
            <span className="ac-badge__dot" />
            {PUBLIC_PASS_STATUS_LABELS[pass.status]}
          </span>
        </div>
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">Titulaire</span>
          <span className="ac-detail-row__value">{pass.holder_name ?? "Non renseigné"}</span>
        </div>
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">Identifiant public</span>
          <span className="ac-detail-row__value ac-detail-row__value--mono">{pass.public_id}</span>
        </div>
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">Code</span>
          <span className="ac-detail-row__value ac-detail-row__value--mono">{pass.qr_payload.code}</span>
        </div>
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">Expire le</span>
          <span className="ac-detail-row__value">{formatPublicPassDate(pass.expires_at)}</span>
        </div>
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">Utilisé le</span>
          <span className="ac-detail-row__value">{formatPublicPassDate(pass.used_at)}</span>
        </div>
      </div>
    </div>
  );
}
