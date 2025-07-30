<?php
/**
 * Reusable hero section component
 * 
 * Usage:
 * $heroTitle = "Your Page Title";
 * $heroSubtitle = "Your subtitle text"; // Optional
 * include __DIR__ . '/includes/hero-section.php';
 */

$title = isset($heroTitle) ? $heroTitle : '';
$subtitle = isset($heroSubtitle) ? $heroSubtitle : '';
?>

<!-- Hero Section -->
<div class="relative mesh-bg pt-24 sm:pt-32 pb-16">
    <div class="mx-auto max-w-7xl px-6 lg:px-8 relative z-10">
        <div class="mx-auto max-w-4xl text-center">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
                <?php echo htmlspecialchars($title); ?>
            </h1>
            <?php if ($subtitle): ?>
            <p class="mt-4 text-lg text-gray-600">
                <?php echo htmlspecialchars($subtitle); ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>