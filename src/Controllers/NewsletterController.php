<?php

class NewsletterController extends Controller {
    public function index() {
        $this->requireAuth();

        $stmt = $this->db->prepare('
            SELECT n.*, 
                   COUNT(DISTINCT r.id) as recipient_count,
                   COUNT(DISTINCT d.id) as delivery_count
            FROM newsletters n
            LEFT JOIN recipients r ON n.id = r.newsletter_id
            LEFT JOIN newsletter_deliveries d ON n.id = d.newsletter_id
            WHERE n.user_id = :user_id
            GROUP BY n.id
            ORDER BY n.created_at DESC
        ');
        $stmt->bindValue(':user_id', $this->user['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $newsletters = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $newsletters[] = $row;
        }

        $this->render('newsletters/index', [
            'title' => 'My Newsletters',
            'newsletters' => $newsletters
        ]);
    }

    public function showCreate() {
        $this->requireAuth();
        $this->render('newsletters/create', [
            'title' => 'Create Newsletter',
            'csrf_token' => $this->generateCSRFToken()
        ]);
    }

    public function create() {
        $this->requireAuth();
        try {
            $this->validateCSRF();

            $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
            $schedule_type = filter_input(INPUT_POST, 'schedule_type', FILTER_SANITIZE_STRING);
            $schedule_time = filter_input(INPUT_POST, 'schedule_time', FILTER_SANITIZE_STRING);
            $schedule_days = filter_input(INPUT_POST, 'schedule_days', FILTER_SANITIZE_STRING);

            if (!$title || !$schedule_type) {
                throw new Exception('Please fill in all required fields');
            }

            $stmt = $this->db->prepare('
                INSERT INTO newsletters (user_id, title, description, schedule_type, schedule_time, schedule_days)
                VALUES (:user_id, :title, :description, :schedule_type, :schedule_time, :schedule_days)
            ');

            $stmt->bindValue(':user_id', $this->user['id'], SQLITE3_INTEGER);
            $stmt->bindValue(':title', $title, SQLITE3_TEXT);
            $stmt->bindValue(':description', $description, SQLITE3_TEXT);
            $stmt->bindValue(':schedule_type', $schedule_type, SQLITE3_TEXT);
            $stmt->bindValue(':schedule_time', $schedule_time, SQLITE3_TEXT);
            $stmt->bindValue(':schedule_days', $schedule_days, SQLITE3_TEXT);

            if (!$stmt->execute()) {
                throw new Exception('Failed to create newsletter');
            }

            $newsletter_id = $this->db->lastInsertRowID();

            // Add sections if provided
            $sections = $_POST['sections'] ?? [];
            foreach ($sections as $order => $section) {
                $stmt = $this->db->prepare('
                    INSERT INTO newsletter_sections (newsletter_id, type, title, config, display_order)
                    VALUES (:newsletter_id, :type, :title, :config, :display_order)
                ');

                $stmt->bindValue(':newsletter_id', $newsletter_id, SQLITE3_INTEGER);
                $stmt->bindValue(':type', $section['type'], SQLITE3_TEXT);
                $stmt->bindValue(':title', $section['title'], SQLITE3_TEXT);
                $stmt->bindValue(':config', json_encode($section['config']), SQLITE3_TEXT);
                $stmt->bindValue(':display_order', $order, SQLITE3_INTEGER);

                $stmt->execute();
            }

            $_SESSION['flash_message'] = 'Newsletter created successfully!';
            $_SESSION['flash_type'] = 'success';

            $this->redirect('/newsletters/' . $newsletter_id . '/edit');
        } catch (Exception $e) {
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'error';
            $this->redirect('/newsletters/create');
        }
    }

    public function edit($id) {
        $this->requireAuth();

        $stmt = $this->db->prepare('
            SELECT n.*, 
                   GROUP_CONCAT(s.id) as section_ids,
                   GROUP_CONCAT(s.type) as section_types,
                   GROUP_CONCAT(s.title) as section_titles,
                   GROUP_CONCAT(s.config) as section_configs,
                   GROUP_CONCAT(s.display_order) as section_orders
            FROM newsletters n
            LEFT JOIN newsletter_sections s ON n.id = s.newsletter_id
            WHERE n.id = :id AND n.user_id = :user_id
            GROUP BY n.id
        ');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':user_id', $this->user['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        $newsletter = $result->fetchArray(SQLITE3_ASSOC);

        if (!$newsletter) {
            $_SESSION['flash_message'] = 'Newsletter not found';
            $_SESSION['flash_type'] = 'error';
            $this->redirect('/newsletters');
        }

        // Get recipients
        $stmt = $this->db->prepare('SELECT * FROM recipients WHERE newsletter_id = :newsletter_id');
        $stmt->bindValue(':newsletter_id', $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        $recipients = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $recipients[] = $row;
        }

        $this->render('newsletters/edit', [
            'title' => 'Edit Newsletter',
            'newsletter' => $newsletter,
            'recipients' => $recipients,
            'csrf_token' => $this->generateCSRFToken()
        ]);
    }

    public function update($id) {
        $this->requireAuth();
        try {
            $this->validateCSRF();

            $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
            $schedule_type = filter_input(INPUT_POST, 'schedule_type', FILTER_SANITIZE_STRING);
            $schedule_time = filter_input(INPUT_POST, 'schedule_time', FILTER_SANITIZE_STRING);
            $schedule_days = filter_input(INPUT_POST, 'schedule_days', FILTER_SANITIZE_STRING);

            if (!$title || !$schedule_type) {
                throw new Exception('Please fill in all required fields');
            }

            $stmt = $this->db->prepare('
                UPDATE newsletters 
                SET title = :title,
                    description = :description,
                    schedule_type = :schedule_type,
                    schedule_time = :schedule_time,
                    schedule_days = :schedule_days,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND user_id = :user_id
            ');

            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->bindValue(':user_id', $this->user['id'], SQLITE3_INTEGER);
            $stmt->bindValue(':title', $title, SQLITE3_TEXT);
            $stmt->bindValue(':description', $description, SQLITE3_TEXT);
            $stmt->bindValue(':schedule_type', $schedule_type, SQLITE3_TEXT);
            $stmt->bindValue(':schedule_time', $schedule_time, SQLITE3_TEXT);
            $stmt->bindValue(':schedule_days', $schedule_days, SQLITE3_TEXT);

            if (!$stmt->execute()) {
                throw new Exception('Failed to update newsletter');
            }

            // Update sections
            $sections = $_POST['sections'] ?? [];
            
            // Delete existing sections
            $stmt = $this->db->prepare('DELETE FROM newsletter_sections WHERE newsletter_id = :newsletter_id');
            $stmt->bindValue(':newsletter_id', $id, SQLITE3_INTEGER);
            $stmt->execute();

            // Add new sections
            foreach ($sections as $order => $section) {
                $stmt = $this->db->prepare('
                    INSERT INTO newsletter_sections (newsletter_id, type, title, config, display_order)
                    VALUES (:newsletter_id, :type, :title, :config, :display_order)
                ');

                $stmt->bindValue(':newsletter_id', $id, SQLITE3_INTEGER);
                $stmt->bindValue(':type', $section['type'], SQLITE3_TEXT);
                $stmt->bindValue(':title', $section['title'], SQLITE3_TEXT);
                $stmt->bindValue(':config', json_encode($section['config']), SQLITE3_TEXT);
                $stmt->bindValue(':display_order', $order, SQLITE3_INTEGER);

                $stmt->execute();
            }

            $_SESSION['flash_message'] = 'Newsletter updated successfully!';
            $_SESSION['flash_type'] = 'success';

            $this->redirect('/newsletters/' . $id . '/edit');
        } catch (Exception $e) {
            $_SESSION['flash_message'] = $e->getMessage();
            $_SESSION['flash_type'] = 'error';
            $this->redirect('/newsletters/' . $id . '/edit');
        }
    }

    public function preview($id) {
        $this->requireAuth();

        $stmt = $this->db->prepare('
            SELECT n.*, 
                   GROUP_CONCAT(s.id) as section_ids,
                   GROUP_CONCAT(s.type) as section_types,
                   GROUP_CONCAT(s.title) as section_titles,
                   GROUP_CONCAT(s.config) as section_configs,
                   GROUP_CONCAT(s.display_order) as section_orders
            FROM newsletters n
            LEFT JOIN newsletter_sections s ON n.id = s.newsletter_id
            WHERE n.id = :id AND n.user_id = :user_id
            GROUP BY n.id
        ');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->bindValue(':user_id', $this->user['id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        $newsletter = $result->fetchArray(SQLITE3_ASSOC);

        if (!$newsletter) {
            $_SESSION['flash_message'] = 'Newsletter not found';
            $_SESSION['flash_type'] = 'error';
            $this->redirect('/newsletters');
        }

        // Generate preview content for each section
        $sections = [];
        if ($newsletter['section_ids']) {
            $ids = explode(',', $newsletter['section_ids']);
            $types = explode(',', $newsletter['section_types']);
            $titles = explode(',', $newsletter['section_titles']);
            $configs = explode(',', $newsletter['section_configs']);
            $orders = explode(',', $newsletter['section_orders']);

            foreach ($ids as $i => $section_id) {
                $sections[] = [
                    'id' => $section_id,
                    'type' => $types[$i],
                    'title' => $titles[$i],
                    'config' => json_decode($configs[$i], true),
                    'display_order' => $orders[$i],
                    'content' => $this->generateSectionContent($types[$i], json_decode($configs[$i], true))
                ];
            }
        }

        $this->render('newsletters/preview', [
            'title' => 'Preview Newsletter',
            'newsletter' => $newsletter,
            'sections' => $sections
        ]);
    }

    protected function generateSectionContent($type, $config) {
        switch ($type) {
            case 'weather':
                return $this->generateWeatherContent($config);
            case 'news':
                return $this->generateNewsContent($config);
            case 'stripe':
                return $this->generateStripeContent($config);
            case 'appstore':
                return $this->generateAppStoreContent($config);
            case 'github':
                return $this->generateGitHubContent($config);
            default:
                return 'Unknown section type';
        }
    }

    protected function generateWeatherContent($config) {
        // Implement weather API integration
        return 'Weather content will be generated here';
    }

    protected function generateNewsContent($config) {
        // Implement news API integration
        return 'News content will be generated here';
    }

    protected function generateStripeContent($config) {
        // Implement Stripe API integration
        return 'Stripe sales data will be generated here';
    }

    protected function generateAppStoreContent($config) {
        // Implement App Store API integration
        return 'App Store revenue data will be generated here';
    }

    protected function generateGitHubContent($config) {
        // Implement GitHub API integration
        return 'GitHub activity will be generated here';
    }
} 