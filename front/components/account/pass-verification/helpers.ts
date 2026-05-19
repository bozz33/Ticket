import type { AccessPassStatus } from "@/lib/types";

export const PUBLIC_PASS_STATUS_LABELS: Record<AccessPassStatus, string> = {
  active: "Valide",
  used: "Déjà utilisé",
  revoked: "Révoqué",
  expired: "Expiré",
};

export const PUBLIC_PASS_STATUS_COPY: Record<AccessPassStatus, string> = {
  active: "Ce pass est actif et peut être accepté à l'entrée si l'identité correspond.",
  used: "Ce pass a déjà été consommé. Il ne doit plus être accepté une seconde fois.",
  revoked: "Ce pass a été révoqué et ne doit pas être accepté.",
  expired: "Ce pass est expiré et ne doit plus être accepté.",
};

export function formatPublicPassDate(iso: string | null) {
  if (!iso) return "—";
  return new Date(iso).toLocaleDateString("fr-FR", {
    day: "numeric",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}
