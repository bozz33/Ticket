export interface DynamicFormOption {
  value: string;
  label: string;
}

export interface DynamicFormField {
  key: string;
  type: string;
  label?: string;
  help_text?: string;
  required?: boolean;
  visible?: boolean;
  accept?: string[];
  options?: DynamicFormOption[] | Record<string, string>;
}

export interface DynamicFormSchema {
  title?: string;
  description?: string;
  submit_label?: string;
  success_message?: string;
  fields: DynamicFormField[];
}
