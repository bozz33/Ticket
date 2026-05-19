"use client";

import { type FormEvent, useEffect, useState } from "react";

import { validateEmail } from "@/lib/client/form-validation";

export function useForgotPasswordForm() {
  const [isReady, setIsReady] = useState(false);
  const [tenant, setTenant] = useState("");
  const [email, setEmail] = useState("");
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const currentSearchParams = new URLSearchParams(window.location.search);

    setTenant(currentSearchParams.get("tenant")?.trim() ?? "");
    setIsReady(true);
  }, []);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");
    setSuccess("");

    const validation = validateEmail(email);

    if (!validation.ok) {
      setError(validation.error);
      return;
    }

    setLoading(true);

    try {
      const response = await fetch("/api/account/forgot-password", {
        body: JSON.stringify({ email, tenant: tenant || undefined }),
        headers: { "Content-Type": "application/json" },
        method: "POST",
      });
      const data = await response.json();

      if (!response.ok) {
        setError(data?.error ?? "Impossible d'envoyer le lien de réinitialisation.");
        return;
      }

      setSuccess(data?.message ?? "Si votre compte existe, un lien de réinitialisation vient d'être envoyé.");
    } catch {
      setError("Impossible de contacter le serveur.");
    } finally {
      setLoading(false);
    }
  }

  return {
    email,
    error,
    loading,
    loginHref: isReady && tenant ? `/compte/connexion?tenant=${encodeURIComponent(tenant)}` : "/compte/connexion",
    success,
    handleSubmit,
    setEmail,
  };
}
