import type { AccountAccessPass } from "@/lib/types";

import { PassBackLink } from "./PassBackLink";
import { PassHeader } from "./PassHeader";
import { PassInfoPanel } from "./PassInfoPanel";
import { PassPublicVerificationLink } from "./PassPublicVerificationLink";
import { PassQrPanel } from "./PassQrPanel";

type PassDetailViewProps = {
  pass: AccountAccessPass;
};

export function PassDetailView({ pass }: PassDetailViewProps) {
  const qrPayload = JSON.stringify({
    code: pass.access_code,
    type: pass.type,
    public_id: pass.public_id,
  });

  return (
    <>
      <PassBackLink />
      <PassHeader pass={pass} />

      <div className="ac-detail">
        <div>
          <PassInfoPanel pass={pass} />
        </div>

        <div>
          <PassQrPanel pass={pass} qrPayload={qrPayload} />
          <PassPublicVerificationLink accessCode={pass.access_code} />
        </div>
      </div>
    </>
  );
}
