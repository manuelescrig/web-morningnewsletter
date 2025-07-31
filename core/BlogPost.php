<?php

class BlogPost {
    private $slug;
    private $title;
    private $excerpt;
    private $content;
    private $author;
    private $date;
    private $tags;
    private $featuredImage;
    private $seoTitle;
    private $seoDescription;
    private $filePath;
    
    public function __construct($slug = null) {
        if ($slug) {
            $this->slug = $slug;
            $this->loadFromFile();
        }
    }
    
    // Static methods for listing posts
    public static function getAll($limit = null, $published = true) {
        $postsDir = __DIR__ . '/../blog/posts/';
        $posts = [];
        
        if (!is_dir($postsDir)) {
            return $posts;
        }
        
        $files = glob($postsDir . '*.md');
        
        foreach ($files as $file) {
            $slug = basename($file, '.md');
            $post = new self($slug);
            
            // Skip drafts if only published requested
            if ($published && !$post->isPublished()) {
                continue;
            }
            
            $posts[] = $post;
        }
        
        // Sort by date descending
        usort($posts, function($a, $b) {
            return strtotime($b->getDate()) - strtotime($a->getDate());
        });
        
        if ($limit) {
            $posts = array_slice($posts, 0, $limit);
        }
        
        return $posts;
    }
    
    public static function findBySlug($slug) {
        $postsDir = __DIR__ . '/../blog/posts/';
        $filePath = $postsDir . $slug . '.md';
        
        if (!file_exists($filePath)) {
            return null;
        }
        
        return new self($slug);
    }
    
    public static function getByTag($tag, $limit = null) {
        $allPosts = self::getAll();
        $filteredPosts = [];
        
        foreach ($allPosts as $post) {
            if (in_array($tag, $post->getTags())) {
                $filteredPosts[] = $post;
            }
        }
        
        if ($limit) {
            $filteredPosts = array_slice($filteredPosts, 0, $limit);
        }
        
        return $filteredPosts;
    }
    
    public static function getAllTags() {
        $allPosts = self::getAll();
        $tags = [];
        
        foreach ($allPosts as $post) {
            $tags = array_merge($tags, $post->getTags());
        }
        
        return array_unique($tags);
    }
    
    private function loadFromFile() {
        $postsDir = __DIR__ . '/../blog/posts/';
        $this->filePath = $postsDir . $this->slug . '.md';
        
        if (!file_exists($this->filePath)) {
            throw new Exception("Blog post not found: " . $this->slug);
        }
        
        $content = file_get_contents($this->filePath);
        $this->parseMarkdown($content);
    }
    
    private function parseMarkdown($content) {
        // Split front matter and content
        $parts = explode('---', $content, 3);
        
        if (count($parts) < 3) {
            throw new Exception("Invalid blog post format - missing front matter");
        }
        
        // Parse front matter (YAML-like)
        $frontMatter = trim($parts[1]);
        $this->content = trim($parts[2]);
        
        $this->parseFrontMatter($frontMatter);
    }
    
