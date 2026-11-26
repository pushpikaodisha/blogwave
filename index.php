<?php
session_start();
require_once __DIR__ . '/db.php';

// Fetch all approved posts
try {
    $stmt = $db->query("SELECT posts.*, users.username as author_name FROM posts JOIN users ON posts.user_id = users.id WHERE posts.status = 'approved' ORDER BY posts.id DESC");
    $posts = $stmt->fetchAll();
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
    <title>BlogWave - Premium Full-Stack CMS Community Hub</title>
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
    <nav class="navbar navbar-expand-lg navbar-dark pt-3 pb-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <span class="logo-accent-blog"><i class="fa-solid fa-feather-pointed me-2"></i>BLOG</span><span class="logo-light-blog">WAVE</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
            
            <div class="collapse navbar-collapse justify-content-end" id="navContent">
                <div class="d-flex align-items-center gap-3 mt-3 mt-lg-0">
                    <?php if (!empty($user_role)): ?>
                        <!-- Logged user dashboard shortcuts -->
                        <span class="user-profile-badge"><i class="fa-regular fa-user me-1"></i> <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($user_role); ?>)</span>
                        
                        <?php if ($user_role === 'author' || $user_role === 'admin'): ?>
                            <a href="dashboard.php" class="btn btn-outline-blog btn-sm"><i class="fa-solid fa-pen-nib me-1"></i> Workspace</a>
                        <?php endif; ?>

                        <?php if ($user_role === 'admin'): ?>
                            <a href="admin.php" class="btn btn-primary-blog btn-sm"><i class="fa-solid fa-shield-halved me-1"></i> Admin Panel</a>
                        <?php endif; ?>

                        <a href="auth.php?action=logout" class="btn btn-outline-danger btn-sm border-danger border-opacity-35 text-danger"><i class="fa-solid fa-right-from-bracket"></i></a>
                    <?php else: ?>
                        <!-- Public Reader Link -->
                        <a href="auth.php" class="btn btn-primary-blog btn-sm"><i class="fa-solid fa-circle-user me-1"></i> Join Community</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Grid -->
    <div class="container my-5">
        
        <!-- Header -->
        <div class="row align-items-center mb-5">
            <div class="col-md-9">
                <h1 class="welcome-text-blog">Voice of the Tech Future</h1>
                <p class="subtitle-text-blog">Publish tech essays, discuss in interactive comment sections, and manage configurations on an enterprise-grade SQLite platform.</p>
            </div>
        </div>

        <!-- Notification feedback banners -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert success-banner-blog p-3 mb-4 d-flex align-items-center" role="alert">
                <i class="fa-solid fa-circle-check fs-5 me-3"></i>
                <div>
                    <?php 
                        if($_GET['success'] === 'login') echo "Welcome back! Authorization confirmed.";
                        elseif($_GET['success'] === 'registered') echo "Account created! Welcome to our developer community board.";
                        elseif($_GET['success'] === 'logout') echo "You have logged out successfully. Hope to see you back soon!";
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-20 text-danger p-3 mb-4 d-flex align-items-center" role="alert">
                <i class="fa-solid fa-circle-exclamation fs-5 me-3"></i>
                <div>
                    <?php 
                        if($_GET['error'] === 'unauthorized') echo "Security Alert: Access denied. Administrative clearance required.";
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Category Filters -->
        <div class="category-tabs-blog mb-5">
            <button class="tab-btn-blog active" onclick="filterBlog('all')">All Articles</button>
            <button class="tab-btn-blog" onclick="filterBlog('development')"><i class="fa-solid fa-code me-1"></i> Development</button>
            <button class="tab-btn-blog" onclick="filterBlog('design')"><i class="fa-solid fa-palette me-1"></i> Design</button>
            <button class="tab-btn-blog" onclick="filterBlog('business')"><i class="fa-solid fa-briefcase me-1"></i> Business</button>
        </div>

        <!-- Blog posts lists -->
        <div class="row g-4" id="blogCardsGrid">
            <?php if (empty($posts)): ?>
                <div class="col-12">
                    <div class="glass-card-blog text-center py-5">
                        <i class="fa-regular fa-folder-open fs-1 text-muted-blog mb-3"></i>
                        <h4 class="text-white">No articles published yet</h4>
                        <p class="text-muted-blog small">Published writeups approved by our admins will populate here.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="col-lg-4 col-md-6 blog-post-card" data-category="<?php echo strtolower($post['category']); ?>">
                        <div class="blog-card-item">
                            <div class="blog-img-box">
                                <span class="blog-badge-tag"><?php echo htmlspecialchars($post['category']); ?></span>
                                <i class="fa-solid <?php echo htmlspecialchars($post['image_url']); ?> blog-img-placeholder"></i>
                            </div>
                            <div class="blog-body-panel">
                                <div>
                                    <h4 class="blog-title-text"><?php echo htmlspecialchars($post['title']); ?></h4>
                                    <p class="blog-summary"><?php echo htmlspecialchars(substr($post['content'], 0, 110)); ?>...</p>
                                </div>
                                <div class="blog-meta-row">
                                    <span>By <?php echo htmlspecialchars($post['author_name']); ?></span>
                                    <span class="d-flex align-items-center gap-3">
                                        <span><i class="fa-regular fa-eye me-1"></i> <?php echo $post['views_count']; ?></span>
                                        <a href="post-view.php?id=<?php echo $post['id']; ?>" class="text-rose-blog fw-bold text-decoration-none">Read <i class="fa-solid fa-angle-right ms-1"></i></a>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- Footer -->
    <footer class="text-center py-4 text-muted-blog border-top-blog">
        <p class="mb-0 small">BlogWave • Full-Stack PHP CMS Ecosystem</p>
    </footer>

    <!-- Bootstrap & Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterBlog(cat) {
            const tabs = document.querySelectorAll('.tab-btn-blog');
            tabs.forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');

            const cards = document.querySelectorAll('.blog-post-card');
            cards.forEach(card => {
                const cardCat = card.getAttribute('data-category');
                if (cat === 'all') {
                    card.style.display = 'block';
                } else if (cardCat === cat) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
