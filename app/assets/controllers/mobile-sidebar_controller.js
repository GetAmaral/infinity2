// assets/controllers/mobile-sidebar_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['section'];

    toggleSection(event) {
        const section = event.currentTarget.closest('.sidebar-section');
        if (!section) return;

        const wasOpen = section.dataset.open === 'true';

        // Close all sections (accordion behavior)
        this.sectionTargets.forEach(s => {
            const content = s.querySelector('.section-content');
            const toggle = s.querySelector('.section-toggle i');
            if (content) content.style.maxHeight = '0';
            if (content) content.style.opacity = '0';
            if (toggle) toggle.style.transform = 'rotate(0deg)';
            s.dataset.open = 'false';
        });

        // Toggle current section
        if (!wasOpen) {
            const content = section.querySelector('.section-content');
            const toggle = section.querySelector('.section-toggle i');
            if (content) content.style.maxHeight = '1000px';
            if (content) content.style.opacity = '1';
            if (toggle) toggle.style.transform = 'rotate(90deg)';
            section.dataset.open = 'true';
        }
    }

    // Update active item styling when offcanvas is shown
    updateActiveItem() {
        const currentPath = window.location.pathname;
        let activeSection = null;

        // Remove all active states
        this.element.querySelectorAll('.sidebar-item').forEach(item => {
            item.classList.remove('active');
            item.style.background = '';
            item.style.borderLeft = '';
            item.style.fontWeight = '';
            item.style.color = '';

            const icon = item.querySelector('.sidebar-icon');
            if (icon) icon.style.color = 'var(--luminai-text)';
        });

        // Find and activate current page
        this.element.querySelectorAll('.sidebar-item').forEach(item => {
            const itemHref = item.getAttribute('href');
            if (itemHref === currentPath) {
                item.classList.add('active');
                item.style.background = 'rgba(99, 102, 241, 0.15)';
                item.style.borderLeft = '3px solid var(--luminai-accent)';
                item.style.fontWeight = '600';
                item.style.color = 'var(--luminai-accent)';

                const icon = item.querySelector('.sidebar-icon');
                if (icon) icon.style.color = 'var(--luminai-accent)';

                activeSection = item.closest('.sidebar-section');
            }
        });

        // Open the active section
        if (activeSection) {
            this.openSection(activeSection);
        }
    }

    openSection(section) {
        const content = section.querySelector('.section-content');
        const toggle = section.querySelector('.section-toggle i');

        if (content) {
            content.style.maxHeight = '1000px';
            content.style.opacity = '1';
        }
        if (toggle) {
            toggle.style.transform = 'rotate(90deg)';
        }
        section.dataset.open = 'true';
    }
}
