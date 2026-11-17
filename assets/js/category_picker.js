document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-category-picker]').forEach((picker) => {
    const trigger = picker.querySelector('[data-category-trigger]');
    const hiddenInput = picker.querySelector('input[type="hidden"]');
    const label = picker.querySelector('[data-category-label]');
    const list = picker.querySelector('[data-category-options]');
    const options = [...picker.querySelectorAll('[role="option"]')];
    if (!trigger || !hiddenInput || !label || !list || !options.length) return;

    const close = (returnFocus = false) => {
      list.hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
      if (returnFocus) trigger.focus();
    };
    const open = (focusSelected = false) => {
      list.hidden = false;
      trigger.setAttribute('aria-expanded', 'true');
      if (focusSelected) {
        const selected = options.find((option) => option.getAttribute('aria-selected') === 'true') || options[0];
        selected.focus();
      }
    };

    trigger.addEventListener('click', () => list.hidden ? open() : close());
    trigger.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        open(true);
      }
    });

    options.forEach((option, index) => {
      option.addEventListener('click', () => {
        hiddenInput.value = option.dataset.value;
        label.textContent = option.textContent.trim();
        options.forEach((item) => item.setAttribute('aria-selected', String(item === option)));
        close(true);
      });
      option.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
          event.preventDefault();
          const next = (index + (event.key === 'ArrowDown' ? 1 : options.length - 1)) % options.length;
          options[next].focus();
        } else if (event.key === 'Escape') {
          event.preventDefault();
          close(true);
        }
      });
    });

    document.addEventListener('click', (event) => {
      if (!picker.contains(event.target)) close();
    });
  });
});