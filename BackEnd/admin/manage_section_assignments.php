<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('HTTP/1.1 403 Forbidden'); exit('Forbidden'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /TCC/public/admin_dashboard.php?section=buildings'); exit(); }

require_once __DIR__ . '/../database/db.php';
$conn = Database::getInstance()->getConnection();

$year = trim($_POST['year'] ?? '');
$sect = trim($_POST['section'] ?? '');
$building = strtoupper(trim($_POST['building'] ?? ''));
$floor = intval($_POST['floor'] ?? 1);
$room = trim($_POST['room'] ?? '');

if ($year === '' || $sect === '' || $building === '' || $room === '') { 
  header('Location: /TCC/public/admin_dashboard.php?section=buildings&error=missing'); 
  exit(); 
}

// Save to database
$stmt = $conn->prepare("INSERT INTO section_assignments (year, section, building, floor, room) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE building=VALUES(building), floor=VALUES(floor), room=VALUES(room)");
$stmt->bind_param('ssiss', $year, $sect, $building, $floor, $room);
$stmt->execute();

// Also save to JSON as backup
$path = __DIR__ . '/../../database/section_assignments.json';
$data = [];
if (file_exists($path)) { $data = json_decode(file_get_contents($path), true) ?: []; }
$key = $year . '|' . $sect;
$data[$key] = ['year'=>$year, 'section'=>$sect, 'building'=>$building, 'floor'=>$floor, 'room'=>$room];
file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

header('Location: /TCC/public/admin_dashboard.php?section=buildings&success=1'); exit();
