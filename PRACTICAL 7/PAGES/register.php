<?php
// ============================================================================
// Practical 7: PHP Form Processing with Server-Side Validation + CSV/JSON Storage
// Key questions answered here:
//  1. Form submitted using POST?  -> Yes: method="POST", checked via REQUEST_METHOD.
//  2. Validated + sanitized server side? -> sanitize_input() + validate_registration().
//  3. File writing handled safely? -> flock(LOCK_EX), fputcsv, atomic JSON write.
//  4. Success/error displayed clearly? -> top summary + per-field messages.
// Run: copy "PRACTICAL 7" to C:\xampp\htdocs\, start Apache,
//      open http://localhost/PRACTICAL%207/PAGES/register.php
// ============================================================================
session_start();
require_once __DIR__ . '/../PHP/functions.php';

$csvFile  = __DIR__ . '/../DATA/registrations.csv';
$jsonFile = __DIR__ . '/../DATA/registrations.json';

// Sticky-form defaults
$old = array('fullname'=>'','email'=>'','phone'=>'','dob'=>'','gender'=>'','studentid'=>'','course'=>'','year'=>'');
$errors = array();
$success = '';
$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- CSRF check (Advanced extension) ---
    $postedToken = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!verify_csrf($postedToken)) {
        $formError = 'Invalid session token. Please reload the form and try again.';
    } else {
        // --- 1. SANITIZE every input on the server ---
        $data = array(
            'fullname'        => sanitize_input(isset($_POST['fullname']) ? $_POST['fullname'] : ''),
            'email'           => sanitize_input(isset($_POST['email']) ? $_POST['email'] : ''),
            'phone'           => sanitize_input(isset($_POST['phone']) ? $_POST['phone'] : ''),
            'dob'             => sanitize_input(isset($_POST['dob']) ? $_POST['dob'] : ''),
            'gender'          => sanitize_input(isset($_POST['gender']) ? $_POST['gender'] : ''),
            'studentid'       => sanitize_input(isset($_POST['studentid']) ? $_POST['studentid'] : ''),
            'course'          => sanitize_input(isset($_POST['course']) ? $_POST['course'] : ''),
            'year'            => sanitize_input(isset($_POST['year']) ? $_POST['year'] : ''),
            'password'        => isset($_POST['password']) ? $_POST['password'] : '',        // don't HTML-escape before hashing
            'confirmPassword' => isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '',
            'terms'           => isset($_POST['terms']) ? '1' : ''
        );

        // Keep sticky values (never refill passwords)
        $old = array(
            'fullname'=>$data['fullname'],'email'=>$data['email'],'phone'=>$data['phone'],
            'dob'=>$data['dob'],'gender'=>$data['gender'],
            'studentid'=>$data['studentid'],'course'=>$data['course'],'year'=>$data['year']
        );

        // --- 2. VALIDATE on the server ---
        $errors = validate_registration($data);

        // dob + gender are required on the PHP form (matches REGISTER.HTML)
        if ($data['dob'] === '') {
            $errors['dob'] = 'Date of birth is required.';
        }
        if ($data['gender'] === '') {
            $errors['gender'] = 'Please select a gender option.';
        }

        // Duplicate email / student ID check
        if (empty($errors)) {
            $dup = is_duplicate($jsonFile, $data['email'], $data['studentid']);
            if ($dup === 'email') {
                $errors['email'] = 'This email is already registered. Try logging in.';
            } elseif ($dup === 'studentid') {
                $errors['studentid'] = 'This Student ID is already registered.';
            }
        }

        // --- 3. STORE in CSV + JSON if valid ---
        if (empty($errors)) {
            $record = array(
                'timestamp' => date('Y-m-d H:i:s'),
                'fullname'  => $data['fullname'],
                'email'     => $data['email'],
                'phone'     => preg_replace('/[\\s\\-()]/', '', preg_replace('/^\\+91/', '', $data['phone'])),
                'dob'       => $data['dob'],
                'gender'    => $data['gender'],
                'studentid' => $data['studentid'],
                'course'    => $data['course'],
                'year'      => $data['year'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT)
            );
            $csvOk  = save_to_csv($csvFile, $record);
            $jsonOk = save_to_json($jsonFile, $record);

            if ($csvOk && $jsonOk) {
                // Log the user in immediately and go to THEIR dashboard
                $public = $record;
                unset($public['password_hash']);
                session_regenerate_id(true);
                set_current_user($public);
                unset($_SESSION['csrf_token']);
                header('Location: dashboard.php');
                exit;
            } else {
                $formError = 'Validation passed but file storage failed. Check that DATA/ is writable.';
            }
        }
    }
}
$token = csrf_token();

