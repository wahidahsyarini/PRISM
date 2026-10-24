
<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

session_start();

function reply($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if (empty($_SESSION['user_id'])) {
    reply(401, [
        'success' => false,
        'message' => 'Please log in.'
    ]);
}

if (!in_array($_SESSION['role'] ?? '', ['Owner', 'Shop Admin'], true)) {
    reply(403, [
        'success' => false,
        'message' => 'Access denied.'
    ]);
}

require_once __DIR__ . '/../config.php';

if (empty($_SESSION['users_csrf'])) {
    $_SESSION['users_csrf'] = bin2hex(random_bytes(32));
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Read users
if ($method === 'GET') {
    $sql = "
        SELECT id, user_code, name, username, email,
               role, status, initials, last_active
        FROM users
        WHERE role <> 'Owner'
        ORDER BY id DESC
    ";

    try {
        $result = mysqli_query($link, $sql);

        reply(200, [
            'success' => true,
            'csrf' => $_SESSION['users_csrf'],
            'users' => mysqli_fetch_all($result, MYSQLI_ASSOC)
        ]);

    } catch (Throwable $e) {
        error_log($e->getMessage());
        reply(500, [
            'success' => false,
            'message' => 'Unable to load users.'
        ]);
    }
}

if ($method !== 'POST') {
    reply(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!hash_equals($_SESSION['users_csrf'], $token)) {
    reply(403, [
        'success' => false,
        'message' => 'Invalid security token. Refresh the page.'
    ]);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    reply(400, [
        'success' => false,
        'message' => 'Invalid request.'
    ]);
}

$action = $input['action'] ?? '';
$id = filter_var(
    $input['id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

// Retrieve one non-Owner user
function getUser($link, $id) {
    $stmt = mysqli_prepare(
        $link,
        "SELECT id, name, role, status
         FROM users
         WHERE id = ? AND role <> 'Owner'
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    return $user ?: null;
}


/* Activity Log */

if ($action === 'activity') {

    if (!$id) {
        reply(400, [
            'success' => false,
            'message' => 'Invalid user ID.'
        ]);
    }

    $user = getUser($link, $id);

    if (!$user) {
        reply(404, [
            'success' => false,
            'message' => 'User not found.'
        ]);
    }

    try {

        $sql = "
            SELECT
                action,
                reference AS note,
                created_at
            FROM activity_log
            WHERE user_name = ?
            ORDER BY created_at DESC
            LIMIT 100
        ";

        $stmt = mysqli_prepare($link, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            's',
            $user['name']
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $activities = mysqli_fetch_all(
            $result,
            MYSQLI_ASSOC
        );

        mysqli_stmt_close($stmt);

        reply(200, [
            'success' => true,
            'activities' => $activities
        ]);

    } catch (Throwable $e) {

        error_log($e->getMessage());

        reply(500, [
            'success' => false,
            'message' => 'Unable to load Activity Log.'
        ]);

    }
}


// Delete inactive user only
if ($action === 'delete') {
    if (!$id) {
        reply(400, [
            'success' => false,
            'message' => 'Invalid user ID.'
        ]);
    }

    if ((int)$id === (int)$_SESSION['user_id']) {
        reply(403, [
            'success' => false,
            'message' => 'You cannot delete your own account.'
        ]);
    }

    try {
        $user = getUser($link, $id);

        if (!$user) {
            reply(404, [
                'success' => false,
                'message' => 'User not found.'
            ]);
        }

        if ($user['status'] !== 'Inactive') {
            reply(403, [
                'success' => false,
                'message' => 'Only Inactive users can be deleted.'
            ]);
        }

        $stmt = mysqli_prepare(
            $link,
            "DELETE FROM users
             WHERE id = ?
               AND status = 'Inactive'
               AND role <> 'Owner'"
        );

        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);

        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($affected === 0) {
            reply(409, [
                'success' => false,
                'message' => 'User could not be deleted.'
            ]);
        }

        reply(200, [
            'success' => true,
            'message' => 'Inactive user deleted successfully.'
        ]);

    } catch (Throwable $e) {
        error_log($e->getMessage());

        reply(409, [
            'success' => false,
            'message' => 'Cannot delete user because related records may exist. Keep the account Inactive instead.'
        ]);
    }
}

// Create / Update
if (!in_array($action, ['create', 'update'], true)) {
    reply(400, [
        'success' => false,
        'message' => 'Invalid action.'
    ]);
}

$name = trim((string)($input['name'] ?? ''));
$username = trim((string)($input['username'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$password = $input['password'] ?? '';
$role = (string)($input['role'] ?? '');
$status = (string)($input['status'] ?? '');

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
        'message' => 'Please check all fields. Username must use name@prism.'
    ]);
}

if ($password !== '' && strlen($password) < 8) {
    reply(400, [
        'success' => false,
        'message' => 'Password must contain at least 8 characters.'
    ]);
}

if (strlen($password) > 4096) {
    reply(400, [
        'success' => false,
        'message' => 'Password is too long.'
    ]);
}

$parts = preg_split('/\s+/', $name);
$initials = '';

foreach (array_slice($parts, 0, 2) as $part) {
    $initials .= strtoupper(substr($part, 0, 1));
}

try {
    if ($action === 'create') {
        if ($password === '') {
            reply(400, [
                'success' => false,
                'message' => 'Password is required.'
            ]);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $tempCode = 'TMP-' . bin2hex(random_bytes(5));

        mysqli_begin_transaction($link);

        $stmt = mysqli_prepare(
            $link,
            "INSERT INTO users
             (user_code, name, username, email,
              password_hash, role, status, initials)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

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

        $userCode = sprintf('USR-%04d', $newId);

        $stmt = mysqli_prepare(
            $link,
            "UPDATE users SET user_code = ? WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, 'si', $userCode, $newId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($link);

        reply(200, [
            'success' => true,
            'message' => 'User added successfully.'
        ]);
    }

    // Update
    if (!$id) {
        reply(400, [
            'success' => false,
            'message' => 'Invalid user ID.'
        ]);
    }

    $existing = getUser($link, $id);

    if (!$existing) {
        reply(404, [
            'success' => false,
            'message' => 'User not found.'
        ]);
    }

    if (
        (int)$id === (int)$_SESSION['user_id'] &&
        ($role !== $_SESSION['role'] || $status !== 'Active')
    ) {
        reply(403, [
            'success' => false,
            'message' => 'You cannot change your own role or deactivate your account.'
        ]);
    }

    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare(
            $link,
            "UPDATE users
             SET name=?, username=?, email=?,
                 password_hash=?, role=?, status=?, initials=?
             WHERE id=? AND role <> 'Owner'"
        );

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
        $stmt = mysqli_prepare(
            $link,
            "UPDATE users
             SET name=?, username=?, email=?,
                 role=?, status=?, initials=?
             WHERE id=? AND role <> 'Owner'"
        );

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

    // Keep the current session details consistent
    if ((int)$id === (int)$_SESSION['user_id']) {
        $_SESSION['name'] = $name;
        $_SESSION['username'] = $username;
    }

    reply(200, [
        'success' => true,
        'message' => 'User updated successfully.'
    ]);

} catch (Throwable $e) {
    if ($action === 'create') {
        try {
            mysqli_rollback($link);
        } catch (Throwable $ignored) {
        }
    }

    error_log($e->getMessage());

    reply(409, [
        'success' => false,
        'message' => 'Unable to save user. Check for duplicate username or email.'
    ]);
}
