-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Newsletters table
CREATE TABLE IF NOT EXISTS newsletters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    schedule_type ENUM('daily', 'weekly', 'custom') NOT NULL,
    schedule_time TIME,
    schedule_days VARCHAR(50), -- Comma-separated days for weekly schedule
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Newsletter sections table
CREATE TABLE IF NOT EXISTS newsletter_sections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    newsletter_id INTEGER NOT NULL,
    type VARCHAR(50) NOT NULL, -- weather, news, stripe, etc.
    title VARCHAR(255) NOT NULL,
    config TEXT NOT NULL, -- JSON configuration for the section
    display_order INTEGER NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (newsletter_id) REFERENCES newsletters(id) ON DELETE CASCADE
);

-- Recipients table
CREATE TABLE IF NOT EXISTS recipients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    newsletter_id INTEGER NOT NULL,
    email VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (newsletter_id) REFERENCES newsletters(id) ON DELETE CASCADE
);

-- Newsletter deliveries table
CREATE TABLE IF NOT EXISTS newsletter_deliveries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    newsletter_id INTEGER NOT NULL,
    status ENUM('pending', 'sent', 'failed') NOT NULL,
    sent_at DATETIME,
    error_message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (newsletter_id) REFERENCES newsletters(id) ON DELETE CASCADE
);

-- Email tracking table
CREATE TABLE IF NOT EXISTS email_tracking (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    delivery_id INTEGER NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    opened_at DATETIME,
    clicked_at DATETIME,
    click_url TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (delivery_id) REFERENCES newsletter_deliveries(id) ON DELETE CASCADE
);

-- Create indexes
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_newsletters_user_id ON newsletters(user_id);
CREATE INDEX idx_newsletter_sections_newsletter_id ON newsletter_sections(newsletter_id);
CREATE INDEX idx_recipients_newsletter_id ON recipients(newsletter_id);
CREATE INDEX idx_newsletter_deliveries_newsletter_id ON newsletter_deliveries(newsletter_id);
CREATE INDEX idx_email_tracking_delivery_id ON email_tracking(delivery_id); 