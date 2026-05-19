import type { AccountOrder } from "@/lib/types";

type OrderBuyerPanelProps = {
  order: AccountOrder;
};

export function OrderBuyerPanel({ order }: OrderBuyerPanelProps) {
  return (
    <div className="ac-detail-panel">
      <div className="ac-detail-panel__head">
        <span className="ac-detail-panel__title">Acheteur</span>
      </div>
      <div className="ac-detail-panel__body">
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">Nom</span>
          <span className="ac-detail-row__value">{order.buyer_name ?? "—"}</span>
        </div>
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">E-mail</span>
          <span className="ac-detail-row__value">{order.buyer_email ?? "—"}</span>
        </div>
        <div className="ac-detail-row">
          <span className="ac-detail-row__label">Téléphone</span>
          <span className="ac-detail-row__value">{order.buyer_phone ?? "—"}</span>
        </div>
      </div>
    </div>
  );
}
