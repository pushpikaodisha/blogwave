<?php
session_start();
require_once __DIR__ . '/db.php';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($post_id <= 0) {
    header("Location: index.php");
    exit;
}

try {
    // 1. DYNAMICALLY INCREMENT ARTICLE VIEWS
    $viewStmt = $db->prepare("UPDATE posts SET views_count = views_count + 1 WHERE id = ?");
    $viewStmt->execute([$post_id]);

    // 2. QUERY DETAILED POST RECORDS
    $stmt = $db->prepare("SELECT posts.*, users.username as author_name FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ? AND posts.status = 'approved'");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        // If post does not exist or is still a draft and not approved
        header("Location: index.php?error=not_found");
        exit;
    }

    // 3. FETCH COMMENTS
    $commentStmt = $db->prepare("SELECT * FROM comments WHERE post_id = ? ORDER BY id DESC");
    $commentStmt->execute([$post_id]);
    $comments = $commentStmt->fetchAll();

} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}

$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - BlogWave</title>
    <!-- Google Fonts: Inter & Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="blog-wave-theme">

    <!-- Sticky Navigation Top Bar -->
    <nav class="navbar navbar-dark pt-3 pb-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="fa-solid fa-arrow-left me-3 text-muted-blog fs-5"></i>
                <span class="logo-accent-blog"><i class="fa-solid fa-feather-pointed me-2"></i>BLOG</span><span class="logo-light-blog">WAVE</span>
            </a>
            
            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="btn btn-outline-blog btn-sm"><i class="fa-solid fa-house-user me-1"></i> Public Feed</a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container my-5">
        <div class="row justify-content-center">
            
            <!-- Article Reader Block -->
            <div class="col-lg-8">
                
                <!-- Banners -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert success-banner-blog p-3 mb-4 d-flex align-items-center" role="alert">
                        <i class="fa-solid fa-circle-check fs-5 me-3"></i>
                        <div>Comment submitted successfully! Live feedback appended.</div>
                    </div>
                <?php endif; ?>

                <div class="article-header-panel">
                    <span class="badge-edu-tag bg-indigo-badge mb-3 text-uppercase"><?php echo htmlspecialchars($post['category']); ?></span>
                    <h1 class="article-title-hero"><?php echo htmlspecialchars($post['title']); ?></h1>
                    
                    <div class="article-meta-capsule mt-4">
                        <span><i class="fa-solid fa-pen-nib text-rose-blog me-1"></i> By <?php echo htmlspecialchars($post['author_name']); ?></span>
                        <span><i class="fa-regular fa-calendar-days text-rose-blog me-1"></i> <?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                        <span><i class="fa-regular fa-eye text-rose-blog me-1"></i> <?php echo $post['views_count']; ?> Views</span>
                    </div>
                </div>

                <!-- Full Post body text -->
                <div class="article-body-content">
                    <div class="text-center bg-dark py-4 rounded border border-secondary border-opacity-10 mb-4">
                        <i class="fa-solid <?php echo htmlspecialchars($post['image_url']); ?> text-rose-blog display-4"></i>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                </div>

                <hr class="card-divider-trail mb-5">

                <!-- COMMENTS SECTION -->
                <div class="comments-section-block">
                    <h3 class="font-outfit text-white fw-bold mb-4"><i class="fa-regular fa-comments text-rose-blog me-2"></i>Comments (<?php echo count($comments); ?>)</h3>

                    <!-- Comment Submission Box -->
                    <div class="glass-card-blog p-4 mb-4">
                        <h5 class="font-outfit fw-bold text-white mb-3">Add Your Thoughts</h5>
                        <form action="action.php?action=add_comment" method="POST">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            
                            <div class="mb-3">
                                <?php if (!empty($username)): ?>
                                    <span class="small text-muted-blog d-block mb-2">Commenting as: <strong><?php echo htmlspecialchars($username); ?></strong></span>
                                <?php else: ?>
                                    <span class="small text-muted-blog d-block mb-2"><i class="fa-solid fa-triangle-exclamation text-warning me-1"></i> You are unsigned. Commenting as <strong>Anonymous</strong>.</span>
                                <?php endif; ?>
                                <textarea name="comment_text" class="form-control-poll w-100" rows="3" placeholder="Join the discussion, write feedback..." required></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary-blog btn-sm"><i class="fa-solid fa-paper-plane me-2"></i>Post Comment</button>
                        </form>
                    </div>

                    <!-- Comments logs lists -->
                    <div class="d-flex flex-column gap-3">
                        <?php if (empty($comments)): ?>
                            <div class="text-center text-muted-blog small py-4 italic border border-secondary border-opacity-10 rounded">
                                No comments posted on this thread. Be the first to share your thoughts!
                            </div>
                        <?php else: ?>
                            <?php foreach ($comments as $com): ?>
                                <div class="comment-card-item">
                                    <div class="d-flex justify-content-between align-items-center border-bottom-blog pb-2 mb-2">
                                        <span class="comment-author-name"><i class="fa-solid fa-user-tag text-xs text-rose-blog me-1"></i> <?php echo htmlspecialchars($com['author_name']); ?></span>
                                        <span class="comment-date-text"><?php echo date('M d, H:i', strtotime($com['created_at'])); ?></span>
                                    </div>
                                    <p class="comment-content-text"><?php echo nl2br(htmlspecialchars($com['comment_text'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                </div>

            </div>

        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-4 text-muted-blog border-top-blog">
        <p class="mb-0 small">BlogWave • Secure PHP commenting core</p>
    </footer>

    <!-- Bootstrap & Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
