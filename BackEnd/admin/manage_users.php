<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('HTTP/1.1 403 Forbidden'); exit('Forbidden'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /TCC/public/admin_dashboard.php?section=users'); exit(); }

require_once __DIR__ . '/../database/db.php';
$conn = Database::getInstance()->getConnection();

$action = $_POST['action'] ?? 'assign';

if ($action === 'update') {
		// update existing user's payment/sanctions/department by full_name
		$full_name = trim($_POST['full_name'] ?? '');
		// If admin selected an existing user, prefer that canonical fullname and id
		$existingUserId = !empty($_POST['existing_user_id']) ? intval($_POST['existing_user_id']) : null;
		if (!empty($existingUserId)) {
			$p = $conn->prepare("SELECT id, full_name FROM users WHERE id = ? LIMIT 1");
			if ($p) { $p->bind_param('i', $existingUserId); $p->execute(); $gr = $p->get_result(); if ($g = $gr->fetch_assoc()) { $full_name = $g['full_name'] ?? $full_name; } $p->close(); }
		}
		if ($full_name === '') { header('Location: /TCC/public/admin_dashboard.php?section=users&error=missing'); exit(); }

		$payment = trim($_POST['payment'] ?? 'paid'); // 'paid' or 'owing'
		$sanctions = trim($_POST['sanctions'] ?? '');
		$department = trim($_POST['department'] ?? '');
		$owing_amount = trim($_POST['owing_amount'] ?? '');

		// validate owing amount when payment is owing
		if ($payment === 'owing') {
			if ($owing_amount === '' || !is_numeric($owing_amount) || floatval($owing_amount) <= 0) {
				header('Location: /TCC/public/admin_dashboard.php?section=users&error=invalid_owing'); exit();
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

	$stmt = $conn->prepare("INSERT INTO user_assignments (username, year, section, department, payment, sanctions, owing_amount) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE department=VALUES(department), payment=VALUES(payment), sanctions=VALUES(sanctions), owing_amount=VALUES(owing_amount)");
		// We don't update year/section here (admin edit modal is for payment/sanctions/department)
		// Try to fetch existing row to preserve year/section
		$sel = $conn->prepare("SELECT year, section FROM user_assignments WHERE username = ? LIMIT 1");
		$sel->bind_param('s', $full_name);
		$sel->execute();
		$res = $sel->get_result();
		$year = '';
		$section = '';
		if ($row = $res->fetch_assoc()) { $year = $row['year']; $section = $row['section']; }
		// if no existing, set blank year/section (admin should have assigned earlier)
		$stmt->bind_param('sssssss', $full_name, $year, $section, $department, $payment, $sanctions, $owing_amount);
		$stmt->execute();

		// if we resolved a users.id, update the mapping column
		if (!empty($user_id)) {
			$up = $conn->prepare("UPDATE user_assignments SET user_id = ? WHERE username = ?");
			if ($up) { $up->bind_param('is', $user_id, $full_name); $up->execute(); }
		}

		// audit
		$a = $_SESSION['username'] ?? null;
		$act = 'update'; $t = 'user_assignments'; $id_s = $conn->insert_id ? (string)$conn->insert_id : $full_name; $details = "updated user_assignment for $full_name";
		$l = $conn->prepare("INSERT INTO audit_log (admin_user, action, target_table, target_id, details) VALUES (?,?,?,?,?)");
		$l->bind_param('sssss', $a, $act, $t, $id_s, $details);
		$l->execute();

		header('Location: /TCC/public/admin_dashboard.php?section=users&updated=1'); exit();

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

		if ($full_name === '' || $year === '' || $section === '') { header('Location: /TCC/public/admin_dashboard.php?section=users&error=missing'); exit(); }

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

		$stmt = $conn->prepare("INSERT INTO user_assignments (username, year, section, department, payment, sanctions, owing_amount) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE year=VALUES(year), section=VALUES(section), department=VALUES(department)");
		$stmt->bind_param('sssssss', $full_name, $year, $section, $department, $payment, $sanctions, $owing_amount);
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

		header('Location: /TCC/public/admin_dashboard.php?section=users&success=1'); exit();
}
