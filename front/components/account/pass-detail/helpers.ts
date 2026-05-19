import type { AccessPassStatus, AccessPassType } from "@/lib/types";

export const PASS_STATUS_LABELS: Record<AccessPassStatus, string> = {
  active: "Actif",
  used: "Utilisé",
  revoked: "Révoqué",
  expired: "Expiré",
};

export const PASS_TYPE_LABELS: Record<AccessPassType, string> = {
  event_ticket: "Billet événement",
  training_enrollment: "Inscription formation",
  stand_reservation: "Réservation stand",
  purchase_pass: "Pass achat",
};

export function formatPassDate(iso: string | null) {
  if (!iso) return "—";
  return new Date(iso).toLocaleDateString("fr-FR", {
    day: "numeric",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}
