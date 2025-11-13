<?php
session_start();
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header('Location: /TCC/public/index.html');
  exit();
}

header('Location: /TCC/public/admin_dashboard.php?section=user_management');
exit();

