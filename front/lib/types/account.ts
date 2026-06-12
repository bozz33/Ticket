export type OrderStatus = "pending" | "confirmed" | "cancelled" | "refund_pending" | "refunded";
export type ReceiptStatus = "issued" | "cancelled" | "refunded";
export type AccessPassStatus = "active" | "used" | "revoked" | "expired";
export type AccessPassType =
  | "event_ticket"
  | "training_enrollment"
  | "stand_reservation"
  | "purchase_pass";
export type ScanResult =
  | "granted"
  | "already_used"
  | "revoked"
  | "expired"
  | "not_found"
  | "denied";

export interface AccountUser {
  id: number;
  name: string;
  first_name: string | null;
  last_name: string | null;
  username: string | null;
  email: string;
  email_verified: boolean;
  email_verified_at: string | null;
  phone: string | null;
  locale: string | null;
  timezone: string | null;
  avatar_url: string | null;
  last_login_at: string | null;
  unread_notifications_count: number;
  profile_completed: boolean;
  missing_profile_fields: string[];
  account_ready_for_actions: boolean;
}

export interface AccountProfileUpdateInput {
  name: string;
  first_name?: string | null;
  last_name?: string | null;
  email: string;
  phone?: string | null;
  locale?: string | null;
  timezone?: string | null;
}

export interface AccountPasswordUpdateInput {
  current_password: string;
  password: string;
  password_confirmation: string;
}

export interface AccountNotification {
  id: string;
  title: string;
  body: string;
  icon: string;
  action_url: string | null;
  read_at: string | null;
  created_at: string | null;
}

export interface AccountOffer {
  id: number;
  name: string;
  offer_type: string;
}

export interface AccountOrder {
  id: number;
  public_id: string;
  reference: string;
  transaction_reference: string;
  status: OrderStatus;
  quantity: number;
  unit_amount: number;
  subtotal_amount?: number;
  customer_fee_amount?: number;
  total_amount: number;
  currency_code: string;
  buyer_name: string | null;
  buyer_email: string | null;
  buyer_phone: string | null;
  offer: AccountOffer | null;
  receipt: AccountReceipt | null;
  access_passes: AccountAccessPass[];
  access_passes_count: number;
  created_at: string;
  meta: Record<string, unknown> | null;
}

export interface AccountReceipt {
  id: number;
  public_id: string;
  reference: string;
  receipt_number: string | null;
  status: ReceiptStatus;
  total_amount: number;
  currency_code: string;
  buyer_name: string | null;
  buyer_email: string | null;
  buyer_phone?: string | null;
  issued_at: string | null;
  created_at: string;
  order: AccountOrder | null;
  meta: Record<string, unknown> | null;
}

export interface AccountAccessPass {
  id: number;
  public_id: string;
  access_code: string;
  type: AccessPassType;
  status: AccessPassStatus;
  holder_name: string | null;
  holder_email: string | null;
  used_at: string | null;
  expires_at: string | null;
  revoked_at: string | null;
  revocation_reason: string | null;
  order: { reference: string; buyer_phone?: string | null } | null;
  offer: { name: string } | null;
  scans_count?: number;
  created_at: string;
  meta: Record<string, unknown> | null;
}

export interface PublicVerificationEvent {
  public_id?: string | null;
  title?: string | null;
  slug?: string | null;
  starts_at?: string | null;
  ends_at?: string | null;
  venue_name?: string | null;
  venue_address?: string | null;
  city?: string | null;
  country_code?: string | null;
  location?: string | null;
}

export interface PublicPassVerification {
  public_id: string;
  type: AccessPassType;
  type_label: string;
  status: AccessPassStatus;
  holder_name: string | null;
  used_at: string | null;
  expires_at: string | null;
  qr_payload: { code: string; type: string; public_id: string };
  event?: PublicVerificationEvent | null;
}

export interface PublicReceiptVerification {
  public_id: string;
  reference: string;
  status: string;
  total_amount: number;
  currency_code: string;
  issued_at: string | null;
  buyer_name: string | null;
  buyer_email_masked: string | null;
  buyer_phone_masked: string | null;
  order_reference: string | null;
  transaction_reference: string | null;
  gateway_reference: string | null;
  gateway_transaction_id?: string | number | null;
  offer_name: string | null;
  access_passes_count: number;
  event?: PublicVerificationEvent | null;
}
