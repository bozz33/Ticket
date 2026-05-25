export interface CheckoutPaymentMethod {
  code: string;
  label: string;
  kind: "free" | "card" | "mobile_money";
}

export interface CheckoutPricing {
  subtotal: number;
  service_fee: number;
  service_fee_label?: string | null;
  service_fee_hint?: string | null;
  total: number;
  currency: string;
  quantity: number;
  policy?: {
    module: string;
    mode: string | null;
    commission_rate: number;
    flat_fee_amount: number;
  } | null;
}

export interface CheckoutPaymentOptions {
  methods: CheckoutPaymentMethod[];
  pricing: CheckoutPricing;
  proforma_reference?: string | null;
  quantity: {
    min: number;
    max: number;
    max_per_account?: number | null;
  };
  offer: {
    id: string;
    title: string;
    currency: string;
    unit_amount: number;
  };
  checkout_item?: {
    type: "offer" | "event_ticket" | string;
    id: string;
    title: string;
  };
  ticket?: {
    id: string;
    title: string;
    availability: {
      status: string;
      label: string;
      remaining?: number | null;
      is_sold_out: boolean;
      is_available: boolean;
    };
  } | null;
  tenant: {
    public_id: string;
    name: string;
    slug: string;
  };
}

export interface CheckoutInitializationResult {
  mode: "free" | "redirect";
  reference: string;
  status: string;
  authorization_url: string | null;
  access_code?: string | null;
  public_key?: string | null;
  callback_url?: string | null;
  order?: {
    public_id: string;
    reference: string;
  } | null;
}

export interface TicketReservationResult {
  type: "event_ticket" | string;
  id: string;
  quantity: number;
  expires_at?: string | null;
  metadata?: Record<string, unknown>;
}

export interface CheckoutVerificationResult {
  reference: string;
  status: string;
  is_successful: boolean;
  paid_at?: string | null;
  quantity: number;
  payment_label?: string | null;
  amounts: {
    gross: number;
    fees: number;
    net: number;
    currency: string;
  };
  order?: {
    public_id: string;
    reference: string;
    status: string;
  } | null;
  receipt?: {
    public_id: string;
    reference: string;
    status: string;
  } | null;
  access_passes_count: number;
}
