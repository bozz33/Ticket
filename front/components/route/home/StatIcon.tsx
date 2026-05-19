export function StatIcon({ index }: { index: number }) {
  const icon = index % 3;

  if (icon === 1) {
    return (
      <svg aria-hidden="true" viewBox="0 0 24 24">
        <path d="M8.5 11.5a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" />
        <path d="M15.5 12.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7Z" />
        <path d="M2.8 20.5c.8-3.7 2.8-5.5 5.7-5.5s4.9 1.8 5.7 5.5" />
        <path d="M13.2 20.5c.4-2.2 1.7-3.6 3.8-3.9 1.8-.2 3.2.5 4.2 2.1" />
      </svg>
    );
  }

  if (icon === 2) {
    return (
      <svg aria-hidden="true" viewBox="0 0 24 24">
        <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
        <path d="M4 20c1-3.8 3.8-6 8-6s7 2.2 8 6" />
      </svg>
    );
  }

  return (
    <svg aria-hidden="true" viewBox="0 0 24 24">
      <path d="M4.5 7.5A2.5 2.5 0 0 1 7 5h10a2.5 2.5 0 0 1 2.5 2.5v2.2a2.3 2.3 0 0 0 0 4.6v2.2A2.5 2.5 0 0 1 17 19H7a2.5 2.5 0 0 1-2.5-2.5v-2.2a2.3 2.3 0 0 0 0-4.6V7.5Z" />
      <path d="M13.5 7.5v9" />
    </svg>
  );
}
