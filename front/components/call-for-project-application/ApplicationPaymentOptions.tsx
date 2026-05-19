import Link from "next/link";

import type { PublicContent } from "@/lib/types";
import { formatMoney } from "@/lib/utils";

type ApplicationPaymentOptionsProps = {
  form: NonNullable<PublicContent["applicationForm"]>;
  item: PublicContent;
};

export function ApplicationPaymentOptions({ form, item }: ApplicationPaymentOptionsProps) {
  if (!form.payment?.has_paid_offers) {
    return null;
  }

  return (
    <div className="checkout-section">
      <h2>Paiement et frais de dossier</h2>
      <p className="section-copy">
        Cet appel à projets comporte une offre gratuite ou payante. Vous pouvez finaliser le paiement avant de soumettre votre candidature.
      </p>
      <div style={{ display: "grid", gap: "12px" }}>
        {form.payment.offers.map((offer) => (
          <Link
            className="button button--ghost"
            href={item.organizerSlug
              ? `/checkout/${item.module}/${item.slug}?offer=${encodeURIComponent(offer.id)}&tenant=${encodeURIComponent(item.organizerSlug)}`
              : `/checkout/${item.module}/${item.slug}?offer=${encodeURIComponent(offer.id)}`}
            key={offer.id}
          >
            {offer.cta_label} · {offer.title} · {offer.price === 0 ? "Gratuit" : formatMoney(offer.price, offer.currency)}
          </Link>
        ))}
      </div>
    </div>
  );
}
