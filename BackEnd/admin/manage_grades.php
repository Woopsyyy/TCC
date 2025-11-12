<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { 
  header('HTTP/1.1 403 Forbidden'); 
  exit('Forbidden'); 
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
  header('Location: /TCC/public/admin_dashboard.php?section=grades'); 
  exit(); 
}

require_once __DIR__ . '/../database/db.php';
$conn = Database::getInstance()->getConnection();

// Ensure grades table exists
$conn->query("CREATE TABLE IF NOT EXISTS grades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  username VARCHAR(200) NOT NULL,
  semester VARCHAR(50) NOT NULL,
  subject VARCHAR(200) NOT NULL,
  teacher VARCHAR(200) NOT NULL,
  prelim DECIMAL(5,2) DEFAULT NULL,
  midterm DECIMAL(5,2) DEFAULT NULL,
  finals DECIMAL(5,2) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user_id (user_id),
  INDEX idx_username (username),
  INDEX idx_semester (semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$action = $_POST['action'] ?? 'create';

if ($action === 'delete') {
  $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
  if ($id <= 0) { 
    header('Location: /TCC/public/admin_dashboard.php?section=grades&error=invalid_id'); 
    exit(); 
  }
  
  // Get grade info for audit log
  $sel = $conn->prepare("SELECT username, subject, semester FROM grades WHERE id = ? LIMIT 1");
  $sel->bind_param('i', $id);
  $sel->execute();
  $res = $sel->get_result();
  $gradeInfo = $res->fetch_assoc();
  $sel->close();
  
  // Delete the grade
  $stmt = $conn->prepare("DELETE FROM grades WHERE id = ?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $stmt->close();
  
  // Audit log
  $a = $_SESSION['username'] ?? null;
  $act = 'delete';
  $t = 'grades';
  $id_s = (string)$id;
  $details = "deleted grade for " . ($gradeInfo['username'] ?? 'unknown') . " - " . ($gradeInfo['subject'] ?? '') . " (" . ($gradeInfo['semester'] ?? '') . ")";
  $l = $conn->prepare("INSERT INTO audit_log (admin_user, action, target_table, target_id, details) VALUES (?,?,?,?,?)");
  $l->bind_param('sssss', $a, $act, $t, $id_s, $details);
  $l->execute();
  $l->close();
  
  header('Location: /TCC/public/admin_dashboard.php?section=grades&success=deleted'); 
  exit();
  
} else if ($action === 'update') {
  $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
  if ($id <= 0) { 
    header('Location: /TCC/public/admin_dashboard.php?section=grades&error=invalid_id'); 
    exit(); 
  }
  
  $username = trim($_POST['username'] ?? '');
  $semester = trim($_POST['semester'] ?? '');
  $subject = trim($_POST['subject'] ?? '');
  $teacher = trim($_POST['teacher'] ?? '');
  $prelim = !empty($_POST['prelim']) ? floatval($_POST['prelim']) : null;
  $midterm = !empty($_POST['midterm']) ? floatval($_POST['midterm']) : null;
  $finals = !empty($_POST['finals']) ? floatval($_POST['finals']) : null;
  
  if ($username === '' || $semester === '' || $subject === '' || $teacher === '') { 
    header('Location: /TCC/public/admin_dashboard.php?section=grades&error=missing'); 
    exit(); 
  }
  
  // Try to get user_id
  $user_id = null;
  $ps = $conn->prepare("SELECT id FROM users WHERE full_name = ? OR username = ? LIMIT 1");
  if ($ps) {
    $ps->bind_param('ss', $username, $username);
    $ps->execute();
    $gres = $ps->get_result();
    if ($g = $gres->fetch_assoc()) { 
      $user_id = (int)$g['id']; 
    }
    $ps->close();
  }
  
  $stmt = $conn->prepare("UPDATE grades SET username=?, user_id=?, semester=?, subject=?, teacher=?, prelim=?, midterm=?, finals=? WHERE id=?");
  $stmt->bind_param('sissdddii', $username, $user_id, $semester, $subject, $teacher, $prelim, $midterm, $finals, $id);
  $stmt->execute();
  $stmt->close();
  
  // Audit log
  $a = $_SESSION['username'] ?? null;
  $act = 'update';
  $t = 'grades';
  $id_s = (string)$id;
  $details = "updated grade for $username - $subject ($semester)";
  $l = $conn->prepare("INSERT INTO audit_log (admin_user, action, target_table, target_id, details) VALUES (?,?,?,?,?)");
  $l->bind_param('sssss', $a, $act, $t, $id_s, $details);
  $l->execute();
  $l->close();
  
  header('Location: /TCC/public/admin_dashboard.php?section=grades&success=updated'); 
  exit();
  
} else {
  // Create new grade
  $username = trim($_POST['username'] ?? '');
  $semester = trim($_POST['semester'] ?? '');
  $subject = trim($_POST['subject'] ?? '');
  $teacher = trim($_POST['teacher'] ?? '');
  $prelim = !empty($_POST['prelim']) ? floatval($_POST['prelim']) : null;
  $midterm = !empty($_POST['midterm']) ? floatval($_POST['midterm']) : null;
  $finals = !empty($_POST['finals']) ? floatval($_POST['finals']) : null;
  
  if ($username === '' || $semester === '' || $subject === '' || $teacher === '') { 
    header('Location: /TCC/public/admin_dashboard.php?section=grades&error=missing'); 
    exit(); 
  }
  
  // Try to get user_id
  $user_id = null;
  $ps = $conn->prepare("SELECT id FROM users WHERE full_name = ? OR username = ? LIMIT 1");
  if ($ps) {
    $ps->bind_param('ss', $username, $username);
    $ps->execute();
    $gres = $ps->get_result();
    if ($g = $gres->fetch_assoc()) { 
      $user_id = (int)$g['id']; 
    }
    $ps->close();
  }
  
  $stmt = $conn->prepare("INSERT INTO grades (username, user_id, semester, subject, teacher, prelim, midterm, finals) VALUES (?,?,?,?,?,?,?,?)");
  $stmt->bind_param('sissdddd', $username, $user_id, $semester, $subject, $teacher, $prelim, $midterm, $finals);
  $stmt->execute();
  $stmt->close();
  
  // Audit log
  $a = $_SESSION['username'] ?? null;
  $act = 'create';
  $t = 'grades';
  $id_s = (string)$conn->insert_id;
  $details = "created grade for $username - $subject ($semester)";
  $l = $conn->prepare("INSERT INTO audit_log (admin_user, action, target_table, target_id, details) VALUES (?,?,?,?,?)");
  $l->bind_param('sssss', $a, $act, $t, $id_s, $details);
  $l->execute();
  $l->close();
  
  header('Location: /TCC/public/admin_dashboard.php?section=grades&success=created'); 
  exit();
}

