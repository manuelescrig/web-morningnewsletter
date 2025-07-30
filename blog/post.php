<?php
require_once __DIR__ . '/../core/BlogPost.php';
require_once __DIR__ . '/../core/Auth.php';

$auth = Auth::getInstance();
$isLoggedIn = $auth->isLoggedIn();
$user = $isLoggedIn ? $auth->getCurrentUser() : null;

// Get the slug from URL parameter
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('HTTP/1.0 404 Not Found');
    header('Location: /blog');
    exit;
}

// Try to load the blog post
try {
    $post = BlogPost::findBySlug($slug);
    
    if (!$post) {
        header('HTTP/1.0 404 Not Found');
        include __DIR__ . '/../404.php';
        exit;
    }
    
    // Get related posts (same tags)
    $relatedPosts = [];
    if (!empty($post->getTags())) {
        $firstTag = $post->getTags()[0];
        $allByTag = BlogPost::getByTag($firstTag, 4);
        // Remove current post from related
        $relatedPosts = array_filter($allByTag, function($p) use ($slug) {
            return $p->getSlug() !== $slug;
        });
        $relatedPosts = array_slice($relatedPosts, 0, 3);
    }
    
} catch (Exception $e) {
    error_log("Blog post error: " . $e->getMessage());
    header('HTTP/1.0 404 Not Found');
    include __DIR__ . '/../404.php';
    exit;
}

// Page configuration
$pageTitle = $post->getSeoTitle() ?: $post->getTitle();
$pageDescription = $post->getSeoDescription() ?: $post->getExcerpt() ?: substr(strip_tags($post->getContent()), 0, 155);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - MorningNewsletter</title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/landing.css">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $post->getUrl(); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($post->getTitle()); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($post->getExcerpt() ?: substr(strip_tags($post->getContent()), 0, 155)); ?>">
    <?php if ($post->getFeaturedImage()): ?>
        <meta property="og:image" content="<?php echo htmlspecialchars($post->getFeaturedImage()); ?>">
    <?php endif; ?>
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $post->getUrl(); ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($post->getTitle()); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($post->getExcerpt() ?: substr(strip_tags($post->getContent()), 0, 155)); ?>">
    <?php if ($post->getFeaturedImage()): ?>
        <meta property="twitter:image" content="<?php echo htmlspecialchars($post->getFeaturedImage()); ?>">
    <?php endif; ?>
    
    <style>
        /* Custom prose styles for blog content */
        .prose {
            max-width: none;
        }
        .prose h1 { @apply text-3xl font-bold text-gray-900 mt-8 mb-4; }
        .prose h2 { @apply text-2xl font-bold text-gray-900 mt-6 mb-3; }
        .prose h3 { @apply text-xl font-bold text-gray-900 mt-5 mb-2; }
        .prose p { @apply text-gray-700 leading-relaxed mb-4; }
        .prose a { @apply text-primary hover:text-primary-darker underline; }
        .prose strong { @apply font-semibold text-gray-900; }
        .prose em { @apply italic; }
        .prose code { @apply bg-gray-100 text-gray-900 px-2 py-1 rounded text-sm font-mono; }
        .prose pre { @apply bg-gray-100 text-gray-900 p-4 rounded-lg overflow-x-auto mb-4; }
        .prose pre code { @apply bg-transparent p-0; }
        .prose ul { @apply list-disc list-inside mb-4 space-y-1; }
        .prose ol { @apply list-decimal list-inside mb-4 space-y-1; }
        .prose blockquote { @apply border-l-4 border-primary-light pl-4 italic text-gray-600 mb-4; }
    </style>
