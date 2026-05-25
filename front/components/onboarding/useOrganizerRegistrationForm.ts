"use client";

import { type FormEvent, useEffect, useState } from "react";

import { firstInvalid, validateEmail, validatePassword, validateRequired } from "@/lib/client/form-validation";

export type OrganizerRegistrationSuccess = {
  slug: string;
  accessUrl: string;
  loginUrl: string;
};

export function useOrganizerRegistrationForm() {
  const [orgName, setOrgName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [isReady, setIsReady] = useState(false);
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState<OrganizerRegistrationSuccess | null>(null);

  useEffect(() => {
    setIsReady(true);
  }, []);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError("");

    const validation = firstInvalid(
      validateRequired(orgName, "Nom de votre organisation"),
      validateEmail(email),
      validatePassword(password),
    );

    if (!validation.ok) {
      setError(validation.error);
      return;
    }

    setLoading(true);

    try {
      const response = await fetch("/api/onboarding/register", {
        body: JSON.stringify({ email, org_name: orgName, password }),
        headers: { "Content-Type": "application/json" },
        method: "POST",
      });
      const data = await response.json();

      if (!response.ok) {
        setError(data?.error ?? "Inscription impossible.");
        return;
      }

      setSuccess({
        accessUrl: data.tenant.access_url,
        loginUrl: data.tenant.login_url,
        slug: data.tenant.slug,
      });
    } catch {
      setError("Impossible de contacter le serveur.");
    } finally {
      setLoading(false);
    }
  }

  return {
    email,
    error,
    isReady,
    loading,
    orgName,
    password,
    success,
    handleSubmit,
    setEmail,
    setOrgName,
    setPassword,
  };
}
