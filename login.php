<?php
require_once "Database.php";
require_once "db_function.php";

session_start();
$db = new Database();
$pdo = $db->connect();
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($usernameOrEmail) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR full_name = ?)");
            $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Username is correct, now check the password
                // Supports both password_verify() and plain-text fallback if applicable
                if (password_verify($password, $user['password']) || $user['password'] === $password) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role_id'] = $user['role_id'];

                    header("Location: draftdashboard.php");
                    exit();
                } else {
                    $error_message = "Incorrect password.";
                }
            } else {
                $error_message = "Invalid username.";
            }
        } catch (\Exception $e) {
            $error_message = "Database connection error.";
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cropella | Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: linear-gradient(135deg, #fce7f3 0%, #f1f5f9 100%); height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { max-width: 420px; width: 100%; border-radius: 16px; box-shadow: 0 10px 30px rgba(236, 72, 153, 0.15); border: none; }
        .btn-pink { background-color: #d63384; color: white; border: none; }
        .btn-pink:hover { background-color: #b02a6b; color: white; }
    </style>
</head>
<body class="auth-body">
    <div class="petals" aria-hidden="true">
        <span style="--x:8%;  --d:11s; --s:0s;  --z:1.1rem">🌸</span>
        <span style="--x:22%; --d:14s; --s:3s;  --z:0.9rem">💗</span>
        <span style="--x:38%; --d:12s; --s:6s;  --z:1.3rem">🌷</span>
        <span style="--x:55%; --d:15s; --s:1s;  --z:1rem">🌸</span>
        <span style="--x:70%; --d:13s; --s:4s;  --z:0.9rem">💗</span>
        <span style="--x:84%; --d:16s; --s:7s;  --z:1.2rem">🌸</span>
        <span style="--x:94%; --d:12s; --s:9s;  --z:1rem">🌷</span>
    </div>
    <div class="login-card p-4 p-md-5 bg-white">
        <div class="text-center mb-4">
            <div class="mb-2" style="font-size: 3rem; color: #ec4899;"><i class="fa-solid fa-seedling"></i></div>
            <h3 class="fw-bold text-dark">Cropella AIS</h3>
            <p class="text-muted small">Agriculture Information System</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger py-2 small mb-3"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="username" class="form-control" required placeholder="Enter username">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" required placeholder="Enter password">
                </div>
            </div>
            <button type="submit" class="btn btn-pink w-100 py-2 mt-2">Sign In</button>
        </form>
    </div>
</body>
</html>