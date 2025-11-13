<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  http_response_code(403);
  echo json_encode(['error' => 'Forbidden']);
  exit();
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 12;
$role = isset($_GET['role']) ? trim($_GET['role']) : '';

if (strlen($q) < 2) {
  echo json_encode(['results' => []]);
  exit();
}

require_once __DIR__ . '/../database/db.php';
$conn = Database::getInstance()->getConnection();

$like = '%' . $conn->real_escape_string($q) . '%';
if ($role === 'teacher') {
  $stmt = $conn->prepare("SELECT id, username, full_name FROM users WHERE (username LIKE ? OR full_name LIKE ?) AND role = 'teacher' ORDER BY full_name, username LIMIT ?");
} else {
  $stmt = $conn->prepare("SELECT id, username, full_name FROM users WHERE username LIKE ? OR full_name LIKE ? ORDER BY full_name, username LIMIT ?");
}
$stmt->bind_param('ssi', $like, $like, $limit);
$stmt->execute();
$res = $stmt->get_result();

$results = [];
while ($row = $res->fetch_assoc()) {
  $results[] = [
    'id' => (int)$row['id'],
    'username' => $row['username'],
    'full_name' => $row['full_name'] ?? $row['username']
  ];
}

echo json_encode(['results' => $results]);
$stmt->close();
?>
