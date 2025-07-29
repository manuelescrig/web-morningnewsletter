// Landing Page JavaScript

// 3D Tilt Effect on Hero Section
document.addEventListener('DOMContentLoaded', function() {
    const heroSection = document.querySelector('.hero-section');
    const macWindow = document.querySelector('.mac-window');
    
    if (heroSection && macWindow) {
        // Track mouse position for 3D tilt
        heroSection.addEventListener('mousemove', (e) => {
            const rect = heroSection.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = ((y - centerY) / centerY) * -10; // -10 to 10 degrees
            const rotateY = ((x - centerX) / centerX) * 10; // -10 to 10 degrees
            
            macWindow.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(50px)`;
        });
        
        // Reset on mouse leave
        heroSection.addEventListener('mouseleave', () => {
            macWindow.style.transform = 'rotateX(0) rotateY(0) translateZ(0)';
        });
    }
});