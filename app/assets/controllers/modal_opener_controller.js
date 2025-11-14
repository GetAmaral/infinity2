import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        url: String
    };

    async open(event) {
        event.preventDefault();

        // Close any open Bootstrap dropdowns
        this.closeAllDropdowns();

        try {
            // Fetch the modal content
            const response = await fetch(this.urlValue, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load modal');
            }

            const html = await response.text();

            // Insert into global modal container
            const container = document.getElementById('global-modal-container');
            if (container) {
                container.innerHTML = html;
            } else {
                console.error('❌ global-modal-container not found!');
            }
        } catch (error) {
            console.error('❌ Error opening modal:', error);
            alert('Failed to open form. Please try again.');
        }
    }

    /**
     * Close all open Bootstrap dropdowns
     */
    closeAllDropdowns() {
        // Find all open dropdowns
        const openDropdowns = document.querySelectorAll('.dropdown-menu.show');

        openDropdowns.forEach(dropdown => {
            const parentDropdown = dropdown.closest('.dropdown');
            if (parentDropdown) {
                const toggle = parentDropdown.querySelector('[data-bs-toggle="dropdown"]');
                if (toggle && typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                    const bsDropdown = bootstrap.Dropdown.getInstance(toggle);
                    if (bsDropdown) {
                        bsDropdown.hide();
                    }
                } else {
                    // Fallback: manually remove show class
                    dropdown.classList.remove('show');
                    if (toggle) {
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                }
            }
        });
    }
}