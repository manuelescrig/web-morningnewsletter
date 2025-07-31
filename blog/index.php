<?php
require_once __DIR__ . '/../core/BlogPost.php';
require_once __DIR__ . '/../core/Auth.php';

$auth = Auth::getInstance();
$isLoggedIn = $auth->isLoggedIn();
$user = $isLoggedIn ? $auth->getCurrentUser() : null;

// Get all published blog posts
try {
    $posts = BlogPost::getAll(10, true); // Limit to 10 posts, published only
    $allTags = BlogPost::getAllTags();
} catch (Exception $e) {
    $posts = [];
    $allTags = [];
    error_log("Blog error: " . $e->getMessage());
}

// Filter by tag if specified
$selectedTag = $_GET['tag'] ?? '';
if ($selectedTag) {
    $posts = BlogPost::getByTag($selectedTag, 10);
}

// Page configuration
$pageTitle = $selectedTag ? "Posts tagged '$selectedTag'" : 'Blog';
$pageDescription = $selectedTag ? "Blog posts about $selectedTag" : 'Insights, tips, and updates from the MorningNewsletter team';
include __DIR__ . '/../includes/page-header.php';
?>
<body class="bg-white">
    <?php include __DIR__ . '/../includes/navigation.php'; ?>

    <?php 
    // Hero section configuration
    $heroTitle = $selectedTag ? "Posts tagged \"" . htmlspecialchars($selectedTag) . "\"" : "MorningNewsletter Blog";
    $heroSubtitle = $selectedTag ? "Posts about " . htmlspecialchars($selectedTag) : "Insights and tips to optimize your morning routine";
    include __DIR__ . '/../includes/hero-section.php';
    
    if ($selectedTag): ?>
        <div class="text-center -mt-8 mb-8">
            <a href="/blog" class="text-primary hover:text-primary-dark font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                View all posts
            </a>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="mx-auto max-w-7xl px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Blog Posts -->
            <div class="lg:col-span-3">
                <?php if (empty($posts)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-newspaper text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">
                            <?php echo $selectedTag ? 'No posts found' : 'No blog posts yet'; ?>
                        </h3>
                        <p class="text-gray-600">
                            <?php echo $selectedTag ? "We haven't written about $selectedTag yet." : 'Stay tuned for upcoming insights and updates!'; ?>
                        </p>
                        <?php if ($selectedTag): ?>
                            <a href="/blog" class="mt-4 inline-block text-primary hover:text-primary">
                                Browse all posts
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="space-y-8">
                        <?php foreach ($posts as $post): ?>
                            <article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden">
                                <?php if ($post->getFeaturedImage()): ?>
                                    <div class="aspect-w-16 aspect-h-9">
                                        <img src="<?php echo htmlspecialchars($post->getFeaturedImage()); ?>" 
                                             alt="<?php echo htmlspecialchars($post->getTitle()); ?>"
                                             class="w-full h-48 object-cover">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="p-6">
                                    <div class="flex items-center text-sm text-gray-500 mb-3">
                                        <time datetime="<?php echo $post->getDate(); ?>">
                                            <?php echo $post->getFormattedDate(); ?>
                                        </time>
                                        <span class="mx-2">•</span>
                                        <span><?php echo $post->getReadingTime(); ?></span>
                                        <span class="mx-2">•</span>
                                        <span><?php echo htmlspecialchars($post->getAuthor()); ?></span>
                                    </div>
                                    
                                    <h2 class="text-xl font-bold text-gray-900 mb-3">
                                        <a href="<?php echo $post->getUrl(); ?>" class="hover:text-primary transition-colors">
                                            <?php echo htmlspecialchars($post->getTitle()); ?>
                                        </a>
                                    </h2>
                                    
                                    <?php if ($post->getExcerpt()): ?>
                                        <p class="text-gray-600 mb-4 leading-relaxed">
                                            <?php echo htmlspecialchars($post->getExcerpt()); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($post->getTags())): ?>
                                        <div class="flex flex-wrap gap-2 mb-4">
                                            <?php foreach ($post->getTags() as $tag): ?>
                                                <a href="/blog?tag=<?php echo urlencode($tag); ?>" 
                                                   class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-lightest text-primary-darker hover:bg-primary-light transition-colors">
                                                    <?php echo htmlspecialchars($tag); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo $post->getUrl(); ?>" 
                                       class="inline-flex items-center text-primary hover:text-primary font-medium">
                                        Read more
                                        <i class="fas fa-arrow-right ml-2 text-sm"></i>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="space-y-8">
                    <!-- Tags -->
                    <?php if (!empty($allTags)): ?>
                        <div class="bg-white rounded-2xl p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-tags mr-2 text-primary"></i>
                                Topics
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($allTags as $tag): ?>
                                    <a href="/blog?tag=<?php echo urlencode($tag); ?>" 
                                       class="btn-pill inline-flex items-center px-3 py-1 text-sm font-medium 
                                              <?php echo $selectedTag === $tag ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> 
                                              transition-colors">
                                        <?php echo htmlspecialchars($tag); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Newsletter CTA -->
                    <div class="bg-gradient-to-br from-purple-50 to-purple-50 rounded-2xl p-6 border border-purple-100">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">
                            <i class="fas fa-envelope mr-2 text-primary"></i>
                            Stay Updated
                        </h3>
                        <p class="text-gray-600 mb-4 text-sm">
                            Get your personalized morning brief delivered daily. Never miss what matters.
                        </p>
                        <a href="/register" 
                           class="btn-pill block w-full bg-primary text-white text-center py-2 px-4 hover:bg-primary-dark transition-colors font-medium">
                            Start for Free
                        </a>
                    </div>

                    <!-- Recent Posts (if viewing by tag) -->
                    <?php if ($selectedTag): ?>
                        <?php 
                        $recentPosts = BlogPost::getAll(5, true);
                        if (!empty($recentPosts)): 
                        ?>
                            <div class="bg-white rounded-2xl p-6 shadow-sm">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    <i class="fas fa-clock mr-2 text-primary"></i>
                                    Recent Posts
                                </h3>
                                <div class="space-y-3">
                                    <?php foreach ($recentPosts as $recentPost): ?>
                                        <div>
                                            <a href="<?php echo $recentPost->getUrl(); ?>" 
                                               class="text-sm font-medium text-gray-900 hover:text-primary transition-colors line-clamp-2">
                                                <?php echo htmlspecialchars($recentPost->getTitle()); ?>
                                            </a>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <?php echo $recentPost->getFormattedDate(); ?>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php include __DIR__ . '/../includes/page-footer.php'; ?>