import assert from 'node:assert/strict';
import test from 'node:test';

function normalizeScanReference(reference) {
  return reference.trim().replace(/\s+/g, '').toUpperCase();
}

test('scan references are normalized before validation', () => {
  assert.equal(normalizeScanReference(' pass-260516 abc '), 'PASS-260516ABC');
});
