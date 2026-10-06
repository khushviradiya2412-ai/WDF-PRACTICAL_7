<?php
// Dynamic dashboard: shows the CURRENT logged-in user's own data.
// Flow: register.php (success) -> sets $_SESSION['user'] -> redirects here.
session_start();
require_once __DIR__ . '/../PHP/functions.php';

$user = get_current_user_record();

if (!$user) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo esc($user['fullname']); ?></title>
    <link rel="stylesheet" href="../CSS/DASHBOARD.CSS">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/INTERACTIVE.CSS">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="navbar-brand">
                <i class="fas fa-graduation-cap"></i>
                <span>Advanced Student Hub</span>
            </div>
            <div class="nav-menu">
                <a href="../HOME.HTML" class="nav-item"><i class="fas fa-home"></i> Home</a>
                <a href="#" class="nav-item active"><i class="fas fa-chart-line"></i> Analytics</a>
                <a href="profile.php" class="nav-item"><i class="fas fa-user"></i> Profile</a>
                <a href="view-records.php" class="nav-item"><i class="fas fa-table"></i> Records</a>
                <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="header-content">
                <h1>Welcome, <?php echo esc($user['fullname']); ?>!</h1>
                <p>Track your academic performance and progress</p>
            </div>
            <div class="header-actions">
                <button class="action-btn" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                <a class="action-btn" style="text-decoration:none" href="profile.php"><i class="fas fa-user"></i> My Profile</a>
            </div>
        </div>

        <!-- Student Info Card : NOW DYNAMIC, no more fixed "Khush Viradiya" -->
        <div class="student-card">
            <div class="student-avatar"><i class="fas fa-user-circle"></i></div>
            <div class="student-info">
                <h2><?php echo esc($user['fullname']); ?></h2>
                <p><strong>Roll No:</strong> <?php echo esc(isset($user['studentid']) ? $user['studentid'] : '—'); ?></p>
                <p><strong>Branch:</strong> <?php echo esc(isset($user['course']) ? $user['course'] : '—'); ?></p>
                <p><strong>Enrolled:</strong> <?php echo esc(isset($user['year']) ? $user['year'] : '—'); ?></p>
                <p><strong>Email:</strong> <?php echo esc(isset($user['email']) ? $user['email'] : '—'); ?></p>
                <p><strong>Phone:</strong> <?php echo esc(isset($user['phone']) ? $user['phone'] : '—'); ?></p>
                <?php if (!empty($user['dob'])): ?><p><strong>DOB:</strong> <?php echo esc($user['dob']); ?></p><?php endif; ?>
                <?php if (!empty($user['gender'])): ?><p><strong>Gender:</strong> <?php echo esc(gender_label($user['gender'])); ?></p><?php endif; ?>
            </div>
            <div class="student-stats">
                <div class="stat"><span class="stat-value"><?php echo esc(isset($user['year']) ? $user['year'] : '—'); ?></span><span class="stat-label">Enroll Year</span></div>
                <div class="stat"><span class="stat-value"><?php echo esc(isset($user['studentid']) ? $user['studentid'] : '—'); ?></span><span class="stat-label">Roll No</span></div>
                <div class="stat"><span class="stat-value">Active</span><span class="stat-label">Status</span></div>
            </div>
        </div>

        <div class="section">
            <h2>Registration Details</h2>
            <div class="table-container">
                <table class="performance-table">
                    <thead><tr><th>Field</th><th>Your Information</th></tr></thead>
                    <tbody>
                        <tr><td>Full Name</td><td><strong><?php echo esc($user['fullname']); ?></strong></td></tr>
                        <tr><td>Email</td><td><?php echo esc(isset($user['email']) ? $user['email'] : ''); ?></td></tr>
                        <tr><td>Phone</td><td><?php echo esc(isset($user['phone']) ? $user['phone'] : ''); ?></td></tr>
                        <tr><td>Student ID</td><td><?php echo esc(isset($user['studentid']) ? $user['studentid'] : ''); ?></td></tr>
                        <tr><td>Course / Branch</td><td><?php echo esc(isset($user['course']) ? $user['course'] : ''); ?></td></tr>
                        <tr><td>Enrollment Year</td><td><?php echo esc(isset($user['year']) ? $user['year'] : ''); ?></td></tr>
                        <tr><td>Registered On</td><td><?php echo esc(isset($user['timestamp']) ? $user['timestamp'] : ''); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="quick-links">
            <a href="profile.php" class="link-btn"><i class="fas fa-user-circle"></i> My Profile</a>
            <a href="view-records.php" class="link-btn"><i class="fas fa-table"></i> All Records</a>
            <a href="logout.php" class="link-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <a href="../HOME.HTML" class="link-btn home-link"><i class="fas fa-home"></i> Home</a>
        </div>
    </div>

    <footer><p>&copy; 2026 Advanced Student Hub Portal. All Rights Reserved.</p></footer>
</body>
</html>
