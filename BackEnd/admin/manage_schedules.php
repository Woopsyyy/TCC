<?php
require_once __DIR__ . '/../helpers/admin_helpers.php';
require_admin_post('/TCC/public/admin_dashboard.php?section=schedule_management');

require_once __DIR__ . '/../database/db.php';
$conn = Database::getInstance()->getConnection();

ensure_tables($conn, [
  'schedules' => "CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year VARCHAR(10) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    day VARCHAR(20) NOT NULL,
    time_start TIME NOT NULL,
    time_end TIME NOT NULL,
    room VARCHAR(100) DEFAULT NULL,
    instructor VARCHAR(255) DEFAULT NULL,
    section VARCHAR(100) DEFAULT NULL,
    building VARCHAR(10) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_year (year),
    INDEX idx_subject (subject),
    INDEX idx_day (day)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  'sections' => "CREATE TABLE IF NOT EXISTS sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year VARCHAR(10) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_year_name (year, name)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  'buildings' => "CREATE TABLE IF NOT EXISTS buildings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(10) NOT NULL UNIQUE,
    floors INT DEFAULT 4,
    rooms_per_floor INT DEFAULT 4
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
  'teacher_assignments' => "CREATE TABLE IF NOT EXISTS teacher_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    username VARCHAR(200) NOT NULL,
    year VARCHAR(10) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_username (username),
    INDEX idx_year (year)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
]);

$action = $_POST['action'] ?? 'create';

if ($action === 'delete') {
	// Delete schedule
	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
	if ($id <= 0) { header('Location: /TCC/public/admin_dashboard.php?section=schedule_management&error=invalid_id'); exit(); }
	
	// Get schedule info for audit log
	$sel = $conn->prepare("SELECT year, subject, day FROM schedules WHERE id = ? LIMIT 1");
	$sel->bind_param('i', $id);
	$sel->execute();
	$res = $sel->get_result();
	$row = $sel->fetch_assoc();
	$sel->close();
	
	if ($row) {
		$del = $conn->prepare("DELETE FROM schedules WHERE id = ?");
		$del->bind_param('i', $id);
		$del->execute();
		$del->close();
		
		$details = "Deleted schedule: " . ($row['subject'] ?? '') . " - " . ($row['day'] ?? '') . " (" . ($row['year'] ?? '') . ")";
		log_audit($conn, 'delete', 'schedules', $id, $details);
	}
	
	header('Location: /TCC/public/admin_dashboard.php?section=schedule_management&success=deleted');
	exit();
}

// Validate required fields
$year = trim($_POST['year'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$day = trim($_POST['day'] ?? '');
$timeStart = trim($_POST['time_start'] ?? '');
$timeEnd = trim($_POST['time_end'] ?? '');

if (empty($year) || empty($subject) || empty($day) || empty($timeStart) || empty($timeEnd)) {
	header('Location: /TCC/public/admin_dashboard.php?section=schedule_management&error=missing');
	exit();
}

$room = trim($_POST['room'] ?? '');
$instructor = trim($_POST['instructor'] ?? '');
$section = trim($_POST['section'] ?? '');
$building = trim($_POST['building'] ?? '');

// Validate Instructor if provided
if (!empty($instructor)) {
	// Check if instructor exists in teacher_assignments table
	$instructorCheck1 = $conn->prepare("SELECT COUNT(*) as cnt FROM teacher_assignments WHERE username = ?");
	$instructorCheck1->bind_param('s', $instructor);
	$instructorCheck1->execute();
	$instructorRes1 = $instructorCheck1->get_result();
	$instructorRow1 = $instructorCheck1->fetch_assoc();
	$instructorCheck1->close();
	
	// Also check users table directly for teachers
	$userCheck = $conn->prepare("SELECT COUNT(*) as cnt FROM users WHERE (full_name = ? OR username = ?) AND role = 'teacher'");
	$userCheck->bind_param('ss', $instructor, $instructor);
	$userCheck->execute();
	$userRes = $userCheck->get_result();
	$userRow = $userCheck->fetch_assoc();
	$userCheck->close();
	
	if (intval($instructorRow1['cnt'] ?? 0) === 0 && intval($userRow['cnt'] ?? 0) === 0) {
		header('Location: /TCC/public/admin_dashboard.php?section=schedule_management&error=instructor_not_found');
		exit();
	}
}

// Validate Section if provided
if (!empty($section)) {
	$sectionCheck = $conn->prepare("SELECT COUNT(*) as cnt FROM sections WHERE year = ? AND name = ?");
	$sectionCheck->bind_param('ss', $year, $section);
	$sectionCheck->execute();
	$sectionRes = $sectionCheck->get_result();
	$sectionRow = $sectionCheck->fetch_assoc();
	$sectionCheck->close();
	
	if (intval($sectionRow['cnt'] ?? 0) === 0) {
		header('Location: /TCC/public/admin_dashboard.php?section=schedule_management&error=section_not_found');
		exit();
	}
}

// Validate Building if provided
if (!empty($building)) {
	// Check buildings table
	$buildingCheck = $conn->prepare("SELECT COUNT(*) as cnt FROM buildings WHERE name = ?");
	$buildingCheck->bind_param('s', $building);
	$buildingCheck->execute();
	$buildingRes = $buildingCheck->get_result();
	$buildingRow = $buildingCheck->fetch_assoc();
	$buildingCheck->close();
	
	// Also check JSON fallback
	$buildingExists = false;
	if (intval($buildingRow['cnt'] ?? 0) > 0) {
		$buildingExists = true;
	} else {
		$buildingsPath = __DIR__ . '/../../database/buildings.json';
		if (file_exists($buildingsPath)) {
			$buildingsData = json_decode(file_get_contents($buildingsPath), true) ?: [];
			$buildingExists = isset($buildingsData[strtoupper($building)]);
		}
	}
	
	if (!$buildingExists) {
		header('Location: /TCC/public/admin_dashboard.php?section=schedule_management&error=building_not_found');
		exit();
	}
}

if ($action === 'update') {
	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
	if ($id <= 0) { header('Location: /TCC/public/admin_dashboard.php?section=schedule_management&error=invalid_id'); exit(); }
	
	$upd = $conn->prepare("UPDATE schedules SET year = ?, subject = ?, day = ?, time_start = ?, time_end = ?, room = ?, instructor = ?, section = ?, building = ? WHERE id = ?");
	$upd->bind_param('sssssssssi', $year, $subject, $day, $timeStart, $timeEnd, $room, $instructor, $section, $building, $id);
	$upd->execute();
	$upd->close();
	
	$details = "Updated schedule: " . $subject . " - " . $day . " (" . $year . ")";
	log_audit($conn, 'update', 'schedules', $id, $details);
	
	header('Location: /TCC/public/admin_dashboard.php?section=schedule_management&success=updated');
	exit();
} else {
	// Create new schedule
	$ins = $conn->prepare("INSERT INTO schedules (year, subject, day, time_start, time_end, room, instructor, section, building) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
	$ins->bind_param('sssssssss', $year, $subject, $day, $timeStart, $timeEnd, $room, $instructor, $section, $building);
	$ins->execute();
	$newId = $conn->insert_id;
	$ins->close();
	
	$details = "Created schedule: " . $subject . " - " . $day . " (" . $year . ")";
	log_audit($conn, 'create', 'schedules', $newId, $details);
	
	header('Location: /TCC/public/admin_dashboard.php?section=schedule_management&success=created');
	exit();
}
?>
