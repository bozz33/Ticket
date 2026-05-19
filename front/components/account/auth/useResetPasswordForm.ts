"use client";

import { type FormEvent, useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";

import { firstInvalid, validateEmail, validatePassword } from "@/lib/client/form-validation";

export function useResetPasswordForm() {
  const router = useRouter();
  const [isReady, setIsReady] = useState(false);
  const [email, setEmail] = useState("");
  const [token, setToken] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);
  const hasValidLink = useMemo(() => email.length > 0 && token.length > 0, [email, token]);

  useEffect(() => {
    const currentSearchParams = new URLSearchParams(window.location.search);

    setEmail(currentSearchParams.get("email") ?? "");
    setToken(currentSearchParams.get("token") ?? "");
    setIsReady(true);
  }, []);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");

    if (!hasValidLink) {
      setError("Le lien de réinitialisation est incomplet ou invalide.");
      return;
    }

    const validation = firstInvalid(validateEmail(email), validatePassword(password));

    if (!validation.ok) {
      setError(validation.error);
      return;
    }

    if (password !== passwordConfirmation) {
      setError("Les mots de passe ne correspondent pas.");
      return;
    }

    setLoading(true);

    try {
      const response = await fetch("/api/account/reset-password", {
        body: JSON.stringify({
          email,
          password,
          passwordConfirmation,
          token,
        }),
        headers: { "Content-Type": "application/json" },
        method: "POST",
      });
      const data = await response.json();

      if (!response.ok) {
        setError(data?.error ?? "Impossible de réinitialiser le mot de passe.");
        return;
      }

      router.push("/compte/connexion?reset=success");
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
    hasValidLink,
    isReady,
    loading,
    password,
    passwordConfirmation,
    handleSubmit,
    setPassword,
    setPasswordConfirmation,
  };
}
