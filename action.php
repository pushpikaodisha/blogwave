<?php
session_start();
require_once __DIR__ . '/db.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Helper: Check if user is logged in
function checkLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: auth.php?error=must_login");
        exit;
    }
}

// Helper: Check if user is Admin
function checkAdmin() {
    checkLoggedIn();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: index.php?error=unauthorized");
        exit;
    }
}

// 1. ADD COMMENT
if ($action === 'add_comment') {
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    $comment_text = isset($_POST['comment_text']) ? trim($_POST['comment_text']) : '';

    if ($post_id <= 0 || empty($comment_text)) {
        header("Location: index.php");
        exit;
    }

    $author_name = isset($_SESSION['username']) ? $_SESSION['username'] : 'Anonymous Reader';

    try {
        $stmt = $db->prepare("INSERT INTO comments (post_id, author_name, comment_text) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $author_name, $comment_text]);
        header("Location: post-view.php?id=" . $post_id . "&success=commented");
        exit;
    } catch (PDOException $e) {
        die("Error adding comment: " . $e->getMessage());
    }
}

// 2. SUBMIT ARTICLE POST (AUTHOR / ADMIN)
if ($action === 'submit_post') {
    checkLoggedIn();
    
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $category = isset($_POST['category']) ? $_POST['category'] : 'General';
    $image_url = isset($_POST['image_url']) ? $_POST['image_url'] : 'fa-newspaper';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';

    if (empty($title) || empty($content)) {
        header("Location: dashboard.php?error=empty_fields");
        exit;
    }

    // Role-based publish rules: Admin posts are auto-approved, author posts default to draft
    $status = ($_SESSION['role'] === 'admin') ? 'approved' : 'draft';

    try {
        $stmt = $db->prepare("INSERT INTO posts (user_id, title, content, category, image_url, status, views_count) VALUES (?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$_SESSION['user_id'], $title, $content, $category, $image_url, $status]);
        header("Location: dashboard.php?success=submitted");
        exit;
    } catch (PDOException $e) {
        die("Error submitting post: " . $e->getMessage());
    }
}

// 3. APPROVE POST DRAFT (ADMIN ONLY)
if ($action === 'approve') {
    checkAdmin();
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id <= 0) {
        header("Location: admin.php");
        exit;
    }

    try {
        $stmt = $db->prepare("UPDATE posts SET status = 'approved' WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: admin.php?success=approved");
        exit;
    } catch (PDOException $e) {
        die("Error approving post: " . $e->getMessage());
    }
}

// 4. DELETE POST (ADMIN ONLY)
if ($action === 'delete_post') {
    checkAdmin();
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id <= 0) {
        header("Location: admin.php");
        exit;
    }

    try {
        $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: admin.php?success=deleted");
        exit;
    } catch (PDOException $e) {
        die("Error deleting post: " . $e->getMessage());
    }
}

// 5. UPDATE USER ROLE (ADMIN ONLY)
if ($action === 'promote') {
    checkAdmin();
    $u_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $new_role = isset($_GET['role']) ? $_GET['role'] : '';

    if ($u_id <= 0 || !in_array($new_role, ['admin', 'author', 'reader'])) {
        header("Location: admin.php");
        exit;
    }

    try {
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $u_id]);
        header("Location: admin.php?success=user_updated");
        exit;
    } catch (PDOException $e) {
        die("Error promoting user: " . $e->getMessage());
    }
}

// Fallthrough redirect
header("Location: index.php");
exit;
?>
