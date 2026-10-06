<?php
// Practical 7 — Intermediate extension: display stored CSV/JSON records.
$csvFile  = __DIR__ . '/../DATA/registrations.csv';
$jsonFile = __DIR__ . '/../DATA/registrations.json';

// Read CSV rows (first row = header)
$csvRows = array();
$csvHeader = array();
if (file_exists($csvFile) && filesize($csvFile) > 0) {
    $lines = array_map('str_getcsv', file($csvFile));
    if (!empty($lines)) {
        $csvHeader = array_shift($lines);
        $csvRows = $lines;
    }
}

// Read JSON rows
$jsonRows = array();
if (file_exists($jsonFile) && filesize($jsonFile) > 0) {
    $decoded = json_decode(file_get_contents($jsonFile), true);
    if (is_array($decoded)) $jsonRows = $decoded;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stored Records - Practical 7 | Student Hub</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <link rel="stylesheet" href="../CSS/practical7.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <span class="navbar-brand">&#127979; Student Hub</span>
            <div class="nav-menu">
                <a href="../HOME.HTML" class="nav-item">Dashboard</a>
                <a href="register.php" class="nav-item">Register (PHP)</a>
                <a href="view-records.php" class="nav-item active">View Records</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 class="section-title">Stored Registrations (CSV / JSON)</h1>
        <p style="text-align:center;color:#666">
            CSV records: <strong><?php echo count($csvRows); ?></strong> &nbsp;|&nbsp;
            JSON records: <strong><?php echo count($jsonRows); ?></strong> &nbsp;|&nbsp;
            Files: <code>DATA/registrations.csv</code>, <code>DATA/registrations.json</code>
        </p>

        <div class="section-box">
            <h2>CSV Records</h2>
            <?php if (empty($csvRows)): ?>
                <p>No CSV records yet. <a href="register.php">Submit the registration form</a> first.</p>
            <?php else: ?>
                <div class="table-container"><table>
                    <tr><?php foreach ($csvHeader as $h): ?><th><?php echo htmlspecialchars($h); ?></th><?php endforeach; ?></tr>
                    <?php foreach ($csvRows as $r): ?><tr><?php foreach ($r as $c): ?><td><?php echo htmlspecialchars($c); ?></td><?php endforeach; ?></tr><?php endforeach; ?>
                </table></div>
            <?php endif; ?>
        </div>

        <div class="section-box">
            <h2>JSON Records</h2>
            <?php if (empty($jsonRows)): ?>
                <p>No JSON records yet. <a href="register.php">Submit the registration form</a> first.</p>
            <?php else: ?>
                <div class="table-container"><table>
                    <tr><th>Timestamp</th><th>Full Name</th><th>Email</th><th>Phone</th><th>Student ID</th><th>Course</th><th>Year</th></tr>
                    <?php foreach ($jsonRows as $r): ?><tr>
                        <td><?php echo htmlspecialchars(isset($r['timestamp'])?$r['timestamp']:''); ?></td>
                        <td><?php echo htmlspecialchars(isset($r['fullname'])?$r['fullname']:''); ?></td>
                        <td><?php echo htmlspecialchars(isset($r['email'])?$r['email']:''); ?></td>
                        <td><?php echo htmlspecialchars(isset($r['phone'])?$r['phone']:''); ?></td>
                        <td><?php echo htmlspecialchars(isset($r['studentid'])?$r['studentid']:''); ?></td>
                        <td><?php echo htmlspecialchars(isset($r['course'])?$r['course']:''); ?></td>
                        <td><?php echo htmlspecialchars(isset($r['year'])?$r['year']:''); ?></td>
                    </tr><?php endforeach; ?>
                </table></div>
            <?php endif; ?>
        </div>

        <div style="text-align:center;margin:20px 0">
            <a href="register.php" class="btn btn-primary">+ New Registration</a>
        </div>
    </div>

    <footer><div class="footer-bottom"><p>&copy; 2026 Student Hub Portal. Practical 7 - Records Viewer.</p></div></footer>
</body>
</html>
