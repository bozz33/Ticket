import Link from "next/link";

import type { NavigationLink } from "@/lib/types";

type HeaderLinkProps = {
  link: NavigationLink;
  onClick?: () => void;
};

export function HeaderLink({ link, onClick }: HeaderLinkProps) {
  const target = link.target || undefined;
  const isExternal = link.href.startsWith("http://") || link.href.startsWith("https://") || target === "_blank";

  if (isExternal) {
    return (
      <a href={link.href} onClick={onClick} rel={target === "_blank" ? "noreferrer" : undefined} target={target}>
        {link.label}
      </a>
    );
  }

  return (
    <Link href={link.href} onClick={onClick} target={target}>
      {link.label}
    </Link>
  );
}
