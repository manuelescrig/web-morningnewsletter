<?php
/**
 * Lucide Icons Initialization Script - for bottom of page
 * 
 * Usage: 
 * include __DIR__ . '/includes/lucide-init.php';
 */
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>