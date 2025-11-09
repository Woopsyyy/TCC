<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('HTTP/1.1 403 Forbidden'); exit('Forbidden'); }
require_once __DIR__ . '/../database/db.php';
$conn = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /TCC/public/admin/unmapped_assignments.php'); exit(); }

$assignmentId = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
$userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

if ($assignmentId <= 0 || $userId <= 0) {
    header('Location: /TCC/public/admin/unmapped_assignments.php?error=invalid'); exit();
}

// verify user exists
$ps = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
if (!$ps) { header('Location: /TCC/public/admin/unmapped_assignments.php?error=dberr'); exit(); }
$ps->bind_param('i', $userId); $ps->execute(); $gres = $ps->get_result();
if (!$gres || $gres->num_rows == 0) { header('Location: /TCC/public/admin/unmapped_assignments.php?error=usernotfound'); exit(); }
$ps->close();

// update assignment
$up = $conn->prepare("UPDATE user_assignments SET user_id = ? WHERE id = ?");
if (!$up) { header('Location: /TCC/public/admin/unmapped_assignments.php?error=dberr2'); exit(); }
$up->bind_param('ii', $userId, $assignmentId); $up->execute();

// audit
$a = $_SESSION['username'] ?? null; $act = 'map_assignment'; $t = 'user_assignments'; $id_s = (string)$assignmentId; $details = "mapped assignment {$assignmentId} -> user_id {$userId}";
$l = $conn->prepare("INSERT INTO audit_log (admin_user, action, target_table, target_id, details) VALUES (?,?,?,?,?)");
if ($l) { $l->bind_param('sssss', $a, $act, $t, $id_s, $details); $l->execute(); }

header('Location: /TCC/public/admin/unmapped_assignments.php?success=1'); exit();
