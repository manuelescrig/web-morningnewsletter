<?php
/**
 * Lucide Icons CDN Include - for <head> section
 * 
 * Usage: 
 * include __DIR__ . '/includes/lucide-head.php';
 */
?>
<!-- Prevent icon flash of unstyled content -->
<style>
[data-lucide] {
    width: 1rem;
    height: 1rem;
    display: inline-block;
    opacity: 1;
    visibility: visible;
}
</style>
<!-- Use versioned CDN for better caching -->
<script src="https://cdn.jsdelivr.net/npm/lucide@0.263.1/dist/umd/lucide.js" defer></script>