<?php
session_start();
require_once __DIR__ . '/db.php';

// Auth Guard: must be logged in and must be author or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['author', 'admin'])) {
    header("Location: auth.php?error=unauthorized");
    exit;
}

$u_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$username = $_SESSION['username'];

// Fetch posts written by this user
try {
    $stmt = $db->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$u_id]);
    $user_posts = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlogWave - Author Workspace & Dashboard</title>
    <!-- Google Fonts: Inter & Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="blog-wave-theme">

    <!-- Top Sticky Bar Navigation -->
    <nav class="navbar navbar-dark pt-3 pb-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="fa-solid fa-arrow-left me-3 text-muted-blog fs-5"></i>
                <span class="logo-accent-blog"><i class="fa-solid fa-feather-pointed me-2"></i>AUTHOR</span><span class="logo-light-blog">WORKSPACE</span>
            </a>
            
            <div class="d-flex align-items-center gap-2">
                <span class="user-profile-badge"><i class="fa-regular fa-user me-1"></i> <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($user_role); ?>)</span>
                <a href="index.php" class="btn btn-outline-blog btn-sm"><i class="fa-solid fa-house-user me-1"></i> Public Feed</a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container my-5">
        
        <!-- Header -->
        <div class="row align-items-center mb-5">
            <div class="col-md-12">
                <h1 class="welcome-text-blog">Creator Workspace</h1>
                <p class="subtitle-text-blog">Draft rich articles, monitor reader interaction views, and review publication pipelines.</p>
            </div>
        </div>

        <!-- Notification banners -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert success-banner-blog p-3 mb-4 d-flex align-items-center" role="alert">
                <i class="fa-solid fa-circle-check fs-5 me-3"></i>
                <div>
                    <?php 
                        if($_GET['success'] === 'submitted') {
                            if($user_role === 'admin') echo "Excellent! Your article has been successfully published to the live public feeds instantly.";
                            else echo "Awesome! Your draft writeup has been submitted to the moderation queue. Administrative approval required.";
                        }
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-20 text-danger p-3 mb-4 d-flex align-items-center" role="alert">
                <i class="fa-solid fa-circle-exclamation fs-5 me-3"></i>
                <div>
                    <?php 
                        if($_GET['error'] === 'empty_fields') echo "Validation Error: Please fill in both the Title and the Article Content.";
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <!-- LEFT PANEL: Article draft composer form -->
            <div class="col-lg-6">
                <div class="glass-card-blog p-4">
                    <h3 class="card-title-blog"><i class="fa-solid fa-circle-plus text-rose-blog me-2"></i>Draft New Essay</h3>
                    <p class="card-desc-blog">Submit tech essays. Draft is placed in moderation before publishing.</p>

                    <form action="action.php?action=submit_post" method="POST">
                        <div class="mb-3">
                            <label class="form-label-blog small">Article Title</label>
                            <input type="text" name="title" class="form-control-poll" placeholder="e.g. Next-Gen Frontends with CSS" required>
                        </div>
                        
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label-blog small">Category Class</label>
                                <select name="category" class="form-select-flow w-100">
                                    <option value="Development">Development</option>
                                    <option value="Design">Design</option>
                                    <option value="Business">Business</option>
                                    <option value="General">General</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label-blog small">Header Layout Icon</label>
                                <select name="image_url" class="form-select-flow w-100">
                                    <option value="fa-code">Code Bracket (fa-code)</option>
                                    <option value="fa-palette">Palette (fa-palette)</option>
                                    <option value="fa-briefcase">Briefcase (fa-briefcase)</option>
                                    <option value="fa-newspaper">Newspaper (fa-newspaper)</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label-blog small">Article Content</label>
                            <textarea name="content" class="form-control-poll w-100" rows="8" placeholder="Type your rich markdown or standard article paragraphs here..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary-blog w-100 py-3"><i class="fa-solid fa-cloud-arrow-up me-2"></i>Publish Draft to Server</button>
                    </form>
                </div>
            </div>

            <!-- RIGHT PANEL: Placed Drafts List -->
            <div class="col-lg-6">
                <h3 class="font-outfit fw-bold text-white mb-3"><i class="fa-solid fa-folder-open text-rose-blog me-2"></i>Your Submitted Writeups</h3>
                
                <?php if ($user_role !== 'admin'): ?>
                    <div class="glass-card-blog p-3 mb-4 border border-secondary border-opacity-15">
                        <span class="small-title-blog"><i class="fa-solid fa-circle-info me-1"></i> Creator Note</span>
                        <p class="small text-muted-blog mb-0 mt-2">Author submissions default to a <span class="status-badge-draft">draft</span> state and must receive Superadmin approval before reflecting publicly. Admin dashboards publish immediately.</p>
                    </div>
                <?php endif; ?>

                <div class="d-flex flex-column gap-3" style="max-height: 520px; overflow-y: auto; padding-right: 5px;">
                    <?php if (empty($user_posts)): ?>
                        <div class="glass-card-blog text-center py-5">
                            <i class="fa-solid fa-feather fs-2 text-muted-blog mb-2"></i>
                            <p class="text-muted-blog small">No submitted writeups in your folder.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($user_posts as $post): 
                            $status = $post['status'];
                        ?>
                            <div class="glass-card-blog p-3 border border-secondary border-opacity-20">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-white text-truncate w-75"><?php echo htmlspecialchars($post['title']); ?></span>
                                    <span class="status-badge-<?php echo $status; ?>"><?php echo $status; ?></span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between mt-3 text-muted-blog tiny">
                                    <span>Category: <strong><?php echo htmlspecialchars($post['category']); ?></strong></span>
                                    <span>Views: <strong><?php echo $post['views_count']; ?> Hits</strong></span>
                                    <span><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-4 text-muted-blog border-top-blog">
        <p class="mb-0 small">BlogWave • Creator Dashboard Workspace</p>
    </footer>

    <!-- Bootstrap & Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
