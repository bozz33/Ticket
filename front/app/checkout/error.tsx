"use client";

export default function CheckoutError({
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {

  return (
    <div className="checkout-error-boundary">
      <h2>Une erreur est survenue</h2>
      <p>
        Votre paiement n&apos;a pas été débité. Veuillez rafraîchir la page ou réessayer plus
        tard.
      </p>
      <button className="button" type="button" onClick={reset}>
        Réessayer
      </button>
    </div>
  );
}
