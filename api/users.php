
<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

session_start();

function reply(int $code, array $body): never {
    http_response_code($code);
    echo json_encode($body);
    exit;
}

// Authentication
if (empty($_SESSION['user_id'])) {
    reply(401, [
        'success' => false,
        'message' => 'Please log in.'
    ]);
}

// Role authorization
if (!in_array($_SESSION['role'] ?? '', ['Owner', 'Shop Admin'], true)) {
    reply(403, [
        'success' => false,
        'message' => 'Access denied.'
    ]);
}

require_once __DIR__ . '/../config.php';

// CSRF token for write operations
if (!isset($_SESSION['users_csrf'])) {
    $_SESSION['users_csrf'] = bin2hex(random_bytes(32));
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ===============================
// GET - Retrieve users
// ===============================

if ($method === 'GET') {
    $sql = "
        SELECT
            id,
            user_code,
            name,
            username,
            email,
            role,
            status,
            initials,
            last_active
        FROM users
        WHERE role <> 'Owner'
        ORDER BY id DESC
    ";

    $result = mysqli_query($link, $sql);

    if (!$result) {
        reply(500, [
            'success' => false,
            'message' => 'Unable to load users.'
        ]);
    }

    reply(200, [
        'success' => true,
        'csrf' => $_SESSION['users_csrf'],
        'users' => mysqli_fetch_all($result, MYSQLI_ASSOC)
    ]);
}

// Only POST is allowed for modifications
if ($method !== 'POST') {
    reply(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
}

// Validate CSRF token
$submittedToken = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

if (!hash_equals($_SESSION['users_csrf'], $submittedToken)) {
    reply(403, [
        'success' => false,
        'message' => 'Invalid security token. Refresh the page.'
    ]);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    reply(400, [
        'success' => false,
        'message' => 'Invalid JSON.'
    ]);
}

$action = $input['action'] ?? '';
$id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);

// ===============================
// DELETE USER
// ===============================

if ($action === 'delete') {
    if (!$id) {
        reply(400, [
            'success' => false,
            'message' => 'Invalid user ID.'
        ]);
    }

    if ((int)$id === (int)$_SESSION['user_id']) {
        reply(400, [
            'success' => false,
            'message' => 'You cannot delete your own account.'
        ]);
    }

    $stmt = mysqli_prepare(
        $link,
        "DELETE FROM users WHERE id = ? AND role <> 'Owner'"
    );

    mysqli_stmt_bind_param($stmt, 'i', $id);

    try {
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());

        reply(409, [
            'success' => false,
            'message' => 'Cannot delete this user because related records exist. Set the account to Inactive instead.'
        ]);
    }

    if (!$affected) {
        reply(404, [
            'success' => false,
            'message' => 'User not found.'
        ]);
    }

    reply(200, [
        'success' => true,
        'message' => 'User deleted.'
    ]);
}

// ===============================
// CREATE / UPDATE USER
// ===============================

if (!in_array($action, ['create', 'update'], true)) {
    reply(400, [
        'success' => false,
        'message' => 'Invalid action.'
    ]);
}

$name = trim((string)($input['name'] ?? ''));
$username = trim((string)($input['username'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$role = (string)($input['role'] ?? '');
$status = (string)($input['status'] ?? '');
$password = $input['password'] ?? '';

// Validate user details
if (
    !is_string($password) ||
    $name === '' ||
    strlen($name) > 100 ||
    !preg_match('/^[^@\s]+@prism$/i', $username) ||
    strlen($username) > 120 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($email) > 150 ||
    !in_array($role, ['Shop Admin', 'Technician', 'Salesperson'], true) ||
    !in_array($status, ['Active', 'Inactive'], true)
) {
    reply(400, [
        'success' => false,
        'message' => 'Please check the user details. Username must use name@prism.'
    ]);
}

if ($password !== '' && strlen($password) < 8) {
    reply(400, [
        'success' => false,
        'message' => 'New passwords must contain at least 8 characters.'
    ]);
}

if (strlen($password) > 4096) {
    reply(400, [
        'success' => false,
        'message' => 'Password is too long.'
    ]);
}

// Generate initials
$words = preg_split('/\s+/', $name);
$initials = '';

foreach (array_slice($words, 0, 2) as $word) {
    $initials .= strtoupper(substr($word, 0, 1));
}

try {

    // ===============================
    // ADD USER
    // ===============================

    if ($action === 'create') {

        if ($password === '') {
            reply(400, [
                'success' => false,
                'message' => 'Password is required for a new user.'
            ]);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $tempCode = 'TEMP-' . bin2hex(random_bytes(5));

        $sql = "
            INSERT INTO users
            (
                user_code,
                name,
                username,
                email,
                password_hash,
                role,
                status,
                initials
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = mysqli_prepare($link, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            'ssssssss',
            $tempCode,
            $name,
            $username,
            $email,
            $hash,
            $role,
            $status,
            $initials
        );

        mysqli_stmt_execute($stmt);

        $newId = mysqli_insert_id($link);

        mysqli_stmt_close($stmt);

        // Generate user code, e.g. USR-005
        $userCode = sprintf('USR-%03d', $newId);

        $stmt = mysqli_prepare(
            $link,
            'UPDATE users SET user_code = ? WHERE id = ?'
        );

        mysqli_stmt_bind_param(
            $stmt,
            'si',
            $userCode,
            $newId
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

    } else {

        // ===============================
        // EDIT USER
        // ===============================

        if (!$id) {
            reply(400, [
                'success' => false,
                'message' => 'Invalid user ID.'
            ]);
        }

        // Check user exists and is not Owner
        $check = mysqli_prepare(
            $link,
            "SELECT id FROM users WHERE id = ? AND role <> 'Owner'"
        );

        mysqli_stmt_bind_param($check, 'i', $id);
        mysqli_stmt_execute($check);

        $result = mysqli_stmt_get_result($check);
        $existing = mysqli_fetch_assoc($result);

        mysqli_stmt_close($check);

        if (!$existing) {
            reply(404, [
                'success' => false,
                'message' => 'User not found.'
            ]);
        }

        // Prevent changing own role or deactivating own account
        if (
            (int)$id === (int)$_SESSION['user_id'] &&
            ($role !== $_SESSION['role'] || $status !== 'Active')
        ) {
            reply(400, [
                'success' => false,
                'message' => 'You cannot change your own role or deactivate your own account.'
            ]);
        }

        if ($password !== '') {

            // Update including new password
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "
                UPDATE users
                SET
                    name = ?,
                    username = ?,
                    email = ?,
                    password_hash = ?,
                    role = ?,
                    status = ?,
                    initials = ?
                WHERE id = ?
            ";

            $stmt = mysqli_prepare($link, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                'sssssssi',
                $name,
                $username,
                $email,
                $hash,
                $role,
                $status,
                $initials,
                $id
            );

        } else {

            // Update without changing password
            $sql = "
                UPDATE users
                SET
                    name = ?,
                    username = ?,
                    email = ?,
                    role = ?,
                    status = ?,
                    initials = ?
                WHERE id = ?
            ";

            $stmt = mysqli_prepare($link, $sql);

            mysqli_stmt_bind_param(
                $stmt,
                'ssssssi',
                $name,
                $username,
                $email,
                $role,
                $status,
                $initials,
                $id
            );
        }

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());

    reply(409, [
        'success' => false,
        'message' => 'Unable to save user. Username, email or user code may already exist.'
    ]);
}

reply(200, [
    'success' => true,
    'message' => 'User saved successfully.'
]);
