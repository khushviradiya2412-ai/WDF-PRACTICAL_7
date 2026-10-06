<?php
// Dynamic profile: shows the CURRENT logged-in user's own data.
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
    <title>Profile - <?php echo esc($user['fullname']); ?></title>
    <link rel="stylesheet" href="../CSS/PROFILE.CSS">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../CSS/INTERACTIVE.CSS">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="navbar-brand"><i class="fas fa-graduation-cap"></i><span>Advanced Student Hub</span></div>
            <div class="nav-menu">
                <a href="../HOME.HTML" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
                <a href="dashboard.php" class="nav-item"><i class="fas fa-chart-line"></i> Analytics</a>
                <a href="#" class="nav-item active"><i class="fas fa-user"></i> Profile</a>
                <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-cover"></div>
            <div class="profile-info">
                <div class="profile-avatar"><i class="fas fa-user-circle"></i></div>
                <div class="profile-details">
                    <h1><?php echo esc($user['fullname']); ?></h1>
                    <p class="role">Student | <?php echo esc(isset($user['course']) ? $user['course'] : ''); ?></p>
                </div>
            </div>
        </div>

        <div class="profile-content">
            <div class="profile-section">
                <div class="section-header"><h2><i class="fas fa-id-card"></i> Personal Information</h2></div>
                <div class="info-grid">
                    <div class="info-item"><label>Full Name</label><p><?php echo esc($user['fullname']); ?></p></div>
                    <div class="info-item"><label>Student ID</label><p><?php echo esc(isset($user['studentid']) ? $user['studentid'] : '—'); ?></p></div>
                    <div class="info-item"><label>Date of Birth</label><p><?php echo esc(!empty($user['dob']) ? $user['dob'] : '—'); ?></p></div>
                    <div class="info-item"><label>Gender</label><p><?php echo esc(!empty($user['gender']) ? gender_label($user['gender']) : '—'); ?></p></div>
                </div>
            </div>

            <div class="profile-section">
                <div class="section-header"><h2><i class="fas fa-envelope"></i> Contact Information</h2></div>
                <div class="info-grid">
                    <div class="info-item"><label>Email Address</label><p><?php echo esc(isset($user['email']) ? $user['email'] : '—'); ?></p></div>
                    <div class="info-item"><label>Phone Number</label><p><?php echo esc(isset($user['phone']) ? $user['phone'] : '—'); ?></p></div>
                    <div class="info-item"><label>Course / Branch</label><p><?php echo esc(isset($user['course']) ? $user['course'] : '—'); ?></p></div>
                    <div class="info-item"><label>Enrollment Year</label><p><?php echo esc(isset($user['year']) ? $user['year'] : '—'); ?></p></div>
                </div>
            </div>

            <div class="profile-actions">
                <a href="dashboard.php" class="action-link"><i class="fas fa-chart-bar"></i> View Dashboard</a>
                <a href="view-records.php" class="action-link"><i class="fas fa-table"></i> All Records</a>
                <a href="logout.php" class="action-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <a href="../HOME.HTML" class="action-link home-link"><i class="fas fa-home"></i> Home</a>
            </div>
        </div>
    </div>

    <footer><p>&copy; 2026 Advanced Student Hub Portal. All Rights Reserved.</p></footer>
</body>
</html>
