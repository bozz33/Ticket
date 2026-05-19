type MenuToggleProps = {
  isMenuOpen: boolean;
  onToggle: () => void;
};

export function MenuToggle({ isMenuOpen, onToggle }: MenuToggleProps) {
  return (
    <button
      aria-expanded={isMenuOpen}
      aria-label={isMenuOpen ? "Fermer le menu" : "Ouvrir le menu"}
      className={`menu-toggle${isMenuOpen ? " is-open" : ""}`}
      onClick={onToggle}
      type="button"
    >
      <span className="menu-toggle__line" />
      <span className="menu-toggle__line" />
      <span className="menu-toggle__line" />
    </button>
  );
}
