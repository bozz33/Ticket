import Link from "next/link";

export function PassBackLink() {
  return (
    <Link href="/compte/passes" className="ac-back">
      <svg
        fill="none"
        height="16"
        stroke="currentColor"
        strokeLinecap="round"
        strokeLinejoin="round"
        strokeWidth="2"
        viewBox="0 0 24 24"
        width="16"
      >
        <path d="m15 18-6-6 6-6" />
      </svg>
      Retour aux passes
    </Link>
  );
}
