import Link from "next/link";

import type { PlatformConfiguration } from "@/lib/types";

type HeaderBrandProps = {
  platform: PlatformConfiguration;
  onClick: () => void;
};

export function HeaderBrand({ platform, onClick }: HeaderBrandProps) {
  return (
    <Link className="brand" href="/" onClick={onClick}>
      {platform.logoUrl ? (
        <img alt={platform.brandName} className="brand__logo" src={platform.logoUrl} />
      ) : (
        <span className="brand__mark">T</span>
      )}
      <span className="brand__copy">
        <strong>{platform.brandName}</strong>
        <small>Public marketplace</small>
      </span>
    </Link>
  );
}
