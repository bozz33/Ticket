export type ValidationResult = {
  ok: boolean;
  error: string;
};

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export function valid(): ValidationResult {
  return { ok: true, error: "" };
}

export function invalid(error: string): ValidationResult {
  return { ok: false, error };
}

export function validateEmail(email: string): ValidationResult {
  const value = email.trim();

  if (!value) {
    return invalid("Adresse e-mail requise.");
  }

  if (!emailPattern.test(value)) {
    return invalid("Adresse e-mail invalide.");
  }

  return valid();
}

export function validatePassword(password: string, minimumLength = 8): ValidationResult {
  if (!password) {
    return invalid("Mot de passe requis.");
  }

  if (password.length < minimumLength) {
    return invalid(`Le mot de passe doit contenir au moins ${minimumLength} caractères.`);
  }

  return valid();
}

export function validateRequired(value: string, label: string): ValidationResult {
  if (!value.trim()) {
    return invalid(`${label} requis.`);
  }

  return valid();
}

export function validateTextLength(value: string, maxLength: number, label: string): ValidationResult {
  if (value.length > maxLength) {
    return invalid(`${label} ne doit pas dépasser ${maxLength} caractères.`);
  }

  return valid();
}

export function firstInvalid(...results: ValidationResult[]): ValidationResult {
  return results.find((result) => !result.ok) ?? valid();
}
