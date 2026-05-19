type OrganizerRegistrationFieldsProps = {
  email: string;
  orgName: string;
  password: string;
  setEmail: (value: string) => void;
  setOrgName: (value: string) => void;
  setPassword: (value: string) => void;
};

const fieldStyle = {
  background: "var(--surface)",
  border: "1px solid var(--border)",
  borderRadius: "8px",
  boxSizing: "border-box",
  color: "var(--text)",
  fontSize: "0.95rem",
  padding: "10px 14px",
  width: "100%",
} as const;

const labelStyle = {
  display: "block",
  fontSize: "0.9rem",
  fontWeight: 600,
  marginBottom: "6px",
} as const;

export function OrganizerRegistrationFields({
  email,
  orgName,
  password,
  setEmail,
  setOrgName,
  setPassword,
}: OrganizerRegistrationFieldsProps) {
  return (
    <>
      <div>
        <label htmlFor="organizer-org-name" style={labelStyle}>
          Nom de votre organisation
        </label>
        <input
          id="organizer-org-name"
          onChange={(event) => setOrgName(event.target.value)}
          placeholder="Association des arts de Dakar"
          required
          style={fieldStyle}
          type="text"
          value={orgName}
        />
      </div>

      <div>
        <label htmlFor="organizer-email" style={labelStyle}>
          Adresse e-mail administrateur
        </label>
        <input
          id="organizer-email"
          onChange={(event) => setEmail(event.target.value)}
          placeholder="contact@organisation.com"
          required
          style={fieldStyle}
          type="email"
          value={email}
        />
      </div>

      <div>
        <label htmlFor="organizer-password" style={labelStyle}>
          Mot de passe
        </label>
        <input
          id="organizer-password"
          minLength={8}
          onChange={(event) => setPassword(event.target.value)}
          placeholder="8 caractères minimum"
          required
          style={fieldStyle}
          type="password"
          value={password}
        />
      </div>
    </>
  );
}
