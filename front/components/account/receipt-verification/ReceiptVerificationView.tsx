import type { PublicReceiptVerification } from "@/lib/types";

import { ReceiptVerificationDetails } from "./ReceiptVerificationDetails";
import { ReceiptVerificationStatus } from "./ReceiptVerificationStatus";

type ReceiptVerificationViewProps = {
  receipt: PublicReceiptVerification;
};

export function ReceiptVerificationView({ receipt }: ReceiptVerificationViewProps) {
  return (
    <>
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">Vérification publique</p>
          <h1>Reçu authentifié</h1>
          <p>Ce justificatif existe bien dans la plateforme et ses métadonnées principales sont cohérentes.</p>
        </div>
      </section>

      <section className="section">
        <div className="shell" style={{ maxWidth: "980px" }}>
          <div className="ac-detail">
            <ReceiptVerificationDetails receipt={receipt} />
            <ReceiptVerificationStatus receipt={receipt} />
          </div>
        </div>
      </section>
    </>
  );
}
