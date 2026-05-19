import type { AccountAccessPass, AccountOrder } from "@/lib/types";

import { AccountHomeStats } from "./AccountHomeStats";
import { buildAccountHomeStats } from "./helpers";
import { LastOrderPanel } from "./LastOrderPanel";

type AccountHomeViewProps = {
  orders: AccountOrder[];
  passes: AccountAccessPass[];
};

export function AccountHomeView({ orders, passes }: AccountHomeViewProps) {
  const stats = buildAccountHomeStats(orders, passes);
  const lastOrder = orders[0] ?? null;

  return (
    <>
      <div className="ac-page-header">
        <h1 className="ac-page-title">Accueil</h1>
        <p className="ac-page-sub">Retrouvez ici vos achats, vos passes et le suivi de vos remboursements.</p>
      </div>

      <AccountHomeStats stats={stats} />
      <LastOrderPanel order={lastOrder} />
    </>
  );
}
