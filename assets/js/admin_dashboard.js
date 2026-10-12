
document.addEventListener('DOMContentLoaded', () => {
    // Navigasi dashboard
    document.querySelectorAll('.nav-link').forEach(button => {
        button.addEventListener('click', () => {
            const pageId = button.dataset.page;

            document.querySelectorAll('.page-section').forEach(section => {
                section.classList.toggle('active', section.id === pageId);
            });

            document.querySelectorAll('.nav-link').forEach(nav => {
                nav.classList.toggle('active', nav === button);
            });

            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    // Tab pembayaran
    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', () => {
            const tabId = button.dataset.tab;

            document.querySelectorAll('.payment-tab').forEach(tab => {
                tab.hidden = tab.id !== tabId;
            });

            document.querySelectorAll('.tab-button').forEach(tabButton => {
                tabButton.classList.toggle('active', tabButton === button);
            });
        });
    });

    // Menu profil dan logout
    const profileButton = document.getElementById('profileButton');
    const profileDropdown = document.getElementById('profileDropdown');

    if (profileButton && profileDropdown) {
        profileButton.addEventListener('click', event => {
            event.stopPropagation();

            const isOpening = profileDropdown.hidden;
            profileDropdown.hidden = !isOpening;

            profileButton.setAttribute('aria-expanded', String(isOpening));
        });

        document.addEventListener('click', event => {
            if (!event.target.closest('.profile-menu')) {
                profileDropdown.hidden = true;
                profileButton.setAttribute('aria-expanded', 'false');
            }
        });
    }
});