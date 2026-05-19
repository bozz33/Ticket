import type { PublicPassVerification } from "@/lib/types";

import { MissingVerificationView } from "./MissingVerificationView";
import { PassVerificationDecisionPanel } from "./PassVerificationDecisionPanel";
import { PassVerificationHero } from "./PassVerificationHero";
import { PassVerificationInfoPanel } from "./PassVerificationInfoPanel";

export function MissingTenantVerificationView() {
  return (
    <MissingVerificationView
      actionHref="/contact"
      actionLabel="Contacter le support"
      body="Le tenant public n'est pas configuré pour cette vérification."
      title="Vérification indisponible"
    />
  );
}

export function MissingPassVerificationView() {
  return (
    <MissingVerificationView
      actionHref="/"
      actionLabel="Retour à l'accueil"
      body="Le code fourni ne correspond à aucun pass actif sur cet espace public."
      title="Pass introuvable"
    />
  );
}

type PassVerificationViewProps = {
  pass: PublicPassVerification;
};

export function PassVerificationView({ pass }: PassVerificationViewProps) {
  const statusTone = pass.status === "active" ? "active" : pass.status;

  return (
    <>
      <PassVerificationHero pass={pass} statusTone={statusTone} />

      <section className="section">
        <div className="shell" style={{ maxWidth: "960px" }}>
          <div className="ac-detail" style={{ gridTemplateColumns: "1.2fr 0.8fr" }}>
            <PassVerificationInfoPanel pass={pass} statusTone={statusTone} />
            <PassVerificationDecisionPanel pass={pass} />
          </div>
        </div>
      </section>
    </>
  );
}
