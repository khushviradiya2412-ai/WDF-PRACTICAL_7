<?php
// Practical 7: shared helpers — sanitization, validation, CSV/JSON storage, CSRF.
// Required by: PAGES/register.php, PAGES/view-records.php

// Normalize submitted text. Escape it only when rendering HTML with esc().
function sanitize_input($data) {
    return is_scalar($data) ? trim((string)$data) : '';
}

// Allowed course values (must match <select> options in register.php).
function allowed_courses() {
    return array(
        'Computer Engineering',
        'Information Technology',
        'Electronics Engineering',
        'Mechanical Engineering',
        'Civil Engineering'
    );
}

// Validate sanitized registration data. Returns array of field => message.
function validate_registration($d) {
    $errors = array();

    // Full name: required, 2-60 chars, letters/spaces/'/-
    if (!isset($d['fullname']) || $d['fullname'] === '') {
        $errors['fullname'] = 'Full name is required.';
    } elseif (mb_strlen($d['fullname']) < 2 || mb_strlen($d['fullname']) > 60) {
        $errors['fullname'] = 'Full name must be 2-60 characters.';
    } elseif (!preg_match("/^[a-zA-Z\\s'\\-]+$/", $d['fullname'])) {
        $errors['fullname'] = 'Name can contain only letters, spaces, apostrophes and hyphens.';
    }

    // Email: required + valid format
    if (!isset($d['email']) || $d['email'] === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    // Phone: required, exactly 10 digits (spaces/dashes ignored, +91 prefix allowed)
    $rawPhone = isset($d['phone']) ? $d['phone'] : '';
    $digits = preg_replace('/[\\s\\-()]/', '', $rawPhone);
    $digits = preg_replace('/^\\+91/', '', $digits);
    if ($rawPhone === '') {
        $errors['phone'] = 'Mobile number is required.';
    } elseif (!preg_match('/^[0-9]{10}$/', $digits)) {
        $errors['phone'] = 'Mobile number must be exactly 10 digits.';
    }

    // Date of birth: optional for old records, required for new registrations
    if (isset($d['dob']) && $d['dob'] !== '') {
        $ts = strtotime($d['dob']);
        if ($ts === false) {
            $errors['dob'] = 'Enter a valid date of birth.';
        } elseif ($ts > time()) {
            $errors['dob'] = 'Date of birth cannot be in the future.';
        }
    }

    // Gender: optional for old records, validate only if supplied
    if (isset($d['gender']) && $d['gender'] !== '') {
        $allowedGenders = array('female', 'male', 'non-binary', 'prefer-not-to-say');
        if (!in_array($d['gender'], $allowedGenders, true)) {
            $errors['gender'] = 'Invalid gender selected.';
        }
    }

    // Student ID: required, 4-20 chars, letters/digits/hyphen
    if (!isset($d['studentid']) || $d['studentid'] === '') {
        $errors['studentid'] = 'Student ID is required.';
    } elseif (!preg_match('/^[A-Za-z0-9\\-]{4,20}$/', $d['studentid'])) {
        $errors['studentid'] = 'Student ID must be 4-20 characters (letters, digits, hyphen).';
    }

    // Course: must be one of the whitelist
    if (!isset($d['course']) || $d['course'] === '') {
        $errors['course'] = 'Please select your course / branch.';
    } elseif (!in_array($d['course'], allowed_courses(), true)) {
        $errors['course'] = 'Invalid course selected.';
    }

    // Year: integer 2000-2099
    if (!isset($d['year']) || $d['year'] === '') {
        $errors['year'] = 'Enrollment year is required.';
    } elseif (!filter_var($d['year'], FILTER_VALIDATE_INT, array('options' => array('min_range' => 2000, 'max_range' => 2099)))) {
        $errors['year'] = 'Year must be a number between 2000 and 2099.';
    }

    // Password: min 8 chars + upper + lower + digit + special
    if (!isset($d['password']) || $d['password'] === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($d['password']) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $d['password']) || !preg_match('/[a-z]/', $d['password']) || !preg_match('/[0-9]/', $d['password']) || !preg_match('/[^A-Za-z0-9]/', $d['password'])) {
        $errors['password'] = 'Password needs uppercase, lowercase, number and special character.';
    }

    // Confirm password must match
    if (!isset($d['confirmPassword']) || $d['confirmPassword'] === '') {
        $errors['confirmPassword'] = 'Please confirm your password.';
    } elseif ($d['password'] !== $d['confirmPassword']) {
        $errors['confirmPassword'] = 'Passwords do not match.';
    }

    // Terms must be accepted
    if (empty($d['terms'])) {
        $errors['terms'] = 'You must agree to the Terms of Service.';
    }

    return $errors;
}

