import { Controller } from '@hotwired/stimulus';

/*
 * Landing Page Controller
 * Handles scroll animations and interactive elements for public landing pages
 */
export default class extends Controller {
    static targets = ['badge', 'title', 'subtitle', 'benefit', 'product']

    connect() {
        console.log('Landing page controller connected');
        this.initScrollAnimations();
        this.initSmoothScrolling();
    }

    initScrollAnimations() {
        // Create an Intersection Observer for scroll animations
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    // Add staggered animation delay
                    setTimeout(() => {
                        entry.target.classList.add('fade-in-up');
                    }, index * 100);

                    // Only animate once
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe all benefit cards
        if (this.hasBenefitTarget) {
            this.benefitTargets.forEach(benefit => {
                observer.observe(benefit);
            });
        }

        // Observe all product cards
        if (this.hasProductTarget) {
            this.productTargets.forEach(product => {
                observer.observe(product);
            });
        }

        // Observe other animatable elements
        const animatableElements = document.querySelectorAll(
            '.solution-card, .social-proof-card, .feature-item'
        );
        animatableElements.forEach((el, index) => {
            observer.observe(el);
        });
    }

    initSmoothScrolling() {
        // Add smooth scrolling to anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                const href = anchor.getAttribute('href');
                if (href === '#') return;

                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }

    // Disconnect cleanup
    disconnect() {
        console.log('Landing page controller disconnected');
    }
}
