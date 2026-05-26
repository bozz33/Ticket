import Link from "next/link";
import type { ReactNode } from "react";

import type { NavigationLink } from "@/lib/types";

type HeaderLinkProps = {
  className?: string;
  icon?: ReactNode;
  link: NavigationLink;
  onClick?: () => void;
};

export function HeaderLink({ className, icon, link, onClick }: HeaderLinkProps) {
  const target = link.target || undefined;
  const isExternal = link.href.startsWith("http://") || link.href.startsWith("https://") || target === "_blank";

  if (isExternal) {
    return (
      <a className={className} href={link.href} onClick={onClick} rel={target === "_blank" ? "noreferrer" : undefined} target={target}>
        {icon}
        {link.label}
      </a>
    );
  }

  return (
    <Link className={className} href={link.href} onClick={onClick} target={target}>
      {icon}
      {link.label}
    </Link>
  );
}
