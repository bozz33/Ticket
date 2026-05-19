"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";

import { firstInvalid, validateRequired, validateTextLength } from "@/lib/client/form-validation";

const REASONS = [
  { value: "customer_request", label: "Je ne peux plus participer" },
  { value: "event_rescheduled", label: "Report incompatible" },
  { value: "technical_issue", label: "Erreur technique" },
  { value: "duplicate_charge", label: "Débit en doublon" },
  { value: "manual_refund", label: "Autre motif" },
] as const;

export function RefundRequestForm({
  orderReference,
  disabled,
}: {
  orderReference: string;
  disabled: boolean;
}) {
  const router = useRouter();
  const [reasonCode, setReasonCode] = useState<string>(REASONS[0]?.value ?? "customer_request");
  const [reason, setReason] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (disabled || isSubmitting) {
      return;
    }

    setIsSubmitting(true);
    setError(null);
    setSuccess(null);

    const validation = firstInvalid(
      validateRequired(reasonCode, "Motif"),
      validateTextLength(reason, 2000, "Précision"),
    );

    if (!validation.ok) {
      setError(validation.error);
      setIsSubmitting(false);
      return;
    }

    try {
      const response = await fetch("/api/account/refund-requests", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({
          order_reference: orderReference,
          reason_code: reasonCode,
          reason,
        }),
      });

      const payload = (await response.json().catch(() => null)) as { error?: string; message?: string } | null;

      if (!response.ok) {
        setError(payload?.error ?? "Impossible d'enregistrer la demande.");
        return;
      }

      setSuccess(payload?.message ?? "Votre demande a bien été envoyée.");
      router.refresh();
    } catch {
      setError("Impossible de contacter le serveur.");
    } finally {
      setIsSubmitting(false);
    }
  }

  if (disabled) {
    return (
      <div className="ac-banner" style={{ marginBottom: 0, background: "var(--bg-alt)", border: "1px solid var(--line)", color: "var(--text-soft)" }}>
        Cette commande n'est pas éligible à une nouvelle demande de remboursement.
      </div>
    );
  }

  return (
    <form className="ac-form" onSubmit={handleSubmit}>
      <div className="ac-form__field">
        <label className="ac-form__label" htmlFor="refund-reason-code">Motif</label>
        <select
          id="refund-reason-code"
          className="ac-form__input"
          value={reasonCode}
          onChange={(event) => setReasonCode(event.target.value)}
          disabled={isSubmitting}
        >
          {REASONS.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
      </div>

      <div className="ac-form__field">
        <label className="ac-form__label" htmlFor="refund-reason">Précision</label>
        <textarea
          id="refund-reason"
          className="ac-form__input"
          rows={4}
          maxLength={2000}
          value={reason}
          onChange={(event) => setReason(event.target.value)}
          disabled={isSubmitting}
          placeholder="Ajoutez un contexte utile à l'équipe support."
        />
      </div>

      {error ? <p className="ac-form__error">{error}</p> : null}
      {success ? <p className="ac-form__success">{success}</p> : null}

      <div className="ac-profile-form__actions" style={{ justifyContent: "flex-start" }}>
        <button className="ac-profile-form__button ac-profile-form__button--inline" type="submit" disabled={isSubmitting}>
          {isSubmitting ? "Envoi..." : "Demander un remboursement"}
        </button>
      </div>
    </form>
  );
}