    private function parseFrontMatter($frontMatter) {
        $lines = explode("\n", $frontMatter);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $key = trim($key);
                $value = trim($value, ' "\'');
                
                switch ($key) {
                    case 'title':
                        $this->title = $value;
                        break;
                    case 'excerpt':
                        $this->excerpt = $value;
                        break;
                    case 'author':
                        $this->author = $value;
                        break;
                    case 'date':
                        $this->date = $value;
                        break;
                    case 'tags':
                        $this->tags = array_map('trim', explode(',', $value));
                        break;
                    case 'featured_image':
                        $this->featuredImage = $value;
                        break;
                    case 'seo_title':
                        $this->seoTitle = $value;
                        break;
                    case 'seo_description':
                        $this->seoDescription = $value;
                        break;
                }
            }
        }
        
        // Set defaults
        if (!$this->author) $this->author = 'MorningNewsletter Team';
        if (!$this->date) $this->date = date('Y-m-d');
        if (!$this->tags) $this->tags = [];
        if (!$this->seoTitle) $this->seoTitle = $this->title;
        if (!$this->seoDescription) $this->seoDescription = $this->excerpt;
    }
    
    // Convert markdown to HTML (basic implementation)
    private function markdownToHtml($markdown) {
        // Basic markdown parsing
        $html = $markdown;
        
        // Convert image placeholders to gray boxes
        $html = preg_replace('/\[IMAGE PLACEHOLDER: (.+?)\]/i', 
            '<div class="image-placeholder">
                <i class="fas fa-image"></i>
                <p>$1</p>
            </div>', $html);
        
        // Process lists before headers to avoid conflicts
        // Collect all list items into arrays for processing
        $lines = explode("\n", $html);
        $processed = [];
        $currentList = [];
        $listType = '';
        
        foreach ($lines as $line) {
            // Check for ordered list items
            if (preg_match('/^(\d+)\.\s+(.+)$/', $line, $matches)) {
                if ($listType !== 'ol' && !empty($currentList)) {
                    // Close previous list
                    $processed[] = $this->wrapList($currentList, $listType);
                    $currentList = [];
                }
                $listType = 'ol';
                $currentList[] = '<li>' . $matches[2] . '</li>';
            }
            // Check for unordered list items
            elseif (preg_match('/^-\s+(.+)$/', $line, $matches)) {
                if ($listType !== 'ul' && !empty($currentList)) {
                    // Close previous list
                    $processed[] = $this->wrapList($currentList, $listType);
                    $currentList = [];
                }
                $listType = 'ul';
                $currentList[] = '<li>' . $matches[1] . '</li>';
            }
            else {
                // Not a list item, close any open list
                if (!empty($currentList)) {
                    $processed[] = $this->wrapList($currentList, $listType);
                    $currentList = [];
                    $listType = '';
                }
                $processed[] = $line;
            }
        }
        
        // Close any remaining list
        if (!empty($currentList)) {
            $processed[] = $this->wrapList($currentList, $listType);
        }
        
        $html = implode("\n", $processed);
        
        // Headers (process after lists to avoid conflicts)
        $html = preg_replace('/^#### (.+)$/m', '<h4>$1</h4>', $html);
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
        
        // Bold and italic (process before paragraphs)
        $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*([^\*\n]+?)\*/s', '<em>$1</em>', $html);
        
        // Links
        $html = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $html);
        
        // Code blocks
        $html = preg_replace('/```(.+?)```/s', '<pre><code>$1</code></pre>', $html);
        $html = preg_replace('/`(.+?)`/', '<code>$1</code>', $html);
        
        // Line breaks (two spaces at end of line)
        $html = preg_replace('/  \n/', '<br>', $html);
        
        // Paragraphs (but not inside lists or headers)
        $lines = explode("\n", $html);
        $inBlock = false;
        $paragraph = [];
        $result = [];
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            // Check if line is a block element
            if (preg_match('/^<(h[1-6]|ul|ol|li|pre|div|blockquote)/', $trimmed) ||
                preg_match('/<\/(h[1-6]|ul|ol|li|pre|div|blockquote)>$/', $trimmed)) {
                // Close any open paragraph
                if (!empty($paragraph)) {
                    $result[] = '<p>' . implode("\n", $paragraph) . '</p>';
                    $paragraph = [];
                }
                $result[] = $line;
                $inBlock = preg_match('/^<(ul|ol|pre|blockquote)/', $trimmed);
            } elseif (empty($trimmed)) {
                // Empty line - close paragraph if we have one
                if (!empty($paragraph)) {
                    $result[] = '<p>' . implode("\n", $paragraph) . '</p>';
                    $paragraph = [];
                }
            } else {
                // Regular text line
                if (!$inBlock) {
                    $paragraph[] = $line;
                } else {
                    $result[] = $line;
                }
            }
        }
        
        // Close any remaining paragraph
        if (!empty($paragraph)) {
            $result[] = '<p>' . implode("\n", $paragraph) . '</p>';
        }
        
        return implode("\n", $result);
    }
    
    // Helper function to wrap list items
    private function wrapList($items, $type) {
        if ($type === 'ol') {
            return '<ol>' . implode('', $items) . '</ol>';
        } else {
            return '<ul>' . implode('', $items) . '</ul>';
        }
    }
    
    // Getters
    public function getSlug() { return $this->slug; }
    public function getTitle() { return $this->title; }
    public function getExcerpt() { return $this->excerpt; }
    public function getContent() { return $this->content; }
    public function getHtmlContent() { return $this->markdownToHtml($this->content); }
    public function getAuthor() { return $this->author; }
    public function getDate() { return $this->date; }
    public function getTags() { return $this->tags; }
    public function getFeaturedImage() { return $this->featuredImage; }
    public function getSeoTitle() { return $this->seoTitle; }
    public function getSeoDescription() { return $this->seoDescription; }
    
    public function getFormattedDate($format = 'F j, Y') {
        return date($format, strtotime($this->date));
    }
    
    public function isPublished() {
        // Consider a post published if it has a date that's not in the future
        return strtotime($this->date) <= time();
    }
    
    public function getUrl() {
        return '/blog/' . $this->slug;
    }
    
    public function getReadingTime() {
        $wordCount = str_word_count(strip_tags($this->content));
        $minutes = ceil($wordCount / 200); // Average reading speed
        return $minutes . ' min read';
    }
}