</head>
<body class="bg-white">
    <?php include __DIR__ . '/../includes/navigation.php'; ?>

    <?php 
    // Hero section for blog post
    $heroTitle = $post->getTitle();
    $heroSubtitle = null; // We'll show meta info separately
    include __DIR__ . '/../includes/hero-section.php';
    ?>

    <!-- Post Meta Info -->
    <div class="text-center -mt-8 mb-8">
        <div class="mx-auto max-w-4xl px-6 lg:px-8">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-4">
                    <li>
                        <a href="/" class="text-gray-400 hover:text-gray-500">
                            <i class="fas fa-home"></i>
                            <span class="sr-only">Home</span>
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <a href="/blog" class="text-gray-500 hover:text-gray-700">Blog</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($post->getTitle()); ?></span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Article -->
    <article class="bg-white">
        <div class="mx-auto max-w-4xl px-6 lg:px-8 py-12">
            <!-- Article Header -->
            <header class="mb-8">
                <div class="text-center mb-8">
                    <?php if (!empty($post->getTags())): ?>
                        <div class="flex justify-center flex-wrap gap-2 mb-4">
                            <?php foreach ($post->getTags() as $tag): ?>
                                <a href="/blog?tag=<?php echo urlencode($tag); ?>" 
                                   class="btn-pill inline-flex items-center px-3 py-1 text-sm font-medium bg-primary-lightest text-primary-darker hover:bg-primary-light transition-colors">
                                    <?php echo htmlspecialchars($tag); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 leading-tight mb-6">
                        <?php echo htmlspecialchars($post->getTitle()); ?>
                    </h1>
                    
                    <?php if ($post->getExcerpt()): ?>
                        <p class="text-xl text-gray-600 leading-relaxed mb-6 max-w-3xl mx-auto">
                            <?php echo htmlspecialchars($post->getExcerpt()); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="flex items-center justify-center text-gray-500 text-sm space-x-4">
                        <div class="flex items-center">
                            <i class="fas fa-user mr-2"></i>
                            <span><?php echo htmlspecialchars($post->getAuthor()); ?></span>
                        </div>
                        <span>•</span>
                        <div class="flex items-center">
                            <i class="fas fa-calendar mr-2"></i>
                            <time datetime="<?php echo $post->getDate(); ?>">
                                <?php echo $post->getFormattedDate(); ?>
                            </time>
                        </div>
                        <span>•</span>
                        <div class="flex items-center">
                            <i class="fas fa-clock mr-2"></i>
                            <span><?php echo $post->getReadingTime(); ?></span>
                        </div>
                    </div>
                </div>
                
                <?php if ($post->getFeaturedImage()): ?>
                    <div class="mb-8">
                        <img src="<?php echo htmlspecialchars($post->getFeaturedImage()); ?>" 
                             alt="<?php echo htmlspecialchars($post->getTitle()); ?>"
                             class="w-full h-64 sm:h-96 object-cover rounded-2xl shadow-lg">
                    </div>
                <?php endif; ?>
            </header>

            <!-- Article Content -->
            <div class="prose max-w-none">
                <?php echo $post->getHtmlContent(); ?>
            </div>

            <!-- Article Footer -->
            <footer class="mt-12 pt-8 border-t border-gray-200">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-500 text-sm">Share this post:</span>
                        <div class="flex space-x-2">
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $post->getUrl()); ?>&text=<?php echo urlencode($post->getTitle()); ?>" 
                               target="_blank" 
                               class="text-gray-400 hover:text-primary transition-colors">
                                <i class="fab fa-twitter text-lg"></i>
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $post->getUrl()); ?>" 
                               target="_blank" 
                               class="text-gray-400 hover:text-primary transition-colors">
                                <i class="fab fa-facebook text-lg"></i>
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode('https://' . $_SERVER['HTTP_HOST'] . $post->getUrl()); ?>" 
                               target="_blank" 
                               class="text-gray-400 hover:text-primary-dark transition-colors">
                                <i class="fab fa-linkedin text-lg"></i>
                            </a>
                        </div>
                    </div>
                    
                    <a href="/blog" class="text-primary hover:text-primary font-medium text-sm">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Blog
                    </a>
                </div>
            </footer>
        </div>
    </article>

    <!-- Related Posts -->
    <?php if (!empty($relatedPosts)): ?>
        <div class="bg-gray-50 py-16">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900">Related Posts</h2>
                    <p class="mt-4 text-lg text-gray-600">Continue reading about similar topics</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <?php foreach ($relatedPosts as $relatedPost): ?>
                        <article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden">
                            <?php if ($relatedPost->getFeaturedImage()): ?>
                                <div class="aspect-w-16 aspect-h-9">
                                    <img src="<?php echo htmlspecialchars($relatedPost->getFeaturedImage()); ?>" 
                                         alt="<?php echo htmlspecialchars($relatedPost->getTitle()); ?>"
                                         class="w-full h-48 object-cover">
                                </div>
                            <?php endif; ?>
                            
                            <div class="p-6">
                                <div class="text-sm text-gray-500 mb-2">
                                    <time datetime="<?php echo $relatedPost->getDate(); ?>">
                                        <?php echo $relatedPost->getFormattedDate(); ?>
                                    </time>
                                    <span class="mx-2">•</span>
                                    <span><?php echo $relatedPost->getReadingTime(); ?></span>
                                </div>
                                
                                <h3 class="text-lg font-bold text-gray-900 mb-3">
                                    <a href="<?php echo $relatedPost->getUrl(); ?>" class="hover:text-primary transition-colors">
                                        <?php echo htmlspecialchars($relatedPost->getTitle()); ?>
                                    </a>
                                </h3>
                                
                                <?php if ($relatedPost->getExcerpt()): ?>
                                    <p class="text-gray-600 mb-4 text-sm leading-relaxed">
                                        <?php echo htmlspecialchars(substr($relatedPost->getExcerpt(), 0, 100)) . '...'; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <a href="<?php echo $relatedPost->getUrl(); ?>" 
                                   class="inline-flex items-center text-primary hover:text-primary font-medium text-sm">
                                    Read more
                                    <i class="fas fa-arrow-right ml-2 text-xs"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Newsletter CTA -->
    <div class="bg-gradient-to-br from-purple-600 to-purple-600 py-16">
        <div class="mx-auto max-w-4xl px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">
                Stay Updated with MorningNewsletter
            </h2>
            <p class="text-xl text-purple-100 mb-8 max-w-2xl mx-auto">
                Get personalized insights delivered to your inbox every morning. Join thousands of professionals who start their day informed.
            </p>
            <a href="/register" 
               class="btn-pill inline-flex items-center bg-white text-primary font-semibold px-8 py-4 hover:bg-gray-50 transition-all duration-200 hover:scale-105 shadow-lg">
                Start Your Free Trial
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>

<?php include __DIR__ . '/../includes/page-footer.php'; ?>