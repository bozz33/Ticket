import { PassesTable } from "@/components/account/PassesTable";
import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountPasses } from "@/lib/data/account";

export const dynamic = "force-dynamic";

export default async function PassesPage() {
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);
  const passes = token ? await getAccountPasses(tenantSlug, token) : [];

  return (
    <>
      <div className="ac-page-header">
        <h1 className="ac-page-title">Mes passes</h1>
        <p className="ac-page-sub">Vos billets et passes d&apos;accès avec leurs QR codes.</p>
      </div>

      {passes.length === 0 ? (
        <div className="ac-empty">
          <svg className="ac-empty__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
            <rect width="5" height="5" x="3" y="3" rx="1" />
            <rect width="5" height="5" x="16" y="3" rx="1" />
            <rect width="5" height="5" x="3" y="16" rx="1" />
            <path d="M21 16h-3a2 2 0 0 0-2 2v3" />
            <path d="M21 21v.01" />
            <path d="M12 7v3a2 2 0 0 1-2 2H7" />
            <path d="M3 12h.01" />
          </svg>
          <p className="ac-empty__title">Aucun pass</p>
          <p className="ac-empty__text">Vos passes d&apos;accès apparaîtront ici après une commande confirmée.</p>
        </div>
      ) : (
        <PassesTable passes={passes} />
      )}
    </>
  );
}
