"use client";

import type { PublicContent } from "@/lib/types";

import { DynamicFormRenderer } from "../dynamic-form/DynamicFormRenderer";
import type { DynamicFormSchema } from "../dynamic-form/types";
import { ApplicationSummary } from "./ApplicationSummary";

export function DynamicCallForProjectApplicationForm({ item }: { item: PublicContent }) {
  const form = item.dynamicForm;

  if (!form) {
    return null;
  }

  const formId = form.id;

  async function submitDynamicForm(responses: Record<string, unknown>) {
    const response = await fetch(
      `/api/public/forms/${encodeURIComponent(formId)}/submissions?tenant=${encodeURIComponent(item.organizerSlug)}`,
      {
        body: JSON.stringify({ responses }),
        headers: { "Content-Type": "application/json" },
        method: "POST",
      },
    );

    if (!response.ok) {
      const payload = await response.json().catch(() => null) as { error?: string; message?: string } | null;
      throw new Error(payload?.error ?? payload?.message ?? "Impossible d’envoyer le formulaire.");
    }
  }

  return (
    <div className="checkout-layout">
      <div className="checkout-form">
        <div className="checkout-head">
          <div>
            <span className="badge">Candidature publique</span>
            <h2>{form.title}</h2>
            <p className="section-copy">
              {form.description ?? "Renseignez soigneusement les informations requises avant l'envoi."}
            </p>
          </div>
        </div>
        <DynamicFormRenderer
          onSubmit={submitDynamicForm}
          schema={{ ...form.schema, success_message: form.success_message } as DynamicFormSchema}
          submitLabel={form.submit_label}
        />
      </div>
      <ApplicationSummary item={item} />
    </div>
  );
}
