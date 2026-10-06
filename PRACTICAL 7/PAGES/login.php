<?php
// Login: verifies email/student-ID + password against DATA/registrations.json
session_start();
require_once __DIR__ . '/../PHP/functions.php';

$jsonFile = __DIR__ . '/../DATA/registrations.json';
$error = '';
$oldId = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim(isset($_POST['username']) ? $_POST['username'] : '');
    $password   = isset($_POST['password']) ? $_POST['password'] : '';
    $oldId = $identifier;

    if (!verify_csrf(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $error = 'Your session expired. Reload the page and try again.';
    } elseif ($identifier === '' || $password === '') {
        $error = 'Enter your Student ID / Email and password.';
    } else {
        $found = find_user($jsonFile, $identifier);
        if (!$found) {
            $error = 'No account found for "' . $identifier . '". Please register first.';
        } elseif (empty($found['password_hash'])) {
            $error = 'This sample record has no password. Please create an account with the PHP registration form.';
        } elseif (!password_verify($password, $found['password_hash'])) {
            $error = 'Incorrect password. Please try again.';
        } else {
            $public = $found;
            unset($public['password_hash']);
            session_regenerate_id(true);
            set_current_user($public);
            header('Location: dashboard.php');
            exit;
        }
    }
}
$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Login (PHP)</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <link rel="stylesheet" href="../CSS/INTERACTIVE.CSS">
    <link rel="stylesheet" href="../CSS/practical7.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-box">
            <h2>&#127979; Student Hub</h2>
            <p>Secure Login Portal (PHP)</p>
            <?php if ($error !== ''): ?>
                <div class="form-summary" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <form method="POST" action="login.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="username">Student ID / Email</label>
                    <input type="text" id="username" name="username" placeholder="Enter your student ID or email"
                           value="<?php echo htmlspecialchars($oldId, ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%">Sign In</button>
            </form>
            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Create one here</a></p>
                <p><a class="home-link" href="../INDEX.HTML">Home</a> | <a href="LOGIN.HTML">Old HTML Login</a></p>
            </div>
        </div>
    </div>
    <footer><div class="footer-bottom"><p>&copy; 2026 Student Hub Portal. All Rights Reserved.</p></div></footer>
</body>
</html>
