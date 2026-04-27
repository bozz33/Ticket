import Link from "next/link";

import { PlatformConfiguration } from "@/lib/types";

export function Footer({ platform }: { platform: PlatformConfiguration }) {
  return (
    <footer className="site-footer">
      <div className="shell footer-grid">
        <div>
          <p className="eyebrow">Portail public</p>
          <h2 className="footer-title">{platform.brandName}</h2>
          <p className="footer-copy">
            Catalogue public unifie pour decouvrir, comparer et convertir sur plusieurs modules
            metier.
          </p>
        </div>

        <div>
          <h3 className="footer-heading">Explorer</h3>
          <ul className="footer-list">
            <li>
              <Link href="/evenements">Evenements</Link>
            </li>
            <li>
              <Link href="/formations">Formations</Link>
            </li>
            <li>
              <Link href="/stands">Stands</Link>
            </li>
            <li>
              <Link href="/crowdfunding">Crowdfunding</Link>
            </li>
          </ul>
        </div>

        <div>
          <h3 className="footer-heading">Plateforme</h3>
          <ul className="footer-list">
            <li>
              <Link href="/categories">Categories</Link>
            </li>
            <li>
              <Link href="/villes">Villes</Link>
            </li>
            <li>
              <Link href="/support">Support</Link>
            </li>
            <li>
              <Link href="/intervenants">Intervenants</Link>
            </li>
            <li>
              <Link href="/a-propos">A propos</Link>
            </li>
            <li>
              <Link href="/devenir-organisateur">Devenir organisateur</Link>
            </li>
          </ul>
        </div>

        <div>
          <h3 className="footer-heading">Paiement</h3>
          <ul className="footer-list">
            {platform.paymentMethods.map((method) => (
              <li key={method}>{method}</li>
            ))}
          </ul>
        </div>
      </div>
      <div className="shell footer-bottom">
        <p>
          {new Date().getFullYear()} {platform.brandName}. Tous droits reserves.
        </p>
        <div className="footer-bottom__links">
          <Link href="/support">FAQ</Link>
          <a href={platform.accountUrl}>Panel user</a>
          <a href={platform.organizerCtaUrl}>Onboarding organisateur</a>
        </div>
      </div>
    </footer>
  );
}
