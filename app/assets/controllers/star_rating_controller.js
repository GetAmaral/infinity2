import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['star', 'input'];

    connect() {
        // Set initial state based on checked radio
        this.inputTargets.forEach((radio) => {
            if (radio.checked) {
                this.updateStars(parseInt(radio.getAttribute('data-star-value')));
            }
        });
    }

    clickStar(event) {
        event.preventDefault();
        const starValue = parseInt(event.currentTarget.getAttribute('data-star'));
        this.updateStars(starValue);

        // Update radio buttons
        this.inputTargets.forEach(radio => {
            radio.checked = (parseInt(radio.getAttribute('data-star-value')) === starValue);
        });
    }

    hoverStar(event) {
        const starValue = parseInt(event.currentTarget.getAttribute('data-star'));
        this.starTargets.forEach((star, idx) => {
            if (idx < starValue) {
                star.style.color = '#f59e0b';
            } else {
                star.style.color = ''; // Reset to default
            }
        });
    }

    leaveStar() {
        // Reset all inline styles
        this.starTargets.forEach(star => {
            star.style.color = '';
        });

        // Restore active state
        this.inputTargets.forEach((radio) => {
            if (radio.checked) {
                this.updateStars(parseInt(radio.getAttribute('data-star-value')));
            }
        });
    }

    updateStars(value) {
        this.starTargets.forEach((star, idx) => {
            // Reset inline style first
            star.style.color = '';

            // Then apply class
            if (idx + 1 <= value) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }
}
