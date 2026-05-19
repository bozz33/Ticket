"use client";

import { type FormEvent, useEffect, useState } from "react";
import { useRouter } from "next/navigation";

import { firstInvalid, validateEmail, validatePassword } from "@/lib/client/form-validation";

export function useLoginForm() {
  const router = useRouter();
  const [isReady, setIsReady] = useState(false);
  const [redirect, setRedirect] = useState("/compte/commandes");
  const [tenant, setTenant] = useState("");
  const [resetSuccess, setResetSuccess] = useState(false);
  const [verifiedSuccess, setVerifiedSuccess] = useState(false);
  const [likeReason, setLikeReason] = useState(false);
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const currentSearchParams = new URLSearchParams(window.location.search);

    setRedirect(currentSearchParams.get("redirect") ?? "/compte/commandes");
    setTenant(currentSearchParams.get("tenant")?.trim() ?? "");
    setResetSuccess(currentSearchParams.get("reset") === "success");
    setVerifiedSuccess(currentSearchParams.get("verified") === "success");
    setLikeReason(currentSearchParams.get("reason") === "like");
    setIsReady(true);
  }, []);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError("");

    const validation = firstInvalid(validateEmail(email), validatePassword(password));

    if (!validation.ok) {
      setError(validation.error);
      return;
    }

    setLoading(true);

    try {
      const response = await fetch("/api/account/login", {
        body: JSON.stringify({ email, password, tenant: tenant || undefined }),
        headers: { "Content-Type": "application/json" },
        method: "POST",
      });
      const data = await response.json();

      if (!response.ok) {
        setError(data?.error ?? "Identifiants incorrects.");
        return;
      }

      router.push(redirect);
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
    forgotPasswordHref: tenant ? `/compte/reinitialisation?tenant=${encodeURIComponent(tenant)}` : "/compte/reinitialisation",
    isReady,
    likeReason,
    loading,
    password,
    resetSuccess,
    signupHref: tenant ? `/compte/inscription?tenant=${encodeURIComponent(tenant)}` : "/compte/inscription",
    verifiedSuccess,
    handleSubmit,
    setEmail,
    setPassword,
  };
}
