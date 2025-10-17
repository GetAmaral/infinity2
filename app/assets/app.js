// ============================================
// TURBO IMPORT & CONFIGURATION
// ============================================
import * as Turbo from '@hotwired/turbo';

console.log('🚀 Turbo Drive enabled');

// Turbo configuration
Turbo.setProgressBarDelay(100); // Show progress bar after 100ms

// ============================================
// REST OF EXISTING IMPORTS
// ============================================
import { startStimulusApp } from '@symfony/stimulus-bundle';
import * as bootstrap from 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.min.css';
import 'tom-select/dist/css/tom-select.bootstrap5.css';
import './styles/app.css';
import './styles/public.css';
import './delete-handler.js';
import './fewshot-handler.js';

// Make Bootstrap available globally
window.bootstrap = bootstrap;

// Start Stimulus application with automatic controller registration
const app = startStimulusApp();

// Disable verbose Stimulus debug logs in console
app.debug = false;

// Initialize Bootstrap tooltips and auto-close dropdowns
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 Dropdown auto-close handler initialized');

    // Initialize all Bootstrap tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    console.log(`💡 Initialized ${tooltipList.length} Bootstrap tooltips`);

    // When a dropdown is about to be shown, close all other card dropdowns
    document.addEventListener('show.bs.dropdown', function(event) {
        const clickedToggle = event.relatedTarget; // The button that was clicked
        const clickedDropdown = clickedToggle?.closest('.dropdown');

        console.log('📂 Dropdown opening:', {
            clickedToggle: clickedToggle,
            isCardDropdown: clickedDropdown?.classList.contains('card-dropdown')
        });

        // Only auto-close if this is a card dropdown
        if (clickedDropdown?.classList.contains('card-dropdown')) {
            // Find all card dropdowns with open toggle buttons (button.show)
            const openToggles = document.querySelectorAll('.card-dropdown [data-bs-toggle="dropdown"].show');
            console.log(`📊 Found ${openToggles.length} open card dropdown toggles to close`, openToggles);

            // Close each one
            openToggles.forEach(toggle => {
                if (toggle !== clickedToggle) {
                    const bsDropdown = bootstrap.Dropdown.getInstance(toggle);
                    if (bsDropdown) {
                        console.log('🔒 Closing dropdown via toggle:', toggle);
                        bsDropdown.hide();
                    }
                }
            });
        }
    });
});

// Reinitialize tooltips after dynamic content loads (modals, AJAX)
document.addEventListener('shown.bs.modal', function() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].forEach(tooltipTriggerEl => {
        if (!bootstrap.Tooltip.getInstance(tooltipTriggerEl)) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        }
    });
});
