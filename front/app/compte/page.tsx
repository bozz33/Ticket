import Link from "next/link";

import { getPlatformConfiguration } from "@/lib/data/public";

export const dynamic = "force-dynamic";

export default async function AccountPage() {
  const platform = await getPlatformConfiguration();

  return (
    <>
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">Compte</p>
          <h1>Espace utilisateur</h1>
          <p>Retrouvez vos commandes, inscriptions et contributions dans le panel user.</p>
          <Link className="button" href={platform.accountUrl}>
            Ouvrir le panel user
          </Link>
        </div>
      </section>
    </>
  );
}
