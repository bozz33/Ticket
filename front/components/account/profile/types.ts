export type ProfileFormState = {
  name: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  locale: string;
  timezone: string;
};

export type PasswordFormState = {
  current_password: string;
  password: string;
  password_confirmation: string;
};
