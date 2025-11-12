<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('HTTP/1.1 403 Forbidden'); exit('Forbidden'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /TCC/public/admin_dashboard.php?section=buildings'); exit(); }

require_once __DIR__ . '/../database/db.php';
$conn = Database::getInstance()->getConnection();

$action = $_POST['action'] ?? 'create';
$year = trim($_POST['year'] ?? '');
$sect = trim($_POST['section'] ?? '');
$building = strtoupper(trim($_POST['building'] ?? ''));
$floor = intval($_POST['floor'] ?? 1);
$room = trim($_POST['room'] ?? '');
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($action === 'delete' && $id > 0) {
  // Delete section assignment
  $stmt = $conn->prepare("DELETE FROM section_assignments WHERE id = ?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  
  // Also update JSON backup
  $path = __DIR__ . '/../../database/section_assignments.json';
  $data = [];
  if (file_exists($path)) { $data = json_decode(file_get_contents($path), true) ?: []; }
  // Find and remove from JSON
  foreach ($data as $key => $info) {
    if (isset($info['id']) && $info['id'] == $id) {
      unset($data[$key]);
      break;
    }
    // Also check by year|section key
    if (strpos($key, '|') !== false) {
      list($y, $s) = explode('|', $key, 2);
      if ($y === $year && $s === $sect) {
        unset($data[$key]);
        break;
      }
    }
  }
  file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
  
  header('Location: /TCC/public/admin_dashboard.php?section=buildings&success=deleted'); exit();
}

if ($action === 'update' && $id > 0) {
  // Update existing section assignment
  if ($year === '' || $sect === '' || $building === '' || $room === '') { 
    header('Location: /TCC/public/admin_dashboard.php?section=buildings&error=missing'); 
    exit(); 
  }
  
  $stmt = $conn->prepare("UPDATE section_assignments SET year=?, section=?, building=?, floor=?, room=? WHERE id=?");
  $stmt->bind_param('ssissi', $year, $sect, $building, $floor, $room, $id);
  $stmt->execute();
  
  // Also update JSON backup
  $path = __DIR__ . '/../../database/section_assignments.json';
  $data = [];
  if (file_exists($path)) { $data = json_decode(file_get_contents($path), true) ?: []; }
  $key = $year . '|' . $sect;
  $data[$key] = ['id'=>$id, 'year'=>$year, 'section'=>$sect, 'building'=>$building, 'floor'=>$floor, 'room'=>$room];
  file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
  
  header('Location: /TCC/public/admin_dashboard.php?section=buildings&success=updated'); exit();
}

// Default: Create new section assignment
if ($year === '' || $sect === '' || $building === '' || $room === '') { 
  header('Location: /TCC/public/admin_dashboard.php?section=buildings&error=missing'); 
  exit(); 
}

// Save to database
$stmt = $conn->prepare("INSERT INTO section_assignments (year, section, building, floor, room) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE building=VALUES(building), floor=VALUES(floor), room=VALUES(room)");
$stmt->bind_param('ssiss', $year, $sect, $building, $floor, $room);
$stmt->execute();

// Get the ID of the inserted/updated record
$insertId = $conn->insert_id;
if ($insertId == 0) {
  // If it was an update due to duplicate key, fetch the ID
  $fetchStmt = $conn->prepare("SELECT id FROM section_assignments WHERE year = ? AND section = ? LIMIT 1");
  $fetchStmt->bind_param('ss', $year, $sect);
  $fetchStmt->execute();
  $result = $fetchStmt->get_result();
  if ($row = $result->fetch_assoc()) {
    $insertId = $row['id'];
  }
  $fetchStmt->close();
}

// Also save to JSON as backup
$path = __DIR__ . '/../../database/section_assignments.json';
$data = [];
if (file_exists($path)) { $data = json_decode(file_get_contents($path), true) ?: []; }
$key = $year . '|' . $sect;
$data[$key] = ['id'=>$insertId, 'year'=>$year, 'section'=>$sect, 'building'=>$building, 'floor'=>$floor, 'room'=>$room];
file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

header('Location: /TCC/public/admin_dashboard.php?section=buildings&success=1'); exit();
