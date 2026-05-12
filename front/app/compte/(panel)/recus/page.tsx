import { ReceiptsTable } from "@/components/account/ReceiptsTable";
import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountReceipts } from "@/lib/data/account";

export const dynamic = "force-dynamic";

export default async function RecusPage() {
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);
  const receipts = token ? await getAccountReceipts(tenantSlug, token) : [];

  return (
    <>
      <div className="ac-page-header">
        <h1 className="ac-page-title">Reçus</h1>
        <p className="ac-page-sub">Vos justificatifs de paiement.</p>
      </div>

      {receipts.length === 0 ? (
        <div className="ac-empty">
          <svg className="ac-empty__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
            <polyline points="14 2 14 8 20 8" />
            <line x1="9" y1="13" x2="15" y2="13" />
            <line x1="9" y1="17" x2="15" y2="17" />
          </svg>
          <p className="ac-empty__title">Aucun reçu</p>
          <p className="ac-empty__text">Vos reçus apparaîtront ici après confirmation du paiement.</p>
        </div>
      ) : (
        <ReceiptsTable receipts={receipts} />
      )}
    </>
  );
}
