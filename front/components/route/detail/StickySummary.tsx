import Link from "next/link";

import { EventLikeButton } from "@/components/EventLikeButton";
import type { PublicContent } from "@/lib/types";
import { formatDateRange, formatMoney } from "@/lib/utils";

import { ShareLinks } from "./ShareLinks";

export function StickySummary({ item }: { item: PublicContent }) {
  const organizerImage = item.organizers[0]?.imageUrl ?? item.coverImageUrl;
  const organizerName = item.organizers[0]?.name ?? "Equipe organisatrice";
  const applicationHref = item.module === "appels-a-projets" && item.applicationForm
    ? `/appels-a-projets/${item.slug}/postuler`
    : null;

  return (
    <aside className="sticky-panel">
      <Link className="publisher-pill publisher-pill--card" href={`/organisateurs/${item.organizerSlug}`}>
        <img alt={organizerName} src={organizerImage} />
        <span>
          <small>Publie par</small>
          <strong>{organizerName}</strong>
        </span>
      </Link>
      <div className="sticky-panel__price">
        <span>A partir de</span>
        <strong>{item.isFree ? "Gratuit" : formatMoney(item.priceFrom, item.currency)}</strong>
      </div>
      <ul className="facts-list">
        <li>
          <strong>Date</strong>
          <span>{formatDateRange(item)}</span>
        </li>
        <li>
          <strong>Lieu</strong>
          <span>
            {item.venueName ?? item.city}, {item.country}
          </span>
        </li>
        <li>
          <strong>Organisateur</strong>
          <span>{organizerName}</span>
        </li>
      </ul>
      <div className="sticky-panel__cta-list">
        {applicationHref ? (
          <Link className="button button--full" href={applicationHref}>
            Soumettre ma candidature
          </Link>
        ) : null}
        {item.tiers.map((tier) => (
          <Link
            className="button button--full"
            href={item.organizerSlug
              ? `/checkout/${item.module}/${item.slug}?offer=${encodeURIComponent(tier.id)}&tenant=${encodeURIComponent(item.organizerSlug)}`
              : `/checkout/${item.module}/${item.slug}?offer=${encodeURIComponent(tier.id)}`}
            key={tier.id}
          >
            {tier.ctaLabel} - {tier.title}
          </Link>
        ))}
      </div>
      {item.module === "evenements" ? (
        <div className="detail-like-panel">
          <span>J'aime</span>
          <EventLikeButton
            eventSlug={item.slug}
            initialCount={item.likesCount}
            tenantSlug={item.organizerSlug}
            variant="detail"
          />
        </div>
      ) : null}
      <div className="detail-trust-box">
        <strong>{applicationHref ? "Candidature securisee" : "Checkout securise"}</strong>
        <p>{applicationHref ? "Formulaire public valide cote serveur, villes et pays locaux, pieces jointes controlees." : "Confirmation, verification paiement et recapitulatif centralises pour chaque commande."}</p>
      </div>
      <ShareLinks item={item} />
    </aside>
  );
}
