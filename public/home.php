<?php
session_start();
// Ensure user is logged in
if (!isset($_SESSION['username'])) {
  header('Location: /TCC/public/index.html');
  exit();
}

// Prefer values saved in session to avoid extra DB queries
$image = $_SESSION['image_path'] ?? '/TCC/public/images/sample.jpg';
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];

// default view: records on home
$view = isset($_GET['view']) ? $_GET['view'] : 'records';
$conn = Database::getInstance()->getConnection();
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT image_path FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$image = $row['image_path'] ?? '/TCC/public/images/sample.jpg';
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="css/home.css" />
    <title>Home</title>
  </head>
  <body>
    <div class="page-container">
      <aside class="sidebar">
        <div class="sidebar-top">
          <!-- user image as logo -->
          <img src="<?php echo htmlspecialchars($image); ?>" alt="User" class="sidebar-logo" />
        </div>

        <nav class="sidebar-nav" aria-label="Main navigation">
          <ul>
            <li>
              <a href="/TCC/public/home.php?view=announcements" class="nav-link <?php echo ($view === 'announcements') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Announcements">
                <i class="bi bi-megaphone-fill"></i>
                <span class="nav-label">Announcements</span>
              </a>
            </li>
            <li>
              <a href="/TCC/public/home.php?view=records" class="nav-link <?php echo ($view === 'records') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Records">
                <i class="bi bi-journal-text"></i>
                <span class="nav-label">Records</span>
              </a>
            </li>
            <li>
              <a href="/TCC/public/home.php?view=transparency" class="nav-link <?php echo ($view === 'transparency') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Transparency">
                <i class="bi bi-graph-up"></i>
                <span class="nav-label">Transparency</span>
              </a>
            </li>
            <li>
              <a href="/TCC/public/settings.php" class="nav-link <?php echo ($view === 'settings') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Settings">
                <i class="bi bi-gear-fill"></i>
                <span class="nav-label">Settings</span>
              </a>
            </li>
          </ul>
        </nav>

        <div class="sidebar-bottom">
          <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="/TCC/public/admin_dashboard.php" class="btn admin-icon" title="Admin Dashboard" data-bs-toggle="tooltip" data-bs-placement="right">
              <i class="bi bi-shield-check"></i>
            </a>
          <?php endif; ?>
          <a href="/TCC/BackEnd/auth/logout.php" class="btn logout-icon" title="Logout"><i class="bi bi-box-arrow-right"></i></a>
        </div>
      </aside>

      <main class="home-main">
        <?php
        // Determine view: records, announcements, transparency
        $view = isset($_GET['view']) ? $_GET['view'] : 'records';

        if ($view === 'records') {
          ?>
          <div class="records-container">
            <div class="records-header">
              <h2 class="records-title">My Records</h2>
              <p class="records-subtitle">View your academic and financial information</p>
            </div>
            
            <div class="records-grid">
              <div class="records-main">
                <div class="info-card assignment-card">
                  <div class="card-header-modern">
                    <i class="bi bi-building"></i>
                    <h3>Assignment Details</h3>
                  </div>
                  <?php
                  // initialize defaults
                  $buildingText = 'Unassigned';
                  $floorText = '';
                  $roomText = '';
                  $yearText = 'N/A';
                  $sectionText = 'N/A';

                  // attempt to read assignment from DB, with fallbacks
                  try {
                    require_once __DIR__ . '/../BackEnd/database/db.php';
                    $conn = Database::getInstance()->getConnection();
                    
                    // Ensure connection is valid
                    if (!$conn || $conn->connect_error) {
                      throw new Exception("Database connection failed");
                    }

                    $currentUserId = $_SESSION['user_id'] ?? null;
                    $currentFullName = $_SESSION['full_name'] ?? '';
                    $currentUsername = $_SESSION['username'] ?? '';

                    $userInfo = null;
                    $assignment_source = 'none';
                    $matched_key = null;

                    // 1) by user_id
                    if (!empty($currentUserId)) {
                      $ps = $conn->prepare("SELECT year, section, department, payment, sanctions, owing_amount FROM user_assignments WHERE user_id = ? LIMIT 1");
                      if ($ps) {
                        $ps->bind_param('i', $currentUserId);
                        $ps->execute();
                        $resu = $ps->get_result();
                        $userInfo = $resu ? $resu->fetch_assoc() : null;
                        $ps->close();
                      }
                      if ($userInfo) { $assignment_source = 'db:user_id'; $matched_key = $currentUserId; }
                    }

                    // 2) exact username/full_name
                    if (!$userInfo && $currentFullName !== '') {
                      $ps = $conn->prepare("SELECT year, section, department, payment, sanctions, owing_amount FROM user_assignments WHERE username = ? LIMIT 1");
                      if ($ps) {
                        $ps->bind_param('s', $currentFullName);
                        $ps->execute();
                        $resu = $ps->get_result();
                        $userInfo = $resu ? $resu->fetch_assoc() : null;
                        $ps->close();
                      }
                      if ($userInfo) { $assignment_source = 'db:exact_full_name'; $matched_key = $currentFullName; }
                    }
                    if (!$userInfo && $currentUsername !== '') {
                      $ps = $conn->prepare("SELECT year, section, department, payment, sanctions, owing_amount FROM user_assignments WHERE username = ? LIMIT 1");
                      if ($ps) {
                        $ps->bind_param('s', $currentUsername);
                        $ps->execute();
                        $resu = $ps->get_result();
                        $userInfo = $resu ? $resu->fetch_assoc() : null;
                        $ps->close();
                      }
                      if ($userInfo) { $assignment_source = 'db:exact_username'; $matched_key = $currentUsername; }
                    }

                    // 3) fuzzy LIKE on username
                    if (!$userInfo && $currentFullName !== '') {
                      $like = '%' . $conn->real_escape_string($currentFullName) . '%';
                      $ps = $conn->prepare("SELECT year, section, department, payment, sanctions, owing_amount FROM user_assignments WHERE username LIKE ? LIMIT 1");
                      if ($ps) {
                        $ps->bind_param('s', $like);
                        $ps->execute();
                        $resu = $ps->get_result();
                        $userInfo = $resu ? $resu->fetch_assoc() : null;
                        $ps->close();
                      }
                      if ($userInfo) { $assignment_source = 'db:like_full_name'; $matched_key = $currentFullName; }
                    }
                    if (!$userInfo && $currentUsername !== '') {
                      $like2 = '%' . $conn->real_escape_string($currentUsername) . '%';
                      $ps = $conn->prepare("SELECT year, section, department, payment, sanctions, owing_amount FROM user_assignments WHERE username LIKE ? LIMIT 1");
                      if ($ps) {
                        $ps->bind_param('s', $like2);
                        $ps->execute();
                        $resu = $ps->get_result();
                        $userInfo = $resu ? $resu->fetch_assoc() : null;
                        $ps->close();
                      }
                      if ($userInfo) { $assignment_source = 'db:like_username'; $matched_key = $currentUsername; }
                    }

                    // 4) try to resolve via users table then by user_id
                    if (!$userInfo && ($currentFullName !== '' || $currentUsername !== '')) {
                      $search = $currentFullName !== '' ? $currentFullName : $currentUsername;
                      $like3 = '%' . $conn->real_escape_string($search) . '%';
                      $psu = $conn->prepare("SELECT id FROM users WHERE full_name LIKE ? OR username LIKE ? LIMIT 1");
                      if ($psu) {
                        $psu->bind_param('ss', $like3, $like3);
                        $psu->execute();
                        $gres = $psu->get_result();
                        if ($g = $gres->fetch_assoc()) {
                          $tryId = (int)$g['id'];
                          $ps2 = $conn->prepare("SELECT year, section, department, payment, sanctions, owing_amount FROM user_assignments WHERE user_id = ? LIMIT 1");
                          if ($ps2) {
                            $ps2->bind_param('i', $tryId);
                            $ps2->execute();
                            $res2 = $ps2->get_result();
                            $userInfo = $res2 ? $res2->fetch_assoc() : null;
                            $ps2->close();
                          }
                          if ($userInfo) { $assignment_source = 'db:via_users'; $matched_key = $tryId; }
                        }
                        $psu->close();
                      }
                    }

                        // 5) If we have a session user_id but no assignment, try to auto-map a unique matching assignment to this user_id.
                        // This is a low-risk, per-login automatic map only when there is a single confident candidate.
                        if (empty($userInfo) && !empty($currentUserId)) {
                          // 5a) exact match by full_name or username
                          $candidateIds = [];
                          if ($currentFullName !== '') {
                            $psExact = $conn->prepare("SELECT id, year, section, username, user_id FROM user_assignments WHERE username = ? OR username = ?");
                            if ($psExact) {
                              $psExact->bind_param('ss', $currentFullName, $currentUsername);
                              $psExact->execute();
                              $resEx = $psExact->get_result();
                              while ($r = $resEx->fetch_assoc()) { $candidateIds[] = $r; }
                              $psExact->close();
                            }
                          }
                          // If exactly one candidate, map it
                          if (count($candidateIds) === 1) {
                            $cand = $candidateIds[0];
                            // only update if not already mapped
                            if (empty($cand['user_id']) || $cand['user_id'] == 0) {
                              $up = $conn->prepare("UPDATE user_assignments SET user_id = ? WHERE id = ?");
                              if ($up) {
                                $up->bind_param('ii', $currentUserId, $cand['id']);
                                @$up->execute();
                                $up->close();
                              }
                            }
                            // reload this assignment into $userInfo
                            $psReload = $conn->prepare("SELECT year, section, department, payment, sanctions, owing_amount FROM user_assignments WHERE id = ? LIMIT 1");
                            if ($psReload) {
                              $psReload->bind_param('i', $cand['id']);
                              $psReload->execute();
                              $resR = $psReload->get_result();
                              $userInfo = $resR ? $resR->fetch_assoc() : null;
                              $psReload->close();
                            }
                            if ($userInfo) { $assignment_source = 'db:auto_mapped_exact'; $matched_key = $cand['username'] ?? $cand['id']; }
                          } else {
                            // 5b) fuzzy candidate search - require a single high-confidence candidate
                            $best = null; $bestScore = 0.0; $bestId = null; $countBest = 0;
                            $norm = function($s) {
                              $s = mb_strtolower(trim($s));
                              $s = preg_replace('/[^\p{L}\p{N} ]+/u', ' ', $s);
                              $s = preg_replace('/\s+/', ' ', $s);
                              return $s;
                            };
                            $tokensA = array_filter(explode(' ', $norm($currentFullName ?: $currentUsername)));
                            if (!empty($tokensA)) {
                              $allQ = $conn->query("SELECT id, username, year, section, user_id FROM user_assignments");
                              if ($allQ) {
                                while ($r = $allQ->fetch_assoc()) {
                                  $tokensB = array_filter(explode(' ', $norm($r['username'] ?? '')));
                                  if (empty($tokensB)) continue;
                                  $common = count(array_intersect($tokensA, $tokensB));
                                  $score = $common / max(1, min(count($tokensA), count($tokensB)));
                                  if ($score > $bestScore) { $bestScore = $score; $best = $r; $bestId = $r['id']; $countBest = 1; }
                                  else if ($score === $bestScore) { $countBest++; }
                                }
                                // map only if unique best and high confidence
                                if ($best !== null && $countBest === 1 && $bestScore >= 0.8) {
                                  if (empty($best['user_id']) || $best['user_id'] == 0) {
                                    $up2 = $conn->prepare("UPDATE user_assignments SET user_id = ? WHERE id = ?");
                                    if ($up2) { $up2->bind_param('ii', $currentUserId, $bestId); @$up2->execute(); $up2->close(); }
                                  }
                                  $psr = $conn->prepare("SELECT year, section, department, payment, sanctions, owing_amount FROM user_assignments WHERE id = ? LIMIT 1");
                                  if ($psr) { $psr->bind_param('i', $bestId); $psr->execute(); $rr = $psr->get_result(); $userInfo = $rr ? $rr->fetch_assoc() : null; $psr->close(); }
                                  if ($userInfo) { $assignment_source = 'db:auto_mapped_fuzzy'; $matched_key = $best['username'] ?? $bestId; }
                                }
                              }
                            }
                          }
                        }

                    // 5) as a last resort, perform a token-overlap fuzzy match against assignment.username
                    if (!$userInfo && $currentFullName !== '') {
                      // lightweight normalization and token-overlap scoring
                      $norm = function($s) {
                        $s = mb_strtolower(trim($s));
                        $s = preg_replace('/[^\p{L}\p{N} ]+/u', ' ', $s);
                        $s = preg_replace('/\s+/', ' ', $s);
                        return $s;
                      };
                      $tokensA = array_filter(explode(' ', $norm($currentFullName)));
                      if (!empty($tokensA)) {
                        $best = null; $bestScore = 0.0;
                        $allAq = $conn->query("SELECT year, section, department, payment, sanctions, owing_amount, username, user_id FROM user_assignments");
                        if ($allAq) {
                          while ($rowA = $allAq->fetch_assoc()) {
                            $tokensB = array_filter(explode(' ', $norm($rowA['username'] ?? '')));
                            if (empty($tokensB)) continue;
                            $common = count(array_intersect($tokensA, $tokensB));
                            $score = $common / max(1, min(count($tokensA), count($tokensB)));
                            if ($score > $bestScore) { $bestScore = $score; $best = $rowA; }
                          }
                          // require reasonably high confidence (>= .6) to avoid false matches
                          if ($best !== null && $bestScore >= 0.5) {
                            $userInfo = $best;
                            $assignment_source = 'db:fuzzy_assignment';
                            $matched_key = $best['username'] ?? '';
                          }
                        }
                      }
                    }

                    if ($userInfo) {
                      $yearText = $userInfo['year'] ?? 'N/A';
                      $sectionText = $userInfo['section'] ?? 'N/A';

                      $ps2 = $conn->prepare("SELECT building, floor, room FROM section_assignments WHERE year = ? AND section = ? LIMIT 1");
                      if ($ps2) {
                        $ps2->bind_param('ss', $yearText, $sectionText);
                        $ps2->execute();
                        $res2 = $ps2->get_result();
                        if ($r2 = $res2->fetch_assoc()) {
                          $buildingText = 'Building ' . $r2['building'];
                          $floorText = $r2['floor'] . 'th Floor';
                          $roomText = 'Room ' . $r2['room'];
                        }
                        $ps2->close();
                      }
                    }
                  // end try
                  } catch (Throwable $ex) {
                    // ignore DB errors and fallback to JSON below
                  }

                  // JSON fallback
                  if (empty($userInfo)) {
                    $uaPath = __DIR__ . '/../database/user_assignments.json';
                    $ua = [];
                    if (file_exists($uaPath)) { $ua = json_decode(file_get_contents($uaPath), true) ?: []; }

                    $currentUser = $_SESSION['username'];
                    $userInfo = $ua[$currentUser] ?? null;

                    $yearText = $userInfo['year'] ?? $yearText;
                    $sectionText = $userInfo['section'] ?? $sectionText;

                    $saPath = __DIR__ . '/../database/section_assignments.json';
                    $sa = [];
                    if (file_exists($saPath)) { $sa = json_decode(file_get_contents($saPath), true) ?: []; }
                    if ($userInfo) {
                      $key = $userInfo['year'] . '|' . $userInfo['section'];
                      if (isset($sa[$key])) {
                        $buildingText = 'Building ' . $sa[$key]['building'];
                        $floorText = $sa[$key]['floor'] . 'th Floor';
                        $roomText = 'Room ' . $sa[$key]['room'];
                        $assignment_source = 'json';
                        $matched_key = $currentUser;
                      }
                    }
                  }
                  if (!isset($assignment_source)) $assignment_source = 'none';
                  ?>
                  <div class="info-grid">
                    <div class="info-item">
                      <div class="info-icon">
                        <i class="bi bi-building"></i>
                      </div>
                      <div class="info-content">
                        <span class="info-label">Building</span>
                        <span class="info-value"><?php echo htmlspecialchars($buildingText); ?></span>
                      </div>
                    </div>
                    
                    <div class="info-item">
                      <div class="info-icon">
                        <i class="bi bi-layers"></i>
                      </div>
                      <div class="info-content">
                        <span class="info-label">Floor / Room</span>
                        <span class="info-value"><?php echo htmlspecialchars($floorText . ' / ' . $roomText); ?></span>
                      </div>
                    </div>
                    
                    <div class="info-item">
                      <div class="info-icon">
                        <i class="bi bi-calendar-year"></i>
                      </div>
                      <div class="info-content">
                        <span class="info-label">Year</span>
                        <span class="info-value"><?php echo htmlspecialchars($yearText); ?></span>
                      </div>
                    </div>
                    
                    <div class="info-item">
                      <div class="info-icon">
                        <i class="bi bi-people"></i>
                      </div>
                      <div class="info-content">
                        <span class="info-label">Section</span>
                        <span class="info-value"><?php echo htmlspecialchars($sectionText); ?></span>
                      </div>
                    </div>
                  </div>
                  <?php
                    // Get payment and sanctions data from userInfo
                    $owingAmount = $userInfo['owing_amount'] ?? null;
                    $paymentStatus = $userInfo['payment'] ?? 'paid';
                    $sanctions = $userInfo['sanctions'] ?? null;
                    
                    // Parse sanctions to check for date-based sanctions
                    $sanctionText = 'No';
                    $sanctionDays = null;
                    if (!empty($sanctions)) {
                      // Try to parse date from sanctions (format: "YYYY-MM-DD" or similar)
                      if (preg_match('/(\d{4}-\d{2}-\d{2})/', $sanctions, $matches)) {
                        $sanctionDate = new DateTime($matches[1]);
                        $now = new DateTime();
                        if ($sanctionDate > $now) {
                          $diff = $now->diff($sanctionDate);
                          $sanctionDays = $diff->days;
                          $sanctionText = $sanctionDays . ' days';
                        } else {
                          $sanctionText = 'Expired';
                        }
                      } else {
                        // If it's a number, treat it as days
                        if (is_numeric($sanctions)) {
                          $sanctionDays = intval($sanctions);
                          $sanctionText = $sanctionDays . ' days';
                        } else {
                          $sanctionText = !empty($sanctions) ? 'Yes' : 'No';
                        }
                      }
                    }
                    ?>
                </div>
                
                <div class="info-card financial-card">
                  <div class="card-header-modern">
                    <i class="bi bi-wallet2"></i>
                    <h3>Financial Status</h3>
                  </div>
                  <div class="financial-grid">
                    <div class="financial-item <?php echo $paymentStatus === 'owing' ? 'status-warning' : 'status-success'; ?>">
                      <div class="financial-icon">
                        <i class="bi <?php echo $paymentStatus === 'owing' ? 'bi-exclamation-triangle' : 'bi-check-circle'; ?>"></i>
                      </div>
                      <div class="financial-content">
                        <span class="financial-label">Outstanding Balance</span>
                        <span class="financial-value">
                          <?php if ($paymentStatus === 'owing' && !empty($owingAmount)): ?>
                            ₱<?php echo htmlspecialchars($owingAmount); ?>
                          <?php elseif ($paymentStatus === 'owing'): ?>
                            Amount pending
                          <?php else: ?>
                            ₱0.00
                          <?php endif; ?>
                        </span>
                      </div>
                    </div>
                    
                    <div class="financial-item <?php echo ($sanctionDays !== null && $sanctionDays > 0) || strpos($sanctionText, 'days') !== false || $sanctionText === 'Yes' ? 'status-warning' : 'status-success'; ?>">
                      <div class="financial-icon">
                        <i class="bi <?php echo (($sanctionDays !== null && $sanctionDays > 0) || strpos($sanctionText, 'days') !== false || $sanctionText === 'Yes') ? 'bi-exclamation-triangle' : 'bi-check-circle'; ?>"></i>
                      </div>
                      <div class="financial-content">
                        <span class="financial-label">Sanctioned</span>
                        <span class="financial-value">
                          <?php if ($sanctionDays !== null && $sanctionDays > 0): ?>
                            <span class="days-remaining"><?php echo $sanctionDays; ?></span> days remaining
                          <?php elseif (strpos($sanctionText, 'days') !== false): ?>
                            <span class="days-remaining"><?php echo htmlspecialchars($sanctionText); ?></span>
                          <?php elseif ($sanctionText === 'Expired'): ?>
                            <span class="text-muted">Sanction expired</span>
                          <?php elseif ($sanctionText === 'Yes'): ?>
                            <span class="text-warning"><?php echo htmlspecialchars($sanctionText); ?></span>
                          <?php else: ?>
                            <span class="text-success"><?php echo htmlspecialchars($sanctionText); ?></span>
                          <?php endif; ?>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div class="info-source">
                  <small class="text-muted">
                    <i class="bi bi-info-circle"></i>
                    Record source: <?php echo htmlspecialchars($assignment_source ?? 'none'); ?>
                    <?php if (!empty($matched_key)): ?>
                      — matched: <?php echo htmlspecialchars((string)$matched_key); ?>
                    <?php endif; ?>
                  </small>
                </div>
              </div>
            </div>
          </div>
          <?php
        } elseif ($view === 'announcements') {
          ?>
          <div class="records-container">
            <div class="records-header">
              <h2 class="records-title">Announcements</h2>
              <p class="records-subtitle">Stay updated with the latest news and information</p>
            </div>
            <div class="info-card">
              <div class="card-header-modern">
                <i class="bi bi-megaphone-fill"></i>
                <h3>Latest Updates</h3>
              </div>
                <?php
                // Prefer announcements from DB; fallback to JSON when table missing
                $annList = [];
                $filterYear = isset($_GET['year_filter']) ? trim($_GET['year_filter']) : '';
                $filterDept = isset($_GET['dept_filter']) ? trim($_GET['dept_filter']) : '';

                try {
                  require_once __DIR__ . '/../BackEnd/database/db.php';
                  $conn = Database::getInstance()->getConnection();
                  $annQ = $conn->query("SELECT id, title, content, year, department, date FROM announcements ORDER BY date DESC");
                } catch (Throwable $ex) {
                  $annQ = false;
                }
                if ($annQ === false) {
                  // fallback to JSON
                  $annPath = __DIR__ . '/../database/announcements.json';
                  if (file_exists($annPath)) { $annList = json_decode(file_get_contents($annPath), true) ?: []; }
                }
                ?>
                <form method="get" class="row g-3 mb-3">
                  <input type="hidden" name="view" value="announcements" />
                  <div class="col-md-3">
                    <label for="year_filter" class="form-label">Year</label>
                    <select id="year_filter" name="year_filter" class="form-select">
                      <option value="">All</option>
                      <option value="1" <?php echo $filterYear==='1'?'selected':'';?>>1st Year</option>
                      <option value="2" <?php echo $filterYear==='2'?'selected':'';?>>2nd Year</option>
                      <option value="3" <?php echo $filterYear==='3'?'selected':'';?>>3rd Year</option>
                      <option value="4" <?php echo $filterYear==='4'?'selected':'';?>>4th Year</option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label for="dept_filter" class="form-label">Department</label>
                    <select id="dept_filter" name="dept_filter" class="form-select">
                      <option value="">All</option>
                      <option value="IT" <?php echo $filterDept==='IT'?'selected':'';?>>IT</option>
                      <option value="HM" <?php echo $filterDept==='HM'?'selected':'';?>>HM</option>
                      <option value="BSEED" <?php echo $filterDept==='BSEED'?'selected':'';?>>BSEED</option>
                      <option value="BEED" <?php echo $filterDept==='BEED'?'selected':'';?>>BEED</option>
                      <option value="TOURISM" <?php echo $filterDept==='TOURISM'?'selected':'';?>>TOURISM</option>
                    </select>
                  </div>
                  <div class="col-md-3 align-self-end"><button class="btn btn-secondary">Filter</button></div>
                </form>

                <ul class="list-group">
                  <?php
                  if (isset($annQ) && $annQ !== false) {
                    if ($annQ->num_rows == 0) {
                      echo '<li class="list-group-item text-muted">No announcements yet.</li>';
                    } else {
                      while ($a = $annQ->fetch_assoc()) {
                        if ($filterYear !== '' && isset($a['year']) && (string)$a['year'] !== $filterYear) continue;
                        if ($filterDept !== '' && isset($a['department']) && $a['department'] !== $filterDept) continue;
                        echo '<li class="list-group-item"><strong>' . htmlspecialchars($a['title']) . '</strong> <small class="text-muted">' . htmlspecialchars($a['date']) . '</small><div>' . nl2br(htmlspecialchars($a['content'])) . '</div></li>';
                      }
                    }
                  } else {
                    if (empty($annList)) {
                      echo '<li class="list-group-item text-muted">No announcements yet.</li>';
                    } else {
                      foreach (array_reverse($annList) as $a) {
                        if ($filterYear !== '' && isset($a['year']) && (string)$a['year'] !== $filterYear) continue;
                        if ($filterDept !== '' && isset($a['department']) && $a['department'] !== $filterDept) continue;
                        echo '<li class="list-group-item"><strong>' . htmlspecialchars($a['title']) . '</strong> <small class="text-muted">' . htmlspecialchars($a['date'] ?? '') . '</small><div>' . nl2br(htmlspecialchars($a['content'])) . '</div></li>';
                      }
                    }
                  }
                  ?>
                </ul>
              </div>
            </div>
          <?php
        } elseif ($view === 'transparency') {
          ?>
          <div class="records-container">
            <div class="records-header">
              <h2 class="records-title">Transparency / Projects</h2>
              <p class="records-subtitle">View project budgets and completion status</p>
            </div>
            <div class="info-card">
              <div class="card-header-modern">
                <i class="bi bi-graph-up"></i>
                <h3>Project Information</h3>
              </div>
                <div class="table-responsive">
                  <?php
                  $pPath = __DIR__ . '/../database/projects.json';
                  $projects = [];
                  if (file_exists($pPath)) { $projects = json_decode(file_get_contents($pPath), true) ?: []; }
                  ?>
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Project Name</th>
                        <th>Budget</th>
                        <th>Started</th>
                        <th>Completed</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($projects)): ?>
                        <tr><td colspan="4" class="text-muted">No projects found.</td></tr>
                      <?php else: ?>
                        <?php foreach ($projects as $proj): ?>
                          <tr>
                            <td><?php echo htmlspecialchars($proj['name']); ?></td>
                            <td><?php echo htmlspecialchars($proj['budget']); ?></td>
                            <td><?php echo htmlspecialchars($proj['started']); ?></td>
                            <td><?php echo ($proj['completed']==='yes')? 'Yes':'No'; ?></td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
            </div>
          <?php
        }
        ?>
      </main>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
      // enable Bootstrap tooltips
      document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.forEach(function (el) {
          new bootstrap.Tooltip(el)
        })
      })
    </script>
  </body>
</html>
