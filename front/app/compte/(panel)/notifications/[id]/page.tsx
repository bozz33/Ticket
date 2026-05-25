import { notFound } from "next/navigation";

import { NotificationDetailView } from "@/components/account/notifications/NotificationDetailView";
import { getAuthToken, getTenantSlug } from "@/lib/auth";
import { getAccountNotifications } from "@/lib/data/account";

export const dynamic = "force-dynamic";

export default async function NotificationDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const [token, tenantSlug] = await Promise.all([getAuthToken(), getTenantSlug()]);

  if (!token) notFound();

  const payload = await getAccountNotifications(tenantSlug, token);
  const notification = payload?.notifications.find((item) => item.id === id);

  if (!notification) notFound();

  return <NotificationDetailView notification={notification} />;
}
