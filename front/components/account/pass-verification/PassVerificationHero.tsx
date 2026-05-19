import type { PublicPassVerification } from "@/lib/types";

import { PUBLIC_PASS_STATUS_COPY, PUBLIC_PASS_STATUS_LABELS } from "./helpers";

type PassVerificationHeroProps = {
  pass: PublicPassVerification;
  statusTone: string;
};

export function PassVerificationHero({ pass, statusTone }: PassVerificationHeroProps) {
  return (
    <section className="page-hero page-hero--compact">
      <div className="shell page-hero__content">
        <p className="eyebrow">Vérification publique</p>
        <h1>{PUBLIC_PASS_STATUS_LABELS[pass.status]}</h1>
        <p>{PUBLIC_PASS_STATUS_COPY[pass.status]}</p>
        <div className="page-hero__pills">
          <span>{pass.type_label}</span>
          <span className={`ac-badge ac-badge--${statusTone}`}>
            <span className="ac-badge__dot" />
            {PUBLIC_PASS_STATUS_LABELS[pass.status]}
          </span>
        </div>
      </div>
    </section>
  );
}
