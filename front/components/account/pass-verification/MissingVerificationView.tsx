import Link from "next/link";

type MissingVerificationViewProps = {
  actionHref: string;
  actionLabel: string;
  body: string;
  title: string;
};

export function MissingVerificationView({ actionHref, actionLabel, body, title }: MissingVerificationViewProps) {
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
            <h1 className="ac-verify__title">{title}</h1>
            <p className="ac-verify__sub">{body}</p>
            <div style={{ marginTop: "20px" }}>
              <Link href={actionHref} className="button button--full">
                {actionLabel}
              </Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
