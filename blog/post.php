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
            color: #374151;
            line-height: 1.75;
        }
        
        /* Headers */
        .prose h1 { 
            font-size: 2.25rem;
            font-weight: 800;
            color: #111827;
            margin-top: 2rem;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .prose h2 { 
            font-size: 1.875rem;
            font-weight: 700;
            color: #111827;
            margin-top: 3rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .prose h3 { 
            font-size: 1.5rem;
            font-weight: 600;
            color: #111827;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        
        .prose h4 { 
            font-size: 1.25rem;
            font-weight: 600;
            color: #374151;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }
        
        /* Paragraphs */
        .prose p { 
            font-size: 1.125rem;
            color: #374151;
            margin-bottom: 1.5rem;
            line-height: 1.75;
        }
        
        /* Links */
        .prose a { 
            color: var(--tufts-blue);
            text-decoration: underline;
            transition: color 0.2s;
        }
        
        .prose a:hover {
            color: var(--cobalt-blue);
        }
        
        /* Text styles */
        .prose strong { 
            font-weight: 600;
            color: #111827;
        }
        
        .prose em { 
            font-style: italic;
            color: #374151;
        }
        
        /* Code */
        .prose code { 
            background-color: #f3f4f6;
            color: #111827;
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        
        .prose pre { 
            background-color: #1f2937;
            color: #e5e7eb;
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        
        .prose pre code { 
            background-color: transparent;
            padding: 0;
            color: inherit;
        }
        
        /* Lists */
        .prose ul { 
            list-style-type: disc;
            margin-left: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .prose ol { 
            list-style-type: decimal;
            margin-left: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .prose ul li, 
        .prose ol li { 
            margin-bottom: 0.5rem;
            padding-left: 0.375rem;
            font-size: 1.125rem;
            color: #374151;
            line-height: 1.75;
        }
        
        .prose ul ul, 
        .prose ol ol, 
        .prose ul ol, 
        .prose ol ul { 
            margin-top: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        /* Blockquotes */
        .prose blockquote { 
            border-left: 4px solid var(--jordy-blue);
            padding-left: 1rem;
            font-style: italic;
            color: #4b5563;
            margin-bottom: 1.5rem;
        }
        
        /* Make emoji headers stand out */
        .prose h3 .emoji,
        .prose h4 .emoji { 
            margin-right: 0.5rem;
        }
        
        /* Image placeholder styling */
        .image-placeholder { 
            background-color: #f3f4f6;
            border-radius: 0.5rem;
            padding: 3rem;
            margin: 2rem 0;
            text-align: center;
            border: 1px solid #e5e7eb;
        }
        
        .image-placeholder i { 
            font-size: 3rem;
            color: #9ca3af;
            margin-bottom: 0.75rem;
            display: block;
        }
        
        .image-placeholder p { 
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 0;
            font-style: italic;
        }
        
        /* First paragraph after header */
        .prose h1 + p,
        .prose h2 + p,
        .prose h3 + p,
        .prose h4 + p {
            margin-top: 0.5rem;
        }
        
        /* Spacing for sections */
        .prose > *:first-child {
            margin-top: 0;
        }
        
        .prose > *:last-child {
            margin-bottom: 0;
        }
        
        /* Table of Contents Styles */
        #table-of-contents {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.5rem;
            max-width: 280px;
            max-height: calc(100vh - 16rem); /* Ensures margin from top and bottom */
            overflow: hidden;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        #table-of-contents.visible {
            opacity: 1;
        }
        
        .toc-nav {
            max-height: calc(100vh - 22rem);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #d1d5db transparent;
        }
        
        .toc-nav::-webkit-scrollbar {
            width: 4px;
        }
        
        .toc-nav::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .toc-nav::-webkit-scrollbar-thumb {
            background-color: #d1d5db;
            border-radius: 2px;
        }
        
        .toc-nav::-webkit-scrollbar-thumb:hover {
            background-color: #9ca3af;
        }
        
        .toc-link {
            display: block;
            padding: 0.125rem 0.5rem;
            margin: 0;
            color: #6b7280;
            text-decoration: none;
            font-size: 0.875rem;
            line-height: 1.25rem;
            transition: all 0.2s ease;
            position: relative;
            border-radius: 0.25rem;
        }
        
        .toc-link:hover {
            color: var(--tufts-blue);
            background-color: rgba(70, 139, 230, 0.05);
            text-decoration: none;
        }
        
        .toc-link.toc-h2 {
            font-weight: 500;
            color: #374151;
        }
        
        .toc-link.toc-h3 {
            padding-left: 1.5rem;
            font-size: 0.8125rem;
        }
        
        .toc-link.toc-h4 {
            padding-left: 2.5rem;
            font-size: 0.75rem;
            color: #9ca3af;
        }
        
        .toc-link.active {
            color: var(--tufts-blue);
            font-weight: 600;
            background-color: rgba(70, 139, 230, 0.1);
        }
        
        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }
        
        /* Scroll offset for fixed header */
        .prose h2[id],
        .prose h3[id],
        .prose h4[id] {
            scroll-margin-top: 100px;
        }
        
        
        /* Mobile TOC button */
        .mobile-toc-button {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 3rem;
            height: 3rem;
            background-color: var(--tufts-blue);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            z-index: 40;
            transition: all 0.2s ease;
        }
        
        .mobile-toc-button:hover {
            transform: scale(1.1);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        @media (min-width: 1024px) {
            .mobile-toc-button {
                display: none;
            }
        }
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


    <!-- Article with Table of Contents -->
    <article class="bg-white">
        <div class="relative">
            <!-- Table of Contents Sidebar -->
            <aside class="hidden xl:block absolute left-0 top-0 w-80 h-full py-12">
                <div class="sticky top-32 ml-auto mr-2" id="table-of-contents">
                    <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4">Table of Contents</h3>
                    <nav class="toc-nav">
                        <!-- Will be populated by JavaScript -->
                    </nav>
                </div>
            </aside>
            
            <!-- Main Article Content (centered) -->
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
            <div class="prose max-w-none" id="article-content">
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
    <div class="relative overflow-hidden py-16">
        <div class="absolute inset-0 bg-gradient-to-br from-primary-lightest via-primary-light to-primary opacity-90"></div>
        <div class="absolute inset-0 mesh-bg opacity-30"></div>
        <div class="relative mx-auto max-w-4xl px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-primary-darker mb-4">
                Stay Updated with MorningNewsletter
            </h2>
            <p class="text-xl text-primary-dark mb-8 max-w-2xl mx-auto">
                Get personalized insights delivered to your inbox every morning. Join thousands of professionals who start their day informed.
            </p>
            <a href="/register" 
               class="btn-pill inline-flex items-center bg-white text-primary-dark font-semibold px-8 py-4 hover:bg-gray-50 transition-all duration-200 hover:scale-105 shadow-lg border border-white/50">
                Start Your Free Trial
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>

    <!-- Mobile TOC Button -->
    <button class="mobile-toc-button lg:hidden" onclick="toggleMobileTOC()" aria-label="Table of Contents">
        <i class="fas fa-list-ul"></i>
    </button>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Generate Table of Contents
        const articleContent = document.getElementById('article-content');
        const tocNav = document.querySelector('.toc-nav');
        const headings = articleContent.querySelectorAll('h2, h3, h4');
        
        if (headings.length === 0) return;
        
        
        // Generate unique IDs and TOC links
        headings.forEach((heading, index) => {
            // Create ID from heading text
            const text = heading.textContent.trim();
            const id = text.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '') + '-' + index;
            heading.id = id;
            
            // Create TOC link
            const link = document.createElement('a');
            link.href = '#' + id;
            link.className = 'toc-link toc-' + heading.tagName.toLowerCase();
            link.textContent = text;
            
            // Add click handler with smooth scroll
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.getElementById(id);
                if (target) {
                    const offset = 100; // Account for fixed header
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - offset;
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
            
            tocNav.appendChild(link);
        });
        
        // Active section tracking
        const tocLinks = tocNav.querySelectorAll('.toc-link');
        let currentActiveIndex = -1;
        const tocContainer = document.getElementById('table-of-contents');
        
        // Get the position of the first heading
        const firstHeading = headings[0];
        const firstHeadingOffset = firstHeading ? firstHeading.offsetTop : 0;
        
        function updateActiveSection() {
            const scrollPosition = window.scrollY + 150; // Offset for detection
            let newActiveIndex = -1;
            
            // Show/hide TOC based on scroll position
            if (window.scrollY > firstHeadingOffset - 200) {
                tocContainer.classList.add('visible');
            } else {
                tocContainer.classList.remove('visible');
            }
            
            // Find the current section
            for (let i = headings.length - 1; i >= 0; i--) {
                if (headings[i].offsetTop <= scrollPosition) {
                    newActiveIndex = i;
                    break;
                }
            }
            
            // Update active state only if changed
            if (newActiveIndex !== currentActiveIndex) {
                tocLinks.forEach(link => link.classList.remove('active'));
                
                if (newActiveIndex >= 0 && tocLinks[newActiveIndex]) {
                    tocLinks[newActiveIndex].classList.add('active');
                    
                    // Ensure active link is visible in scrollable TOC
                    const activeLink = tocLinks[newActiveIndex];
                    const linkRect = activeLink.getBoundingClientRect();
                    const tocNavRect = tocNav.getBoundingClientRect();
                    if (linkRect.top < tocNavRect.top || linkRect.bottom > tocNavRect.bottom) {
                        activeLink.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
                
                currentActiveIndex = newActiveIndex;
            }
        }
        
        // Throttle scroll events for performance
        let scrollTimeout;
        function handleScroll() {
            if (scrollTimeout) {
                window.cancelAnimationFrame(scrollTimeout);
            }
            scrollTimeout = window.requestAnimationFrame(updateActiveSection);
        }
        
        // Listen for scroll events
        window.addEventListener('scroll', handleScroll);
        window.addEventListener('resize', updateActiveSection);
        
        // Initial update
        updateActiveSection();
        
        // Mobile TOC functionality
        window.toggleMobileTOC = function() {
            // Create mobile TOC modal
            const existingModal = document.getElementById('mobile-toc-modal');
            if (existingModal) {
                existingModal.remove();
                return;
            }
            
            const modal = document.createElement('div');
            modal.id = 'mobile-toc-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 lg:hidden';
            modal.innerHTML = `
                <div class="bg-white rounded-t-2xl absolute bottom-0 left-0 right-0 max-h-[80vh] overflow-hidden animate-slide-up">
                    <div class="flex justify-between items-center p-4 border-b">
                        <h3 class="text-lg font-semibold">Table of Contents</h3>
                        <button onclick="toggleMobileTOC()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div class="p-4 overflow-y-auto max-h-[calc(80vh-4rem)]">
                        ${tocNav.outerHTML}
                    </div>
                </div>
            `;
            
            // Add click outside to close
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    toggleMobileTOC();
                }
            });
            
            document.body.appendChild(modal);
        };
    });
    </script>

    <style>
    @keyframes slide-up {
        from {
            transform: translateY(100%);
        }
        to {
            transform: translateY(0);
        }
    }
    
    .animate-slide-up {
        animation: slide-up 0.3s ease-out;
    }
    
    #mobile-toc-modal .toc-nav {
        max-height: none;
    }
    
    </style>

<?php include __DIR__ . '/../includes/page-footer.php'; ?>