document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-profile-menu]').forEach((menu) => {
    const button = menu.querySelector('[data-profile-button]');
    const dropdown = menu.querySelector('[data-profile-dropdown]');
    if (!button || !dropdown) return;

    const close = () => {
      dropdown.hidden = true;
      button.setAttribute('aria-expanded', 'false');
    };

    button.addEventListener('click', (event) => {
      event.stopPropagation();
      const opening = dropdown.hidden;
      dropdown.hidden = !opening;
      button.setAttribute('aria-expanded', String(opening));
    });

    document.addEventListener('click', (event) => {
      if (!menu.contains(event.target)) close();
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') close();
    });
  });
});