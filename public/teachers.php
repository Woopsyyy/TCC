<?php
session_start();
// Ensure user is logged in and is a teacher
if (!isset($_SESSION['username'])) {
  header('Location: /TCC/public/index.html');
  exit();
}

// Check if user is a teacher
$userRole = $_SESSION['role'] ?? 'student';
if ($userRole !== 'teacher') {
  header('Location: /TCC/public/home.php');
  exit();
}

// Prefer values saved in session to avoid extra DB queries
$image = $_SESSION['image_path'] ?? '/TCC/public/images/sample.jpg';
$schoolId = $_SESSION['school_id'] ?? '';
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];

// default view: schedule for teachers
$view = isset($_GET['view']) ? $_GET['view'] : 'schedule';
require_once __DIR__ . '/../BackEnd/database/db.php';
$conn = Database::getInstance()->getConnection();
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT id, image_path, school_id, role, created_at FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$image = $row['image_path'] ?? '/TCC/public/images/sample.jpg';
$userRole = $row['role'] ?? $_SESSION['role'] ?? 'teacher';
if (empty($schoolId) && isset($row['school_id']) && $row['school_id'] !== '') {
  $schoolId = $row['school_id'];
  $_SESSION['school_id'] = $schoolId;
} elseif (empty($schoolId) && $row) {
  require_once __DIR__ . '/../BackEnd/helpers/school_id.php';
  try {
    $schoolId = ensure_school_id_for_user($conn, $row);
    $_SESSION['school_id'] = $schoolId;
  } catch (Throwable $th) {
    $schoolId = '';
  }
}

function formatOrdinal($number) {
  $number = intval($number);
  if ($number <= 0) { return ''; }
  $suffixes = ['th','st','nd','rd','th','th','th','th','th','th'];
  $value = $number % 100;
  if ($value >= 11 && $value <= 13) {
    return $number . 'th';
  }
  return $number . ($suffixes[$number % 10] ?? 'th');
}

