import Link from "next/link";

export function ReceiptNotFoundView() {
  return (
    <section className="section">
      <div className="shell ac-verify-wrap">
        <div className="ac-verify">
          <div className="ac-verify__result ac-verify__result--denied">
            <div className="ac-verify__icon ac-verify__icon--denied">
              <svg
                fill="none"
                height="28"
                stroke="currentColor"
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="2"
                viewBox="0 0 24 24"
                width="28"
              >
                <circle cx="12" cy="12" r="10" />
                <line x1="15" y1="9" x2="9" y2="15" />
                <line x1="9" y1="9" x2="15" y2="15" />
              </svg>
            </div>
            <h1 className="ac-verify__title">Reçu introuvable</h1>
            <p className="ac-verify__sub">
              La référence fournie ne correspond à aucun justificatif actif sur cette plateforme.
            </p>
            <div style={{ marginTop: "20px" }}>
              <Link href="/" className="button button--full">
                Retour à l&apos;accueil
              </Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
