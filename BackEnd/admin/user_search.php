<?php
// Server-side user search endpoint for admin autocomplete
if (session_status() === PHP_SESSION_NONE) session_start();
// require admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'forbidden']);
    exit();
}
require_once __DIR__ . '/../database/db.php';
$conn = Database::getInstance()->getConnection();

$q = trim($_GET['q'] ?? '');
$limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$out = ['results' => [], 'meta' => ['q' => $q, 'limit' => $limit, 'page' => $page]];
if ($q === '') {
    echo json_encode($out);
    exit();
}

$like = '%' . $q . '%';
$stmt = $conn->prepare("SELECT id, full_name, username FROM users WHERE full_name LIKE ? OR username LIKE ? ORDER BY full_name LIMIT ? OFFSET ?");
if ($stmt) {
    $stmt->bind_param('ssii', $like, $like, $limit, $offset);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $out['results'][] = $r;
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($out);
