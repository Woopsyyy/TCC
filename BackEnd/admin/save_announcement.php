<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header('HTTP/1.1 403 Forbidden'); exit('Forbidden');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /TCC/public/admin_dashboard.php?section=announcements'); exit(); }

require_once __DIR__ . '/../database/db.php';

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$year = $_POST['year'] ?? '';
$department = $_POST['department'] ?? '';
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($title === '' || $content === '') {
  header('Location: /TCC/public/admin_dashboard.php?section=announcements&error=missing'); exit();
}

$conn = Database::getInstance()->getConnection();
if ($id > 0) {
    $stmt = $conn->prepare("UPDATE announcements SET title=?, content=?, year=?, department=? WHERE id = ?");
    $stmt->bind_param('ssssi', $title, $content, $year, $department, $id);
    $stmt->execute();
    // audit
  $a = $_SESSION['username'] ?? null;
  $stmt2 = $conn->prepare("INSERT INTO audit_log (admin_user, action, target_table, target_id, details) VALUES (?,?,?,?,?)");
  $details = "updated announcement id=$id";
  $act = 'update';
  $t = 'announcements';
  $id_s = (string)$id;
  $stmt2->bind_param('sssss', $a, $act, $t, $id_s, $details);
  $stmt2->execute();
} else {
    $stmt = $conn->prepare("INSERT INTO announcements (title, content, year, department, date) VALUES (?,?,?,?,NOW())");
    $stmt->bind_param('ssss', $title, $content, $year, $department);
    $stmt->execute();
    $newId = $stmt->insert_id;
    // audit
  $a = $_SESSION['username'] ?? null;
  $stmt2 = $conn->prepare("INSERT INTO audit_log (admin_user, action, target_table, target_id, details) VALUES (?,?,?,?,?)");
  $details = "created announcement id=$newId";
  $act = 'create';
  $t = 'announcements';
  $id_s = (string)$newId;
  $stmt2->bind_param('sssss', $a, $act, $t, $id_s, $details);
  $stmt2->execute();
}

header('Location: /TCC/public/admin_dashboard.php?section=announcements&success=1');
exit();
