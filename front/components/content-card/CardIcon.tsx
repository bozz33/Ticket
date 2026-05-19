type CardIconProps = {
  name: "calendar" | "location" | "ticket";
};

export function CardIcon({ name }: CardIconProps) {
  if (name === "calendar") {
    return (
      <svg aria-hidden="true" viewBox="0 0 24 24">
        <path d="M7 3v4" />
        <path d="M17 3v4" />
        <path d="M4.5 9.5h15" />
        <path d="M6.5 5.5h11A2.5 2.5 0 0 1 20 8v10a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 18V8a2.5 2.5 0 0 1 2.5-2.5Z" />
      </svg>
    );
  }

  if (name === "location") {
    return (
      <svg aria-hidden="true" viewBox="0 0 24 24">
        <path d="M19 10.4c0 5.1-7 10.1-7 10.1s-7-5-7-10.1A7 7 0 0 1 19 10.4Z" />
        <path d="M12 13a2.6 2.6 0 1 0 0-5.2A2.6 2.6 0 0 0 12 13Z" />
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
