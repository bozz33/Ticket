import { RefundsView } from "@/components/account/refunds/RefundsView";
import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountOrders } from "@/lib/data/account";

export const dynamic = "force-dynamic";

export default async function AccountRefundsPage() {
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);

  const orders = token ? await getAccountOrders(tenantSlug, token) : [];

  return <RefundsView orders={orders} />;
}
