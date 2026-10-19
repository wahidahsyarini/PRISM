<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}
require_once __DIR__ . '/../config.php';
$stmt = mysqli_prepare($link, 'SELECT id, name, username, role, status, initials FROM users WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
if (!$user || $user['status'] !== 'Active') {
    $_SESSION = [];
    session_destroy();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}
$_SESSION['role'] = $user['role'];
$_SESSION['name'] = $user['name'];
$_SESSION['username'] = $user['username'];
echo json_encode(['success' => true, 'user' => ['id' => (int)$user['id'], 'name' => $user['name'], 'username' => $user['username'], 'role' => $user['role'], 'initials' => $user['initials']]]);
