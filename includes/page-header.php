<?php
/**
 * Shared page header include
 * 
 * Usage: 
 * $pageTitle = "Your Page Title";
 * $pageDescription = "Your page description"; // Optional
 * include __DIR__ . '/includes/page-header.php';
 */

$fullTitle = isset($pageTitle) ? $pageTitle . ' - MorningNewsletter' : 'MorningNewsletter';
$description = isset($pageDescription) ? $pageDescription : 'Your personalized morning brief with everything that matters to you.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($description); ?>">
    <title><?php echo htmlspecialchars($fullTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/landing.css">
</head>