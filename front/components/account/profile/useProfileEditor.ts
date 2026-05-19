"use client";

import { useRouter } from "next/navigation";
import { type ChangeEvent, type FormEvent, useEffect, useState } from "react";

import type { AccountUser } from "@/lib/types";

import {
  sendVerificationNotification,
  updatePassword,
  updateProfile,
  uploadAvatar,
} from "./api";
import {
  notifyAccountProfileUpdated,
  toProfileFormState,
} from "./helpers";
import type { PasswordFormState, ProfileFormState } from "./types";

export function useProfileEditor(initialUser: AccountUser | null) {
  const router = useRouter();
  const [user, setUser] = useState<AccountUser | null>(initialUser);
  const [form, setForm] = useState<ProfileFormState | null>(initialUser ? toProfileFormState(initialUser) : null);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);
  const [isSendingVerification, setIsSendingVerification] = useState(false);
  const [avatarPreview, setAvatarPreview] = useState<string | null>(null);
  const [avatarError, setAvatarError] = useState<string | null>(null);
  const [avatarSuccess, setAvatarSuccess] = useState<string | null>(null);
  const [isUploadingAvatar, setIsUploadingAvatar] = useState(false);
  const [passwordForm, setPasswordForm] = useState<PasswordFormState>({
    current_password: "",
    password: "",
    password_confirmation: "",
  });
  const [passwordError, setPasswordError] = useState<string | null>(null);
  const [passwordSuccess, setPasswordSuccess] = useState<string | null>(null);
  const [isChangingPassword, setIsChangingPassword] = useState(false);

  useEffect(() => {
    setUser(initialUser);
    setForm(initialUser ? toProfileFormState(initialUser) : null);
  }, [initialUser]);

  useEffect(() => {
    return () => {
      if (avatarPreview?.startsWith("blob:")) {
        URL.revokeObjectURL(avatarPreview);
      }
    };
  }, [avatarPreview]);

  function handleChange(event: ChangeEvent<HTMLInputElement | HTMLSelectElement>) {
    const { name, value } = event.target;
    setForm((current) => (current ? { ...current, [name]: value } : current));
    setError(null);
    setSuccess(null);
  }

  function handlePasswordChange(event: ChangeEvent<HTMLInputElement>) {
    const { name, value } = event.target;
    setPasswordForm((current) => ({ ...current, [name]: value }));
    setPasswordError(null);
    setPasswordSuccess(null);
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (!form) {
      return;
    }

    setIsSaving(true);
    setError(null);
    setSuccess(null);

    try {
      const { payload, response } = await updateProfile(form);

      if (!response.ok || !payload?.user) {
        setError(payload?.error ?? payload?.message ?? "Impossible de mettre à jour votre profil.");
        return;
      }

      setUser(payload.user);
      setForm(toProfileFormState(payload.user));
      setSuccess(payload.message ?? "Profil mis à jour avec succès.");
      notifyAccountProfileUpdated(payload.user);
      router.refresh();
    } catch {
      setError("Impossible de contacter le serveur pour le moment.");
    } finally {
      setIsSaving(false);
    }
  }

  async function handleAvatarChange(event: ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0] ?? null;

    if (!file) {
      return;
    }

    const preview = URL.createObjectURL(file);
    setAvatarPreview((current) => {
      if (current?.startsWith("blob:")) {
        URL.revokeObjectURL(current);
      }

      return preview;
    });
    setAvatarError(null);
    setAvatarSuccess(null);
    setIsUploadingAvatar(true);

    try {
      const { payload, response } = await uploadAvatar(file);

      if (!response.ok || !payload?.user) {
        setAvatarError(payload?.error ?? payload?.message ?? "Impossible de mettre à jour la photo.");
        return;
      }

      setUser(payload.user);
      setForm(toProfileFormState(payload.user));
      setAvatarSuccess(payload.message ?? "Photo de profil mise à jour.");
      notifyAccountProfileUpdated(payload.user);
      router.refresh();
    } catch {
      setAvatarError("Impossible de contacter le serveur pour le moment.");
    } finally {
      setIsUploadingAvatar(false);
      event.target.value = "";
    }
  }

  async function handleSendVerification() {
    setIsSendingVerification(true);
    setError(null);
    setSuccess(null);

    try {
      const { payload, response } = await sendVerificationNotification();

      if (!response.ok) {
        setError(payload?.error ?? payload?.message ?? "Impossible d'envoyer le lien de vérification.");
        return;
      }

      if (payload?.user) {
        setUser(payload.user);
        setForm(toProfileFormState(payload.user));
      }

      setSuccess(payload?.message ?? "Lien de vérification envoyé.");
    } catch {
      setError("Impossible de contacter le serveur pour le moment.");
    } finally {
      setIsSendingVerification(false);
    }
  }

  async function handlePasswordSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPasswordError(null);
    setPasswordSuccess(null);

    if (passwordForm.password !== passwordForm.password_confirmation) {
      setPasswordError("Les deux nouveaux mots de passe ne correspondent pas.");
      return;
    }

    setIsChangingPassword(true);

    try {
      const { payload, response } = await updatePassword(passwordForm);

      if (!response.ok || !payload?.user) {
        setPasswordError(payload?.error ?? payload?.message ?? "Impossible de modifier le mot de passe.");
        return;
      }

      setUser(payload.user);
      setPasswordForm({
        current_password: "",
        password: "",
        password_confirmation: "",
      });
      setPasswordSuccess(payload.message ?? "Mot de passe mis à jour.");
      notifyAccountProfileUpdated(payload.user);
    } catch {
      setPasswordError("Impossible de contacter le serveur pour le moment.");
    } finally {
      setIsChangingPassword(false);
    }
  }

  return {
    avatarError,
    avatarSrc: avatarPreview ?? (user?.avatar_url ? `/api/account/avatar-image?v=${encodeURIComponent(user.avatar_url)}` : null),
    avatarSuccess,
    error,
    form,
    handleAvatarChange,
    handleChange,
    handlePasswordChange,
    handlePasswordSubmit,
    handleSendVerification,
    handleSubmit,
    isChangingPassword,
    isSaving,
    isSendingVerification,
    isUploadingAvatar,
    passwordError,
    passwordForm,
    passwordSuccess,
    success,
    user,
  };
}
