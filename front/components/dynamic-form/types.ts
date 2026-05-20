export interface DynamicFormOption {
  value: string;
  label: string;
}

export interface DynamicFormVisibilityCondition {
  field: string;
  operator?: "equals" | "not_equals" | "in" | "not_in" | "filled" | "empty";
  value?: string | number | boolean | Array<string | number | boolean>;
}

export interface DynamicFormField {
  key: string;
  type: string;
  label?: string;
  help_text?: string;
  required?: boolean;
  visible?: boolean;
  visible_if?: DynamicFormVisibilityCondition | DynamicFormVisibilityCondition[];
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
