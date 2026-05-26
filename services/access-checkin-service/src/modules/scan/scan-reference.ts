export function normalizeScanReference(reference: string): string {
  return reference.trim().replace(/\s+/g, '').toUpperCase();
}
