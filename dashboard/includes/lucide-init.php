<?php
/**
 * Lucide Icons Initialization Script - for bottom of page
 * 
 * Usage: 
 * include __DIR__ . '/includes/lucide-init.php';
 */
?>
<script>
// Initialize Lucide icons with better error handling and caching
function initializeLucideIcons() {
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
        lucide.createIcons();
    } else {
        // Retry if lucide isn't loaded yet
        setTimeout(initializeLucideIcons, 50);
    }
}

// Handle both deferred loading and immediate execution
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeLucideIcons);
} else {
    initializeLucideIcons();
}
</script>