// Helper: echo a per-field error paragraph
function field_error($errors, $name) {
    if (isset($errors[$name])) {
        return '<p class="field-error">' . htmlspecialchars($errors[$name], ENT_QUOTES, 'UTF-8') . '</p>';
    }
    return '<p class="field-error"></p>';
}

// Helper: validation class + aria attribute for full-field red/green styling.
// Neutral on first load; after POST -> is-invalid (red) or is-valid (green).
function field_attrs($errors, $old, $name, $isPost) {
    if (!$isPost) return '';
    if (isset($errors[$name])) return ' class="is-invalid" aria-invalid="true"';
    // passwords are never refilled, so keep them neutral on re-render
    if ($name === 'password' || $name === 'confirmPassword') {
        return '';
    }
    $v = isset($old[$name]) ? $old[$name] : '';
    if ($v !== '') return ' class="is-valid" aria-invalid="false"';
    return ' class="is-invalid" aria-invalid="true"';
}
$isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration (PHP) - Student Hub | Practical 7</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <link rel="stylesheet" href="../CSS/REGISTER.CSS">
    <link rel="stylesheet" href="../CSS/practical7.css">
</head>
<body class="registration-page">
    <nav class="navbar">
        <div class="nav-container">
            <span class="navbar-brand">&#127979; Student Hub</span>
            <div class="nav-menu">
                <a href="../HOME.HTML" class="nav-item">Dashboard</a>
                <a href="register.php" class="nav-item active">Register (PHP)</a>
                <a href="login.php" class="nav-item">Login (PHP)</a>
                <a href="dashboard.php" class="nav-item">My Dashboard</a>
                <a href="profile.php" class="nav-item">My Profile</a>
                <a href="view-records.php" class="nav-item">View Records</a>
                <a href="REGISTER.HTML" class="nav-item">Old HTML Form</a>
            </div>
        </div>
    </nav>

    <main class="auth-wrapper">
        <section class="auth-box registration-box" aria-labelledby="registration-title" style="max-width:650px">
            <h1 id="registration-title">Create Student Account (PHP)</h1>
            <p>Practical 7: POST + server validation + CSV/JSON storage</p>

            <?php if ($success !== ''): ?>
                <div class="form-summary success" role="alert">
                    <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                    <br><a href="view-records.php">View stored records &rarr;</a>
                </div>
            <?php endif; ?>

            <?php if ($formError !== ''): ?>
                <div class="form-summary" role="alert"><?php echo htmlspecialchars($formError, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="form-summary" role="alert">
                    Please fix <?php echo count($errors); ?> error(s) below and resubmit.
                </div>
            <?php endif; ?>

            <!-- method="POST" is REQUIRED for this practical (never GET for passwords) -->
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

                <fieldset><legend>Personal Information</legend>
                    <div class="form-group">
                        <label for="fullname">Full Name *</label>
                        <input type="text" id="fullname" name="fullname" maxlength="60" placeholder="e.g. Khush Viradiya"
                               value="<?php echo htmlspecialchars($old['fullname'], ENT_QUOTES, 'UTF-8'); ?>" required<?php echo field_attrs($errors, $old, 'fullname', $isPost); ?>>
                        <?php echo field_error($errors, 'fullname'); ?>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" placeholder="student@example.com"
                               value="<?php echo htmlspecialchars($old['email'], ENT_QUOTES, 'UTF-8'); ?>" required<?php echo field_attrs($errors, $old, 'email', $isPost); ?>>
                        <?php echo field_error($errors, 'email'); ?>
                    </div>
                    <div class="form-group">
                        <label for="phone">Mobile Number (10 digits) *</label>
                        <input type="tel" id="phone" name="phone" inputmode="tel" placeholder="9876543210"
                               value="<?php echo htmlspecialchars($old['phone'], ENT_QUOTES, 'UTF-8'); ?>" required<?php echo field_attrs($errors, $old, 'phone', $isPost); ?>>
                        <?php echo field_error($errors, 'phone'); ?>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="dob">Date of Birth *</label>
                            <input type="date" id="dob" name="dob"
                                   value="<?php echo htmlspecialchars($old['dob'], ENT_QUOTES, 'UTF-8'); ?>" required<?php echo field_attrs($errors, $old, 'dob', $isPost); ?>>
                            <?php echo field_error($errors, 'dob'); ?>
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender *</label>
                            <select id="gender" name="gender" required<?php echo field_attrs($errors, $old, 'gender', $isPost); ?>>
                                <option value="">Select gender</option>
                                <?php foreach (array('female'=>'Female','male'=>'Male','non-binary'=>'Non-binary','prefer-not-to-say'=>'Prefer not to say') as $gv=>$gl): ?>
                                    <option value="<?php echo $gv; ?>" <?php echo ($old['gender']===$gv)?'selected':''; ?>><?php echo $gl; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php echo field_error($errors, 'gender'); ?>
                        </div>
                    </div>
                </fieldset>

                <fieldset><legend>Academic Information</legend>
                    <div class="form-group">
                        <label for="studentid">Student ID / Roll No *</label>
                        <input type="text" id="studentid" name="studentid" placeholder="e.g. 25DCE123"
                               value="<?php echo htmlspecialchars($old['studentid'], ENT_QUOTES, 'UTF-8'); ?>" required<?php echo field_attrs($errors, $old, 'studentid', $isPost); ?>>
                        <?php echo field_error($errors, 'studentid'); ?>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="course">Course / Branch *</label>
                            <select id="course" name="course" required<?php echo field_attrs($errors, $old, 'course', $isPost); ?>>
                                <option value="">Select course</option>
                                <?php foreach (allowed_courses() as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php echo ($old['course'] === $c) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php echo field_error($errors, 'course'); ?>
                        </div>
                        <div class="form-group">
                            <label for="year">Enrollment Year *</label>
                            <input type="number" id="year" name="year" min="2000" max="2099" placeholder="2025"
                                   value="<?php echo htmlspecialchars($old['year'], ENT_QUOTES, 'UTF-8'); ?>" required<?php echo field_attrs($errors, $old, 'year', $isPost); ?>>
                            <?php echo field_error($errors, 'year'); ?>
                        </div>
                    </div>
                </fieldset>

                <fieldset><legend>Secure Your Account</legend>
                    <div class="form-group">
                        <label for="password">Password (min 8, upper + lower + digit + special) *</label>
                        <input type="password" id="password" name="password" autocomplete="new-password" required minlength="8"<?php echo field_attrs($errors, $old, 'password', $isPost); ?>>
                        <?php echo field_error($errors, 'password'); ?>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password *</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" autocomplete="new-password" required<?php echo field_attrs($errors, $old, 'confirmPassword', $isPost); ?>>
                        <?php echo field_error($errors, 'confirmPassword'); ?>
                    </div>
                </fieldset>

                <fieldset><legend>Agreement</legend>
                    <div class="checkbox-group">
                        <input type="checkbox" id="terms" name="terms" value="1" required>
                        <label for="terms">I agree to the Terms of Service and Privacy Policy. *</label>
                    </div>
                    <?php echo field_error($errors, 'terms'); ?>
                </fieldset>

                <button type="submit" class="btn btn-primary submit-button">&#10003; Create Account (PHP)</button>
            </form>

            <div class="auth-footer">
                <p><a href="view-records.php">View stored CSV/JSON records</a> (Intermediate extension)</p>
                <p><a class="home-link" href="../INDEX.HTML"><span aria-hidden="true">&#9000;</span> Home</a></p>
            </div>
        </section>
    </main>

    <footer><div class="footer-bottom"><p>&copy; 2026 Student Hub Portal. Practical 7 - PHP Form Processing.</p></div></footer>
    <script>
    // Live full-field red/green (mirrors screenshots): empty/invalid => pink+red, valid => green.
    (function () {
        function paint(el, valid) {
            el.classList.remove('is-valid', 'is-invalid');
            el.classList.add(valid ? 'is-valid' : 'is-invalid');
            el.setAttribute('aria-invalid', valid ? 'false' : 'true');
        }
        function check(el) {
            if (el.type === 'checkbox') return;
            if (el.tagName === 'SELECT') { paint(el, el.value !== ''); return; }
            if (el.value.trim() === '') { paint(el, false); return; }
            var v = el.value.trim(), ok = true;
            if (el.id === 'email') ok = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v);
            else if (el.id === 'phone') ok = /^[0-9]{10}$/.test(v.replace(/[\s\-()+]/g, '').replace(/^91/, ''));
            else if (el.id === 'password') ok = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(el.value);
            else if (el.id === 'confirmPassword') ok = el.value === document.getElementById('password').value && el.value !== '';
            paint(el, ok);
        }
        document.querySelectorAll('form input, form select').forEach(function (el) {
            el.addEventListener('blur', function () { check(el); });
            el.addEventListener('input', function () { check(el); });
            el.addEventListener('change', function () { check(el); });
        });
        // On submit, paint empty required fields red immediately (like screenshot 1)
        document.querySelector('form').addEventListener('submit', function () {
            document.querySelectorAll('form input[required], form select[required]').forEach(check);
        });
    })();
    </script>
</body>
</html>
