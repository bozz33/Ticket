import type { AccountAccessPass, AccountOrder, AccountReceipt } from "@/lib/types";
import { apiBase, apiFetch } from "./client";
import { normalizeAccountOrderRecord, normalizeAccountReceiptRecord } from "./normalizers";

export async function getAccountOrders(
  tenantSlug: string,
  token: string,
): Promise<AccountOrder[]> {
  const p = await apiFetch<{ data: AccountOrder[] }>(
    `/api/v1/tenants/${tenantSlug}/orders?limit=100`,
    token,
  );
  return (p?.data ?? []).map((order) => normalizeAccountOrderRecord(order));
}

export async function getAccountOrder(
  tenantSlug: string,
  token: string,
  ref: string,
): Promise<AccountOrder | null> {
  const p = await apiFetch<{ data: AccountOrder }>(
    `/api/v1/tenants/${tenantSlug}/orders/${ref}`,
    token,
  );
  return p?.data ? normalizeAccountOrderRecord(p.data) : null;
}

export async function createAccountRefundRequest(
  tenantSlug: string,
  token: string,
  payload: {
    order_reference: string;
    reason_code: string;
    reason?: string;
  },
): Promise<{ order: AccountOrder; message: string } | { error: string } | null> {
  if (!apiBase) return null;

  try {
    const res = await fetch(`${apiBase}/api/v1/tenants/${tenantSlug}/refund-requests`, {
      method: "POST",
      cache: "no-store",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(payload),
    });

    const data = await res.json().catch(() => null);

    if (!res.ok) {
      return {
        error:
          data?.message ??
          data?.error ??
          (Object.values(data?.errors ?? {}) as string[][])[0]?.[0] ??
          "Impossible d'enregistrer la demande de remboursement.",
      };
    }

    return {
      order: normalizeAccountOrderRecord((data?.order ?? (data?.data as AccountOrder)) as AccountOrder),
      message: (data?.message as string) ?? "Votre demande de remboursement a été enregistrée.",
    };
  } catch {
    return { error: "Impossible de contacter le serveur." };
  }
}

export async function getAccountReceipts(
  tenantSlug: string,
  token: string,
): Promise<AccountReceipt[]> {
  const p = await apiFetch<{ data: AccountReceipt[] }>(
    `/api/v1/tenants/${tenantSlug}/receipts`,
    token,
  );
  return (p?.data ?? []).map((receipt) => normalizeAccountReceiptRecord(receipt));
}

export async function getAccountReceipt(
  tenantSlug: string,
  token: string,
  ref: string,
): Promise<AccountReceipt | null> {
  const p = await apiFetch<{ data: AccountReceipt }>(
    `/api/v1/tenants/${tenantSlug}/receipts/${ref}`,
    token,
  );
  return p?.data ? normalizeAccountReceiptRecord(p.data) : null;
}

export async function getAccountPasses(
  tenantSlug: string,
  token: string,
): Promise<AccountAccessPass[]> {
  const p = await apiFetch<{ data: AccountAccessPass[] }>(
    `/api/v1/tenants/${tenantSlug}/access-passes`,
    token,
  );
  return p?.data ?? [];
}

export async function getAccountPass(
  tenantSlug: string,
  token: string,
  id: string,
): Promise<AccountAccessPass | null> {
  const p = await apiFetch<{ data: AccountAccessPass }>(
    `/api/v1/tenants/${tenantSlug}/access-passes/${id}`,
    token,
  );
  return p?.data ?? null;
}
