import Link from "next/link";

type PassPublicVerificationLinkProps = {
  accessCode: string;
};

export function PassPublicVerificationLink({ accessCode }: PassPublicVerificationLinkProps) {
  return (
    <div style={{ marginTop: "16px", textAlign: "center" }}>
      <Link
        href={`/verifier/${accessCode}`}
        className="ac-back"
        rel="noreferrer"
        style={{ justifyContent: "center" }}
        target="_blank"
      >
        <svg
          fill="none"
          height="14"
          stroke="currentColor"
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth="2"
          viewBox="0 0 24 24"
          width="14"
        >
          <circle cx="11" cy="11" r="8" />
          <path d="m21 21-4.35-4.35" />
        </svg>
        Vérifier ce pass publiquement
      </Link>
    </div>
  );
}
