type MenuToggleProps = {
  disabled: boolean;
  isMenuOpen: boolean;
  onToggle: () => void;
};

export function MenuToggle({ disabled, isMenuOpen, onToggle }: MenuToggleProps) {
  return (
    <button
      aria-controls="primary-navigation"
      aria-expanded={isMenuOpen}
      aria-label={isMenuOpen ? "Fermer le menu" : "Ouvrir le menu"}
      className={`menu-toggle${isMenuOpen ? " is-open" : ""}`}
      disabled={disabled}
      onClick={onToggle}
      type="button"
    >
      <span className="menu-toggle__line" />
      <span className="menu-toggle__line" />
      <span className="menu-toggle__line" />
    </button>
  );
}
