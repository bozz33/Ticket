import type { AccountAccessPass } from "@/lib/types";

import { PASS_STATUS_LABELS, PASS_TYPE_LABELS } from "./helpers";

type PassHeaderProps = {
  pass: AccountAccessPass;
};

export function PassHeader({ pass }: PassHeaderProps) {
  return (
    <div className="ac-page-header">
      <h1 className="ac-page-title">{pass.offer?.name ?? PASS_TYPE_LABELS[pass.type]}</h1>
      <p className="ac-page-sub">
        <span className={`ac-badge ac-badge--${pass.status}`}>
          <span className="ac-badge__dot" />
          {PASS_STATUS_LABELS[pass.status]}
        </span>
      </p>
    </div>
  );
}
