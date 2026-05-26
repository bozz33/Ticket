import assert from 'node:assert/strict';
import test from 'node:test';

function normalizePagination(page, perPage) {
  const normalizedPage = positiveInt(page, 1);
  const normalizedPerPage = Math.min(48, positiveInt(perPage, 12));
  return { page: normalizedPage, perPage: normalizedPerPage };
}

function positiveInt(value, fallback) {
  const parsed = Number.parseInt(String(value ?? ''), 10);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
}

test('catalog pagination is bounded for public endpoints', () => {
  assert.deepEqual(normalizePagination('-1', '200'), { page: 1, perPage: 48 });
  assert.deepEqual(normalizePagination('3', '24'), { page: 3, perPage: 24 });
});
