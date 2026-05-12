import { OrdersTable } from "@/components/account/OrdersTable";
import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountOrders } from "@/lib/data/account";

export const dynamic = "force-dynamic";

export default async function CommandesPage() {
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);
  const orders = token ? await getAccountOrders(tenantSlug, token) : [];

  return (
    <>
      <div className="ac-page-header">
        <h1 className="ac-page-title">Commandes</h1>
        <p className="ac-page-sub">Historique de toutes vos commandes.</p>
      </div>

      {orders.length === 0 ? (
        <div className="ac-empty">
          <svg className="ac-empty__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
            <line x1="3" x2="21" y1="6" y2="6" />
            <path d="M16 10a4 4 0 0 1-8 0" />
          </svg>
          <p className="ac-empty__title">Aucune commande</p>
          <p className="ac-empty__text">Vos commandes apparaîtront ici après un achat.</p>
        </div>
      ) : (
        <OrdersTable orders={orders} />
      )}
    </>
  );
}
