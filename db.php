<?php
$db_file = __DIR__ . '/database.db';
$db_created = !file_exists($db_file);

try {
    $db = new PDO("sqlite:" . $db_file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Initialize Schema if newly created
    if ($db_created || filesize($db_file) === 0) {
        // Create Users Table
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'reader' -- admin, author, reader
        )");

        // Create Posts Table
        $db->exec("CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            content TEXT NOT NULL,
            category TEXT DEFAULT 'General',
            image_url TEXT DEFAULT 'fa-newspaper',
            status TEXT DEFAULT 'draft', -- draft, approved
            views_count INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        // Create Comments Table
        $db->exec("CREATE TABLE IF NOT EXISTS comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            post_id INTEGER NOT NULL,
            author_name TEXT NOT NULL,
            comment_text TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE
        )");

        // Seed Hashed User accounts (admin123 & writer123)
        $admin_hash = password_hash("admin123", PASSWORD_DEFAULT);
        $writer_hash = password_hash("writer123", PASSWORD_DEFAULT);

        $userStmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $userStmt->execute(["admin", $admin_hash, "admin"]);
        $userStmt->execute(["writer", $writer_hash, "author"]);
        $userStmt->execute(["reader_demo", password_hash("reader123", PASSWORD_DEFAULT), "reader"]);

        // Seed Beautiful default blog posts (approved vs draft)
        $postStmt = $db->prepare("INSERT INTO posts (user_id, title, content, category, image_url, status, views_count) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $c1 = "In 2026, web engineering is shifting towards highly dynamic, lightweight localized backends. SQLite has transitioned from a development convenience to a major production contender for edge networks and full-stack single-server systems due to zero overhead and direct file integrations. Combined with PHP 8.x and vanilla web elements, developers can build enterprise-level applications with near-instant page response limits.";
        $postStmt->execute([2, "The Future of Web Engineering in 2026", $c1, "Development", "fa-code", "approved", 140]);

        $c2 = "Glassmorphism continues to captivate designers. To achieve premium glass aesthetics, you must coordinate deep backdrop blurs, harmonious color grids tailored via HSL values, and thin high-contrast border limits. Adding subtle CSS keyframe micro-animations creates physical interactions that make interfaces feel responsive, premium, and wowed at first glance.";
        $postStmt->execute([2, "Mastering Glassmorphism in UI/UX Dashboards", $c2, "Design", "fa-palette", "approved", 85]);

        $c3 = "Before issuing equity investments, VC companies analyze standard customer acquisition loops and corporate burn rates. For early-stage tech startups, establishing clean financial runways and measuring client churn metrics is far more crucial than rapid raw user additions. Focus on unit economics before scaling channels.";
        $postStmt->execute([2, "Venture Audit: Scaling Early Tech Startups", $c3, "Business", "fa-briefcase", "draft", 12]); // Draft needs admin approval

        // Seed some initial comments
        $commentStmt = $db->prepare("INSERT INTO comments (post_id, author_name, comment_text) VALUES (?, ?, ?)");
        $commentStmt->execute([1, "Logan Howlett", "Incredible insights. I've been running edge databases on SQLite recently and the latency outcomes are absolutely outstanding."]);
        $commentStmt->execute([2, "Charles Xavier", "Frosted glass overlays are incredibly relaxing on the eyes when dark mode is enabled."]);
    }
} catch (PDOException $e) {
    die("SQLite Connection failed: " . $e->getMessage());
}
?>
