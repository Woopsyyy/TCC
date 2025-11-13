<?php
require_once __DIR__ . '/../helpers/admin_helpers.php';
require_admin_post('/TCC/public/admin_dashboard.php?section=announcements');

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
    log_audit($conn, 'update', 'announcements', $id, "updated announcement id=$id");
} else {
    $stmt = $conn->prepare("INSERT INTO announcements (title, content, year, department, date) VALUES (?,?,?,?,NOW())");
    $stmt->bind_param('ssss', $title, $content, $year, $department);
    $stmt->execute();
    $newId = $stmt->insert_id;
    log_audit($conn, 'create', 'announcements', $newId, "created announcement id=$newId");
}

header('Location: /TCC/public/admin_dashboard.php?section=announcements&success=1');
exit();