// Days of the week for schedule ordering
$dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="css/home.css" />
    <link rel="stylesheet" href="css/home_sidebar.css" />
    <title>Teacher Dashboard</title>
  </head>
  <body>
    <div class="page-container">
      <aside class="sidebar">
        <div class="sidebar-glass"></div>
        <div class="sidebar-top">
          <!-- user image as logo -->
          <div class="sidebar-profile-tile">
            <img src="<?php echo htmlspecialchars($image); ?>" alt="User" class="sidebar-logo" />
            <?php if (!empty($schoolId)): ?>
              <span class="sidebar-school-id" title="School ID"><?php echo htmlspecialchars($schoolId); ?></span>
            <?php endif; ?>
            <?php if (!empty($userRole)): ?>
              <span class="sidebar-role" title="Role"><?php echo htmlspecialchars(ucfirst($userRole)); ?></span>
            <?php endif; ?>
          </div>
        </div>

        <nav class="sidebar-nav" aria-label="Main navigation">
          <ul>
            <li>
              <a href="/TCC/public/teachers.php?view=announcements" class="nav-link <?php echo ($view === 'announcements') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Announcements">
                <i class="bi bi-megaphone-fill"></i>
                <span class="nav-label">Announcements</span>
              </a>
            </li>
            <li>
              <a href="/TCC/public/teachers.php?view=schedule" class="nav-link <?php echo ($view === 'schedule') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Schedule">
                <i class="bi bi-calendar-week"></i>
                <span class="nav-label">Schedule</span>
              </a>
            </li>
            <li>
              <a href="/TCC/public/teachers.php?view=transparency" class="nav-link <?php echo ($view === 'transparency') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Transparency">
                <i class="bi bi-graph-up"></i>
                <span class="nav-label">Transparency</span>
              </a>
            </li>
            <li>
              <a href="/TCC/public/teachers.php?view=settings" class="nav-link <?php echo ($view === 'settings') ? 'active' : '' ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="Settings">
                <i class="bi bi-gear-fill"></i>
                <span class="nav-label">Settings</span>
              </a>
            </li>
          </ul>
        </nav>

        <div class="sidebar-bottom">
          <a href="/TCC/BackEnd/auth/logout.php" class="btn logout-icon" title="Logout"><i class="bi bi-box-arrow-right"></i></a>
        </div>
      </aside>

      <main class="home-main">
        <?php
        $view = isset($_GET['view']) ? $_GET['view'] : 'schedule';
        $heroSpotlights = [
          'schedule' => [
            'hero_copy' => 'View your teaching schedule, class times, and room assignments in one organized view.',
            'spotlight_eyebrow' => 'Teaching schedule',
            'spotlight_title' => 'My Schedule',
            'spotlight_copy' => 'Check your weekly schedule, see which classes you teach, and manage your time effectively.'
          ],
          'announcements' => [
            'hero_copy' => 'Catch up on campus headlines, filter by year or department, and keep every announcement at your fingertips.',
            'spotlight_eyebrow' => 'Latest broadcasts',
            'spotlight_title' => 'Announcements',
            'spotlight_copy' => 'Browse targeted updates, stay informed on school activities, and never miss important campus news.'
          ],
          'transparency' => [
            'hero_copy' => 'See where resources go, review project milestones, and keep the community informed with transparent reporting.',
            'spotlight_eyebrow' => 'Project insights',
            'spotlight_title' => 'Transparency',
            'spotlight_copy' => 'Explore school project budgets, completion status, and milestones through an accessible transparency log.'
          ],
          'settings' => [
            'hero_copy' => 'Personalize your profile, update your login details, and keep your account aligned with your current information.',
            'spotlight_eyebrow' => 'Account controls',
            'spotlight_title' => 'Settings',
            'spotlight_copy' => 'Update your username, display name, password, and profile picture to keep your account up to date.'
          ],
        ];
        $activeSpotlight = $heroSpotlights[$view] ?? $heroSpotlights['schedule'];
        ?>
        <section class="dashboard-hero">
          <div class="hero-content">
            <span class="hero-eyebrow">Teacher Dashboard</span>
            <h1 class="hero-title">Hi, <?php echo htmlspecialchars($full_name); ?>!</h1>
            <p class="hero-copy">
              <?php echo htmlspecialchars($activeSpotlight['hero_copy']); ?>
            </p>
            <div class="hero-action-group">
              <a class="hero-action <?php echo ($view === 'schedule') ? 'active' : ''; ?>" href="/TCC/public/teachers.php?view=schedule">
                <i class="bi bi-calendar-week"></i>
                <span>Schedule</span>
              </a>
              <a class="hero-action <?php echo ($view === 'announcements') ? 'active' : ''; ?>" href="/TCC/public/teachers.php?view=announcements">
                <i class="bi bi-megaphone-fill"></i>
                <span>Announcements</span>
              </a>
              <a class="hero-action <?php echo ($view === 'transparency') ? 'active' : ''; ?>" href="/TCC/public/teachers.php?view=transparency">
                <i class="bi bi-graph-up-arrow"></i>
                <span>Transparency</span>
              </a>
              <a class="hero-action <?php echo ($view === 'settings') ? 'active' : ''; ?>" href="/TCC/public/teachers.php?view=settings">
                <i class="bi bi-gear-fill"></i>
                <span>Settings</span>
              </a>
            </div>
          </div>
          <div class="hero-spotlight">
            <div class="spotlight-card">
              <span class="spotlight-eyebrow"><?php echo htmlspecialchars($activeSpotlight['spotlight_eyebrow']); ?></span>
              <h2 class="spotlight-title"><?php echo htmlspecialchars($activeSpotlight['spotlight_title']); ?></h2>
              <p class="spotlight-copy"><?php echo htmlspecialchars($activeSpotlight['spotlight_copy']); ?></p>
            </div>
            <div class="spotlight-card alt">
              <span class="spotlight-eyebrow">Stay updated</span>
              <h2 class="spotlight-title">Announcements Feed</h2>
              <p class="spotlight-copy">Filter updates by year or department so you never miss the details that matter most.</p>
            </div>
          </div>
        </section>
        <?php
        if ($view === 'schedule') {
          ?>
          <div class="records-container">
            <div class="records-header">
              <h2 class="records-title">My Schedule</h2>
              <p class="records-subtitle">View your teaching schedule and class assignments</p>
            </div>
            
            <div class="records-main">
              <?php
              try {
                $currentFullName = $_SESSION['full_name'] ?? '';
                $currentUsername = $_SESSION['username'] ?? '';
                
                // Query schedules where instructor matches the teacher
                $schedules = [];
                $scheduleQuery = $conn->prepare("SELECT id, year, subject, day, time_start, time_end, room, section, building FROM schedules WHERE instructor = ? OR instructor = ? ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), time_start");
                if ($scheduleQuery) {
                  $scheduleQuery->bind_param('ss', $currentFullName, $currentUsername);
                  $scheduleQuery->execute();
                  $scheduleResult = $scheduleQuery->get_result();
                  while ($scheduleRow = $scheduleResult->fetch_assoc()) {
                    $schedules[] = $scheduleRow;
                  }
                  $scheduleQuery->close();
                }
                
                if (empty($schedules)) {
                  ?>
                  <div class="info-card">
                    <div class="card-header-modern">
                      <i class="bi bi-calendar-x"></i>
                      <h3>No Schedule Found</h3>
                    </div>
                    <p class="text-muted mb-0">You don't have any scheduled classes yet. Please contact the administrator to set up your schedule.</p>
                  </div>
                  <?php
                } else {
                  // Group schedules by day
                  $schedulesByDay = [];
                  foreach ($schedules as $schedule) {
                    $day = $schedule['day'] ?? 'Unknown';
                    if (!isset($schedulesByDay[$day])) {
                      $schedulesByDay[$day] = [];
                    }
                    $schedulesByDay[$day][] = $schedule;
                  }
                  
                  // Sort days according to dayOrder
                  uksort($schedulesByDay, function($a, $b) use ($dayOrder) {
                    $posA = array_search($a, $dayOrder);
                    $posB = array_search($b, $dayOrder);
                    if ($posA === false) $posA = 999;
                    if ($posB === false) $posB = 999;
                    return $posA - $posB;
                  });
                  
                  foreach ($schedulesByDay as $day => $daySchedules) {
                    ?>
                    <div class="info-card">
                      <div class="card-header-modern">
                        <i class="bi bi-calendar-day"></i>
                        <h3><?php echo htmlspecialchars($day); ?></h3>
                      </div>
                      <div class="grades-grid">
                        <?php foreach ($daySchedules as $schedule): 
                          $timeStart = date('g:i A', strtotime($schedule['time_start']));
                          $timeEnd = date('g:i A', strtotime($schedule['time_end']));
                          $timeRange = $timeStart . ' - ' . $timeEnd;
                        ?>
                          <div class="grade-card-modern">
                            <div class="grade-card-header-modern">
                              <div class="grade-subject-info">
                                <h5 class="grade-subject-name"><?php echo htmlspecialchars($schedule['subject']); ?></h5>
                                <p class="grade-instructor">
                                  <i class="bi bi-clock"></i>
                                  <span><?php echo htmlspecialchars($timeRange); ?></span>
                                </p>
                              </div>
                            </div>
                            <div class="grade-details-modern">
                              <div class="grade-detail-item">
                                <span class="grade-period">
                                  <i class="bi bi-circle-fill"></i>
                                  Year
                                </span>
                                <span class="grade-number"><?php echo htmlspecialchars($schedule['year'] ?? 'N/A'); ?></span>
                              </div>
                              <?php if (!empty($schedule['section'])): ?>
                              <div class="grade-detail-item">
                                <span class="grade-period">
                                  <i class="bi bi-circle-fill"></i>
                                  Section
                                </span>
                                <span class="grade-number"><?php echo htmlspecialchars($schedule['section']); ?></span>
                              </div>
                              <?php endif; ?>
                              <?php if (!empty($schedule['room'])): ?>
                              <div class="grade-detail-item">
                                <span class="grade-period">
                                  <i class="bi bi-circle-fill"></i>
                                  Room
                                </span>
                                <span class="grade-number"><?php echo htmlspecialchars($schedule['room']); ?></span>
                              </div>
                              <?php endif; ?>
                              <?php if (!empty($schedule['building'])): ?>
                              <div class="grade-detail-item">
                                <span class="grade-period">
                                  <i class="bi bi-circle-fill"></i>
                                  Building
                                </span>
                                <span class="grade-number"><?php echo htmlspecialchars($schedule['building']); ?></span>
                              </div>
                              <?php endif; ?>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                    <?php
                  }
                }
              } catch (Throwable $ex) {
                ?>
                <div class="info-card">
                  <div class="card-header-modern">
                    <i class="bi bi-exclamation-triangle"></i>
                    <h3>Error Loading Schedule</h3>
                  </div>
                  <p class="text-muted mb-0">Unable to load your schedule. Please try again later.</p>
                </div>
                <?php
              }
              ?>
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
            <div class="records-main">
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
              
              // Collect announcements
              $announcements = [];
              if (isset($annQ) && $annQ !== false) {
                while ($a = $annQ->fetch_assoc()) {
                  if ($filterYear !== '' && isset($a['year']) && (string)$a['year'] !== $filterYear) continue;
                  if ($filterDept !== '' && isset($a['department']) && $a['department'] !== $filterDept) continue;
                  $announcements[] = $a;
                }
              } else {
                foreach (array_reverse($annList) as $a) {
                  if ($filterYear !== '' && isset($a['year']) && (string)$a['year'] !== $filterYear) continue;
                  if ($filterDept !== '' && isset($a['department']) && $a['department'] !== $filterDept) continue;
                  $announcements[] = $a;
                }
              }
              ?>
              
              <div class="info-card">
                <div class="card-header-modern">
                  <i class="bi bi-funnel"></i>
                  <h3>Filter Announcements</h3>
                </div>
                <form method="get" class="announcements-filter-form">
                  <input type="hidden" name="view" value="announcements" />
                  <div class="filter-group">
                    <label for="year_filter" class="filter-label">
                      <i class="bi bi-calendar-year"></i>
                      Year Level
                    </label>
                    <select id="year_filter" name="year_filter" class="filter-select">
                      <option value="">All Years</option>
                      <option value="1" <?php echo $filterYear==='1'?'selected':'';?>>1st Year</option>
                      <option value="2" <?php echo $filterYear==='2'?'selected':'';?>>2nd Year</option>
                      <option value="3" <?php echo $filterYear==='3'?'selected':'';?>>3rd Year</option>
                      <option value="4" <?php echo $filterYear==='4'?'selected':'';?>>4th Year</option>
                    </select>
                  </div>
                  <div class="filter-group">
                    <label for="dept_filter" class="filter-label">
                      <i class="bi bi-building"></i>
                      Department
                    </label>
                    <select id="dept_filter" name="dept_filter" class="filter-select">
                      <option value="">All Departments</option>
                      <option value="IT" <?php echo $filterDept==='IT'?'selected':'';?>>IT</option>
                      <option value="HM" <?php echo $filterDept==='HM'?'selected':'';?>>HM</option>
                      <option value="BSEED" <?php echo $filterDept==='BSEED'?'selected':'';?>>BSEED</option>
                      <option value="BEED" <?php echo $filterDept==='BEED'?'selected':'';?>>BEED</option>
                      <option value="TOURISM" <?php echo $filterDept==='TOURISM'?'selected':'';?>>TOURISM</option>
                    </select>
                  </div>
                  <button type="submit" class="filter-btn">
                    <i class="bi bi-search"></i>
                    Apply Filters
                  </button>
                </form>
              </div>

              <?php if (empty($announcements)): ?>
                <div class="info-card">
                  <div class="card-header-modern">
                    <i class="bi bi-megaphone"></i>
                    <h3>No Announcements</h3>
                  </div>
                  <p class="text-muted mb-0">No announcements match your current filters. Check back later for updates.</p>
                </div>
              <?php else: ?>
                <div class="announcements-grid">
                  <?php foreach ($announcements as $a): ?>
                    <div class="announcement-card-modern">
                      <div class="announcement-card-header">
                        <div class="announcement-title-section">
                          <h4 class="announcement-title"><?php echo htmlspecialchars($a['title']); ?></h4>
                          <div class="announcement-meta">
                            <span class="announcement-date">
                              <i class="bi bi-calendar3"></i>
                              <?php echo htmlspecialchars($a['date'] ?? 'Date not specified'); ?>
                            </span>
                          </div>
                        </div>
                      </div>
                      <div class="announcement-content">
                        <p><?php echo nl2br(htmlspecialchars($a['content'])); ?></p>
                      </div>
                      <div class="announcement-footer">
                        <?php if (!empty($a['year'])): ?>
                          <span class="announcement-badge">
                            <i class="bi bi-mortarboard"></i>
                            <?php 
                              $yearNum = (int)$a['year'];
                              echo $yearNum > 0 ? formatOrdinal($yearNum) . ' Year' : htmlspecialchars($a['year']);
                            ?>
                          </span>
                        <?php endif; ?>
                        <?php if (!empty($a['department'])): ?>
                          <span class="announcement-badge">
                            <i class="bi bi-building"></i>
                            <?php echo htmlspecialchars($a['department']); ?>
                          </span>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <?php
        } elseif ($view === 'settings') {
          ?>
          <div class="records-container">
            <div class="records-header">
              <h2 class="records-title">
                <i class="bi bi-gear-fill"></i> Settings
              </h2>
              <p class="records-subtitle">Manage your account preferences and profile information</p>
            </div>

            <?php if (isset($_GET['success'])): ?>
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>Profile updated successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php elseif (isset($_GET['error'])): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>An error occurred: <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

            <div class="info-card">
              <div class="card-header-modern">
                <i class="bi bi-person-circle"></i>
                <h3>Profile Information</h3>
              </div>

              <form id="settingsForm" action="/TCC/BackEnd/auth/update_profile.php" method="post" enctype="multipart/form-data">
                <div class="settings-profile-section">
                  <div class="profile-image-container">
                    <div class="profile-image-wrapper">
                      <img id="preview" src="<?php echo htmlspecialchars($image); ?>" class="profile-preview-large" alt="Profile" />
                      <label for="profile_image" class="profile-upload-label">
                        <i class="bi bi-camera-fill"></i>
                        <span>Change Photo</span>
                      </label>
                      <input type="file" name="profile_image" id="profile_image" accept="image/*" class="profile-upload-input" />
                    </div>
                  </div>

                  <div class="settings-form-fields">
                    <div class="settings-field">
                      <label for="username" class="settings-label">
                        <i class="bi bi-person"></i> Username
                      </label>
                      <input id="username" name="username" class="settings-input" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required />
                      <div id="usernameFeedback" class="settings-feedback text-danger" style="display:none"></div>
                    </div>

                    <div class="settings-field">
                      <label for="full_name" class="settings-label">
                        <i class="bi bi-card-text"></i> Full Name
                      </label>
                      <input id="full_name" name="full_name" class="settings-input" value="<?php echo htmlspecialchars($full_name); ?>" required />
                      <div id="fullnameFeedback" class="settings-feedback text-danger" style="display:none"></div>
                    </div>

                    <div class="settings-field">
                      <label for="password" class="settings-label">
                        <i class="bi bi-lock"></i> New Password
                      </label>
                      <input id="password" name="password" type="password" class="settings-input" placeholder="Leave blank to keep current password" />
                      <small class="settings-hint">Leave blank if you don't want to change your password</small>
                    </div>

                    <div class="settings-actions">
                      <button class="btn btn-primary settings-save-btn" type="submit">
                        <i class="bi bi-check-lg me-2"></i>Save Changes
                      </button>
                      <a href="/TCC/public/teachers.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Cancel
                      </a>
                    </div>
                  </div>
                </div>
              </form>
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
            <div class="records-main">
              <?php
              $pPath = __DIR__ . '/../database/projects.json';
              $projects = [];
              if (file_exists($pPath)) { $projects = json_decode(file_get_contents($pPath), true) ?: []; }
              ?>
              
              <?php if (empty($projects)): ?>
                <div class="info-card">
                  <div class="card-header-modern">
                    <i class="bi bi-folder-x"></i>
                    <h3>No Projects</h3>
                  </div>
                  <p class="text-muted mb-0">No project information available at this time.</p>
                </div>
              <?php else: ?>
                <div class="projects-grid">
                  <?php foreach ($projects as $proj): 
                    $isCompleted = isset($proj['completed']) && strtolower($proj['completed']) === 'yes';
                  ?>
                    <div class="project-card-modern">
                      <div class="project-card-header">
                        <div class="project-title-section">
                          <h4 class="project-title"><?php echo htmlspecialchars($proj['name']); ?></h4>
                          <div class="project-status-badge <?php echo $isCompleted ? 'status-completed' : 'status-ongoing'; ?>">
                            <i class="bi <?php echo $isCompleted ? 'bi-check-circle-fill' : 'bi-clock-history'; ?>"></i>
                            <span><?php echo $isCompleted ? 'Completed' : 'Ongoing'; ?></span>
                          </div>
                        </div>
                      </div>
                      <div class="project-details">
                        <div class="project-detail-item">
                          <div class="project-detail-label">
                            <i class="bi bi-cash-coin"></i>
                            <span>Budget</span>
                          </div>
                          <div class="project-detail-value"><?php echo htmlspecialchars($proj['budget']); ?></div>
                        </div>
                        <div class="project-detail-item">
                          <div class="project-detail-label">
                            <i class="bi bi-calendar-event"></i>
                            <span>Started</span>
                          </div>
                          <div class="project-detail-value"><?php echo htmlspecialchars($proj['started']); ?></div>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
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
    <?php if ($view === 'settings'): ?>
    <script>
      document.getElementById('profile_image').addEventListener('change', function (e) {
        const f = e.target.files[0];
        if (!f) return;
        const url = URL.createObjectURL(f);
        document.getElementById('preview').src = url;
      });

      function debounce(fn, wait) {
        let t;
        return function (...args) {
          clearTimeout(t);
          t = setTimeout(() => fn.apply(this, args), wait);
        };
      }

      const usernameInput = document.getElementById('username');
      const fullInput = document.getElementById('full_name');

      if (usernameInput) {
        usernameInput.addEventListener('input', debounce(function () {
          const val = this.value.trim();
          if (!val) return;
          fetch('/TCC/BackEnd/auth/check_availability.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'type=username&value=' + encodeURIComponent(val)
          })
          .then(r => r.json())
          .then(j => {
            const fb = document.getElementById('usernameFeedback');
            if (!j.success) { fb.style.display = 'block'; fb.textContent = 'Error checking availability'; }
            else if (!j.available) { fb.style.display = 'block'; fb.textContent = 'Username already taken'; }
            else { fb.style.display = 'none'; }
          });
        }, 400));
      }

      if (fullInput) {
        fullInput.addEventListener('input', debounce(function () {
          const val = this.value.trim();
          if (!val) return;
          fetch('/TCC/BackEnd/auth/check_availability.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'type=full_name&value=' + encodeURIComponent(val)
          })
          .then(r => r.json())
          .then(j => {
            const fb = document.getElementById('fullnameFeedback');
            if (!j.success) { fb.style.display = 'block'; fb.textContent = 'Error checking availability'; }
            else if (!j.available) { fb.style.display = 'block'; fb.textContent = 'Full name already used'; }
            else { fb.style.display = 'none'; }
          });
        }, 400));
      }
    </script>
    <?php endif; ?>
  </body>
</html>

