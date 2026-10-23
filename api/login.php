<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

session_start();

function respond(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

if (!str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
    respond(415, ['success' => false, 'message' => 'JSON required.']);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    respond(400, ['success' => false, 'message' => 'Invalid request.']);
}

// TAMBAH ROLE: Get selected role
$role = trim((string)($input['role'] ?? ''));

$username = trim((string)($input['username'] ?? ''));
$password = $input['password'] ?? '';

// TAMBAH ROLE: Require selected role
if ($role === '' || $username === '' || !is_string($password) || $password === '') {
    respond(400, [
        'success' => false,
        'message' => 'Role, username and password are required.'
    ]);
}

if (strlen($username) > 120 || strlen($password) > 4096) {
    respond(400, ['success' => false, 'message' => 'Invalid credentials.']);
}

require_once __DIR__ . '/../config.php';

$sql = "SELECT id, user_code, name, username, email,
               password_hash, role, status, initials
        FROM users
        WHERE username = ?
        LIMIT 1";

$stmt = mysqli_prepare($link, $sql);

if (!$stmt) {
    error_log(mysqli_error($link));
    respond(500, ['success' => false, 'message' => 'Server error.']);
}

mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (
    !$user ||
    !password_verify($password, $user['password_hash']) ||
    $user['status'] !== 'Active'
) {
    respond(401, [
        'success' => false,
        'message' => 'Invalid username or password.'
    ]);
}

// TAMBAH ROLE: Compare selected role with database role
if ($user['role'] !== $role) {
    respond(401, [
        'success' => false,
        'message' => 'Invalid login credentials.'
    ]);
}

session_regenerate_id(true);

$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['name'] = $user['name'];
$_SESSION['username'] = $user['username'];

respond(200, [
    'success' => true,
    'message' => 'Login successful.',
    'user' => [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'username' => $user['username'],
        'role' => $user['role'],
        'initials' => $user['initials']
    ]
]);