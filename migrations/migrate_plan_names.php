<?php
/**
 * Migration script to update plan names from old system to new system
 * 
 * Old system: free, medium, premium
 * New system: free, starter, pro, unlimited
 * 
 * Mapping:
 * - free -> free (no change)
 * - medium -> starter
 * - premium -> pro (note: premium had unlimited sources, but new pro has 15 sources)
 * 
 * Note: This migration creates a new "unlimited" plan tier for users who need unlimited sources
 */

require_once __DIR__ . '/../config/database.php';

function migratePlanNames() {
    try {
        $db = Database::getInstance()->getConnection();
        
        echo "Starting plan name migration...\n";
        
        // Get current plan distribution
        $stmt = $db->query("SELECT plan, COUNT(*) as count FROM users GROUP BY plan");
        $currentPlans = $stmt->fetchAll();
        
        echo "Current plan distribution:\n";
        foreach ($currentPlans as $planData) {
            echo "  {$planData['plan']}: {$planData['count']} users\n";
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        // Update plan names
        $migrations = [
            'medium' => 'starter',
            'premium' => 'pro'
        ];
        
        $totalUpdated = 0;
        
        foreach ($migrations as $oldPlan => $newPlan) {
            $stmt = $db->prepare("UPDATE users SET plan = ? WHERE plan = ?");
            $stmt->execute([$newPlan, $oldPlan]);
            $updated = $stmt->rowCount();
            $totalUpdated += $updated;
            
            if ($updated > 0) {
                echo "Migrated {$updated} users from '{$oldPlan}' to '{$newPlan}'\n";
            }
        }
        
        // Also update subscriptions table if it has plan column
        foreach ($migrations as $oldPlan => $newPlan) {
            $stmt = $db->prepare("UPDATE subscriptions SET plan = ? WHERE plan = ?");
            $stmt->execute([$newPlan, $oldPlan]);
            $updated = $stmt->rowCount();
            
            if ($updated > 0) {
                echo "Updated {$updated} subscription records from '{$oldPlan}' to '{$newPlan}'\n";
            }
        }
        
        // Commit transaction
        $db->commit();
        
        echo "\nMigration completed successfully!\n";
        echo "Total users updated: {$totalUpdated}\n";
        
        // Show new plan distribution
        $stmt = $db->query("SELECT plan, COUNT(*) as count FROM users GROUP BY plan");
        $newPlans = $stmt->fetchAll();
        
        echo "\nNew plan distribution:\n";
        foreach ($newPlans as $planData) {
            echo "  {$planData['plan']}: {$planData['count']} users\n";
        }
        
        echo "\nPlan limits after migration:\n";
        echo "  free: 1 source\n";
        echo "  starter: 5 sources\n";
        echo "  pro: 15 sources\n";
        echo "  unlimited: unlimited sources\n";
        
        echo "\nNote: Users previously on 'premium' plan (unlimited sources) are now on 'pro' plan (15 sources).\n";
        echo "If any users need unlimited sources, manually update them to 'unlimited' plan.\n";
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo "Migration failed: " . $e->getMessage() . "\n";
        return false;
    }
    
    return true;
}

// Run migration if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    if (migratePlanNames()) {
        exit(0);
    } else {
        exit(1);
    }
}