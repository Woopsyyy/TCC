<?php
// One-time migration script: creates tables and imports existing JSON files into DB
// Run this script once from browser or CLI and then remove it.
require_once __DIR__ . '/../database/db.php';

if (php_sapi_name() !== 'cli') {
    echo "Running migration...\n";
}

$db = Database::getInstance();
$conn = $db->getConnection();

$queries = [
    // announcements
    "CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        year VARCHAR(10),
        department VARCHAR(50),
        date DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // projects
    "CREATE TABLE IF NOT EXISTS projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        budget VARCHAR(64),
        started DATE,
        completed ENUM('yes','no') DEFAULT 'no'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // buildings
    "CREATE TABLE IF NOT EXISTS buildings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(10) NOT NULL UNIQUE,
        floors INT DEFAULT 4,
        rooms_per_floor INT DEFAULT 4
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // section assignments
    "CREATE TABLE IF NOT EXISTS section_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        year VARCHAR(10) NOT NULL,
        section VARCHAR(100) NOT NULL,
        building VARCHAR(10) NOT NULL,
        floor INT NOT NULL,
        room VARCHAR(50) NOT NULL,
        UNIQUE KEY uniq_year_section (year, section)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // user assignments (store full name in username field for compatibility)
    "CREATE TABLE IF NOT EXISTS user_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(200) NOT NULL UNIQUE,
        year VARCHAR(10) NOT NULL,
        section VARCHAR(100) NOT NULL,
        department VARCHAR(100) DEFAULT NULL,
        payment ENUM('paid','owing') DEFAULT 'paid',
        sanctions TEXT DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // audit log
    "CREATE TABLE IF NOT EXISTS audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_user VARCHAR(100),
        action VARCHAR(50),
        target_table VARCHAR(50),
        target_id VARCHAR(50),
        details TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($queries as $q) {
    if (!$conn->query($q)) {
        echo "Error creating table: " . $conn->error . "\n";
    }
}

// ensure user_assignments has the new columns (for older installs)
$cols = [
    'department' => "VARCHAR(100) DEFAULT NULL",
    'payment' => "ENUM('paid','owing') DEFAULT 'paid'",
    'sanctions' => "TEXT DEFAULT NULL",
    'owing_amount' => "VARCHAR(64) DEFAULT NULL",
];
foreach ($cols as $col => $def) {
    // check if column exists
    $chk = $conn->prepare("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_assignments' AND COLUMN_NAME = ?");
    $chk->bind_param('s', $col);
    $chk->execute();
    $res = $chk->get_result();
    $row = $res->fetch_assoc();
    if (isset($row['c']) && intval($row['c']) === 0) {
        $sql = "ALTER TABLE user_assignments ADD COLUMN $col $def";
        if (!$conn->query($sql)) {
            echo "Error adding column $col: " . $conn->error . "\n";
        } else {
            echo "Added column $col to user_assignments\n";
        }
    }
}

// ensure user_id column exists for mapping to canonical users table
$col = 'user_id';
$chk = $conn->prepare("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_assignments' AND COLUMN_NAME = ?");
$chk->bind_param('s', $col);
$chk->execute();
$res = $chk->get_result();
$row = $res->fetch_assoc();
if (isset($row['c']) && intval($row['c']) === 0) {
    $sql = "ALTER TABLE user_assignments ADD COLUMN user_id INT DEFAULT NULL";
    if (!$conn->query($sql)) {
        echo "Error adding column user_id: " . $conn->error . "\n";
    } else {
        echo "Added column user_id to user_assignments\n";
    }
}

// helper to import json into table
function import_json($conn, $path, $callback) {
    if (!file_exists($path)) return 0;
    $raw = file_get_contents($path);
    $arr = json_decode($raw, true);
    if (!$arr) return 0;
    $count = 0;
    foreach ($arr as $item) {
        $callback($conn, $item);
        $count++;
    }
    // rename backup
    @rename($path, $path . '.bak');
    return $count;
}

$base = __DIR__ . '/../../database';

$n1 = import_json($conn, $base . '/announcements.json', function($conn, $a){
    $stmt = $conn->prepare("INSERT INTO announcements (title, content, year, department, date) VALUES (?,?,?,?,?)");
    $title = $a['title'] ?? '';
    $content = $a['content'] ?? '';
    $year = $a['year'] ?? '';
    $department = $a['department'] ?? '';
    $date = $a['date'] ?? date('Y-m-d H:i:s');
    $stmt->bind_param('sssss', $title, $content, $year, $department, $date);
    $stmt->execute();
});

$n2 = import_json($conn, $base . '/projects.json', function($conn, $p){
    $stmt = $conn->prepare("INSERT INTO projects (name, budget, started, completed) VALUES (?,?,?,?)");
    $name = $p['name'] ?? '';
    $budget = $p['budget'] ?? '';
    $started = $p['started'] ?? null;
    $completed = $p['completed'] ?? 'no';
    $stmt->bind_param('ssss', $name, $budget, $started, $completed);
    $stmt->execute();
});

// For buildings.json that may be an object of name->info
if (file_exists($base . '/buildings.json')) {
    $raw = json_decode(file_get_contents($base . '/buildings.json'), true) ?: [];
    foreach ($raw as $name => $info) {
        $floors = isset($info['floors']) ? (int)$info['floors'] : 4;
        $rooms = isset($info['rooms']) ? (int)$info['rooms'] : 4;
        $stmt = $conn->prepare("INSERT INTO buildings (name, floors, rooms_per_floor) VALUES (?,?,?) ON DUPLICATE KEY UPDATE floors=VALUES(floors), rooms_per_floor=VALUES(rooms_per_floor)");
        $stmt->bind_param('sii', $name, $floors, $rooms);
        $stmt->execute();
    }
    @rename($base . '/buildings.json', $base . '/buildings.json.bak');
}

// section_assignments.json (keyed by "year|section")
if (file_exists($base . '/section_assignments.json')) {
    $raw = json_decode(file_get_contents($base . '/section_assignments.json'), true) ?: [];
    foreach ($raw as $key => $info) {
        $year = $info['year'] ?? '';
        $section = $info['section'] ?? '';
        $building = $info['building'] ?? '';
        $floor = isset($info['floor']) ? (int)$info['floor'] : 1;
        $room = $info['room'] ?? '';
        $stmt = $conn->prepare("INSERT INTO section_assignments (year, section, building, floor, room) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE building=VALUES(building), floor=VALUES(floor), room=VALUES(room)");
        $stmt->bind_param('ssiss', $year, $section, $building, $floor, $room);
        $stmt->execute();
    }
    @rename($base . '/section_assignments.json', $base . '/section_assignments.json.bak');
}

// user_assignments.json (import + include owing_amount)
if (file_exists($base . '/user_assignments.json')) {
    $raw = json_decode(file_get_contents($base . '/user_assignments.json'), true) ?: [];
    foreach ($raw as $username => $info) {
        // $username may be full name if exported that way
        $year = $info['year'] ?? '';
        $section = $info['section'] ?? '';
        $department = $info['department'] ?? null;
        $payment = isset($info['payment']) ? $info['payment'] : 'paid';
        $sanctions = $info['sanctions'] ?? null;
        $owing = isset($info['owing_amount']) ? $info['owing_amount'] : (isset($info['owing']) ? $info['owing'] : (isset($info['balance']) ? $info['balance'] : null));

        $stmt = $conn->prepare("INSERT INTO user_assignments (username, year, section, department, payment, sanctions, owing_amount) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE year=VALUES(year), section=VALUES(section), department=VALUES(department), payment=VALUES(payment), sanctions=VALUES(sanctions), owing_amount=VALUES(owing_amount)");
        $u = $username;
        $y = $year;
        $s = $section;
        $d = $department;
        $pmt = $payment;
        $san = $sanctions;
        $ow = $owing;
        $stmt->bind_param('sssssss', $u, $y, $s, $d, $pmt, $san, $ow);
        $stmt->execute();
    }
    @rename($base . '/user_assignments.json', $base . '/user_assignments.json.bak');

    // Attempt to map inserted assignments to canonical users.id when possible
    $mapQ = $conn->query("SELECT id, username FROM user_assignments WHERE user_id IS NULL OR user_id = 0");
    if ($mapQ) {
        while ($r = $mapQ->fetch_assoc()) {
            $uaId = (int)$r['id'];
            $uname = trim($r['username']);
            if ($uname === '') continue;

            $foundId = null;

            // try exact match on full_name
            $ps = $conn->prepare("SELECT id FROM users WHERE full_name = ? LIMIT 1");
            if ($ps) {
                $ps->bind_param('s', $uname);
                $ps->execute();
                $gres = $ps->get_result();
                if ($g = $gres->fetch_assoc()) { $foundId = (int)$g['id']; }
                $ps->close();
            }

            // try exact match on username if not found
            if (!$foundId) {
                $ps2 = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
                if ($ps2) {
                    $ps2->bind_param('s', $uname);
                    $ps2->execute();
                    $gres = $ps2->get_result();
                    if ($g = $gres->fetch_assoc()) { $foundId = (int)$g['id']; }
                    $ps2->close();
                }
            }

            // fallback: LIKE match on full_name
            if (!$foundId) {
                $like = "%$uname%";
                $ps3 = $conn->prepare("SELECT id FROM users WHERE full_name LIKE ? LIMIT 1");
                if ($ps3) {
                    $ps3->bind_param('s', $like);
                    $ps3->execute();
                    $gres = $ps3->get_result();
                    if ($g = $gres->fetch_assoc()) { $foundId = (int)$g['id']; }
                    $ps3->close();
                }
            }

            if ($foundId) {
                $up = $conn->prepare("UPDATE user_assignments SET user_id = ? WHERE id = ?");
                if ($up) {
                    $up->bind_param('ii', $foundId, $uaId);
                    $up->execute();
                    echo "Mapped user_assignments.id={$uaId} (username='{$uname}') -> users.id={$foundId}\n";
                    $up->close();
                }
            }
        }
    }
}

echo "Migration complete. Imported announcements: $n1, projects: $n2\n";

?>
