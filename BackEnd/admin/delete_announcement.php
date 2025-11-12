<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('HTTP/1.1 403 Forbidden'); exit('Forbidden'); }
require_once __DIR__ . '/../database/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /TCC/public/admin_dashboard.php?section=announcements'); exit(); }

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) { header('Location: /TCC/public/admin_dashboard.php?section=announcements&error=missing'); exit(); }

$conn = Database::getInstance()->getConnection();
$stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();

$a = $_SESSION['username'] ?? null;
$stmt2 = $conn->prepare("INSERT INTO audit_log (admin_user, action, target_table, target_id, details) VALUES (?,?,?,?,?)");
$details = "deleted announcement id=$id";
$act = 'delete';
$t = 'announcements';
$id_s = (string)$id;
$stmt2->bind_param('sssss', $a, $act, $t, $id_s, $details);
$stmt2->execute();

header('Location: /TCC/public/admin_dashboard.php?section=announcements&deleted=1');
exit();
