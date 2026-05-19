import type {
  CallForProjectApplicationField,
  CallForProjectApplicationForm,
  PublicContent,
} from "@/lib/types";

import { buildApplicationFormData, toFieldErrors } from "./helpers";
import type { FieldErrorMap, FormValue } from "./types";

type SubmitApplicationInput = {
  files: Record<string, File | null>;
  form: CallForProjectApplicationForm;
  item: PublicContent;
  values: Record<string, FormValue>;
  visibleFields: CallForProjectApplicationField[];
};

type ApplicationSubmissionPayload = {
  message?: string;
  error?: string;
  data?: { id?: string };
  errors?: Record<string, string[] | string>;
};

export type ApplicationSubmissionResult =
  | {
      ok: true;
      message: string;
    }
  | {
      ok: false;
      errors: FieldErrorMap;
      message: string;
    };

export async function submitApplication({
  files,
  form,
  item,
  values,
  visibleFields,
}: SubmitApplicationInput): Promise<ApplicationSubmissionResult> {
  const formData = buildApplicationFormData(visibleFields, values, files);
  const response = await fetch(
    `/api/public/call-for-projects/${encodeURIComponent(item.slug)}/apply?tenant=${encodeURIComponent(item.organizerSlug)}`,
    {
      body: formData,
      method: "POST",
    },
  );
  const payload = (await response.json().catch(() => null)) as ApplicationSubmissionPayload | null;

  if (!response.ok) {
    return {
      ok: false,
      errors: toFieldErrors(payload),
      message: payload?.error ?? payload?.message ?? "Impossible d'envoyer votre candidature.",
    };
  }

  return {
    ok: true,
    message: payload?.message ?? form.success_message ?? "Votre candidature a bien été envoyée.",
  };
}
