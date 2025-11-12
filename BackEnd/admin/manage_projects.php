<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('HTTP/1.1 403 Forbidden'); exit('Forbidden'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /TCC/public/admin_dashboard.php?section=projects'); exit(); }

$name = trim($_POST['name'] ?? '');
$budget = trim($_POST['budget'] ?? '');
$started = $_POST['started'] ?? '';
$completed = $_POST['completed'] ?? 'no';

if ($name === '' || $budget === '' || $started === '') { header('Location: /TCC/public/admin_dashboard.php?section=projects&error=missing'); exit(); }

$path = __DIR__ . '/../../database/projects.json';
$list = [];
if (file_exists($path)) { $list = json_decode(file_get_contents($path), true) ?: []; }
$entry = ['name'=>$name, 'budget'=>$budget, 'started'=>$started, 'completed'=>$completed];
array_push($list, $entry);
file_put_contents($path, json_encode($list, JSON_PRETTY_PRINT));

header('Location: /TCC/public/admin_dashboard.php?section=projects&success=1'); exit();
