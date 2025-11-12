<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
  header('HTTP/1.1 403 Forbidden'); 
  exit('Forbidden'); 
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
  header('Location: /TCC/public/admin_dashboard.php?section=grade_system'); 
  exit(); 
}

require_once __DIR__ . '/../database/db.php';
$conn = Database::getInstance()->getConnection();

$action = $_POST['action'] ?? 'create';

if ($action === 'delete') {
  // Delete grade record
  $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
  if ($id <= 0) { 
    header('Location: /TCC/public/admin_dashboard.php?section=grade_system&error=invalid_id'); 
    exit(); 
  }
  
  // Get grade info for audit log
  $sel = $conn->prepare("SELECT username, year, semester, subject FROM student_grades WHERE id = ? LIMIT 1");
  $sel->bind_param('i', $id);
  $sel->execute();
  $res = $sel->get_result();
  $gradeInfo = $res->fetch_assoc();
  $sel->close();
  
  // Delete the grade
  $stmt = $conn->prepare("DELETE FROM student_grades WHERE id = ?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $stmt->close();
  
  // Audit log
  $a = $_SESSION['username'] ?? null;
  $act = 'delete';
  $t = 'student_grades';
  $id_s = (string)$id;
  $details = "deleted grade for " . ($gradeInfo['username'] ?? 'unknown') . " (subject: " . ($gradeInfo['subject'] ?? '') . ", year: " . ($gradeInfo['year'] ?? '') . ", semester: " . ($gradeInfo['semester'] ?? '') . ")";
  $l = $conn->prepare("INSERT INTO audit_log (admin_user, action, target_table, target_id, details) VALUES (?,?,?,?,?)");
  $l->bind_param('sssss', $a, $act, $t, $id_s, $details);
  $l->execute();
  $l->close();
  
  header('Location: /TCC/public/admin_dashboard.php?section=grade_system&success=deleted'); 
  exit();
  
} else if ($action === 'update') {
  // Update existing grade
  $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
  if ($id <= 0) { 
    header('Location: /TCC/public/admin_dashboard.php?section=grade_system&error=invalid_id'); 
    exit(); 
  }
  
  $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
  $year = trim($_POST['year'] ?? '');
  $semester = trim($_POST['semester'] ?? '');
  $subject = trim($_POST['subject'] ?? '');
  $instructor = trim($_POST['instructor'] ?? '');
  $prelim_grade = !empty($_POST['prelim_grade']) ? floatval($_POST['prelim_grade']) : null;
  $midterm_grade = !empty($_POST['midterm_grade']) ? floatval($_POST['midterm_grade']) : null;
  $finals_grade = !empty($_POST['finals_grade']) ? floatval($_POST['finals_grade']) : null;
  
  if (empty($year) || empty($semester) || empty($subject)) {
    header('Location: /TCC/public/admin_dashboard.php?section=grade_system&error=missing'); 
    exit();
  }
  
  // Get username from user_id if provided
  $username = '';
  if ($user_id) {
    $u = $conn->prepare("SELECT username, full_name FROM users WHERE id = ? LIMIT 1");
    $u->bind_param('i', $user_id);
    $u->execute();
    $ur = $u->get_result();
    if ($urow = $ur->fetch_assoc()) {
      $username = $urow['username'] ?? $urow['full_name'] ?? '';
    }
    $u->close();
  }
  
  // Update grade
  $stmt = $conn->prepare("UPDATE student_grades SET user_id = ?, username = ?, year = ?, semester = ?, subject = ?, instructor = ?, prelim_grade = ?, midterm_grade = ?, finals_grade = ? WHERE id = ?");
  $stmt->bind_param('isssssdddi', $user_id, $username, $year, $semester, $subject, $instructor, $prelim_grade, $midterm_grade, $finals_grade, $id);
  $stmt->execute();
  $stmt->close();
  
  // Audit log
  $a = $_SESSION['username'] ?? null;
  $act = 'update';
  $t = 'student_grades';
  $id_s = (string)$id;
  $details = "updated grade for " . $username . " (subject: " . $subject . ", year: " . $year . ", semester: " . $semester . ")";
  $l = $conn->prepare("INSERT INTO audit_log (admin_user, action, target_table, target_id, details) VALUES (?,?,?,?,?)");
  $l->bind_param('sssss', $a, $act, $t, $id_s, $details);
  $l->execute();
  $l->close();
  
  header('Location: /TCC/public/admin_dashboard.php?section=grade_system&success=updated'); 
  exit();
  
} else if ($action === 'create') {
  // Create new grade
  $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
  $year = trim($_POST['year'] ?? '');
  $semester = trim($_POST['semester'] ?? '');
  $subject = trim($_POST['subject'] ?? '');
  $instructor = trim($_POST['instructor'] ?? '');
  $prelim_grade = !empty($_POST['prelim_grade']) ? floatval($_POST['prelim_grade']) : null;
  $midterm_grade = !empty($_POST['midterm_grade']) ? floatval($_POST['midterm_grade']) : null;
  $finals_grade = !empty($_POST['finals_grade']) ? floatval($_POST['finals_grade']) : null;
  
  if (empty($year) || empty($semester) || empty($subject) || empty($user_id)) {
    header('Location: /TCC/public/admin_dashboard.php?section=grade_system&error=missing'); 
    exit();
  }
  
  // Get username from user_id
  $username = '';
  if ($user_id) {
    $u = $conn->prepare("SELECT username, full_name FROM users WHERE id = ? LIMIT 1");
    $u->bind_param('i', $user_id);
    $u->execute();
    $ur = $u->get_result();
    if ($urow = $ur->fetch_assoc()) {
      $username = $urow['username'] ?? $urow['full_name'] ?? '';
    }
    $u->close();
  }
  
  // Insert grade
  $stmt = $conn->prepare("INSERT INTO student_grades (user_id, username, year, semester, subject, instructor, prelim_grade, midterm_grade, finals_grade) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param('isssssddd', $user_id, $username, $year, $semester, $subject, $instructor, $prelim_grade, $midterm_grade, $finals_grade);
  $stmt->execute();
  $newId = $conn->insert_id;
  $stmt->close();
  
  // Audit log
  $a = $_SESSION['username'] ?? null;
  $act = 'create';
  $t = 'student_grades';
  $id_s = (string)$newId;
  $details = "created grade for " . $username . " (subject: " . $subject . ", year: " . $year . ", semester: " . $semester . ")";
  $l = $conn->prepare("INSERT INTO audit_log (admin_user, action, target_table, target_id, details) VALUES (?,?,?,?,?)");
  $l->bind_param('sssss', $a, $act, $t, $id_s, $details);
  $l->execute();
  $l->close();
  
  header('Location: /TCC/public/admin_dashboard.php?section=grade_system&success=created'); 
  exit();
}

header('Location: /TCC/public/admin_dashboard.php?section=grade_system&error=unknown'); 
exit();
?>

