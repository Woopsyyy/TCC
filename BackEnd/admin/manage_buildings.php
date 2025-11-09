<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('HTTP/1.1 403 Forbidden'); exit('Forbidden'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /TCC/public/admin_dashboard.php?section=buildings'); exit(); }

$building = strtoupper(trim($_POST['building'] ?? ''));
$floors = intval($_POST['floors'] ?? 4);
$rooms = intval($_POST['rooms'] ?? 4);

if ($building === '') { header('Location: /TCC/public/admin_dashboard.php?section=buildings&error=missing'); exit(); }

$path = __DIR__ . '/../../database/buildings.json';
$data = [];
if (file_exists($path)) { $data = json_decode(file_get_contents($path), true) ?: []; }

$data[$building] = ['floors'=>$floors, 'rooms'=>$rooms];
file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

header('Location: /TCC/public/admin_dashboard.php?section=buildings&success=1'); exit();
