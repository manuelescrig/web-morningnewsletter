// Landing Page Specific JavaScript

// Typewriter Effect
class TypewriterEffect {
    constructor() {
        this.element = document.getElementById('typewriter');
        this.words = ['Informed', 'Productive', 'Focused', 'Prepared', 'Connected'];
        this.currentIndex = 0;
        this.currentWord = '';
        this.isDeleting = false;
        this.baseSpeed = 150; // Slower typing for smoother effect
        
        if (this.element) {
            this.type();
        }
    }
    
    type() {
        const fullWord = this.words[this.currentIndex % this.words.length];
        
        if (this.isDeleting) {
            this.currentWord = fullWord.substring(0, this.currentWord.length - 1);
        } else {
            this.currentWord = fullWord.substring(0, this.currentWord.length + 1);
        }
        
        this.element.textContent = this.currentWord;
        
        // Variable speed for more natural typing
        let typeSpeed = this.isDeleting ? 80 : this.baseSpeed + Math.random() * 50;
        
        if (!this.isDeleting && this.currentWord === fullWord) {
            typeSpeed = 2500; // Longer pause at end
            this.isDeleting = true;
        } else if (this.isDeleting && this.currentWord === '') {
            this.isDeleting = false;
            this.currentIndex++;
            typeSpeed = 700; // Pause before new word
        }
        
        setTimeout(() => this.type(), typeSpeed);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize typewriter
    new TypewriterEffect();
    // Handle hero email form submission
    const heroForm = document.getElementById('hero-signup-form');
    if (heroForm) {
        heroForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const emailInput = document.getElementById('hero-email');
            const email = emailInput.value.trim();
            
            if (email) {
                // Redirect to register page with email as query parameter
                window.location.href = `/auth/register.php?email=${encodeURIComponent(email)}`;
            }
        });
    }

    // Add ripple effect to buttons
    document.querySelectorAll('.btn-primary.ripple').forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple-effect');
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });

    // Newsletter preview animation on scroll
    const newsletterPreview = document.querySelector('.newsletter-preview');
    if (newsletterPreview) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'perspective(1000px) rotateX(0deg) translateY(0)';
                }
            });
        }, { threshold: 0.1 });
        
        newsletterPreview.style.opacity = '0';
        newsletterPreview.style.transform = 'perspective(1000px) rotateX(-10deg) translateY(20px)';
        newsletterPreview.style.transition = 'all 0.8s ease-out';
        observer.observe(newsletterPreview);
    }

    // Smooth scroll for pricing button in hero
    const pricingButtons = document.querySelectorAll('a[href="#pricing"]');
    pricingButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const pricingSection = document.getElementById('pricing');
            if (pricingSection) {
                pricingSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});