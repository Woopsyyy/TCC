<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('HTTP/1.1 403 Forbidden'); exit('Forbidden'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /TCC/public/user_management.php'); exit(); }

require_once __DIR__ . '/../database/db.php';
$conn = Database::getInstance()->getConnection();

$action = $_POST['action'] ?? 'assign';

if ($action === 'delete') {
	// Delete user assignment
	$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
	if ($id <= 0) { header('Location: /TCC/public/user_management.php?error=invalid_id'); exit(); }
	
	// Get assignment info for audit log
	$sel = $conn->prepare("SELECT username, year, section FROM user_assignments WHERE id = ? LIMIT 1");
	$sel->bind_param('i', $id);
	$sel->execute();
	$res = $sel->get_result();
	$assignmentInfo = $res->fetch_assoc();
	$sel->close();
	
	// Delete the assignment
	$stmt = $conn->prepare("DELETE FROM user_assignments WHERE id = ?");
	$stmt->bind_param('i', $id);
	$stmt->execute();
	$stmt->close();
	
	// Audit log
	$a = $_SESSION['username'] ?? null;
	$act = 'delete';
	$t = 'user_assignments';
	$id_s = (string)$id;
	$details = "deleted user_assignment for " . ($assignmentInfo['username'] ?? 'unknown') . " (year: " . ($assignmentInfo['year'] ?? '') . ", section: " . ($assignmentInfo['section'] ?? '') . ")";
	$l = $conn->prepare("INSERT INTO audit_log (admin_user, action, target_table, target_id, details) VALUES (?,?,?,?,?)");
	$l->bind_param('sssss', $a, $act, $t, $id_s, $details);
	$l->execute();
	$l->close();
	
	header('Location: /TCC/public/user_management.php?deleted=1'); exit();
	
} else if ($action === 'update') {
		// update existing user's payment/sanctions/department by full_name
		$full_name = trim($_POST['full_name'] ?? '');
		// If admin selected an existing user, prefer that canonical fullname and id
		$existingUserId = !empty($_POST['existing_user_id']) ? intval($_POST['existing_user_id']) : null;
		if (!empty($existingUserId)) {
			$p = $conn->prepare("SELECT id, full_name FROM users WHERE id = ? LIMIT 1");
			if ($p) { $p->bind_param('i', $existingUserId); $p->execute(); $gr = $p->get_result(); if ($g = $gr->fetch_assoc()) { $full_name = $g['full_name'] ?? $full_name; } $p->close(); }
		}
		if ($full_name === '') { header('Location: /TCC/public/user_management.php?error=missing'); exit(); }

		$payment = trim($_POST['payment'] ?? 'paid'); // 'paid' or 'owing'
		$sanctions = trim($_POST['sanctions'] ?? '');
		$department = trim($_POST['department'] ?? '');
		$owing_amount = trim($_POST['owing_amount'] ?? '');

		// validate owing amount when payment is owing
		if ($payment === 'owing') {
			if ($owing_amount === '' || !is_numeric($owing_amount) || floatval($owing_amount) <= 0) {
				header('Location: /TCC/public/user_management.php?error=invalid_owing'); exit();
			}
		} else {
			// clear owing when not owing
			$owing_amount = '';
		}

		// try to resolve a users.id mapping for this full_name (unless existingUserId provided)
		$user_id = !empty($existingUserId) ? $existingUserId : null;
		if (empty($user_id)) {
			$ps = $conn->prepare("SELECT id FROM users WHERE full_name = ? OR username = ? LIMIT 1");
			if ($ps) {
				$ps->bind_param('ss', $full_name, $full_name);
				$ps->execute();
				$gres = $ps->get_result();
				if ($g = $gres->fetch_assoc()) { $user_id = (int)$g['id']; }
				$ps->close();
			}
		}

	// We don't update year/section here (admin edit modal is for payment/sanctions/department)
	// Try to fetch existing row to preserve year/section
	$sel = $conn->prepare("SELECT id, year, section, user_id FROM user_assignments WHERE username = ? LIMIT 1");
	$sel->bind_param('s', $full_name);
	$sel->execute();
	$res = $sel->get_result();
	$existing_id = null;
	$existing_user_id = null;
	if ($row = $res->fetch_assoc()) { 
		$existing_id = $row['id'];
		$existing_user_id = $row['user_id'];
	}
	
	// Use provided user_id or existing one
	$final_user_id = !empty($user_id) ? $user_id : $existing_user_id;
	
	// Update existing record or insert new one
	if ($existing_id) {
		// Update existing record
		$stmt = $conn->prepare("UPDATE user_assignments SET department=?, payment=?, sanctions=?, owing_amount=?, user_id=? WHERE id=?");
		$stmt->bind_param('ssssii', $department, $payment, $sanctions, $owing_amount, $final_user_id, $existing_id);
		$stmt->execute();
	} else {
		// Insert new record (shouldn't happen in update action, but handle it)
		$year = '';
		$section = '';
		$stmt = $conn->prepare("INSERT INTO user_assignments (username, year, section, department, payment, sanctions, owing_amount, user_id) VALUES (?,?,?,?,?,?,?,?)");
		$stmt->bind_param('sssssssi', $full_name, $year, $section, $department, $payment, $sanctions, $owing_amount, $final_user_id);
		$stmt->execute();
	}

		// audit
		$a = $_SESSION['username'] ?? null;
		$act = 'update'; 
		$t = 'user_assignments'; 
		$id_s = $existing_id ? (string)$existing_id : ($conn->insert_id ? (string)$conn->insert_id : $full_name); 
		$details = "updated user_assignment for $full_name: payment=$payment, sanctions=" . (empty($sanctions) ? 'none' : $sanctions) . ", owing=" . ($owing_amount ?: '0');
		$l = $conn->prepare("INSERT INTO audit_log (admin_user, action, target_table, target_id, details) VALUES (?,?,?,?,?)");
		$l->bind_param('sssss', $a, $act, $t, $id_s, $details);
		$l->execute();

		header('Location: /TCC/public/user_management.php?updated=1'); exit();

} else {
		// assign new user to year/section (and optional department)
		$full_name = trim($_POST['full_name'] ?? '');
		$existingUserId = !empty($_POST['existing_user_id']) ? intval($_POST['existing_user_id']) : null;
		if (!empty($existingUserId)) {
			$p = $conn->prepare("SELECT id, full_name FROM users WHERE id = ? LIMIT 1");
			if ($p) { $p->bind_param('i', $existingUserId); $p->execute(); $gr = $p->get_result(); if ($g = $gr->fetch_assoc()) { $full_name = $g['full_name'] ?? $full_name; } $p->close(); }
		}
		$year = trim($_POST['year'] ?? '');
		$section = trim($_POST['section'] ?? '');
		$department = trim($_POST['department'] ?? '');

		if ($full_name === '' || $year === '' || $section === '') { header('Location: /TCC/public/user_management.php?error=missing'); exit(); }

		$payment = 'paid'; $sanctions = ''; $owing_amount = '';

		// try to resolve user id for this full_name (or use selected existing user)
		$user_id = !empty($existingUserId) ? $existingUserId : null;
		if (empty($user_id)) {
			$ps = $conn->prepare("SELECT id FROM users WHERE full_name = ? OR username = ? LIMIT 1");
			if ($ps) {
				$ps->bind_param('ss', $full_name, $full_name);
				$ps->execute();
				$gres = $ps->get_result();
				if ($g = $gres->fetch_assoc()) { $user_id = (int)$g['id']; }
				$ps->close();
			}
		}

		$stmt = $conn->prepare("INSERT INTO user_assignments (username, year, section, department, payment, sanctions, owing_amount, user_id) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE year=VALUES(year), section=VALUES(section), department=VALUES(department), payment=VALUES(payment), sanctions=VALUES(sanctions), owing_amount=VALUES(owing_amount), user_id=VALUES(user_id)");
		$user_id_for_insert = !empty($user_id) ? $user_id : null;
		$stmt->bind_param('sssssssi', $full_name, $year, $section, $department, $payment, $sanctions, $owing_amount, $user_id_for_insert);
		$stmt->execute();

		if (!empty($user_id)) {
			$up = $conn->prepare("UPDATE user_assignments SET user_id = ? WHERE username = ?");
			if ($up) { $up->bind_param('is', $user_id, $full_name); $up->execute(); }
		}

		// audit
		$a = $_SESSION['username'] ?? null; $act = 'create'; $t = 'user_assignments'; $id_s = (string)$conn->insert_id; $details = "assigned $full_name to $year/$section";
		$l = $conn->prepare("INSERT INTO audit_log (admin_user, action, target_table, target_id, details) VALUES (?,?,?,?,?)");
		$l->bind_param('sssss', $a, $act, $t, $id_s, $details);
		$l->execute();

		header('Location: /TCC/public/user_management.php?success=1'); exit();
}
