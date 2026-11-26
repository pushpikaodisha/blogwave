<?php
session_start();
require_once __DIR__ . '/db.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

// 1. PROCESS SIGN IN
if ($action === 'login') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        header("Location: auth.php?error=empty_fields");
        exit;
    }

    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Setup session logs
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header("Location: index.php?success=login");
            exit;
        } else {
            header("Location: auth.php?error=invalid_credentials");
            exit;
        }
    } catch (PDOException $e) {
        die("Error logging in: " . $e->getMessage());
    }
}

// 2. PROCESS SIGN UP
if ($action === 'register') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        header("Location: auth.php?error=empty_fields");
        exit;
    }

    try {
        // Check uniqueness
        $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE username = ?");
        $checkStmt->execute([$username]);
        $res = $checkStmt->fetch();

        if ($res['count'] > 0) {
            header("Location: auth.php?error=username_taken");
            exit;
        }

        // Hash and insert
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'reader')");
        $stmt->execute([$username, $hashed]);
        $user_id = $db->lastInsertId();

        // Auto log-in
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'reader';

        header("Location: index.php?success=registered");
        exit;
    } catch (PDOException $e) {
        die("Error registering user: " . $e->getMessage());
    }
}

// 3. LOG OUT PROCESS
if ($action === 'logout') {
    session_destroy();
    header("Location: index.php?success=logout");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlogWave - Secure Access Gatekeeper</title>
    <!-- Google Fonts: Inter & Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="blog-wave-theme">

    <div class="container my-5 py-5">
        <div class="row justify-content-center">
            
            <!-- Login & Registration Container Card -->
            <div class="col-md-5">
                <div class="text-center mb-4">
                    <a href="index.php" class="text-decoration-none">
                        <h2 class="font-outfit fw-bold text-white mb-0"><i class="fa-solid fa-feather-pointed text-rose-blog me-2"></i>BLOG<span class="text-rose-blog">WAVE</span></h2>
                    </a>
                    <p class="text-muted-blog small mt-2">Secure access to the blogging community hub.</p>
                </div>

                <!-- Error notifications -->
                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-20 text-danger p-3 mb-4 d-flex align-items-center" role="alert">
                        <i class="fa-solid fa-circle-exclamation fs-5 me-3"></i>
                        <div>
                            <?php 
                                if($_GET['error'] === 'invalid_credentials') echo "Authorization Error: Incorrect username or password.";
                                elseif($_GET['error'] === 'username_taken') echo "Validation Error: That username is already registered on our server.";
                                elseif($_GET['error'] === 'empty_fields') echo "Validation Error: Please fill in all fields.";
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- LOGIN CARD PANEL -->
                <div class="glass-card-blog p-4" id="loginCard">
                    <h3 class="font-outfit fw-bold text-white mb-3 text-center">Sign In Portal</h3>
                    <form action="auth.php?action=login" method="POST">
                        <div class="mb-3">
                            <label class="form-label-blog small">Username</label>
                            <input type="text" name="username" class="form-control-poll" placeholder="Enter username (e.g. admin)" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label-blog small">Password</label>
                            <input type="password" name="password" class="form-control-poll" placeholder="Enter password (e.g. admin123)" required>
                        </div>
                        <button type="submit" class="btn btn-primary-blog w-100 py-2.5 mb-3"><i class="fa-solid fa-right-to-bracket me-2"></i>Authorize Account</button>
                    </form>
                    <div class="text-center small text-muted-blog">
                        Don't have an account? <a href="#" class="text-rose-blog fw-semibold" onclick="toggleAuthCards('register')">Create One</a>
                    </div>
                </div>

                <!-- REGISTER CARD PANEL (Hidden by default) -->
                <div class="glass-card-blog p-4 d-none" id="registerCard">
                    <h3 class="font-outfit fw-bold text-white mb-3 text-center">Create Reader Account</h3>
                    <form action="auth.php?action=register" method="POST">
                        <div class="mb-3">
                            <label class="form-label-blog small">Choose Username</label>
                            <input type="text" name="username" class="form-control-poll" placeholder="e.g. creative_writer" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label-blog small">Choose Password</label>
                            <input type="password" name="password" class="form-control-poll" placeholder="Minimum 6 characters" required>
                        </div>
                        <button type="submit" class="btn btn-primary-blog w-100 py-2.5 mb-3"><i class="fa-solid fa-user-plus me-2"></i>Register Reader Account</button>
                    </form>
                    <div class="text-center small text-muted-blog">
                        Already registered? <a href="#" class="text-rose-blog fw-semibold" onclick="toggleAuthCards('login')">Sign In</a>
                    </div>
                </div>

                <!-- Default Seed Logins Tip Box -->
                <div class="glass-card-blog p-3 mt-4 text-start" style="border-color: rgba(244, 63, 94, 0.15);">
                    <div class="small-title-blog"><i class="fa-solid fa-circle-info me-1"></i> Default Seeded Logins (Out-of-the-Box)</div>
                    <ul class="small text-muted-blog mb-0 ps-3 mt-2">
                        <li><strong>Super Administrator:</strong> username: <code>admin</code> | password: <code>admin123</code></li>
                        <li><strong>Content Author:</strong> username: <code>writer</code> | password: <code>writer123</code></li>
                    </ul>
                </div>

            </div>

        </div>
    </div>

    <!-- Bootstrap & Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleAuthCards(target) {
            const loginCard = document.getElementById('loginCard');
            const registerCard = document.getElementById('registerCard');

            if (target === 'register') {
                loginCard.classList.add('d-none');
                registerCard.classList.remove('d-none');
            } else {
                loginCard.classList.remove('d-none');
                registerCard.classList.add('d-none');
            }
        }
    </script>
</body>
</html>
