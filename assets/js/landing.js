// Landing Page Specific JavaScript

// Particle Animation System
class ParticleSystem {
    constructor() {
        this.canvas = document.createElement('canvas');
        this.ctx = this.canvas.getContext('2d');
        this.particles = [];
        this.mouseX = 0;
        this.mouseY = 0;
        
        this.init();
    }
    
    init() {
        this.canvas.style.position = 'absolute';
        this.canvas.style.top = '0';
        this.canvas.style.left = '0';
        this.canvas.style.width = '100%';
        this.canvas.style.height = '100%';
        this.canvas.style.pointerEvents = 'none';
        this.canvas.style.zIndex = '1';
        
        const heroSection = document.querySelector('.hero-section');
        if (heroSection) {
            heroSection.style.position = 'relative';
            heroSection.appendChild(this.canvas);
        }
        
        this.resize();
        window.addEventListener('resize', () => this.resize());
        window.addEventListener('mousemove', (e) => {
            this.mouseX = e.clientX;
            this.mouseY = e.clientY;
        });
        
        // Create initial particles
        for (let i = 0; i < 50; i++) {
            this.particles.push(this.createParticle());
        }
        
        this.animate();
    }
    
    resize() {
        this.canvas.width = window.innerWidth;
        this.canvas.height = this.canvas.parentElement.offsetHeight;
    }
    
    createParticle() {
        return {
            x: Math.random() * this.canvas.width,
            y: Math.random() * this.canvas.height,
            vx: (Math.random() - 0.5) * 0.5,
            vy: (Math.random() - 0.5) * 0.5,
            radius: Math.random() * 3 + 1,
            opacity: Math.random() * 0.5 + 0.2,
            color: Math.random() > 0.5 ? '#468BE6' : '#1A5799'
        };
    }
    
    updateParticle(particle) {
        particle.x += particle.vx;
        particle.y += particle.vy;
        
        // Mouse interaction
        const dx = this.mouseX - particle.x;
        const dy = this.mouseY - particle.y;
        const distance = Math.sqrt(dx * dx + dy * dy);
        
        if (distance < 100) {
            const force = (100 - distance) / 100;
            particle.vx += (dx / distance) * force * 0.02;
            particle.vy += (dy / distance) * force * 0.02;
        }
        
        // Damping
        particle.vx *= 0.99;
        particle.vy *= 0.99;
        
        // Wrap around edges
        if (particle.x < 0) particle.x = this.canvas.width;
        if (particle.x > this.canvas.width) particle.x = 0;
        if (particle.y < 0) particle.y = this.canvas.height;
        if (particle.y > this.canvas.height) particle.y = 0;
    }
    
    drawParticle(particle) {
        this.ctx.globalAlpha = particle.opacity;
        this.ctx.fillStyle = particle.color;
        this.ctx.beginPath();
        this.ctx.arc(particle.x, particle.y, particle.radius, 0, Math.PI * 2);
        this.ctx.fill();
        
        // Draw connections
        this.particles.forEach(other => {
            const dx = particle.x - other.x;
            const dy = particle.y - other.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            if (distance < 150 && distance > 0) {
                this.ctx.globalAlpha = (1 - distance / 150) * 0.2;
                this.ctx.strokeStyle = particle.color;
                this.ctx.lineWidth = 0.5;
                this.ctx.beginPath();
                this.ctx.moveTo(particle.x, particle.y);
                this.ctx.lineTo(other.x, other.y);
                this.ctx.stroke();
            }
        });
    }
    
    animate() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        this.particles.forEach(particle => {
            this.updateParticle(particle);
            this.drawParticle(particle);
        });
        
        requestAnimationFrame(() => this.animate());
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize particle system
    new ParticleSystem();
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