"use client";

type GuestContributor = {
  name: string;
  email: string;
  phone: string;
  isAnonymous: boolean;
};

type GuestContributorFormProps = {
  guestContributor: GuestContributor;
  onGuestContributorChange: (value: GuestContributor) => void;
};

export function GuestContributorForm({ guestContributor, onGuestContributorChange }: GuestContributorFormProps) {
  return (
    <article className="checkout-stage-card checkout-stage-card--quantity">
      <div className="checkout-stage-card__section-title">
        <div>
          <p className="eyebrow">Contributeur</p>
          <h2>Vos informations</h2>
        </div>
      </div>
      <p className="checkout-stage-card__hint">
        Ces informations servent à confirmer votre contribution. Si vous cochez l’anonymat, votre nom ne sera pas utilisé pour un affichage public.
      </p>
      <div className="form-grid">
        <label className="field">
          <span>Nom</span>
          <input
            aria-label="Nom"
            onChange={(event) => onGuestContributorChange({ ...guestContributor, name: event.target.value })}
            required
            type="text"
            value={guestContributor.name}
          />
        </label>
        <label className="field">
          <span>E-mail</span>
          <input
            aria-label="E-mail"
            onChange={(event) => onGuestContributorChange({ ...guestContributor, email: event.target.value })}
            required
            type="email"
            value={guestContributor.email}
          />
        </label>
        <label className="field">
          <span>Téléphone</span>
          <input
            aria-label="Téléphone"
            onChange={(event) => onGuestContributorChange({ ...guestContributor, phone: event.target.value })}
            type="tel"
            value={guestContributor.phone}
          />
        </label>
        <label className="checkbox-field">
          <input
            aria-label="Contribuer anonymement"
            checked={guestContributor.isAnonymous}
            onChange={(event) => onGuestContributorChange({ ...guestContributor, isAnonymous: event.target.checked })}
            type="checkbox"
          />
          <span>Contribuer anonymement si les contributeurs sont affichés plus tard</span>
        </label>
      </div>
    </article>
  );
}
