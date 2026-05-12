import { notFound } from "next/navigation";

import { CallForProjectApplicationForm } from "@/components/CallForProjectApplicationForm";
import { getContentDetail } from "@/lib/data/public";
import { createMetadata } from "@/lib/metadata";

export const dynamic = "force-dynamic";

export async function generateMetadata({
  params,
  searchParams,
}: {
  params: Promise<{ slug: string }>;
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const { slug } = await params;
  const rawSearchParams = await searchParams;
  const tenant = Array.isArray(rawSearchParams.tenant) ? rawSearchParams.tenant[0] : rawSearchParams.tenant;
  const item = await getContentDetail("appels-a-projets", slug, tenant);

  return createMetadata({
    title: item?.title ? `Candidater · ${item.title}` : "Candidater à un appel à projets",
    description: item?.summary ?? "Soumettez votre candidature à cet appel à projets.",
    path: `/appels-a-projets/${slug}/postuler`,
    image: item?.coverImageUrl,
  });
}

export default async function CallForProjectApplicationPage({
  params,
  searchParams,
}: {
  params: Promise<{ slug: string }>;
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const { slug } = await params;
  const rawSearchParams = await searchParams;
  const tenant = Array.isArray(rawSearchParams.tenant) ? rawSearchParams.tenant[0] : rawSearchParams.tenant;
  const item = await getContentDetail("appels-a-projets", slug, tenant);

  if (!item?.applicationForm) {
    notFound();
  }

  return (
    <>
      <section className="page-hero page-hero--compact">
        <div className="shell page-hero__content">
          <p className="eyebrow">Appel à projets</p>
          <h1>Soumettre votre candidature</h1>
          <p>Remplissez le formulaire public avec vos informations, vos pièces jointes et votre numéro avec indicatif international.</p>
          <div className="page-hero__pills">
            <span>{item.title}</span>
            {item.deadlineAt ? <span>Clôture {new Date(item.deadlineAt).toLocaleDateString("fr-FR")}</span> : null}
          </div>
        </div>
      </section>

      <section className="section">
        <div className="shell">
          <CallForProjectApplicationForm item={item} />
        </div>
      </section>
    </>
  );
}
