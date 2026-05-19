"use client";

import { type FormEvent, useEffect, useState } from "react";
import { useRouter } from "next/navigation";

import { firstInvalid, validateEmail, validatePassword, validateRequired } from "@/lib/client/form-validation";

export function useRegistrationForm() {
  const router = useRouter();
  const [isReady, setIsReady] = useState(false);
  const [tenant, setTenant] = useState("");
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const currentSearchParams = new URLSearchParams(window.location.search);

    setTenant(currentSearchParams.get("tenant")?.trim() ?? "");
    setIsReady(true);
  }, []);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError("");

    const validation = firstInvalid(
      validateRequired(name, "Nom complet"),
      validateEmail(email),
      validatePassword(password),
    );

    if (!validation.ok) {
      setError(validation.error);
      return;
    }

    setLoading(true);

    try {
      const response = await fetch("/api/account/register", {
        body: JSON.stringify({ email, name, password, tenant: tenant || undefined }),
        headers: { "Content-Type": "application/json" },
        method: "POST",
      });
      const data = await response.json();

      if (!response.ok) {
        setError(data?.error ?? "Inscription impossible.");
        return;
      }

      router.push("/compte/profil?verify=sent");
      router.refresh();
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
    name,
    password,
    handleSubmit,
    setEmail,
    setName,
    setPassword,
  };
}
