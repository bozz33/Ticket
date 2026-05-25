import { redirect } from "next/navigation";

export const dynamic = "force-dynamic";

export default async function RecuDetailPage({
  params,
}: {
  params: Promise<{ ref: string }>;
}) {
  const { ref } = await params;
  redirect(`/compte/recus/${encodeURIComponent(ref)}/imprimer`);
}
