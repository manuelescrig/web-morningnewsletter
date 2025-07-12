<?php
/**
 * Cleanup Script for Newsletter Titles
 * 
 * This script cleans all newsletter titles to show only dates (removes all prefixes)
 * DELETE THIS FILE after running it once
 */

require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/config/database.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$user = $auth->getCurrentUser();

// Only allow admin users to run this cleanup
if (!$user->isAdmin()) {
    die('Access denied. Admin privileges required.');
}

echo "<h1>Cleanup Newsletter Titles - Keep Only Dates</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Find all history entries
    $stmt = $db->prepare("SELECT id, title FROM newsletter_history ORDER BY id");
    $stmt->execute();
    $entries = $stmt->fetchAll();
    
    echo "<p>Found " . count($entries) . " entries to clean up:</p>";
    
    if (count($entries) > 0) {
        echo "<h3>Before cleanup:</h3><ul>";
        foreach ($entries as $entry) {
            echo "<li>ID {$entry['id']}: " . htmlspecialchars($entry['title']) . "</li>";
        }
        echo "</ul>";
        
        // Clean up titles using regex to extract just the date part
        $updateCount = 0;
        $updateStmt = $db->prepare("UPDATE newsletter_history SET title = ? WHERE id = ?");
        
        foreach ($entries as $entry) {
            $title = $entry['title'];
            
            // Extract date pattern (e.g., "July 12, 2025" or "December 25, 2024")
            if (preg_match('/([A-Za-z]+ \d{1,2}, \d{4})/', $title, $matches)) {
                $newTitle = $matches[1]; // Just the date
                
                if ($newTitle !== $title) {
                    $updateStmt->execute([$newTitle, $entry['id']]);
                    $updateCount++;
                }
            }
        }
        
        if ($updateCount > 0) {
            echo "<p style='color: green;'><strong>✓ Successfully updated {$updateCount} entries!</strong></p>";
            
            // Show the results
            $stmt = $db->prepare("SELECT id, title FROM newsletter_history ORDER BY id");
            $stmt->execute();
            $updatedEntries = $stmt->fetchAll();
            
            echo "<h3>After cleanup:</h3><ul>";
            foreach ($updatedEntries as $entry) {
                echo "<li>ID {$entry['id']}: " . htmlspecialchars($entry['title']) . "</li>";
            }
            echo "</ul>";
            
        } else {
            echo "<p style='color: blue;'><strong>No entries needed updating. All titles are already clean!</strong></p>";
        }
    } else {
        echo "<p style='color: blue;'><strong>No history entries found.</strong></p>";
    }
    
    echo "<p><a href='/dashboard/history.php'>View History</a> | <a href='/dashboard/'>Back to Dashboard</a></p>";
    
    echo "<hr>";
    echo "<p><strong>Note:</strong> You can safely delete this file (cleanup-preview-titles.php) after running it.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>