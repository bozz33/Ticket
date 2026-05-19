export const progressShellStyle = {
  background: "linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(250, 243, 231, 0.96))",
  border: "1px solid rgba(213, 154, 54, 0.2)",
  borderRadius: "24px",
  boxShadow: "0 24px 64px rgba(17, 24, 32, 0.08)",
  display: "grid",
  gap: "18px",
  marginBottom: "6px",
  overflow: "hidden",
  padding: "24px",
} as const;

export const progressHeaderStyle = {
  alignItems: "start",
  display: "flex",
  flexWrap: "wrap",
  gap: "18px",
  justifyContent: "space-between",
} as const;

export const progressScoreStyle = {
  alignItems: "center",
  alignSelf: "stretch",
  background: "rgba(13, 20, 28, 0.92)",
  borderRadius: "20px",
  color: "#fff",
  display: "grid",
  minWidth: "152px",
  padding: "16px 18px",
  placeItems: "center",
} as const;

export const stepListStyle = {
  alignItems: "stretch",
  display: "flex",
  flexWrap: "wrap",
  gap: "12px",
} as const;
