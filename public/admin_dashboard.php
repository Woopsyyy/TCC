<?php
session_start();
require_once __DIR__ . '/../BackEnd/auth/login.php';
require_once __DIR__ . '/../BackEnd/database/db.php';

// Only admins allowed
if (!isset($_SESSION['user_id']) || !Auth::isAdmin()) {
    header('Location: /TCC/public/index.html');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Ensure 'verified' column exists
$col = $conn->query("SHOW COLUMNS FROM users LIKE 'verified'");
if ($col->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN verified TINYINT(1) NOT NULL DEFAULT 0");
}

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role'])) {
    foreach ($_POST['role'] as $id => $role) {
        $verified = isset($_POST['verified'][$id]) ? 1 : 0;
        $stmt = $conn->prepare("UPDATE users SET role = ?, verified = ? WHERE id = ?");
        $stmt->bind_param('sii', $role, $verified, $id);
        $stmt->execute();
    }
    header('Location: admin_dashboard.php?updated=1');
    exit();
}

$users = $conn->query("SELECT id, username, role, verified FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="css/bootstrap.min.css" />
  <title>Admin Dashboard - Verify Users</title>
  <style>
    body { padding: 20px; }
    table { width: 100%; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Admin - Verify Users</h1>
    <p>Use this page to verify users and set their role (teacher or student).</p>

    <?php if (isset($_GET['updated'])): ?>
      <div class="alert alert-success">Updates saved.</div>
    <?php endif; ?>

    <form method="POST" action="admin_dashboard.php">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Verified</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $users->fetch_assoc()): ?>
            <tr>
              <td><?php echo $row['id']; ?></td>
              <td><?php echo htmlspecialchars($row['username']); ?></td>
              <td>
                <select name="role[<?php echo $row['id']; ?>]" class="form-select">
                  <option value="student" <?php echo $row['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
                  <option value="teacher" <?php echo $row['role'] === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                  <option value="admin" <?php echo $row['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
              </td>
              <td>
                <input type="checkbox" name="verified[<?php echo $row['id']; ?>]" value="1" <?php echo $row['verified'] ? 'checked' : ''; ?> />
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit">Save changes</button>
        <a class="btn btn-secondary" href="home.php">Back to Home</a>
      </div>
    </form>
  </div>
</body>
</html>
