import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        url: String
    };

    async open(event) {
        event.preventDefault();

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
            }
        } catch (error) {
            console.error('Error opening modal:', error);
            alert('Failed to open form. Please try again.');
        }
    }
}