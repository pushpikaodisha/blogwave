<?php
session_start();
require_once __DIR__ . '/db.php';

// Superadmin Auth Guard: must be logged in and must be admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php?error=unauthorized");
    exit;
}

$admin_username = $_SESSION['username'];

// Fetch all users
try {
    $userStmt = $db->query("SELECT * FROM users ORDER BY username ASC");
    $users = $userStmt->fetchAll();
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}

// Fetch only draft posts (pending approvals)
try {
    $draftStmt = $db->query("SELECT posts.*, users.username as author_name FROM posts JOIN users ON posts.user_id = users.id WHERE posts.status = 'draft' ORDER BY posts.id DESC");
    $drafts = $draftStmt->fetchAll();
} catch (PDOException $e) {
    $drafts = [];
}

// Fetch all posts count
try {
    $allStmt = $db->query("SELECT COUNT(*) as count FROM posts");
    $allCount = $allStmt->fetch();
    $totalPosts = $allCount['count'];
} catch (PDOException $e) {
    $totalPosts = 0;
}

$pendingCount = count($drafts);
$usersCount = count($users);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlogWave - Superadmin Moderation Console</title>
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
                <span class="logo-accent-blog"><i class="fa-solid fa-feather-pointed me-2"></i>ADMIN</span><span class="logo-light-blog">PORTAL</span>
            </a>
            
            <div class="d-flex align-items-center gap-2">
                <span class="user-profile-badge"><i class="fa-solid fa-user-shield me-1"></i> <?php echo htmlspecialchars($admin_username); ?> (Admin)</span>
                <a href="index.php" class="btn btn-outline-blog btn-sm"><i class="fa-solid fa-house-user me-1"></i> Public Feed</a>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container my-5">
        
        <!-- Header -->
        <div class="row align-items-center mb-5">
            <div class="col-md-12">
                <h1 class="welcome-text-blog">Superadmin Moderation Center</h1>
                <p class="subtitle-text-blog">Approve pending essay drafts, promote user credentials, and manage system directories.</p>
            </div>
        </div>

        <!-- Success/Error Banners -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert success-banner-blog p-3 mb-4 d-flex align-items-center" role="alert">
                <i class="fa-solid fa-circle-check fs-5 me-3"></i>
                <div>
                    <?php 
                        if($_GET['success'] === 'approved') echo "Excellent! The article draft has been approved and published to public feeds.";
                        elseif($_GET['success'] === 'deleted') echo "The draft post has been permanently deleted from the database.";
                        elseif($_GET['success'] === 'user_updated') echo "Success! User credential permissions have been promoted/demoted successfully.";
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- METRICS ROW -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="admin-metric-card bg-dark border-secondary border-opacity-15">
                    <span class="small-title-blog">Registered Users</span>
                    <div class="admin-metric-val text-white"><?php echo $usersCount; ?> Accounts</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="admin-metric-card bg-dark border-secondary border-opacity-15">
                    <span class="small-title-blog">Pending Moderations</span>
                    <div class="admin-metric-val text-rose-blog"><?php echo $pendingCount; ?> Drafts</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="admin-metric-card bg-dark border-secondary border-opacity-15">
                    <span class="small-title-blog">Total Published Essays</span>
                    <div class="admin-metric-val text-success"><?php echo $totalPosts; ?> Writeups</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            
            <!-- LEFT COLUMN: Moderation Draft Queue -->
            <div class="col-lg-7">
                <h3 class="font-outfit fw-bold text-white mb-3"><i class="fa-solid fa-clock-rotate-left text-rose-blog me-2"></i>Pending Moderation Queue</h3>
                
                <div class="d-flex flex-column gap-3">
                    <?php if (empty($drafts)): ?>
                        <div class="glass-card-blog text-center py-5">
                            <i class="fa-solid fa-clipboard-check fs-2 text-muted-blog mb-2"></i>
                            <p class="text-muted-blog small">Zero items pending. Moderation queue is completely clean!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($drafts as $post): ?>
                            <div class="glass-card-blog p-4 border border-secondary border-opacity-15">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <span class="badge-edu-tag bg-indigo-badge mb-2 text-uppercase"><?php echo htmlspecialchars($post['category']); ?></span>
                                        <h4 class="font-outfit fw-bold text-white"><?php echo htmlspecialchars($post['title']); ?></h4>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="action.php?action=approve&id=<?php echo $post['id']; ?>" class="btn btn-emerald-shop btn-sm" title="Approve Post"><i class="fa-solid fa-check"></i> Approve</a>
                                        <button class="btn btn-outline-danger btn-sm border-danger border-opacity-35 text-danger" onclick="confirmDelete(<?php echo $post['id']; ?>)" title="Delete Draft"><i class="fa-solid fa-trash-can"></i></button>
                                    </div>
                                </div>
                                <p class="small text-muted-blog mb-3"><?php echo htmlspecialchars(substr($post['content'], 0, 150)); ?>...</p>
                                <div class="d-flex justify-content-between align-items-center tiny text-muted-blog border-top-blog pt-3">
                                    <span>Drafted by: <strong><?php echo htmlspecialchars($post['author_name']); ?></strong></span>
                                    <span>Date: <?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT COLUMN: User Directory Console -->
            <div class="col-lg-5">
                <h3 class="font-outfit fw-bold text-white mb-3"><i class="fa-solid fa-users text-rose-blog me-2"></i>User Directories Console</h3>
                
                <div class="table-responsive">
                    <table class="table table-glass-blog align-middle">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Role</th>
                                <th class="text-end">Promote</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="fw-bold text-white"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td>
                                        <span class="status-badge-<?php echo ($user['role'] === 'admin' ? 'approved' : ($user['role'] === 'author' ? 'draft' : 'ok')); ?> text-uppercase small" style="<?php if($user['role'] === 'reader') echo 'background:rgba(255,255,255,0.03); color:#9ca3af; border:1px solid rgba(255,255,255,0.08);'; ?>">
                                            <?php echo htmlspecialchars($user['role']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <!-- Role selectors -->
                                        <div class="dropdown">
                                            <button class="btn-action-med dropdown-toggle border-secondary border-opacity-30" type="button" data-bs-toggle="dropdown">
                                                <i class="fa-solid fa-user-gear"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark bg-dark border-secondary">
                                                <li><a class="dropdown-item" href="action.php?action=promote&id=<?php echo $user['id']; ?>&role=admin"><i class="fa-solid fa-user-shield me-2 text-success"></i> Promote to Admin</a></li>
                                                <li><a class="dropdown-item" href="action.php?action=promote&id=<?php echo $user['id']; ?>&role=author"><i class="fa-solid fa-pen-nib me-2 text-warning"></i> Make Author</a></li>
                                                <li><a class="dropdown-item" href="action.php?action=promote&id=<?php echo $user['id']; ?>&role=reader"><i class="fa-solid fa-book-open me-2 text-secondary"></i> Demote to Reader</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-4 text-muted-blog border-top-blog">
        <p class="mb-0 small">BlogWave • Secure Moderation Portal Console</p>
    </footer>

    <!-- Bootstrap & Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id) {
            if(confirm("Are you sure you want to permanently delete this draft post? This will automatically wipe any associated comments logs.")) {
                window.location.href = `action.php?action=delete_post&id=${id}`;
            }
        }
    </script>
</body>
</html>