// Append one record to CSV safely (LOCK_EX). Creates header row if new file.
function save_to_csv($csvFile, $record) {
    $isNew = !file_exists($csvFile) || filesize($csvFile) === 0;
    $fp = fopen($csvFile, 'a');
    if ($fp === false) return false;
    $ok = false;
    if (flock($fp, LOCK_EX)) {
        if ($isNew) {
            fputcsv($fp, array('Timestamp', 'Full Name', 'Email', 'Phone', 'Student ID', 'Course', 'Year'));
        }
        $ok = fputcsv($fp, array(
            $record['timestamp'], $record['fullname'], $record['email'],
            $record['phone'], $record['studentid'], $record['course'], $record['year']
        )) !== false;
        flock($fp, LOCK_UN);
    }
    fclose($fp);
    return $ok;
}

// Append one record to JSON safely (read-modify-write with LOCK_EX).
function save_to_json($jsonFile, $record) {
    $rows = array();
    if (file_exists($jsonFile)) {
        $raw = file_get_contents($jsonFile);
        if ($raw !== false && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $rows = $decoded;
        }
    }
    $rows[] = $record;
    $json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) return false;
    return file_put_contents($jsonFile, $json, LOCK_EX) !== false;
}

// Create/return the session CSRF token.
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify posted CSRF token against session (timing-safe).
function verify_csrf($posted) {
    return isset($_SESSION['csrf_token']) && is_string($posted)
        && hash_equals($_SESSION['csrf_token'], $posted);
}

// --- Session / auth helpers: pages show only the authenticated session user ---
function set_current_user($record) {
    $_SESSION['user'] = $record;
}

function get_current_user_record() {
    if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    return null;
}

function require_login_or_redirect($loginUrl = 'login.php') {
    $u = get_current_user_record();
    if (!$u) {
        header('Location: ' . $loginUrl);
        exit;
    }
    return $u;
}

// Find a user by email or student ID in JSON storage.
function find_user($jsonFile, $identifier) {
    if (!file_exists($jsonFile)) return null;
    $decoded = json_decode(file_get_contents($jsonFile), true);
    if (!is_array($decoded)) return null;
    $id = strtolower(trim($identifier));
    foreach ($decoded as $r) {
        if (strtolower(trim(isset($r['email']) ? $r['email'] : '')) === $id ||
            strtolower(trim(isset($r['studentid']) ? $r['studentid'] : '')) === $id) {
            return $r;
        }
    }
    return null;
}

// Duplicate check: email or student ID already registered?
function is_duplicate($jsonFile, $email, $studentid) {
    if (!file_exists($jsonFile)) return false;
    $decoded = json_decode(file_get_contents($jsonFile), true);
    if (!is_array($decoded)) return false;
    foreach ($decoded as $r) {
        if (strcasecmp(trim(isset($r['email']) ? $r['email'] : ''), trim($email)) === 0) return 'email';
        if (strcasecmp(trim(isset($r['studentid']) ? $r['studentid'] : ''), trim($studentid)) === 0) return 'studentid';
    }
    return false;
}

function esc($v) {
    return htmlspecialchars(isset($v) ? (string)$v : '', ENT_QUOTES, 'UTF-8');
}

function gender_label($g) {
    $map = array('female'=>'Female','male'=>'Male','non-binary'=>'Non-binary','prefer-not-to-say'=>'Prefer not to say');
    return isset($map[$g]) ? $map[$g] : ($g !== '' ? $g : '—');
}
