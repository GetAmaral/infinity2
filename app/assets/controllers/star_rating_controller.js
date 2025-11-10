import { Controller } from '@hotwired/stimulus';

/**
 * Star Rating Controller
 *
 * Manages star rating UI for importance field
 * Syncs visual stars with hidden radio inputs
 */
export default class extends Controller {
    static targets = ['input', 'star'];

    connect() {
        console.log('[star-rating] Controller connected');
        this.updateStars();
    }

    clickStar(event) {
        const starValue = parseInt(event.currentTarget.dataset.star);
        console.log('[star-rating] Star clicked:', starValue);

        // Find and check the corresponding radio input
        this.inputTargets.forEach(input => {
            const inputValue = parseInt(input.dataset.starValue);
            if (inputValue === starValue) {
                input.checked = true;
                console.log('[star-rating] Radio input checked:', inputValue);
            }
        });

        this.updateStars();
    }

    hoverStar(event) {
        const starValue = parseInt(event.currentTarget.dataset.star);
        this.highlightStars(starValue);
    }

    leaveStar() {
        this.updateStars();
    }

    updateStars() {
        // Find which radio is checked
        let checkedValue = 0;
        this.inputTargets.forEach(input => {
            if (input.checked) {
                checkedValue = parseInt(input.dataset.starValue);
            }
        });

        console.log('[star-rating] Updating stars, checked value:', checkedValue);
        this.highlightStars(checkedValue);
    }

    highlightStars(upTo) {
        this.starTargets.forEach(star => {
            const starValue = parseInt(star.dataset.star);
            if (starValue <= upTo) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }
}
