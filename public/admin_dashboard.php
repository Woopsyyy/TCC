<?php
session_start();
// only for admins
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header('Location: /TCC/public/index.html');
  exit();
}

function formatOrdinal($number) {
  $number = (int)$number;
  $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
  if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
    return $number . 'th';
  }
  return $number . ($ends[$number % 10] ?? 'th');
}

$image = $_SESSION['image_path'] ?? '/TCC/public/images/sample.jpg';
$adminName = $_SESSION['full_name'] ?? $_SESSION['username'];
$schoolId = $_SESSION['school_id'] ?? '';
$userRole = $_SESSION['role'] ?? 'admin';
$section = isset($_GET['section']) ? $_GET['section'] : 'announcements';

if (empty($schoolId)) {
  try {
    require_once __DIR__ . '/../BackEnd/database/db.php';
    $conn = Database::getInstance()->getConnection();
    $stmt = $conn->prepare("SELECT id, school_id, role, created_at FROM users WHERE username = ? LIMIT 1");
    if ($stmt) {
      $usernameLookup = $_SESSION['username'];
      $stmt->bind_param('s', $usernameLookup);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($row = $res->fetch_assoc()) {
        $schoolId = $row['school_id'] ?? '';
        $userRole = $row['role'] ?? $_SESSION['role'] ?? 'admin';
        if (empty($schoolId)) {
          require_once __DIR__ . '/../BackEnd/helpers/school_id.php';
          try {
            $schoolId = ensure_school_id_for_user($conn, $row);
          } catch (Throwable $th) {
            $schoolId = '';
          }
        }
        if (!empty($schoolId)) {
          $_SESSION['school_id'] = $schoolId;
        }
      }
      $stmt->close();
    }
  } catch (Throwable $th) {
    $schoolId = '';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes" />
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="css/home.css" />
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin_dashboard.css" />
  </head>
  <body class="admin-dashboard">
    <div class="page-container">
      <aside class="sidebar">
        <div class="sidebar-glass"></div>
        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <form method="post" action="/TCC/BackEnd/admin/manage_users.php">
                <input type="hidden" name="action" value="update" />
                <div class="modal-header">
                  <h5 class="modal-title">
                    <i class="bi bi-pencil-square me-2"></i>Edit User Financial Status
                  </h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <div class="mb-3">
                    <label class="admin-form-label"><i class="bi bi-person-badge"></i> Full Name</label>
                    <p id="modalFullNameDisplay" class="form-control-plaintext fw-bold fs-5"></p>
                    <input type="hidden" name="full_name" id="modalFullName" />
                  </div>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="admin-form-label"><i class="bi bi-wallet2"></i> Payment Status</label>
                      <select name="payment" id="modalPayment" class="form-select form-select-lg">
                        <option value="paid">Paid</option>
                        <option value="owing">Lacking Payment</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="admin-form-label"><i class="bi bi-building"></i> Department</label>
                      <select name="department" id="modalDepartment" class="form-select form-select-lg">
                        <option value="">(none)</option>
                        <option value="IT">IT</option>
                        <option value="HM">HM</option>
                        <option value="BSEED">BSEED</option>
                        <option value="BEED">BEED</option>
                        <option value="TOURISM">TOURISM</option>
                      </select>
                    </div>
                  </div>
                  <div class="mb-3 mt-3" id="owingRow" style="display:none;">
                    <label class="admin-form-label"><i class="bi bi-currency-dollar"></i> Amount Owing</label>
                    <input name="owing_amount" id="modalOwingAmount" class="form-control form-control-lg" placeholder="e.g. 2350.00" type="number" step="0.01" min="0"/>
                  </div>
                  <div class="mb-3">
                    <label class="admin-form-label"><i class="bi bi-exclamation-triangle"></i> Sanctions (Days)</label>
                    <input name="sanctions" id="modalSanctions" class="form-control form-control-lg" placeholder="Enter number of days (e.g. 3) or date (YYYY-MM-DD)" />
                    <small class="text-muted">Enter number of days for sanction duration, or a date in YYYY-MM-DD format</small>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                  </button>
                  <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>Save Changes
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <div class="sidebar-top">
          <div class="sidebar-profile-tile">
            <img src="<?php echo htmlspecialchars($image); ?>" class="sidebar-logo" alt="admin"/>
            <?php if (!empty($schoolId)): ?>
              <span class="sidebar-school-id" title="School ID"><?php echo htmlspecialchars($schoolId); ?></span>
            <?php endif; ?>
            <?php if (!empty($userRole)): ?>
              <span class="sidebar-role" title="Role"><?php echo htmlspecialchars(ucfirst($userRole)); ?></span>
            <?php endif; ?>
          </div>
        </div>
        <nav class="sidebar-nav">
          <ul>
            <li><a href="/TCC/public/admin_dashboard.php?section=announcements" class="nav-link <?php echo ($section==='announcements')?'active':''?>" data-bs-toggle="tooltip" title="Announcements"><i class="bi bi-megaphone-fill"></i><span class="nav-label">Announcements</span></a></li>
            <li><a href="/TCC/public/admin_dashboard.php?section=buildings" class="nav-link <?php echo ($section==='buildings')?'active':''?>" data-bs-toggle="tooltip" title="Buildings"><i class="bi bi-building"></i><span class="nav-label">Buildings</span></a></li>
            <li><a href="/TCC/public/admin_dashboard.php?section=projects" class="nav-link <?php echo ($section==='projects')?'active':''?>" data-bs-toggle="tooltip" title="Projects"><i class="bi bi-folder-fill"></i><span class="nav-label">Projects</span></a></li>
            <li><a href="/TCC/public/admin_dashboard.php?section=user_management" class="nav-link <?php echo ($section==='user_management')?'active':''?>" data-bs-toggle="tooltip" title="User Management"><i class="bi bi-people-fill"></i><span class="nav-label">User Management</span></a></li>
            <li><a href="/TCC/public/admin_dashboard.php?section=sections" class="nav-link <?php echo ($section==='sections')?'active':''?>" data-bs-toggle="tooltip" title="Sections"><i class="bi bi-collection-fill"></i><span class="nav-label">Sections</span></a></li>
            <li><a href="/TCC/public/admin_dashboard.php?section=grade_system" class="nav-link <?php echo ($section==='grade_system')?'active':''?>" data-bs-toggle="tooltip" title="Grade System"><i class="bi bi-journal-bookmark-fill"></i><span class="nav-label">Grade System</span></a></li>
          </ul>
        </nav>
        <div class="sidebar-bottom">
          <a href="/TCC/public/home.php" class="btn btn-switch-view sidebar-switch-btn" title="Switch to User View">
            <i class="bi bi-people-fill"></i>
            <span>User View</span>
          </a>
          <a href="/TCC/BackEnd/auth/logout.php" class="btn logout-icon" title="Logout"><i class="bi bi-box-arrow-right"></i></a>
        </div>
      </aside>

      <main class="home-main">
        <?php
        $heroSpotlights = [
          'announcements' => [
            'title' => 'Announcements',
            'copy' => 'Compose fresh updates, pin urgent bulletins, and keep the campus informed with streamlined editing tools.'
          ],
          'buildings' => [
            'title' => 'Facilities & Rooms',
            'copy' => 'Assign sections in moments, review capacities, and keep building details aligned with the physical campus.'
          ],
          'projects' => [
            'title' => 'Campus Projects',
            'copy' => 'Surface budgets, highlight completion milestones, and maintain transparency on ongoing initiatives.'
          ],
          'user_management' => [
            'title' => 'User Management',
            'copy' => 'Handle assignments, monitor financial standing, and manage sanctions from a single cohesive space.'
          ],
          'sections' => [
            'title' => 'Sections',
            'copy' => 'Create and manage academic sections for each year level. Organize students into groups like Power, Benevolence, and more.'
          ],
          'grade_system' => [
            'title' => 'Grade System',
            'copy' => 'Manage student progress, semester summaries, and detailed records with the enhanced modal experience.'
          ],
        ];
        $activeSpotlight = $heroSpotlights[$section] ?? $heroSpotlights['grade_system'];
        ?>
        <section class="dashboard-hero">
          <div class="hero-content">
            <span class="hero-eyebrow">Administrative Portal</span>
            <h1 class="hero-title">Welcome back, <?php echo htmlspecialchars($adminName); ?>.</h1>
            <p class="hero-copy">
              Stay in control of campus activity with quick insights and refined tools designed around our new aesthetic.
              Use the quick links to jump straight into the workspace you need.
            </p>
            <div class="hero-action-group">
              <a class="hero-action <?php echo ($section === 'announcements') ? 'active' : ''; ?>" href="/TCC/public/admin_dashboard.php?section=announcements">
                <i class="bi bi-megaphone-fill"></i>
                <span>Announcements</span>
              </a>
              <a class="hero-action <?php echo ($section === 'buildings') ? 'active' : ''; ?>" href="/TCC/public/admin_dashboard.php?section=buildings">
                <i class="bi bi-building"></i>
                <span>Facilities</span>
              </a>
              <a class="hero-action <?php echo ($section === 'projects') ? 'active' : ''; ?>" href="/TCC/public/admin_dashboard.php?section=projects">
                <i class="bi bi-folder-fill"></i>
                <span>Projects</span>
              </a>
              <a class="hero-action <?php echo ($section === 'user_management') ? 'active' : ''; ?>" href="/TCC/public/admin_dashboard.php?section=user_management">
                <i class="bi bi-people-fill"></i>
                <span>Users</span>
              </a>
              <a class="hero-action <?php echo ($section === 'sections') ? 'active' : ''; ?>" href="/TCC/public/admin_dashboard.php?section=sections">
                <i class="bi bi-collection-fill"></i>
                <span>Sections</span>
              </a>
              <a class="hero-action <?php echo ($section === 'grade_system') ? 'active' : ''; ?>" href="/TCC/public/admin_dashboard.php?section=grade_system">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span>Grades</span>
              </a>
              </div>
            </div>
          <div class="hero-spotlight">
            <div class="spotlight-card">
              <span class="spotlight-eyebrow">Current Focus</span>
              <h2 class="spotlight-title"><?php echo htmlspecialchars($activeSpotlight['title']); ?></h2>
              <p class="spotlight-copy"><?php echo htmlspecialchars($activeSpotlight['copy']); ?></p>
              </div>
            <div class="spotlight-card alt">
              <span class="spotlight-eyebrow">Need to switch?</span>
              <h2 class="spotlight-title">Jump to User View</h2>
              <p class="spotlight-copy">Preview the student experience instantly to ensure everything looks just right.</p>
              <a class="spotlight-link" href="/TCC/public/home.php">
                <i class="bi bi-arrow-right-circle"></i>
                Go to User Dashboard
              </a>
              </div>
          </div>
        </section>

          <?php if ($section === 'announcements'): ?>
            <?php
            // announcement edit support and pagination
            require_once __DIR__ . '/../BackEnd/database/db.php';
            $conn = Database::getInstance()->getConnection();
            $editId = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
            $editRow = null;
            if ($editId > 0) {
              $s = $conn->prepare("SELECT id, title, content, year, department FROM announcements WHERE id = ? LIMIT 1");
              $s->bind_param('i', $editId);
              $s->execute();
              $r = $s->get_result();
              $editRow = $r->fetch_assoc();
            }

            // pagination variables for announcements
            $annPerPage = 5;
            $annPage = isset($_GET['ann_page']) ? max(1, intval($_GET['ann_page'])) : 1;
            $annOffset = ($annPage - 1) * $annPerPage;
            $annList = [];
            $annTotal = 0;
            $annTotalPages = 1;
            try {
              // count total announcements
              $countRes = $conn->query("SELECT COUNT(*) as c FROM announcements");
              if ($countRes) { $cntRow = $countRes->fetch_assoc(); $annTotal = intval($cntRow['c']); }
              $annTotalPages = max(1, intval(ceil($annTotal / $annPerPage)));
              $stmt = $conn->prepare("SELECT id, title, content, year, department, date FROM announcements ORDER BY date DESC LIMIT ? OFFSET ?");
              if ($stmt) {
                $stmt->bind_param('ii', $annPerPage, $annOffset);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()) { $annList[] = $row; }
                $stmt->close();
              }
            } catch (Throwable $ex) {
              // fallback to JSON when table missing or DB error
              $annPathFallback = __DIR__ . '/../database/announcements.json';
              if (file_exists($annPathFallback)) { $all = json_decode(file_get_contents($annPathFallback), true) ?: []; } else { $all = []; }
              $annTotal = count($all);
              $annTotalPages = max(1, intval(ceil($annTotal / $annPerPage)));
              $start = $annOffset;
              $annList = array_slice($all, $start, $annPerPage);
            }
            ?>
            <div class="records-container">
              <div class="records-header">
                <h2 class="records-title">
                  <i class="bi bi-megaphone-fill"></i> Announcements
                </h2>
                <p class="records-subtitle">Manage and create announcements for students</p>
              </div>
              <div class="records-main">
            <div class="info-card">
              <div class="card-header-modern">
                <i class="bi bi-megaphone-fill"></i>
                    <h3><?php echo $editRow ? 'Edit Announcement' : 'Create New Announcement'; ?></h3>
              </div>
                <form class="form-small" action="/TCC/BackEnd/admin/save_announcement.php" method="post">
                  <?php if ($editRow): ?><input type="hidden" name="id" value="<?php echo (int)$editRow['id']; ?>" /><?php endif; ?>
                  <div class="mb-2"><label class="form-label">Title</label><input name="title" class="form-control" required value="<?php echo $editRow ? htmlspecialchars($editRow['title']) : ''; ?>"/></div>
                  <div class="mb-2"><label class="form-label">Content</label><textarea name="content" class="form-control" rows="3" required><?php echo $editRow ? htmlspecialchars($editRow['content']) : ''; ?></textarea></div>
                  <div class="row g-2 mb-2">
                      <div class="col"><label class="form-label">Year</label><select name="year" class="form-select"><option value="">All</option><option value="1" <?php echo ($editRow && $editRow['year']=='1')?'selected':'';?>>1</option><option value="2" <?php echo ($editRow && $editRow['year']=='2')?'selected':'';?>>2</option><option value="3" <?php echo ($editRow && $editRow['year']=='3')?'selected':'';?>>3</option><option value="4" <?php echo ($editRow && $editRow['year']=='4')?'selected':'';?>>4</option></select></div>
                      <div class="col"><label class="form-label">Department</label><select name="department" class="form-select"><option value="">All</option><option value="IT" <?php echo ($editRow && $editRow['department']=='IT')?'selected':'';?>>IT</option><option value="HM" <?php echo ($editRow && $editRow['department']=='HM')?'selected':'';?>>HM</option><option value="BSEED" <?php echo ($editRow && $editRow['department']=='BSEED')?'selected':'';?>>BSEED</option><option value="BEED" <?php echo ($editRow && $editRow['department']=='BEED')?'selected':'';?>>BEED</option><option value="TOURISM" <?php echo ($editRow && $editRow['department']=='TOURISM')?'selected':'';?>>TOURISM</option></select></div>
                  </div>
                  <button class="btn btn-primary"><?php echo $editRow ? 'Update Announcement' : 'Save Announcement'; ?></button>
                  <?php if ($editRow): ?><a href="/TCC/public/admin_dashboard.php?section=announcements" class="btn btn-secondary ms-2">Cancel</a><?php endif; ?>
                </form>
              </div>

                <?php if (empty($annList)): ?>
            <div class="info-card mt-3">
              <div class="card-header-modern">
                      <i class="bi bi-megaphone"></i>
                      <h3>No Announcements</h3>
              </div>
                    <p class="text-muted mb-0">No announcements have been created yet. Create one above to get started.</p>
                  </div>
                <?php else: ?>
                  <div class="announcements-grid mt-3">
                  <?php foreach ($annList as $a): ?>
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
                        <?php if (!empty($a['id'])): ?>
                            <div class="announcement-actions" style="display: flex; gap: 8px;">
                              <a href="/TCC/public/admin_dashboard.php?section=announcements&edit_id=<?php echo (int)$a['id']; ?>" class="Btn Btn-edit">
                                <div class="svgWrapper">
                                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 42 42" class="svgIcon">
                                    <path stroke-width="5" stroke="#fff" d="M21 5L7 19L5 37L23 35L37 21L21 5Z"></path>
                                    <path stroke-width="3" stroke="#fff" d="M21 5L37 21"></path>
                                    <path stroke-width="3" stroke="#fff" d="M15 19L23 27"></path>
                                  </svg>
                                  <div class="text">Edit</div>
                                </div>
                              </a>
                        <form method="post" action="/TCC/BackEnd/admin/delete_announcement.php" onsubmit="return confirm('Delete this announcement?');" style="display:inline;">
                          <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>" />
                                <button class="Btn Btn-delete" type="submit">
                                  <div class="svgWrapper">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 42 42" class="svgIcon">
                                      <path stroke-width="5" stroke="#fff" d="M9.14073 2.5H32.8593C33.3608 2.5 33.8291 2.75065 34.1073 3.16795L39.0801 10.6271C39.3539 11.0378 39.5 11.5203 39.5 12.0139V21V37C39.5 38.3807 38.3807 39.5 37 39.5H5C3.61929 39.5 2.5 38.3807 2.5 37V21V12.0139C2.5 11.5203 2.6461 11.0378 2.91987 10.6271L7.89266 3.16795C8.17086 2.75065 8.63921 2.5 9.14073 2.5Z"></path>
                                      <path stroke-width="5" stroke="#fff" d="M14 18L28 18M18 14V30M24 14V30"></path>
                                    </svg>
                                    <div class="text">Delete</div>
                                  </div>
                                </button>
                        </form>
                            </div>
                        <?php endif; ?>
                      </div>
                      </div>
                  <?php endforeach; ?>
              </div>

              <!-- pagination for announcements -->
              <?php if (isset($annTotalPages) && $annTotalPages > 1): ?>
                  <nav class="mt-3" aria-label="Announcements pages">
                    <ul class="pagination pagination-sm justify-content-center">
                  <?php
                  $baseParams = $_GET; unset($baseParams['ann_page']);
                  $prevPage = max(1, $annPage-1); $nextPage = min($annTotalPages, $annPage+1);
                  $prevClass = ($annPage <= 1) ? 'disabled' : '';
                  $nextClass = ($annPage >= $annTotalPages) ? 'disabled' : '';
                  $baseParams['ann_page'] = $prevPage; echo '<li class="page-item ' . $prevClass . '"><a class="page-link" href="?' . htmlspecialchars(http_build_query($baseParams)) . '" aria-label="Previous announcements page">&lt;</a></li>';

                  $showPages = min(5, $annTotalPages);
                  for ($p = 1; $p <= $showPages; $p++) {
                    $baseParams['ann_page'] = $p; $qstr = htmlspecialchars(http_build_query($baseParams));
                    $isActive = ($p === $annPage);
                    $active = $isActive ? ' active' : '';
                    $aria = $isActive ? ' aria-current="page"' : '';
                    echo '<li class="page-item' . $active . '"><a class="page-link" href="?' . $qstr . '" aria-label="Announcements page ' . $p . '"' . $aria . '>' . $p . '</a></li>';
                  }

                  $baseParams['ann_page'] = $nextPage; echo '<li class="page-item ' . $nextClass . '"><a class="page-link" href="?' . htmlspecialchars(http_build_query($baseParams)) . '" aria-label="Next announcements page">&gt;</a></li>';
                  ?>
                </ul>
              </nav>
                  <?php endif; ?>
              <?php endif; ?>
              </div>
            </div>
            <?php
            // end announcements
            ?>

          <?php elseif ($section === 'buildings'): ?>
            <?php
            // Initialize toast variables
            if (!isset($toastMessage)) $toastMessage = '';
            if (!isset($toastType)) $toastType = 'success';
            
            // Load database connection early
            require_once __DIR__ . '/../BackEnd/database/db.php';
            $bPath = __DIR__ . '/../database/buildings.json';
            $buildings = [];
            if (file_exists($bPath)) { $buildings = json_decode(file_get_contents($bPath), true) ?: []; }
            
            // Initialize editSectionRow early to prevent undefined variable error
            $editSectionId = isset($_GET['edit_section_id']) ? intval($_GET['edit_section_id']) : 0;
            $editSectionRow = null;
            if ($editSectionId > 0) {
              try {
                $connEdit = Database::getInstance()->getConnection();
                $editStmt = $connEdit->prepare("SELECT id, year, section, building, floor, room FROM section_assignments WHERE id = ? LIMIT 1");
                $editStmt->bind_param('i', $editSectionId);
                $editStmt->execute();
                $editRes = $editStmt->get_result();
                $editSectionRow = $editRes->fetch_assoc();
                $editStmt->close();
              } catch (Throwable $ex) {
                $editSectionRow = null;
              }
            }
            ?>
            <div class="records-container">
              <div class="records-header">
                <h2 class="records-title">
                  <i class="bi bi-building"></i> Buildings & Facilities
                </h2>
                <p class="records-subtitle">Manage buildings, rooms, and section assignments</p>
              </div>
              <div class="records-main">
            <?php 
            // Toast notifications for buildings section
            if (isset($_GET['success'])): 
              $successMsg = $_GET['success'];
              if ($successMsg === '1' || $successMsg === 'deleted' || $successMsg === 'updated'): 
                if ($successMsg === 'deleted') {
                  $toastMessage = 'Section assignment deleted successfully!';
                } elseif ($successMsg === 'updated') {
                  $toastMessage = 'Section assignment updated successfully!';
                } else {
                  $toastMessage = 'Section assignment saved successfully!';
                }
                $toastType = 'success';
              endif;
            endif;
            if (isset($_GET['error'])): 
              $errorMsg = $_GET['error'];
              $toastType = 'error';
              if ($errorMsg === 'missing') {
                $toastMessage = 'Error: Please fill in all required fields (Year, Section, Building, Floor, and Room).';
              } elseif ($errorMsg === 'notfound') {
                $toastMessage = 'Error: Building not found.';
              } elseif ($errorMsg === 'dberror') {
                $toastMessage = 'Error: Database operation failed. Please check the server logs or try again.';
              } elseif ($errorMsg === 'section_not_found') {
                $toastMessage = 'Error: Section does not exist. Please create the section first in the Sections section.';
              } else {
                $toastMessage = 'Error: ' . htmlspecialchars($errorMsg);
              }
            endif;
            ?>
            <div class="info-card">
              <div class="card-header-modern">
                <i class="bi bi-building"></i>
                <h3>Manage Buildings &amp; Rooms</h3>
              </div>
              <div class="card-body p-3">
                <form class="row g-3 align-items-end" action="/TCC/BackEnd/admin/manage_buildings.php" method="post">
                  <div class="col-md-3">
                    <label class="form-label fw-bold">Building</label>
                    <input name="building" class="form-control" placeholder="A" required/>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-bold">Floors</label>
                    <input name="floors" type="number" class="form-control" value="4" min="1" required/>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-bold">Rooms per floor</label>
                    <input name="rooms" type="number" class="form-control" value="4" min="1" required/>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label d-block">&nbsp;</label>
                    <button class="btn btn-primary w-100">Save Building</button>
                  </div>
                </form>
              </div>
            </div>
            <?php
            // paginate buildings (convert assoc -> list of entries)
            $bldPerPage = 5;
            $bldPage = isset($_GET['bld_page']) ? max(1, intval($_GET['bld_page'])) : 1;
            $bEntries = [];
            foreach ($buildings as $bn => $binfo) { $bEntries[] = ['name'=>$bn, 'info'=>$binfo]; }
            $bldTotal = count($bEntries);
            $bldTotalPages = max(1, intval(ceil($bldTotal / $bldPerPage)));
            $bldSlice = array_slice($bEntries, ($bldPage-1)*$bldPerPage, $bldPerPage);
            
            // Load section assignments from database
            $sa = [];
            try {
              $connSa = Database::getInstance()->getConnection();
              $saQuery = $connSa->query("SELECT id, year, section, building, floor, room FROM section_assignments ORDER BY year, section");
              if ($saQuery) {
                while ($row = $saQuery->fetch_assoc()) {
                  $sa[] = $row;
                }
              }
            } catch (Throwable $ex) {
              // Fallback to JSON
              $saPath = __DIR__ . '/../database/section_assignments.json';
              if (file_exists($saPath)) { 
                $saJson = json_decode(file_get_contents($saPath), true) ?: [];
                foreach ($saJson as $key => $info) {
                  if (!isset($info['id'])) {
                    $info['id'] = 0; // Assign temporary ID for JSON entries
                  }
                  $sa[] = $info;
                }
              }
            }
            ?>
            <div class="buildings-grid mt-3">
              <div class="info-card buildings-card">
              <div class="card-header-modern">
                <i class="bi bi-building-check"></i>
                <h3>Configured Buildings</h3>
              </div>
                <ul class="list-group">
                  <?php if (empty($bEntries)): ?><li class="list-group-item text-muted">No buildings configured.</li><?php endif; ?>
                  <?php foreach ($bldSlice as $ent): $bname = $ent['name']; $binfo = $ent['info']; ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <div>
                        <strong>Building <?php echo htmlspecialchars($bname); ?></strong> — Floors: <?php echo (int)$binfo['floors']; ?>, Rooms/floor: <?php echo (int)$binfo['rooms']; ?>
                      </div>
                      <form action="/TCC/BackEnd/admin/manage_buildings.php" method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete Building <?php echo htmlspecialchars($bname); ?>? This action cannot be undone.');">
                        <input type="hidden" name="action" value="delete" />
                        <input type="hidden" name="building" value="<?php echo htmlspecialchars($bname); ?>" />
                        <button type="submit" class="Btn Btn-delete">
                          <div class="svgWrapper">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 42 42" class="svgIcon">
                              <path stroke-width="5" stroke="#fff" d="M9.14073 2.5H32.8593C33.3608 2.5 33.8291 2.75065 34.1073 3.16795L39.0801 10.6271C39.3539 11.0378 39.5 11.5203 39.5 12.0139V21V37C39.5 38.3807 38.3807 39.5 37 39.5H5C3.61929 39.5 2.5 38.3807 2.5 37V21V12.0139C2.5 11.5203 2.6461 11.0378 2.91987 10.6271L7.89266 3.16795C8.17086 2.75065 8.63921 2.5 9.14073 2.5Z"></path>
                              <path stroke-width="5" stroke="#fff" d="M14 18L28 18M18 14V30M24 14V30"></path>
                            </svg>
                            <div class="text">Delete</div>
                          </div>
                        </button>
                      </form>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php if ($bldTotalPages > 1): ?>
              <nav class="mt-2" aria-label="Buildings pages">
                <ul class="pagination pagination-sm">
                  <?php
                  $baseParams = $_GET; unset($baseParams['bld_page']);
                  $prevPage = max(1, $bldPage-1); $nextPage = min($bldTotalPages, $bldPage+1);
                  $prevClass = ($bldPage <= 1) ? 'disabled' : '';
                  $nextClass = ($bldPage >= $bldTotalPages) ? 'disabled' : '';
                  $baseParams['bld_page'] = $prevPage; echo '<li class="page-item ' . $prevClass . '"><a class="page-link" href="?' . htmlspecialchars(http_build_query($baseParams)) . '" aria-label="Previous buildings page">&lt;</a></li>';
                  $showPages = min(5, $bldTotalPages);
                  for ($p = 1; $p <= $showPages; $p++) { $baseParams['bld_page'] = $p; $qstr = htmlspecialchars(http_build_query($baseParams)); $isActive = ($p === $bldPage); $active = $isActive ? ' active' : ''; $aria = $isActive ? ' aria-current="page"' : ''; echo '<li class="page-item' . $active . '"><a class="page-link" href="?' . $qstr . '" aria-label="Buildings page ' . $p . '"' . $aria . '>' . $p . '</a></li>'; }
                  $baseParams['bld_page'] = $nextPage; echo '<li class="page-item ' . $nextClass . '"><a class="page-link" href="?' . htmlspecialchars(http_build_query($baseParams)) . '" aria-label="Next buildings page">&gt;</a></li>';
                  ?>
                </ul>
              </nav>
              <?php endif; ?>
              </div>
            </div>
            <?php
            // Get all sections from the sections table (synced with sections section)
            $availableSections = [];
            $existingAssignments = [];
            try {
              $connSections = Database::getInstance()->getConnection();
              
              // Ensure sections table exists
              $connSections->query("CREATE TABLE IF NOT EXISTS sections (
                id INT AUTO_INCREMENT PRIMARY KEY,
                year VARCHAR(10) NOT NULL,
                name VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_year_name (year, name)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
              
              // Get all sections from the sections table (this is the source of truth)
              $sectionsQuery = $connSections->query("SELECT year, name as section FROM sections ORDER BY CAST(year AS UNSIGNED), name");
              if ($sectionsQuery) {
                while ($row = $sectionsQuery->fetch_assoc()) {
                  $availableSections[] = $row;
                }
              }
              
              // Get all existing building/room assignments
              $existingQuery = $connSections->query("SELECT id, year, section, building, floor, room FROM section_assignments");
              if ($existingQuery) {
                while ($row = $existingQuery->fetch_assoc()) {
                  $key = $row['year'] . '|' . $row['section'];
                  $existingAssignments[$key] = $row;
                }
              }
            } catch (Throwable $ex) {
              $availableSections = [];
              $existingAssignments = [];
            }
            ?>
            <?php if ($editSectionRow): ?>
            <div class="info-card mt-3">
              <div class="card-header-modern">
                <i class="bi bi-pencil-square"></i>
                <h3>Edit Section Building &amp; Room Assignment</h3>
              </div>
                <form class="admin-user-assign-form" action="/TCC/BackEnd/admin/manage_section_assignments.php" method="post">
                <input type="hidden" name="action" value="update" />
                <input type="hidden" name="id" value="<?php echo (int)$editSectionRow['id']; ?>" />
                  <div class="row g-3">
                    <div class="col-md-3">
                      <div class="admin-form-group">
                        <label class="admin-form-label"><i class="bi bi-calendar-year"></i> Year</label>
                        <select name="year" class="form-select form-select-lg">
                        <option value="1" <?php echo ($editSectionRow['year']=='1')?'selected':'';?>>1st Year</option>
                        <option value="2" <?php echo ($editSectionRow['year']=='2')?'selected':'';?>>2nd Year</option>
                        <option value="3" <?php echo ($editSectionRow['year']=='3')?'selected':'';?>>3rd Year</option>
                        <option value="4" <?php echo ($editSectionRow['year']=='4')?'selected':'';?>>4th Year</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="admin-form-group">
                        <label class="admin-form-label"><i class="bi bi-people"></i> Section Name</label>
                      <input name="section" class="form-control form-control-lg" value="<?php echo htmlspecialchars($editSectionRow['section']); ?>" required/>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="admin-form-group">
                        <label class="admin-form-label"><i class="bi bi-building"></i> Building</label>
                        <select name="building" class="form-select form-select-lg" required>
                          <option value="">Select Building...</option>
                          <?php foreach (array_keys($buildings) as $bn): ?>
                          <option value="<?php echo htmlspecialchars($bn); ?>" <?php echo ($editSectionRow['building']===$bn)?'selected':'';?>><?php echo htmlspecialchars($bn); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="admin-form-group">
                        <label class="admin-form-label"><i class="bi bi-layers"></i> Floor</label>
                      <input name="floor" type="number" class="form-control form-control-lg" value="<?php echo (int)$editSectionRow['floor']; ?>" min="1" required/>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="admin-form-group">
                        <label class="admin-form-label"><i class="bi bi-door-closed"></i> Room</label>
                      <input name="room" class="form-control form-control-lg" value="<?php echo htmlspecialchars($editSectionRow['room']); ?>" required/>
                      </div>
                    </div>
                  </div>
                  <div class="row g-3 mt-2">
                    <div class="col-md-12">
                      <button type="submit" class="btn btn-primary btn-lg">
                      <i class="bi bi-check-circle me-2"></i>Update Section Assignment
                      </button>
                    <a href="/TCC/public/admin_dashboard.php?section=buildings" class="btn btn-secondary btn-lg ms-2">
                      <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                    </div>
                  </div>
                </form>
              </div>
            <?php else: ?>
            <div class="info-card mt-3">
              <div class="card-header-modern">
                <i class="bi bi-door-open"></i>
                <h3>Setup Section Building &amp; Room Assignment</h3>
              </div>
              <div class="admin-hint mb-3">
                <i class="bi bi-info-circle"></i>
                <span><strong>Note:</strong> When you assign a user to a year and section in User Management, their building and room will automatically display based on the section assignment below.</span>
              </div>
              <form class="admin-user-assign-form" action="/TCC/BackEnd/admin/manage_section_assignments.php" method="post">
                <input type="hidden" name="action" value="create" />
                <div class="row g-3">
                  <div class="col-md-3">
                    <div class="admin-form-group">
                      <label class="admin-form-label"><i class="bi bi-calendar-year"></i> Year</label>
                      <select name="year" class="form-select form-select-lg" required>
                        <option value="">Select Year...</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="admin-form-group">
                      <label class="admin-form-label"><i class="bi bi-people"></i> Section Name</label>
                      <input name="section" class="form-control form-control-lg" placeholder="Benevolence" required/>
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="admin-form-group">
                      <label class="admin-form-label"><i class="bi bi-building"></i> Building</label>
                      <select name="building" class="form-select form-select-lg" required>
                        <option value="">Select Building...</option>
                        <?php foreach (array_keys($buildings) as $bn): ?>
                          <option value="<?php echo htmlspecialchars($bn); ?>"><?php echo htmlspecialchars($bn); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="admin-form-group">
                      <label class="admin-form-label"><i class="bi bi-layers"></i> Floor</label>
                      <input name="floor" type="number" class="form-control form-control-lg" value="1" min="1" required/>
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="admin-form-group">
                      <label class="admin-form-label"><i class="bi bi-door-closed"></i> Room</label>
                      <input name="room" class="form-control form-control-lg" placeholder="301" required/>
                    </div>
                  </div>
                </div>
                <div class="row g-3 mt-2">
                  <div class="col-md-12">
                    <button type="submit" class="btn btn-primary btn-lg">
                      <i class="bi bi-check-circle me-2"></i>Assign Section
                    </button>
                  </div>
                </div>
              </form>
            </div>
            <?php endif; ?>
            <div class="info-card mt-3">
              <div class="card-header-modern">
                <i class="bi bi-list-check"></i>
                <h3>Assign Building &amp; Room to Available Sections</h3>
              </div>
              <div class="admin-hint mb-3">
                <i class="bi bi-info-circle"></i>
                <span><strong>Quick Assign:</strong> Select from available sections and assign them to buildings and rooms.</span>
              </div>
              <?php if (empty($availableSections)): ?>
                <div class="alert alert-info">
                  <i class="bi bi-info-circle me-2"></i>No sections found. Please create sections in the Sections section first.
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Year</th>
                        <th>Section</th>
                        <th>Current Assignment</th>
                        <th>Building</th>
                        <th>Floor</th>
                        <th>Room</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($availableSections as $sec): 
                        $key = $sec['year'] . '|' . $sec['section'];
                        $existing = $existingAssignments[$key] ?? null;
                        $hasAssignment = $existing !== null;
                      ?>
                        <tr>
                          <td><strong><?php echo htmlspecialchars($sec['year']); ?></strong></td>
                          <td><?php echo htmlspecialchars($sec['section']); ?></td>
                          <td>
                            <?php if ($hasAssignment): ?>
                              <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i> Building <?php echo htmlspecialchars($existing['building']); ?>, 
                                Floor <?php echo (int)$existing['floor']; ?>, 
                                Room <?php echo htmlspecialchars($existing['room']); ?>
                              </span>
                            <?php else: ?>
                              <span class="badge bg-warning">
                                <i class="bi bi-exclamation-triangle"></i> Not Assigned
                              </span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <select name="building" class="form-select form-select-sm" form="assignForm_<?php echo htmlspecialchars($key); ?>" required>
                              <option value="">Select...</option>
                              <?php foreach (array_keys($buildings) as $bn): ?>
                                <option value="<?php echo htmlspecialchars($bn); ?>" <?php echo ($hasAssignment && $existing['building']===$bn)?'selected':'';?>><?php echo htmlspecialchars($bn); ?></option>
                              <?php endforeach; ?>
                            </select>
                          </td>
                          <td>
                            <input type="number" name="floor" class="form-control form-control-sm" 
                                   value="<?php echo $hasAssignment ? (int)$existing['floor'] : '1'; ?>" 
                                   min="1" form="assignForm_<?php echo htmlspecialchars($key); ?>" required />
                          </td>
                          <td>
                            <input type="text" name="room" class="form-control form-control-sm" 
                                   value="<?php echo $hasAssignment ? htmlspecialchars($existing['room']) : ''; ?>" 
                                   placeholder="301" form="assignForm_<?php echo htmlspecialchars($key); ?>" required />
                          </td>
                          <td>
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                              <form id="assignForm_<?php echo htmlspecialchars($key); ?>" action="/TCC/BackEnd/admin/manage_section_assignments.php" method="post" style="display:inline;" onsubmit="return updateSectionForm(this, '<?php echo htmlspecialchars($key); ?>')">
                                <input type="hidden" name="action" value="<?php echo $hasAssignment ? 'update' : 'create'; ?>" />
                                <?php if ($hasAssignment): ?>
                                  <input type="hidden" name="id" value="<?php echo (int)$existing['id']; ?>" />
                                <?php endif; ?>
                                <input type="hidden" name="year" value="<?php echo htmlspecialchars($sec['year']); ?>" />
                                <input type="hidden" name="section" value="<?php echo htmlspecialchars($sec['section']); ?>" />
                                <button type="submit" class="Btn Btn-edit">
                                  <div class="svgWrapper">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 42 42" class="svgIcon">
                                      <path stroke-width="5" stroke="#fff" d="M21 5L7 19L5 37L23 35L37 21L21 5Z"></path>
                                      <path stroke-width="3" stroke="#fff" d="M21 5L37 21"></path>
                                      <path stroke-width="3" stroke="#fff" d="M15 19L23 27"></path>
                                    </svg>
                                    <div class="text"><?php echo $hasAssignment ? 'Update' : 'Assign'; ?></div>
                                  </div>
                                </button>
                </form>
                              <?php if ($hasAssignment): ?>
                              <form action="/TCC/BackEnd/admin/manage_section_assignments.php" method="post" style="display:inline;" onsubmit="return confirm('Delete this section assignment? This will remove the building/room assignment for this section.');">
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="id" value="<?php echo (int)$existing['id']; ?>" />
                                <input type="hidden" name="year" value="<?php echo htmlspecialchars($sec['year']); ?>" />
                                <input type="hidden" name="section" value="<?php echo htmlspecialchars($sec['section']); ?>" />
                                <button type="submit" class="Btn Btn-delete">
                                  <div class="svgWrapper">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 42 42" class="svgIcon">
                                      <path stroke-width="5" stroke="#fff" d="M9.14073 2.5H32.8593C33.3608 2.5 33.8291 2.75065 34.1073 3.16795L39.0801 10.6271C39.3539 11.0378 39.5 11.5203 39.5 12.0139V21V37C39.5 38.3807 38.3807 39.5 37 39.5H5C3.61929 39.5 2.5 38.3807 2.5 37V21V12.0139C2.5 11.5203 2.6461 11.0378 2.91987 10.6271L7.89266 3.16795C8.17086 2.75065 8.63921 2.5 9.14073 2.5Z"></path>
                                      <path stroke-width="5" stroke="#fff" d="M14 18L28 18M18 14V30M24 14V30"></path>
                                    </svg>
                                    <div class="text">Delete</div>
              </div>
                                </button>
                              </form>
                              <?php endif; ?>
            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
              </div>
            </div>

          <?php elseif ($section === 'projects'): ?>
            <?php
            // Initialize toast variables for projects
            if (!isset($toastMessage)) $toastMessage = '';
            if (!isset($toastType)) $toastType = 'success';
            
            // Handle toast notifications for projects
            if (isset($_GET['success'])) {
              if ($_GET['success'] === 'deleted') {
                $toastMessage = 'Project deleted successfully!';
              } else {
                $toastMessage = 'Project saved successfully!';
              }
              $toastType = 'success';
            } elseif (isset($_GET['error'])) {
              $toastType = 'error';
              if ($_GET['error'] === 'missing') {
                $toastMessage = 'Error: Please fill in all required fields.';
              } elseif ($_GET['error'] === 'invalid_index') {
                $toastMessage = 'Error: Invalid project index.';
              } else {
                $toastMessage = 'Error: ' . htmlspecialchars($_GET['error']);
              }
            }
            
            $pPath = __DIR__ . '/../database/projects.json';
            $projects = [];
            if (file_exists($pPath)) { $projects = json_decode(file_get_contents($pPath), true) ?: []; }
            // paginate projects
            $projPerPage = 5;
            $projPage = isset($_GET['proj_page']) ? max(1, intval($_GET['proj_page'])) : 1;
            $projTotal = count($projects);
            $projTotalPages = max(1, intval(ceil($projTotal / $projPerPage)));
            $projectsPage = array_slice($projects, ($projPage-1)*$projPerPage, $projPerPage);
            ?>
            <div class="records-container">
              <div class="records-header">
                <h2 class="records-title">
                  <i class="bi bi-folder-fill"></i> Projects
                </h2>
                <p class="records-subtitle">Manage campus projects and track their progress</p>
              </div>
              <div class="records-main">
                <div class="info-card">
                  <div class="card-header-modern">
                    <i class="bi bi-folder-fill"></i>
                    <h3>Create New Project</h3>
                  </div>
                  <form class="form-small" action="/TCC/BackEnd/admin/manage_projects.php" method="post">
                    <div class="mb-2"><label class="form-label">Project Name</label><input name="name" class="form-control" required/></div>
                    <div class="mb-2 row g-2"><div class="col"><label class="form-label">Budget</label><input name="budget" class="form-control" required/></div><div class="col"><label class="form-label">Started</label><input name="started" type="date" class="form-control" required/></div></div>
                    <div class="mb-2"><label class="form-label">Completed?</label><select name="completed" class="form-select"><option value="no">No</option><option value="yes">Yes</option></select></div>
                    <button class="btn btn-primary">Save Project</button>
                  </form>
                </div>

                <?php if (empty($projectsPage)): ?>
            <div class="info-card mt-3">
              <div class="card-header-modern">
                      <i class="bi bi-folder-x"></i>
                      <h3>No Projects</h3>
              </div>
                    <p class="text-muted mb-0">No projects have been created yet. Create one above to get started.</p>
                </div>
                <?php else: ?>
                  <div class="projects-grid mt-3">
                    <?php foreach ($projectsPage as $index => $proj): 
                      $isCompleted = isset($proj['completed']) && strtolower($proj['completed']) === 'yes';
                      $actualIndex = ($projPage - 1) * $projPerPage + $index;
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
                        <div class="project-actions" style="margin-top: 12px; display: flex; gap: 8px; justify-content: flex-end;">
                          <form method="post" action="/TCC/BackEnd/admin/manage_projects.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this project?');">
                            <input type="hidden" name="action" value="delete" />
                            <input type="hidden" name="index" value="<?php echo $actualIndex; ?>" />
                            <button type="submit" class="Btn Btn-delete">
                              <div class="svgWrapper">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 42 42" class="svgIcon">
                                  <path stroke-width="5" stroke="#fff" d="M9.14073 2.5H32.8593C33.3608 2.5 33.8291 2.75065 34.1073 3.16795L39.0801 10.6271C39.3539 11.0378 39.5 11.5203 39.5 12.0139V21V37C39.5 38.3807 38.3807 39.5 37 39.5H5C3.61929 39.5 2.5 38.3807 2.5 37V21V12.0139C2.5 11.5203 2.6461 11.0378 2.91987 10.6271L7.89266 3.16795C8.17086 2.75065 8.63921 2.5 9.14073 2.5Z"></path>
                                  <path stroke-width="5" stroke="#fff" d="M14 18L28 18M18 14V30M24 14V30"></path>
                                </svg>
                                <div class="text">Delete</div>
                              </div>
                            </button>
                          </form>
                        </div>
                      </div>
                    <?php endforeach; ?>
              </div>

              <?php if ($projTotalPages > 1): ?>
                  <nav class="mt-3" aria-label="Projects pages">
                    <ul class="pagination pagination-sm justify-content-center">
                  <?php
                  $baseParams = $_GET; unset($baseParams['proj_page']);
                  $prevPage = max(1, $projPage-1); $nextPage = min($projTotalPages, $projPage+1);
                  $prevClass = ($projPage <= 1) ? 'disabled' : '';
                  $nextClass = ($projPage >= $projTotalPages) ? 'disabled' : '';
                  $baseParams['proj_page'] = $prevPage; echo '<li class="page-item ' . $prevClass . '"><a class="page-link" href="?' . htmlspecialchars(http_build_query($baseParams)) . '" aria-label="Previous projects page">&lt;</a></li>';
                  $showPages = min(5, $projTotalPages);
                  for ($p = 1; $p <= $showPages; $p++) { $baseParams['proj_page'] = $p; $qstr = htmlspecialchars(http_build_query($baseParams)); $isActive = ($p === $projPage); $active = $isActive ? ' active' : ''; $aria = $isActive ? ' aria-current="page"' : ''; echo '<li class="page-item' . $active . '"><a class="page-link" href="?' . $qstr . '" aria-label="Projects page ' . $p . '"' . $aria . '>' . $p . '</a></li>'; }
                  $baseParams['proj_page'] = $nextPage; echo '<li class="page-item ' . $nextClass . '"><a class="page-link" href="?' . htmlspecialchars(http_build_query($baseParams)) . '" aria-label="Next projects page">&gt;</a></li>';
                  ?>
                </ul>
              </nav>
                  <?php endif; ?>
              <?php endif; ?>
              </div>
            </div>

          <?php elseif ($section === 'user_management'): ?>
            <?php
            require_once __DIR__ . '/../BackEnd/database/db.php';
            $conn = Database::getInstance()->getConnection();
            
            // Ensure teacher_assignments table exists
            $conn->query("CREATE TABLE IF NOT EXISTS teacher_assignments (
              id INT AUTO_INCREMENT PRIMARY KEY,
              user_id INT DEFAULT NULL,
              username VARCHAR(200) NOT NULL,
              year VARCHAR(10) NOT NULL,
              subject VARCHAR(255) NOT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              INDEX idx_user_id (user_id),
              INDEX idx_username (username),
              INDEX idx_year (year)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // filters
            $q = isset($_GET['q']) ? trim($_GET['q']) : '';
            $filterYear = isset($_GET['year_filter']) ? trim($_GET['year_filter']) : '';
            $filterSection = isset($_GET['section_filter']) ? trim($_GET['section_filter']) : '';
            $filterDept = isset($_GET['dept_filter']) ? trim($_GET['dept_filter']) : '';
            $filterLacking = isset($_GET['lacking_payment']) ? true : false;
            $filterSanctions = isset($_GET['has_sanctions']) ? true : false;
            
            // Ensure sections table exists
            $conn->query("CREATE TABLE IF NOT EXISTS sections (
              id INT AUTO_INCREMENT PRIMARY KEY,
              year VARCHAR(10) NOT NULL,
              name VARCHAR(100) NOT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY uniq_year_name (year, name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            // Get available years and sections for filter chips
            $availableFilterYears = [];
            $availableFilterSections = [];
            $availableFilterDepartments = [];
            
            // Get sections from the sections table (synced with sections section)
            $sectionsFilterQuery = $conn->query("SELECT DISTINCT year, name as section FROM sections ORDER BY CAST(year AS UNSIGNED), name");
            if ($sectionsFilterQuery) {
              while ($row = $sectionsFilterQuery->fetch_assoc()) {
                if (!empty($row['year']) && !in_array($row['year'], $availableFilterYears)) {
                  $availableFilterYears[] = $row['year'];
                }
                if (!empty($row['section']) && !in_array($row['section'], $availableFilterSections)) {
                  $availableFilterSections[] = $row['section'];
                }
              }
            }
            
            // Get departments from user_assignments (departments are not in sections table)
            $deptQuery = $conn->query("SELECT DISTINCT department FROM user_assignments WHERE department IS NOT NULL AND department <> '' ORDER BY department");
            if ($deptQuery) {
              while ($row = $deptQuery->fetch_assoc()) {
                if (!empty($row['department']) && !in_array($row['department'], $availableFilterDepartments)) {
                  $availableFilterDepartments[] = $row['department'];
                }
              }
            }
            ?>
            <div class="records-container">
              <div class="records-header">
                <h2 class="records-title">
                  <i class="bi bi-people-fill"></i> User Management
                </h2>
                <p class="records-subtitle">Manage user assignments, financial status, and sanctions</p>
              </div>
              <div class="records-main">
            <?php

            // pagination
            $perPage = 10;
            $page = isset($_GET['ua_page']) ? max(1, intval($_GET['ua_page'])) : 1;
            $offset = ($page - 1) * $perPage;

            $conds = [];
            $types = '';
            $values = [];
            if ($q !== '') {
              $like = '%' . $q . '%';
              $conds[] = '(ua.username LIKE ? OR ua.section LIKE ? OR ua.department LIKE ?)';
              $types .= 'sss';
              $values[] = $like; $values[] = $like; $values[] = $like;
            }
            if ($filterYear !== '') { $conds[] = 'ua.year = ?'; $types .= 's'; $values[] = $filterYear; }
            if ($filterSection !== '') { $conds[] = 'ua.section = ?'; $types .= 's'; $values[] = $filterSection; }
            if ($filterDept !== '') { $conds[] = 'ua.department = ?'; $types .= 's'; $values[] = $filterDept; }
            if ($filterLacking) { $conds[] = 'ua.payment = ?'; $types .= 's'; $values[] = 'owing'; }
            if ($filterSanctions) { $conds[] = "TRIM(COALESCE(ua.sanctions,'')) <> ''"; }

            $where = count($conds) ? 'WHERE ' . implode(' AND ', $conds) : '';

            // total count
            $total = 0;
            $countSql = "SELECT COUNT(*) as c FROM user_assignments ua LEFT JOIN users u ON ua.user_id = u.id $where";
            $countStmt = $conn->prepare($countSql);
            if ($countStmt) {
              if ($types !== '') { $countStmt->bind_param($types, ...$values); }
              $countStmt->execute();
              $cres = $countStmt->get_result();
              if ($cr = $cres->fetch_assoc()) { $total = intval($cr['c']); }
              $countStmt->close();
            }

            $totalPages = max(1, intval(ceil($total / $perPage)));

            // fetch page rows with role information
            $ua = [];
            $selSql = "SELECT ua.id, ua.username, ua.year, ua.section, ua.department, ua.payment, ua.sanctions, ua.owing_amount, ua.user_id, COALESCE(u.role, 'student') as role FROM user_assignments ua LEFT JOIN users u ON ua.user_id = u.id $where ORDER BY ua.year, ua.username LIMIT ? OFFSET ?";
            $selStmt = $conn->prepare($selSql);
            if ($selStmt) {
              if ($types !== '') {
                $bindTypes = $types . 'ii';
                $bindValues = array_merge($values, [$perPage, $offset]);
                $selStmt->bind_param($bindTypes, ...$bindValues);
              } else {
                $selStmt->bind_param('ii', $perPage, $offset);
              }
              $selStmt->execute();
              $res = $selStmt->get_result();
              while ($r = $res->fetch_assoc()) {
                $ua[] = $r;
              }
              $selStmt->close();
            }
            
            // Get active tab from URL or default to students
            $activeTab = 'students';
            if (isset($_GET['tab'])) {
              if ($_GET['tab'] === 'teachers') {
                $activeTab = 'teachers';
              } elseif ($_GET['tab'] === 'schedules') {
                $activeTab = 'schedules';
              }
            }
            
            // Handle user management toasts
            if (isset($_GET['success'])) {
              $successMsg = $_GET['success'];
              if ($successMsg === 'teacher_assigned') {
                $toastMessage = 'Teacher assigned successfully!';
              } elseif ($successMsg === 'teacher_deleted') {
                $toastMessage = 'Teacher assignment deleted successfully!';
              } else {
                $toastMessage = 'User assignment saved successfully!';
              }
              $toastType = 'success';
            } elseif (isset($_GET['updated'])) {
              $toastMessage = 'User assignment updated successfully!';
              $toastType = 'success';
            } elseif (isset($_GET['deleted'])) {
              $toastMessage = 'User assignment deleted successfully!';
              $toastType = 'success';
            } elseif (isset($_GET['error'])) {
              $toastType = 'error';
              $errorMsg = $_GET['error'];
              if ($errorMsg === 'section_not_found') {
                $toastMessage = 'Error: Section does not exist. Please create the section first in the Sections section.';
              } elseif ($errorMsg === 'user_not_found') {
                $toastMessage = 'Error: User does not exist in the database. Please check the username or full name.';
              } else {
                $toastMessage = 'Error: ' . htmlspecialchars($errorMsg);
              }
            }
            
            // Get teacher assignments
            $teacherAssignments = [];
            $teacherQuery = $conn->query("SELECT ta.id, ta.username, ta.year, ta.subject, ta.user_id, u.full_name, COALESCE(u.role, 'teacher') as role FROM teacher_assignments ta LEFT JOIN users u ON ta.user_id = u.id ORDER BY ta.year, ta.subject, ta.username");
            if ($teacherQuery) {
              while ($row = $teacherQuery->fetch_assoc()) {
                $teacherAssignments[] = $row;
              }
            }
            ?>
            
            <!-- Tab Navigation -->
            <ul class="nav nav-tabs mb-4" id="userManagementTabs" role="tablist" style="border-bottom: 2px solid var(--color-sage);">
              <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'students' ? 'active' : ''; ?>" id="students-tab" data-bs-toggle="tab" data-bs-target="#students-pane" type="button" role="tab" aria-controls="students-pane" aria-selected="<?php echo $activeTab === 'students' ? 'true' : 'false'; ?>" style="color: var(--color-sage); font-weight: 600; padding: 12px 24px;">
                  <i class="bi bi-people-fill me-2"></i>Manage Students
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'teachers' ? 'active' : ''; ?>" id="teachers-tab" data-bs-toggle="tab" data-bs-target="#teachers-pane" type="button" role="tab" aria-controls="teachers-pane" aria-selected="<?php echo $activeTab === 'teachers' ? 'true' : 'false'; ?>" style="color: var(--color-sage); font-weight: 600; padding: 12px 24px;">
                  <i class="bi bi-person-badge me-2"></i>Manage Teachers
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'schedules' ? 'active' : ''; ?>" id="schedules-tab" data-bs-toggle="tab" data-bs-target="#schedules-pane" type="button" role="tab" aria-controls="schedules-pane" aria-selected="<?php echo $activeTab === 'schedules' ? 'true' : 'false'; ?>" style="color: var(--color-sage); font-weight: 600; padding: 12px 24px;">
                  <i class="bi bi-calendar-week me-2"></i>Schedule Management
                </button>
              </li>
            </ul>

            <div class="tab-content" id="userManagementTabContent">
              <!-- Students Tab -->
              <div class="tab-pane fade <?php echo $activeTab === 'students' ? 'show active' : ''; ?>" id="students-pane" role="tabpanel" aria-labelledby="students-tab">
                <div class="info-card">
              <div class="card-header-modern">
                <i class="bi bi-person-plus"></i>
                <h3>Assign User to Year / Section</h3>
              </div>
              <form action="/TCC/BackEnd/admin/manage_users.php" method="post" class="admin-user-assign-form">
                <input type="hidden" name="action" value="assign" />
                <input type="hidden" id="existingUserIdHidden" name="existing_user_id" value="" />
                <div class="row g-3">
                  <div class="col-md-6">
                    <div class="admin-form-group">
                      <label class="admin-form-label" for="userSearchInput">
                        <i class="bi bi-search"></i> User Search
                      </label>
                      <div class="admin-search-wrapper">
                        <input type="text" id="userSearchInput" class="form-control form-control-lg" placeholder="Start typing a name or username" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="userSearchList" aria-haspopup="listbox" />
                        <ul id="userSearchList" role="listbox" class="admin-search-dropdown" aria-hidden="true"></ul>
                      </div>
                      <div class="admin-hint">
                        <i class="bi bi-info-circle"></i>
                        <span>Select a suggestion to map to an existing account, or type a full name to create an assignment without a user account.</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="admin-form-group">
                      <label class="admin-form-label" for="assignFullName">
                        <i class="bi bi-person-badge"></i> Full Name
                      </label>
                      <input type="text" id="assignFullName" name="full_name" class="form-control form-control-lg" placeholder="Full Name (e.g. Joshua Paculaba)" required />
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="admin-form-group">
                      <label class="admin-form-label" for="assignYear">
                        <i class="bi bi-calendar-year"></i> Year
                      </label>
                      <select name="year" id="assignYear" class="form-select form-select-lg">
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3" selected>3rd Year</option>
                        <option value="4">4th Year</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="admin-form-group">
                      <label class="admin-form-label" for="assignSection">
                        <i class="bi bi-people"></i> Section
                      </label>
                      <input type="text" name="section" id="assignSection" class="form-control form-control-lg" placeholder="Benevolence" required />
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="admin-form-group">
                      <label class="admin-form-label" for="assignDepartment">
                        <i class="bi bi-building"></i> Department
                      </label>
                      <select name="department" id="assignDepartment" class="form-select form-select-lg">
                        <option value="">(none)</option>
                        <option value="IT">IT</option>
                        <option value="HM">HM</option>
                        <option value="BSEED">BSEED</option>
                        <option value="BEED">BEED</option>
                        <option value="TOURISM">TOURISM</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                      <i class="bi bi-check-circle me-2"></i>Assign User
                    </button>
                  </div>
                </div>
              </form>
            </div>

            <div class="info-card mt-3 grade-filter-card">
              <div class="grade-filter-inner">
                <div class="grade-filter-head">
                  <div class="grade-filter-title">
                    <span class="grade-filter-icon"><i class="bi bi-funnel-fill"></i></span>
                    <div>
                      <h3>Filter Users</h3>
                      <p>Focus the overview by year, section, department, or status.</p>
                    </div>
                  </div>
                  <?php if ($filterYear !== '' || $filterSection !== '' || $filterDept !== '' || $filterLacking || $filterSanctions || $q !== ''): ?>
                    <a href="/TCC/public/admin_dashboard.php?section=user_management" class="grade-filter-reset">
                      <i class="bi bi-arrow-counterclockwise"></i> Reset view
                    </a>
                  <?php endif; ?>
                </div>
                
                <!-- Search Bar -->
                <form method="get" class="mb-3">
                  <input type="hidden" name="section" value="user_management" />
                  <input type="hidden" name="year_filter" value="<?php echo htmlspecialchars($filterYear); ?>" />
                  <input type="hidden" name="section_filter" value="<?php echo htmlspecialchars($filterSection); ?>" />
                  <input type="hidden" name="dept_filter" value="<?php echo htmlspecialchars($filterDept); ?>" />
                  <input type="hidden" name="lacking_payment" value="<?php echo $filterLacking ? '1' : ''; ?>" />
                  <input type="hidden" name="has_sanctions" value="<?php echo $filterSanctions ? '1' : ''; ?>" />
                  <div class="input-group input-group-lg">
                    <span class="input-group-text" style="background: rgba(107, 95, 79, 0.12); border: 1px solid rgba(107, 95, 79, 0.3);">
                      <i class="bi bi-search"></i>
                    </span>
                    <input type="search" name="q" class="form-control" placeholder="Search by full name, section, or department..." value="<?php echo htmlspecialchars($q); ?>" style="border: 1px solid rgba(107, 95, 79, 0.3);" />
                    <?php if ($q !== ''): ?>
                      <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel-fill"></i> Search
                      </button>
                    <?php endif; ?>
                  </div>
                </form>
                
                <div class="grade-filter-actions">
                  <?php if (!empty($availableFilterYears)): ?>
                  <div class="grade-filter-group">
                    <span class="grade-filter-label">Year Level</span>
                    <?php 
                    $filterBase = $_GET;
                    $filterBase['section'] = 'user_management';
                    unset($filterBase['year_filter']);
                    $yearBase = $filterBase;
                    $yearAllUrl = '/TCC/public/admin_dashboard.php?' . htmlspecialchars(http_build_query($yearBase));
                    ?>
                    <a href="<?php echo $yearAllUrl; ?>" class="grade-chip <?php echo ($filterYear === '') ? 'active' : ''; ?>">
                      <i class="bi bi-layers"></i>
                      <span>All Years</span>
                    </a>
                    <?php foreach ($availableFilterYears as $yearValue): 
                      $yearParams = $yearBase;
                      $yearParams['year_filter'] = $yearValue;
                      $yearUrl = '/TCC/public/admin_dashboard.php?' . htmlspecialchars(http_build_query($yearParams));
                      $yearLabel = $yearValue === '1' ? '1st Year' : ($yearValue === '2' ? '2nd Year' : ($yearValue === '3' ? '3rd Year' : ($yearValue === '4' ? '4th Year' : $yearValue . ' Year')));
                      ?>
                      <a href="<?php echo $yearUrl; ?>" class="grade-chip <?php echo ($filterYear === $yearValue) ? 'active' : ''; ?>">
                        <i class="bi bi-calendar-week"></i>
                        <span><?php echo htmlspecialchars($yearLabel); ?></span>
                      </a>
                    <?php endforeach; ?>
                  </div>
                  <?php endif; ?>
                  
                  <?php if (!empty($availableFilterSections)): ?>
                  <div class="grade-filter-group">
                    <span class="grade-filter-label">Section</span>
                    <?php
                    $sectionBase = $filterBase;
                    unset($sectionBase['section_filter']);
                    $sectionAllUrl = '/TCC/public/admin_dashboard.php?' . htmlspecialchars(http_build_query($sectionBase));
                    ?>
                    <a href="<?php echo $sectionAllUrl; ?>" class="grade-chip <?php echo (!isset($_GET['section_filter']) || $_GET['section_filter'] === '') ? 'active' : ''; ?>">
                      <i class="bi bi-grid-1x2"></i>
                      <span>All Sections</span>
                    </a>
                    <?php foreach ($availableFilterSections as $sectionValue): 
                      $sectionParams = $sectionBase;
                      $sectionParams['section_filter'] = $sectionValue;
                      $sectionUrl = '/TCC/public/admin_dashboard.php?' . htmlspecialchars(http_build_query($sectionParams));
                      ?>
                      <a href="<?php echo $sectionUrl; ?>" class="grade-chip <?php echo (isset($_GET['section_filter']) && $_GET['section_filter'] === $sectionValue) ? 'active' : ''; ?>">
                        <i class="bi bi-collection"></i>
                        <span><?php echo htmlspecialchars($sectionValue); ?></span>
                      </a>
                    <?php endforeach; ?>
                  </div>
                  <?php endif; ?>
                  
                  <?php if (!empty($availableFilterDepartments)): ?>
                  <div class="grade-filter-group">
                    <span class="grade-filter-label">Department</span>
                    <?php
                    $deptBase = $filterBase;
                    unset($deptBase['dept_filter']);
                    $deptAllUrl = '/TCC/public/admin_dashboard.php?' . htmlspecialchars(http_build_query($deptBase));
                    ?>
                    <a href="<?php echo $deptAllUrl; ?>" class="grade-chip <?php echo ($filterDept === '') ? 'active' : ''; ?>">
                      <i class="bi bi-grid-1x2"></i>
                      <span>All Departments</span>
                    </a>
                    <?php foreach ($availableFilterDepartments as $deptValue): 
                      $deptParams = $deptBase;
                      $deptParams['dept_filter'] = $deptValue;
                      $deptUrl = '/TCC/public/admin_dashboard.php?' . htmlspecialchars(http_build_query($deptParams));
                      ?>
                      <a href="<?php echo $deptUrl; ?>" class="grade-chip <?php echo ($filterDept === $deptValue) ? 'active' : ''; ?>">
                        <i class="bi bi-building"></i>
                        <span><?php echo htmlspecialchars($deptValue); ?></span>
                      </a>
                    <?php endforeach; ?>
                  </div>
                  <?php endif; ?>
                  
                  <div class="grade-filter-group">
                    <span class="grade-filter-label">Status</span>
                    <?php
                    $statusBase = $filterBase;
                    unset($statusBase['lacking_payment'], $statusBase['has_sanctions']);
                    $statusAllUrl = '/TCC/public/admin_dashboard.php?' . htmlspecialchars(http_build_query($statusBase));
                    ?>
                    <a href="<?php echo $statusAllUrl; ?>" class="grade-chip <?php echo (!$filterLacking && !$filterSanctions) ? 'active' : ''; ?>">
                      <i class="bi bi-check-circle"></i>
                      <span>All Status</span>
                    </a>
                    <?php
                    $lackingParams = $statusBase;
                    $lackingParams['lacking_payment'] = '1';
                    $lackingUrl = '/TCC/public/admin_dashboard.php?' . htmlspecialchars(http_build_query($lackingParams));
                    ?>
                    <a href="<?php echo $lackingUrl; ?>" class="grade-chip <?php echo $filterLacking ? 'active' : ''; ?>">
                      <i class="bi bi-exclamation-triangle"></i>
                      <span>Lacking Payment</span>
                    </a>
                    <?php
                    $sanctionsParams = $statusBase;
                    $sanctionsParams['has_sanctions'] = '1';
                    $sanctionsUrl = '/TCC/public/admin_dashboard.php?' . htmlspecialchars(http_build_query($sanctionsParams));
                    ?>
                    <a href="<?php echo $sanctionsUrl; ?>" class="grade-chip <?php echo $filterSanctions ? 'active' : ''; ?>">
                      <i class="bi bi-shield-exclamation"></i>
                      <span>Has Sanctions</span>
                    </a>
                  </div>
                </div>
                
                <?php if ($filterYear !== '' || $filterSection !== '' || $filterDept !== '' || $filterLacking || $filterSanctions || $q !== ''): ?>
                  <div class="grade-filter-note">
                    <i class="bi bi-info-circle"></i>
                    Showing user assignments
                    <?php if ($filterYear !== ''): ?>
                      for <strong><?php echo htmlspecialchars($filterYear === '1' ? '1st Year' : ($filterYear === '2' ? '2nd Year' : ($filterYear === '3' ? '3rd Year' : ($filterYear === '4' ? '4th Year' : $filterYear . ' Year')))); ?></strong>
                    <?php endif; ?>
                    <?php if ($filterSection !== ''): ?>
                      in section <strong><?php echo htmlspecialchars($filterSection); ?></strong>
                    <?php endif; ?>
                    <?php if ($filterDept !== ''): ?>
                      in department <strong><?php echo htmlspecialchars($filterDept); ?></strong>
                    <?php endif; ?>
                    <?php if ($filterLacking): ?>
                      with <strong>lacking payment</strong>
                    <?php endif; ?>
                    <?php if ($filterSanctions): ?>
                      with <strong>sanctions</strong>
                    <?php endif; ?>
                    <?php if ($q !== ''): ?>
                      matching "<strong><?php echo htmlspecialchars($q); ?></strong>"
                    <?php endif; ?>
                    .
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="info-card mt-3">
              <div class="card-header-modern">
                <i class="bi bi-list-check"></i>
                <h3>User Assignments (<?php echo $total; ?> total)</h3>
              </div>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Full Name</th>
                      <th>Role</th>
                      <th>Year</th>
                      <th>Section</th>
                      <th>Department</th>
                      <th>Payment</th>
                      <th>Sanctions</th>
                      <th>Owing Amount</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($ua)): ?>
                      <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                          <i class="bi bi-inbox"></i> No user assignments found.
                        </td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($ua as $r):
                        $assignmentId = $r['id'] ?? null;
                        $fullName = $r['username'];
                        $role = $r['role'] ?? 'student';
                        $year = $r['year'] ?? '';
                        $sectionName = $r['section'] ?? '';
                        $department = $r['department'] ?? '';
                        $payment = $r['payment'] ?? 'paid';
                        $sanctions = $r['sanctions'] ?? '';
                        $owingAmount = $r['owing_amount'] ?? '';
                        
                        // Role badge colors
                        $roleBadgeClass = 'secondary';
                        $roleLabel = ucfirst($role);
                        if ($role === 'admin') {
                          $roleBadgeClass = 'danger';
                        } elseif ($role === 'teacher') {
                          $roleBadgeClass = 'info';
                        } elseif ($role === 'student') {
                          $roleBadgeClass = 'success';
                        }

                        $sanctionDisplay = 'None';
                        $sanctionDays = null;
                        if (!empty($sanctions)) {
                          if (preg_match('/(\d{4}-\d{2}-\d{2})/', $sanctions, $matches)) {
                            $sanctionDate = new DateTime($matches[1]);
                            $now = new DateTime();
                            if ($sanctionDate > $now) {
                              $diff = $now->diff($sanctionDate);
                              $sanctionDays = $diff->days;
                              $sanctionDisplay = $sanctionDays . ' days';
                            } else {
                              $sanctionDisplay = 'Expired';
                            }
                          } elseif (is_numeric($sanctions)) {
                            $sanctionDays = intval($sanctions);
                            $sanctionDisplay = $sanctionDays . ' days';
                          } else {
                            $sanctionDisplay = $sanctions;
                          }
                        }
                      ?>
                        <tr>
                          <td><strong><?php echo htmlspecialchars($fullName); ?></strong></td>
                          <td>
                            <span class="badge bg-<?php echo $roleBadgeClass; ?>">
                              <?php echo htmlspecialchars($roleLabel); ?>
                            </span>
                          </td>
                          <td><?php echo htmlspecialchars($year); ?></td>
                          <td><?php echo htmlspecialchars($sectionName); ?></td>
                          <td><?php echo htmlspecialchars($department ?: '-'); ?></td>
                          <td>
                            <span class="badge bg-<?php echo $payment === 'paid' ? 'success' : 'danger'; ?>">
                              <?php echo htmlspecialchars($payment); ?>
                            </span>
                          </td>
                          <td>
                            <?php if ($sanctionDays !== null && $sanctionDays > 0): ?>
                              <span class="badge bg-warning"><?php echo $sanctionDays; ?> days</span>
                            <?php elseif ($sanctionDisplay !== 'None'): ?>
                              <span class="badge bg-warning"><?php echo htmlspecialchars($sanctionDisplay); ?></span>
                            <?php else: ?>
                              <span class="badge bg-success">None</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if ($payment === 'owing' && !empty($owingAmount)): ?>
                              <span class="text-danger fw-bold">₱<?php echo htmlspecialchars($owingAmount); ?></span>
                            <?php else: ?>
                              <span class="text-muted">-</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <div style="display: flex; gap: 8px;">
                              <button type="button" class="Btn Btn-edit" data-bs-toggle="modal" data-bs-target="#editUserModal"
                                data-fullname="<?php echo htmlspecialchars($fullName); ?>"
                                data-payment="<?php echo htmlspecialchars($payment); ?>"
                                data-sanctions="<?php echo htmlspecialchars($sanctions); ?>"
                                data-department="<?php echo htmlspecialchars($department); ?>"
                                data-owing="<?php echo htmlspecialchars($owingAmount); ?>"
                              >
                                <div class="svgWrapper">
                                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 42 42" class="svgIcon">
                                    <path stroke-width="5" stroke="#fff" d="M21 5L7 19L5 37L23 35L37 21L21 5Z"></path>
                                    <path stroke-width="3" stroke="#fff" d="M21 5L37 21"></path>
                                    <path stroke-width="3" stroke="#fff" d="M15 19L23 27"></path>
                                  </svg>
                                  <div class="text">Edit</div>
                                </div>
                              </button>
                              <?php if ($assignmentId): ?>
                              <form method="post" action="/TCC/BackEnd/admin/manage_users.php" onsubmit="return confirm('Are you sure you want to delete this user assignment? This action cannot be undone.');" style="display:inline;">
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="id" value="<?php echo (int)$assignmentId; ?>" />
                                <button type="submit" class="Btn Btn-delete">
                                  <div class="svgWrapper">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 42 42" class="svgIcon">
                                      <path stroke-width="5" stroke="#fff" d="M9.14073 2.5H32.8593C33.3608 2.5 33.8291 2.75065 34.1073 3.16795L39.0801 10.6271C39.3539 11.0378 39.5 11.5203 39.5 12.0139V21V37C39.5 38.3807 38.3807 39.5 37 39.5H5C3.61929 39.5 2.5 38.3807 2.5 37V21V12.0139C2.5 11.5203 2.6461 11.0378 2.91987 10.6271L7.89266 3.16795C8.17086 2.75065 8.63921 2.5 9.14073 2.5Z"></path>
                                      <path stroke-width="5" stroke="#fff" d="M14 18L28 18M18 14V30M24 14V30"></path>
                                    </svg>
                                    <div class="text">Delete</div>
                                  </div>
                                </button>
                              </form>
                              <?php endif; ?>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

              <?php if ($totalPages > 1): ?>
              <nav class="mt-3" aria-label="User assignments pages">
                <ul class="pagination justify-content-center">
                  <?php
                  $baseParams = $_GET;
                  unset($baseParams['ua_page']);
                  $prevPage = max(1, $page - 1);
                  $nextPage = min($totalPages, $page + 1);
                  $prevClass = ($page <= 1) ? 'disabled' : '';
                  $nextClass = ($page >= $totalPages) ? 'disabled' : '';

                  $baseParams['ua_page'] = $prevPage;
                  echo '<li class="page-item ' . $prevClass . '"><a class="page-link" href="?' . htmlspecialchars(http_build_query($baseParams)) . '">&laquo; Previous</a></li>';

                  $showPages = min(5, $totalPages);
                  $startPage = max(1, min($page - 2, $totalPages - $showPages + 1));
                  for ($p = $startPage; $p < $startPage + $showPages && $p <= $totalPages; $p++) {
                    $baseParams['ua_page'] = $p;
                    $qstr = htmlspecialchars(http_build_query($baseParams));
                    $isActive = ($p === $page);
                    $active = $isActive ? ' active' : '';
                    echo '<li class="page-item' . $active . '"><a class="page-link" href="?' . $qstr . '">' . $p . '</a></li>';
                  }

                  $baseParams['ua_page'] = $nextPage;
                  echo '<li class="page-item ' . $nextClass . '"><a class="page-link" href="?' . htmlspecialchars(http_build_query($baseParams)) . '">Next &raquo;</a></li>';
                  ?>
                </ul>
              </nav>
              <?php endif; ?>
                </div>
              </div>
              
              <!-- Teachers Tab -->
              <div class="tab-pane fade <?php echo $activeTab === 'teachers' ? 'show active' : ''; ?>" id="teachers-pane" role="tabpanel" aria-labelledby="teachers-tab">
                <div class="info-card">
                  <div class="card-header-modern">
                    <i class="bi bi-person-badge"></i>
                    <h3>Assign Teacher to Year / Subject</h3>
                  </div>
                  <form action="/TCC/BackEnd/admin/manage_users.php" method="post" class="admin-user-assign-form">
                    <input type="hidden" name="action" value="assign_teacher" />
                    <input type="hidden" id="teacherUserIdHidden" name="existing_user_id" value="" />
                    <div class="row g-3">
                      <div class="col-md-6">
                        <div class="admin-form-group">
                          <label class="admin-form-label" for="teacherSearchInput">
                            <i class="bi bi-search"></i> Teacher Search
                          </label>
                          <div class="admin-search-wrapper">
                            <input type="text" id="teacherSearchInput" class="form-control form-control-lg" placeholder="Start typing a name or username" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="teacherSearchList" aria-haspopup="listbox" />
                            <ul id="teacherSearchList" role="listbox" class="admin-search-dropdown" aria-hidden="true"></ul>
                          </div>
                          <div class="admin-hint">
                            <i class="bi bi-info-circle"></i>
                            <span>Select a suggestion to map to an existing teacher account, or type a full name to create an assignment without a user account.</span>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="admin-form-group">
                          <label class="admin-form-label" for="teacherFullName">
                            <i class="bi bi-person-badge"></i> Full Name
                          </label>
                          <input type="text" id="teacherFullName" name="full_name" class="form-control form-control-lg" placeholder="Full Name (e.g. Ms. Johnson)" required />
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="admin-form-group">
                          <label class="admin-form-label" for="teacherYear">
                            <i class="bi bi-calendar-year"></i> Year
                          </label>
                          <select name="year" id="teacherYear" class="form-select form-select-lg" required>
                            <option value="">Select Year...</option>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-5">
                        <div class="admin-form-group">
                          <label class="admin-form-label" for="teacherSubject">
                            <i class="bi bi-book"></i> Subject
                          </label>
                          <input type="text" name="subject" id="teacherSubject" class="form-control form-control-lg" placeholder="e.g. Mathematics, English, Science" required />
                        </div>
                      </div>
                      <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                          <i class="bi bi-check-circle me-2"></i>Assign Teacher
                        </button>
                      </div>
                    </div>
                  </form>
                </div>
                
                <div class="info-card mt-3">
                  <div class="card-header-modern">
                    <i class="bi bi-list-check"></i>
                    <h3>Teacher Assignments (<?php echo count($teacherAssignments); ?> total)</h3>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead>
                        <tr>
                          <th>Teacher Name</th>
                          <th>Role</th>
                          <th>Year</th>
                          <th>Subject</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (empty($teacherAssignments)): ?>
                          <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                              <i class="bi bi-inbox"></i> No teacher assignments found.
                            </td>
                          </tr>
                        <?php else: ?>
                          <?php foreach ($teacherAssignments as $ta): 
                            $taRole = $ta['role'] ?? 'teacher';
                            $taRoleBadgeClass = 'info';
                            $taRoleLabel = ucfirst($taRole);
                            if ($taRole === 'admin') {
                              $taRoleBadgeClass = 'danger';
                            } elseif ($taRole === 'teacher') {
                              $taRoleBadgeClass = 'info';
                            } elseif ($taRole === 'student') {
                              $taRoleBadgeClass = 'success';
                            }
                          ?>
                            <tr>
                              <td><strong><?php echo htmlspecialchars($ta['full_name'] ?? $ta['username']); ?></strong></td>
                              <td>
                                <span class="badge bg-<?php echo $taRoleBadgeClass; ?>">
                                  <?php echo htmlspecialchars($taRoleLabel); ?>
                                </span>
                              </td>
                              <td><?php echo htmlspecialchars($ta['year']); ?></td>
                              <td><?php echo htmlspecialchars($ta['subject']); ?></td>
                              <td>
                                <form method="post" action="/TCC/BackEnd/admin/manage_users.php" onsubmit="return confirm('Are you sure you want to delete this teacher assignment? This action cannot be undone.');" style="display:inline;">
                                  <input type="hidden" name="action" value="delete_teacher" />
                                  <input type="hidden" name="id" value="<?php echo (int)$ta['id']; ?>" />
                                  <button type="submit" class="Btn Btn-delete">
                                    <div class="svgWrapper">
                                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 42 42" class="svgIcon">
                                        <path stroke-width="5" stroke="#fff" d="M9.14073 2.5H32.8593C33.3608 2.5 33.8291 2.75065 34.1073 3.16795L39.0801 10.6271C39.3539 11.0378 39.5 11.5203 39.5 12.0139V21V37C39.5 38.3807 38.3807 39.5 37 39.5H5C3.61929 39.5 2.5 38.3807 2.5 37V21V12.0139C2.5 11.5203 2.6461 11.0378 2.91987 10.6271L7.89266 3.16795C8.17086 2.75065 8.63921 2.5 9.14073 2.5Z"></path>
                                        <path stroke-width="5" stroke="#fff" d="M14 18L28 18M18 14V30M24 14V30"></path>
                                      </svg>
                                      <div class="text">Delete</div>
                                    </div>
                                  </button>
                                </form>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
              
              <!-- Schedules Tab -->
              <div class="tab-pane fade <?php echo $activeTab === 'schedules' ? 'show active' : ''; ?>" id="schedules-pane" role="tabpanel" aria-labelledby="schedules-tab">
                <?php
                // Ensure schedules table exists
                $conn->query("CREATE TABLE IF NOT EXISTS schedules (
                  id INT AUTO_INCREMENT PRIMARY KEY,
                  year VARCHAR(10) NOT NULL,
                  subject VARCHAR(255) NOT NULL,
                  day VARCHAR(20) NOT NULL,
                  time_start TIME NOT NULL,
                  time_end TIME NOT NULL,
                  room VARCHAR(100) DEFAULT NULL,
                  instructor VARCHAR(255) DEFAULT NULL,
                  section VARCHAR(100) DEFAULT NULL,
                  building VARCHAR(10) DEFAULT NULL,
                  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  INDEX idx_year (year),
                  INDEX idx_subject (subject),
                  INDEX idx_day (day)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                
                // Get available buildings for dropdown
                $availableBuildings = [];
                $buildingsQuery = $conn->query("SELECT name FROM buildings ORDER BY name");
                if ($buildingsQuery) {
                  while ($row = $buildingsQuery->fetch_assoc()) {
                    $availableBuildings[] = $row['name'];
                  }
                }
                // Also check JSON fallback
                if (empty($availableBuildings)) {
                  $buildingsPath = __DIR__ . '/../database/buildings.json';
                  if (file_exists($buildingsPath)) {
                    $buildingsData = json_decode(file_get_contents($buildingsPath), true) ?: [];
                    $availableBuildings = array_keys($buildingsData);
                  }
                }
                
                // Handle edit schedule
                $editScheduleId = isset($_GET['edit_schedule_id']) ? intval($_GET['edit_schedule_id']) : 0;
                $editScheduleRow = null;
                if ($editScheduleId > 0) {
                  $s = $conn->prepare("SELECT id, year, subject, day, time_start, time_end, room, instructor, section, building FROM schedules WHERE id = ? LIMIT 1");
                  $s->bind_param('i', $editScheduleId);
                  $s->execute();
                  $r = $s->get_result();
                  $editScheduleRow = $r->fetch_assoc();
                }
                
                // Handle toast notifications for schedules
                if (isset($_GET['success'])) {
                  $successMsg = $_GET['success'];
                  if ($successMsg === 'deleted') {
                    $toastMessage = 'Schedule deleted successfully!';
                  } elseif ($successMsg === 'updated') {
                    $toastMessage = 'Schedule updated successfully!';
                  } elseif ($successMsg === 'created') {
                    $toastMessage = 'Schedule created successfully!';
                  }
                  $toastType = 'success';
                } elseif (isset($_GET['error'])) {
                  $errorMsg = $_GET['error'];
                  $toastType = 'error';
                  if ($errorMsg === 'missing') {
                    $toastMessage = 'Error: Please fill in all required fields.';
                  } elseif ($errorMsg === 'invalid_id') {
                    $toastMessage = 'Error: Invalid schedule ID.';
                  } elseif ($errorMsg === 'instructor_not_found') {
                    $toastMessage = 'Error: Instructor does not exist. Please assign the instructor first in the Manage Teachers section.';
                  } elseif ($errorMsg === 'section_not_found') {
                    $toastMessage = 'Error: Section does not exist. Please create the section first in the Sections section.';
                  } elseif ($errorMsg === 'building_not_found') {
                    $toastMessage = 'Error: Building does not exist. Please create the building first in the Buildings section.';
                  } else {
                    $toastMessage = 'Error: ' . htmlspecialchars($errorMsg);
                  }
                }
                
                // Get filter parameters
                $scheduleFilterYear = isset($_GET['schedule_year_filter']) ? trim($_GET['schedule_year_filter']) : '';
                $scheduleFilterSubject = isset($_GET['schedule_subject_filter']) ? trim($_GET['schedule_subject_filter']) : '';
                
                // Get available years and subjects for filter chips
                $availableScheduleYears = [];
                $availableScheduleSubjects = [];
                
                $scheduleYearsQuery = $conn->query("SELECT DISTINCT year FROM schedules WHERE year IS NOT NULL AND year <> '' ORDER BY CAST(year AS UNSIGNED)");
                if ($scheduleYearsQuery) {
                  while ($row = $scheduleYearsQuery->fetch_assoc()) {
                    if (!empty($row['year']) && !in_array($row['year'], $availableScheduleYears)) {
                      $availableScheduleYears[] = $row['year'];
                    }
                  }
                }
                
                $scheduleSubjectsQuery = $conn->query("SELECT DISTINCT subject FROM schedules WHERE subject IS NOT NULL AND subject <> '' ORDER BY subject");
                if ($scheduleSubjectsQuery) {
                  while ($row = $scheduleSubjectsQuery->fetch_assoc()) {
                    if (!empty($row['subject']) && !in_array($row['subject'], $availableScheduleSubjects)) {
                      $availableScheduleSubjects[] = $row['subject'];
                    }
                  }
                }
                
                // Build query with filters
                $scheduleConds = [];
                $scheduleTypes = '';
                $scheduleValues = [];
                if ($scheduleFilterYear !== '') {
                  $scheduleConds[] = 'year = ?';
                  $scheduleTypes .= 's';
                  $scheduleValues[] = $scheduleFilterYear;
                }
                if ($scheduleFilterSubject !== '') {
                  $scheduleConds[] = 'subject = ?';
                  $scheduleTypes .= 's';
                  $scheduleValues[] = $scheduleFilterSubject;
                }
                
                $scheduleWhere = count($scheduleConds) ? 'WHERE ' . implode(' AND ', $scheduleConds) : '';
                
                // Get schedules
                $schedules = [];
                $scheduleSql = "SELECT id, year, subject, day, time_start, time_end, room, instructor, section, building FROM schedules $scheduleWhere ORDER BY year, day, time_start";
                if (count($scheduleConds) > 0) {
                  $scheduleStmt = $conn->prepare($scheduleSql);
                  if ($scheduleStmt) {
                    $scheduleStmt->bind_param($scheduleTypes, ...$scheduleValues);
                    $scheduleStmt->execute();
                    $scheduleRes = $scheduleStmt->get_result();
                    while ($row = $scheduleRes->fetch_assoc()) {
                      $schedules[] = $row;
                    }
                    $scheduleStmt->close();
                  }
                } else {
                  $scheduleQuery = $conn->query($scheduleSql);
                  if ($scheduleQuery) {
                    while ($row = $scheduleQuery->fetch_assoc()) {
                      $schedules[] = $row;
                    }
                  }
                }
                ?>
                
                <div class="info-card">
                  <div class="card-header-modern">
                    <i class="bi bi-calendar-plus"></i>
                    <h3><?php echo $editScheduleRow ? 'Edit Schedule' : 'Create New Schedule'; ?></h3>
                  </div>
                  <form action="/TCC/BackEnd/admin/manage_schedules.php" method="post" class="admin-user-assign-form">
                    <?php if ($editScheduleRow): ?>
                      <input type="hidden" name="action" value="update" />
                      <input type="hidden" name="id" value="<?php echo (int)$editScheduleRow['id']; ?>" />
                    <?php else: ?>
                      <input type="hidden" name="action" value="create" />
                    <?php endif; ?>
                    
                    <div class="row g-3">
                      <div class="col-md-3">
                        <div class="admin-form-group">
                          <label class="admin-form-label"><i class="bi bi-calendar-year"></i> Year</label>
                          <select name="year" class="form-select form-select-lg" required>
                            <option value="">Select Year...</option>
                            <option value="1" <?php echo ($editScheduleRow && $editScheduleRow['year']=='1')?'selected':'';?>>1st Year</option>
                            <option value="2" <?php echo ($editScheduleRow && $editScheduleRow['year']=='2')?'selected':'';?>>2nd Year</option>
                            <option value="3" <?php echo ($editScheduleRow && $editScheduleRow['year']=='3')?'selected':'';?>>3rd Year</option>
                            <option value="4" <?php echo ($editScheduleRow && $editScheduleRow['year']=='4')?'selected':'';?>>4th Year</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="admin-form-group">
                          <label class="admin-form-label"><i class="bi bi-book"></i> Subject</label>
                          <input type="text" name="subject" class="form-control form-control-lg" placeholder="e.g. Mathematics" required value="<?php echo $editScheduleRow ? htmlspecialchars($editScheduleRow['subject']) : ''; ?>"/>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="admin-form-group">
                          <label class="admin-form-label"><i class="bi bi-calendar-day"></i> Day</label>
                          <select name="day" class="form-select form-select-lg" required>
                            <option value="">Select Day...</option>
                            <option value="Monday" <?php echo ($editScheduleRow && $editScheduleRow['day']=='Monday')?'selected':'';?>>Monday</option>
                            <option value="Tuesday" <?php echo ($editScheduleRow && $editScheduleRow['day']=='Tuesday')?'selected':'';?>>Tuesday</option>
                            <option value="Wednesday" <?php echo ($editScheduleRow && $editScheduleRow['day']=='Wednesday')?'selected':'';?>>Wednesday</option>
                            <option value="Thursday" <?php echo ($editScheduleRow && $editScheduleRow['day']=='Thursday')?'selected':'';?>>Thursday</option>
                            <option value="Friday" <?php echo ($editScheduleRow && $editScheduleRow['day']=='Friday')?'selected':'';?>>Friday</option>
                            <option value="Saturday" <?php echo ($editScheduleRow && $editScheduleRow['day']=='Saturday')?'selected':'';?>>Saturday</option>
                            <option value="Sunday" <?php echo ($editScheduleRow && $editScheduleRow['day']=='Sunday')?'selected':'';?>>Sunday</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="admin-form-group">
                          <label class="admin-form-label"><i class="bi bi-clock"></i> Time Start</label>
                          <input type="time" name="time_start" class="form-control form-control-lg" required value="<?php echo $editScheduleRow ? htmlspecialchars($editScheduleRow['time_start']) : ''; ?>"/>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="admin-form-group">
                          <label class="admin-form-label"><i class="bi bi-clock-history"></i> Time End</label>
                          <input type="time" name="time_end" class="form-control form-control-lg" required value="<?php echo $editScheduleRow ? htmlspecialchars($editScheduleRow['time_end']) : ''; ?>"/>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="admin-form-group">
                          <label class="admin-form-label"><i class="bi bi-door-closed"></i> Room</label>
                          <input type="text" name="room" class="form-control form-control-lg" placeholder="e.g. Room 301" value="<?php echo $editScheduleRow ? htmlspecialchars($editScheduleRow['room'] ?? '') : ''; ?>"/>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="admin-form-group">
                          <label class="admin-form-label"><i class="bi bi-person-badge"></i> Instructor</label>
                          <input type="text" name="instructor" class="form-control form-control-lg" placeholder="e.g. Ms. Johnson" value="<?php echo $editScheduleRow ? htmlspecialchars($editScheduleRow['instructor'] ?? '') : ''; ?>"/>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="admin-form-group">
                          <label class="admin-form-label"><i class="bi bi-people"></i> Section</label>
                          <input type="text" name="section" class="form-control form-control-lg" placeholder="e.g. Benevolence" value="<?php echo $editScheduleRow ? htmlspecialchars($editScheduleRow['section'] ?? '') : ''; ?>"/>
                        </div>
                      </div>
                      <div class="col-md-3">
                        <div class="admin-form-group">
                          <label class="admin-form-label"><i class="bi bi-building"></i> Building</label>
                          <select name="building" class="form-select form-select-lg">
                            <option value="">Select Building...</option>
                            <?php foreach ($availableBuildings as $bld): ?>
                              <option value="<?php echo htmlspecialchars($bld); ?>" <?php echo ($editScheduleRow && isset($editScheduleRow['building']) && $editScheduleRow['building']===$bld)?'selected':'';?>><?php echo htmlspecialchars($bld); ?></option>
                            <?php endforeach; ?>
                          </select>
                        </div>
                      </div>
                    </div>
                    <div class="row g-3 mt-2">
                      <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-lg">
                          <i class="bi bi-check-circle me-2"></i><?php echo $editScheduleRow ? 'Update Schedule' : 'Create Schedule'; ?>
                        </button>
                        <?php if ($editScheduleRow): ?>
                          <a href="/TCC/public/admin_dashboard.php?section=user_management&tab=schedules" class="btn btn-secondary btn-lg ms-2">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                          </a>
                        <?php endif; ?>
                      </div>
                    </div>
                  </form>
                </div>
                
                <!-- Schedule Filters -->
                <div class="info-card mt-3 grade-filter-card">
                  <div class="grade-filter-inner">
                    <div class="grade-filter-head">
                      <div class="grade-filter-title">
                        <span class="grade-filter-icon"><i class="bi bi-funnel-fill"></i></span>
                        <div>
                          <h3>Filter Schedules</h3>
                          <p>Focus the overview by year or subject.</p>
                        </div>
                      </div>
                      <?php if ($scheduleFilterYear !== '' || $scheduleFilterSubject !== ''): ?>
                        <a href="/TCC/public/admin_dashboard.php?section=user_management&tab=schedules" class="grade-filter-reset">
                          <i class="bi bi-arrow-counterclockwise"></i> Reset view
                        </a>
                      <?php endif; ?>
                    </div>
                    
                    <div class="grade-filter-actions">
                      <?php if (!empty($availableScheduleYears)): ?>
                      <div class="grade-filter-group">
                        <span class="grade-filter-label">Year Level</span>
                        <?php 
                        $scheduleFilterBase = $_GET;
                        $scheduleFilterBase['section'] = 'user_management';
                        $scheduleFilterBase['tab'] = 'schedules';
                        unset($scheduleFilterBase['schedule_year_filter']);
                        $scheduleYearBase = $scheduleFilterBase;
                        $scheduleYearAllUrl = '/TCC/public/admin_dashboard.php?' . htmlspecialchars(http_build_query($scheduleYearBase));
                        ?>
                        <a href="<?php echo $scheduleYearAllUrl; ?>" class="grade-chip <?php echo ($scheduleFilterYear === '') ? 'active' : ''; ?>">
                          <i class="bi bi-layers"></i>
                          <span>All Years</span>
                        </a>
                        <?php foreach ($availableScheduleYears as $yearValue): 
                          $scheduleYearParams = $scheduleYearBase;
                          $scheduleYearParams['schedule_year_filter'] = $yearValue;
                          $scheduleYearUrl = '/TCC/public/admin_dashboard.php?' . htmlspecialchars(http_build_query($scheduleYearParams));
                          $scheduleYearLabel = $yearValue === '1' ? '1st Year' : ($yearValue === '2' ? '2nd Year' : ($yearValue === '3' ? '3rd Year' : ($yearValue === '4' ? '4th Year' : $yearValue . ' Year')));
                          ?>
                          <a href="<?php echo $scheduleYearUrl; ?>" class="grade-chip <?php echo ($scheduleFilterYear === $yearValue) ? 'active' : ''; ?>">
                            <i class="bi bi-calendar-week"></i>
                            <span><?php echo htmlspecialchars($scheduleYearLabel); ?></span>
                          </a>
                        <?php endforeach; ?>
                      </div>
                      <?php endif; ?>
                      
                      <?php if (!empty($availableScheduleSubjects)): ?>
                      <div class="grade-filter-group">
                        <span class="grade-filter-label">Subject</span>
                        <?php
                        $scheduleSubjectBase = $scheduleFilterBase;
                        unset($scheduleSubjectBase['schedule_subject_filter']);
                        $scheduleSubjectAllUrl = '/TCC/public/admin_dashboard.php?' . htmlspecialchars(http_build_query($scheduleSubjectBase));
                        ?>
                        <a href="<?php echo $scheduleSubjectAllUrl; ?>" class="grade-chip <?php echo ($scheduleFilterSubject === '') ? 'active' : ''; ?>">
                          <i class="bi bi-grid-1x2"></i>
                          <span>All Subjects</span>
                        </a>
                        <?php foreach ($availableScheduleSubjects as $subjectValue): 
                          $scheduleSubjectParams = $scheduleSubjectBase;
                          $scheduleSubjectParams['schedule_subject_filter'] = $subjectValue;
                          $scheduleSubjectUrl = '/TCC/public/admin_dashboard.php?' . htmlspecialchars(http_build_query($scheduleSubjectParams));
                          ?>
                          <a href="<?php echo $scheduleSubjectUrl; ?>" class="grade-chip <?php echo ($scheduleFilterSubject === $subjectValue) ? 'active' : ''; ?>">
                            <i class="bi bi-book"></i>
                            <span><?php echo htmlspecialchars($subjectValue); ?></span>
                          </a>
                        <?php endforeach; ?>
                      </div>
                      <?php endif; ?>
                    </div>
                    
                    <?php if ($scheduleFilterYear !== '' || $scheduleFilterSubject !== ''): ?>
                      <div class="grade-filter-note">
                        <i class="bi bi-info-circle"></i>
                        Showing schedules
                        <?php if ($scheduleFilterYear !== ''): ?>
                          for <strong><?php echo htmlspecialchars($scheduleFilterYear === '1' ? '1st Year' : ($scheduleFilterYear === '2' ? '2nd Year' : ($scheduleFilterYear === '3' ? '3rd Year' : ($scheduleFilterYear === '4' ? '4th Year' : $scheduleFilterYear . ' Year')))); ?></strong>
                        <?php endif; ?>
                        <?php if ($scheduleFilterSubject !== ''): ?>
                          in subject <strong><?php echo htmlspecialchars($scheduleFilterSubject); ?></strong>
                        <?php endif; ?>
                        .
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
                
                <!-- Schedules Table -->
                <div class="info-card mt-3">
                  <div class="card-header-modern">
                    <i class="bi bi-calendar-week"></i>
                    <h3>Schedules (<?php echo count($schedules); ?> total)</h3>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead>
                        <tr>
                          <th>Year</th>
                          <th>Subject</th>
                          <th>Day</th>
                          <th>Time</th>
                          <th>Room</th>
                          <th>Instructor</th>
                          <th>Section</th>
                          <th>Building</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (empty($schedules)): ?>
                          <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                              <i class="bi bi-inbox"></i> No schedules found.
                            </td>
                          </tr>
                        <?php else: ?>
                          <?php foreach ($schedules as $sched): ?>
                            <tr>
                              <td><strong><?php echo htmlspecialchars($sched['year']); ?></strong></td>
                              <td><?php echo htmlspecialchars($sched['subject']); ?></td>
                              <td><?php echo htmlspecialchars($sched['day']); ?></td>
                              <td><?php echo htmlspecialchars(date('g:i A', strtotime($sched['time_start'])) . ' - ' . date('g:i A', strtotime($sched['time_end']))); ?></td>
                              <td><?php echo htmlspecialchars($sched['room'] ?: '-'); ?></td>
                              <td><?php echo htmlspecialchars($sched['instructor'] ?: '-'); ?></td>
                              <td><?php echo htmlspecialchars($sched['section'] ?: '-'); ?></td>
                              <td><?php echo htmlspecialchars($sched['building'] ?: '-'); ?></td>
                              <td>
                                <div style="display: flex; gap: 8px;">
                                  <a href="/TCC/public/admin_dashboard.php?section=user_management&tab=schedules&edit_schedule_id=<?php echo (int)$sched['id']; ?>" class="Btn Btn-edit">
                                    <div class="svgWrapper">
                                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 42 42" class="svgIcon">
                                        <path stroke-width="5" stroke="#fff" d="M21 5L7 19L5 37L23 35L37 21L21 5Z"></path>
                                        <path stroke-width="3" stroke="#fff" d="M21 5L37 21"></path>
                                        <path stroke-width="3" stroke="#fff" d="M15 19L23 27"></path>
                                      </svg>
                                      <div class="text">Edit</div>
                                    </div>
                                  </a>
                                  <form method="post" action="/TCC/BackEnd/admin/manage_schedules.php" onsubmit="return confirm('Are you sure you want to delete this schedule? This action cannot be undone.');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete" />
                                    <input type="hidden" name="id" value="<?php echo (int)$sched['id']; ?>" />
                                    <button type="submit" class="Btn Btn-delete">
                                      <div class="svgWrapper">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 42 42" class="svgIcon">
                                          <path stroke-width="5" stroke="#fff" d="M9.14073 2.5H32.8593C33.3608 2.5 33.8291 2.75065 34.1073 3.16795L39.0801 10.6271C39.3539 11.0378 39.5 11.5203 39.5 12.0139V21V37C39.5 38.3807 38.3807 39.5 37 39.5H5C3.61929 39.5 2.5 38.3807 2.5 37V21V12.0139C2.5 11.5203 2.6461 11.0378 2.91987 10.6271L7.89266 3.16795C8.17086 2.75065 8.63921 2.5 9.14073 2.5Z"></path>
                                          <path stroke-width="5" stroke="#fff" d="M14 18L28 18M18 14V30M24 14V30"></path>
                                        </svg>
                                        <div class="text">Delete</div>
                                      </div>
                                    </button>
                                  </form>
                                </div>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
              </div>
            </div>
          <?php elseif ($section === 'sections'): ?>
            <?php
            require_once __DIR__ . '/../BackEnd/database/db.php';
            $conn = Database::getInstance()->getConnection();
            
            // Ensure sections table exists
            $conn->query("CREATE TABLE IF NOT EXISTS sections (
              id INT AUTO_INCREMENT PRIMARY KEY,
              year VARCHAR(10) NOT NULL,
              name VARCHAR(100) NOT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY uniq_year_name (year, name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            // Handle form submissions
            $editSectionId = isset($_GET['edit_section_id']) ? intval($_GET['edit_section_id']) : 0;
            $editSectionRow = null;
            if ($editSectionId > 0) {
              $s = $conn->prepare("SELECT id, year, name FROM sections WHERE id = ? LIMIT 1");
              $s->bind_param('i', $editSectionId);
              $s->execute();
              $r = $s->get_result();
              $editSectionRow = $r->fetch_assoc();
            }
            
            // Handle toast notifications
            if (!isset($toastMessage)) $toastMessage = '';
            if (!isset($toastType)) $toastType = 'success';
            
            if (isset($_GET['success'])) {
              $successMsg = $_GET['success'];
              if ($successMsg === 'deleted') {
                $toastMessage = 'Section deleted successfully!';
              } elseif ($successMsg === 'updated') {
                $toastMessage = 'Section updated successfully!';
              } elseif ($successMsg === 'created') {
                $toastMessage = 'Section created successfully!';
              } else {
                $toastMessage = 'Section saved successfully!';
              }
              $toastType = 'success';
            } elseif (isset($_GET['error'])) {
              $errorMsg = $_GET['error'];
              $toastType = 'error';
              if ($errorMsg === 'missing') {
                $toastMessage = 'Error: Please fill in all required fields.';
              } elseif ($errorMsg === 'duplicate') {
                $toastMessage = 'Error: A section with this name already exists for this year.';
              } elseif ($errorMsg === 'invalid_id') {
                $toastMessage = 'Error: Invalid section ID.';
              } else {
                $toastMessage = 'Error: ' . htmlspecialchars($errorMsg);
              }
            }
            
            // Get all sections grouped by year
            $sectionsByYear = [];
            $sectionsQuery = $conn->query("SELECT id, year, name, created_at FROM sections ORDER BY CAST(year AS UNSIGNED), name");
            if ($sectionsQuery) {
              while ($row = $sectionsQuery->fetch_assoc()) {
                $year = $row['year'];
                if (!isset($sectionsByYear[$year])) {
                  $sectionsByYear[$year] = [];
                }
                $sectionsByYear[$year][] = $row;
              }
            }
            ?>
            <div class="records-container">
              <div class="records-header">
                <h2 class="records-title">
                  <i class="bi bi-collection-fill"></i> Sections
                </h2>
                <p class="records-subtitle">Create and manage academic sections for each year level</p>
              </div>
              <div class="records-main">
                <div class="info-card">
                  <div class="card-header-modern">
                    <i class="bi bi-collection-fill"></i>
                    <h3><?php echo $editSectionRow ? 'Edit Section' : 'Create New Section'; ?></h3>
                  </div>
                  <form class="form-small" action="/TCC/BackEnd/admin/manage_sections.php" method="post">
                    <?php if ($editSectionRow): ?>
                      <input type="hidden" name="action" value="update" />
                      <input type="hidden" name="id" value="<?php echo (int)$editSectionRow['id']; ?>" />
                    <?php else: ?>
                      <input type="hidden" name="action" value="create" />
                    <?php endif; ?>
                    
                    <div class="row g-3 mb-3">
                      <div class="col-md-6">
                        <label class="admin-form-label"><i class="bi bi-calendar-year"></i> Year</label>
                        <select name="year" class="form-select form-select-lg" required>
                          <option value="">Select Year...</option>
                          <option value="1" <?php echo ($editSectionRow && $editSectionRow['year']=='1')?'selected':'';?>>1st Year</option>
                          <option value="2" <?php echo ($editSectionRow && $editSectionRow['year']=='2')?'selected':'';?>>2nd Year</option>
                          <option value="3" <?php echo ($editSectionRow && $editSectionRow['year']=='3')?'selected':'';?>>3rd Year</option>
                          <option value="4" <?php echo ($editSectionRow && $editSectionRow['year']=='4')?'selected':'';?>>4th Year</option>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label class="admin-form-label"><i class="bi bi-tag-fill"></i> Section Name</label>
                        <input name="name" class="form-control form-control-lg" placeholder="e.g. Power, Benevolence, Excellence" required value="<?php echo $editSectionRow ? htmlspecialchars($editSectionRow['name']) : ''; ?>"/>
                      </div>
                    </div>
                    
                    <button class="btn btn-primary btn-lg">
                      <i class="bi bi-check-circle me-2"></i><?php echo $editSectionRow ? 'Update Section' : 'Create Section'; ?>
                    </button>
                    <?php if ($editSectionRow): ?>
                      <a href="/TCC/public/admin_dashboard.php?section=sections" class="btn btn-secondary btn-lg ms-2">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                      </a>
                    <?php endif; ?>
                  </form>
                </div>

                <?php if (empty($sectionsByYear)): ?>
                  <div class="info-card mt-3">
                    <div class="card-header-modern">
                      <i class="bi bi-collection"></i>
                      <h3>No Sections</h3>
                    </div>
                    <p class="text-muted mb-0">No sections have been created yet. Create one above to get started.</p>
                  </div>
                <?php else: ?>
                  <?php
                  $years = ['1', '2', '3', '4'];
                  foreach ($years as $yearNum):
                    if (!isset($sectionsByYear[$yearNum]) || empty($sectionsByYear[$yearNum])) {
                      continue;
                    }
                    $yearLabel = $yearNum == '1' ? '1st Year' : ($yearNum == '2' ? '2nd Year' : ($yearNum == '3' ? '3rd Year' : '4th Year'));
                  ?>
                    <div class="info-card grade-year-card mt-3">
                      <div class="card-header-modern">
                        <i class="bi bi-calendar-year"></i>
                        <h3><?php echo $yearLabel; ?></h3>
                      </div>
                      <div class="grade-year-body">
                        <div class="grade-student-list">
                          <?php foreach ($sectionsByYear[$yearNum] as $section): ?>
                            <div class="student-grade-card">
                              <div class="student-grade-main">
                                <div style="width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, var(--color-flora), var(--color-sage)); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; color: white; flex-shrink: 0;">
                                  <?php echo strtoupper(substr(trim($section['name']), 0, 1)); ?>
                                </div>
                                <div>
                                  <span class="student-grade-name"><?php echo htmlspecialchars($section['name']); ?></span>
                                  <span class="student-grade-summary">Section for <?php echo htmlspecialchars($yearLabel); ?></span>
                                </div>
                              </div>
                              <div class="student-grade-meta">
                                <div style="display: flex; gap: 8px; align-items: center;">
                                  <a href="/TCC/public/admin_dashboard.php?section=sections&edit_section_id=<?php echo (int)$section['id']; ?>" class="Btn Btn-edit">
                                    <div class="svgWrapper">
                                      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 42 42" class="svgIcon">
                                        <path stroke-width="5" stroke="#fff" d="M21 5L7 19L5 37L23 35L37 21L21 5Z"></path>
                                        <path stroke-width="3" stroke="#fff" d="M21 5L37 21"></path>
                                        <path stroke-width="3" stroke="#fff" d="M15 19L23 27"></path>
                                      </svg>
                                      <div class="text">Edit</div>
                                    </div>
                                  </a>
                                  <form action="/TCC/BackEnd/admin/manage_sections.php" method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete the section &quot;<?php echo htmlspecialchars($section['name']); ?>&quot;? This action cannot be undone.');">
                                    <input type="hidden" name="action" value="delete" />
                                    <input type="hidden" name="id" value="<?php echo (int)$section['id']; ?>" />
                                    <button type="submit" class="Btn Btn-delete">
                                      <div class="svgWrapper">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 42 42" class="svgIcon">
                                          <path stroke-width="5" stroke="#fff" d="M9.14073 2.5H32.8593C33.3608 2.5 33.8291 2.75065 34.1073 3.16795L39.0801 10.6271C39.3539 11.0378 39.5 11.5203 39.5 12.0139V21V37C39.5 38.3807 38.3807 39.5 37 39.5H5C3.61929 39.5 2.5 38.3807 2.5 37V21V12.0139C2.5 11.5203 2.6461 11.0378 2.91987 10.6271L7.89266 3.16795C8.17086 2.75065 8.63921 2.5 9.14073 2.5Z"></path>
                                          <path stroke-width="5" stroke="#fff" d="M14 18L28 18M18 14V30M24 14V30"></path>
                                        </svg>
                                        <div class="text">Delete</div>
                                      </div>
                                    </button>
                                  </form>
                                </div>
                              </div>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          <?php elseif ($section === 'grade_system'): ?>
            <?php
            require_once __DIR__ . '/../BackEnd/database/db.php';
            $conn = Database::getInstance()->getConnection();
            
            // Handle form submissions
            $editGradeId = isset($_GET['edit_grade_id']) ? intval($_GET['edit_grade_id']) : 0;
            $editGradeRow = null;
            if ($editGradeId > 0) {
              $s = $conn->prepare("SELECT id, user_id, username, year, semester, subject, instructor, prelim_grade, midterm_grade, finals_grade FROM student_grades WHERE id = ? LIMIT 1");
              $s->bind_param('i', $editGradeId);
              $s->execute();
              $r = $s->get_result();
              $editGradeRow = $r->fetch_assoc();
            }
            
            // Get all students for dropdown
            $students = [];
            $studentsQuery = $conn->query("SELECT id, username, full_name FROM users WHERE role = 'student' ORDER BY full_name, username");
            if ($studentsQuery) {
              while ($row = $studentsQuery->fetch_assoc()) {
                $students[] = $row;
              }
            }
            
            // Handle toast notifications for grade system
            if (!isset($toastMessage)) $toastMessage = '';
            if (!isset($toastType)) $toastType = 'success';
            
            if (isset($_GET['success'])) {
              $successMsg = $_GET['success'];
              if ($successMsg === 'deleted') {
                $toastMessage = 'Grade deleted successfully!';
              } elseif ($successMsg === 'deleted_all') {
                $toastMessage = 'All student grades deleted successfully!';
              } elseif ($successMsg === 'updated') {
                $toastMessage = 'Grade updated successfully!';
              } else {
                $toastMessage = 'Grade saved successfully!';
              }
              $toastType = 'success';
            } elseif (isset($_GET['error'])) {
              $errorMsg = $_GET['error'];
              $toastType = 'error';
              if ($errorMsg === 'missing') {
                $toastMessage = 'Error: Please fill in all required fields.';
              } elseif ($errorMsg === 'invalid_id' || $errorMsg === 'invalid_ids') {
                $toastMessage = 'Error: Invalid grade ID(s).';
              } elseif ($errorMsg === 'no_grades') {
                $toastMessage = 'Error: No grades found to delete.';
              } else {
                $toastMessage = 'Error: ' . htmlspecialchars($errorMsg);
              }
            }
            ?>
            <div class="records-container">
              <div class="records-header">
                <h2 class="records-title">
                  <i class="bi bi-journal-bookmark-fill"></i> Grade System
                </h2>
                <p class="records-subtitle">Manage student grades and academic records</p>
              </div>
              <div class="records-main">
            <div class="info-card">
              <div class="card-header-modern">
                <i class="bi bi-journal-bookmark-fill"></i>
                <h3><?php echo $editGradeRow ? 'Edit Student Grade' : 'Add Student Grade'; ?></h3>
              </div>
              <form class="form-small" action="/TCC/BackEnd/admin/manage_grades.php" method="post">
                <?php if ($editGradeRow): ?>
                  <input type="hidden" name="action" value="update" />
                  <input type="hidden" name="id" value="<?php echo (int)$editGradeRow['id']; ?>" />
                <?php else: ?>
                  <input type="hidden" name="action" value="create" />
                <?php endif; ?>
                
                <div class="mb-3">
                  <label class="admin-form-label"><i class="bi bi-person"></i> Student</label>
                  <select name="user_id" class="form-select form-select-lg" required>
                    <option value="">Select Student...</option>
                    <?php if (empty($students)): ?>
                      <option value="" disabled>No students found. Please create student accounts first.</option>
                    <?php else: ?>
                      <?php foreach ($students as $student): ?>
                        <option value="<?php echo (int)$student['id']; ?>" <?php echo ($editGradeRow && $editGradeRow['user_id'] == $student['id']) ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars($student['full_name'] . ' (' . $student['username'] . ')'); ?>
                        </option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </div>
                
                <div class="row g-3 mb-3">
                  <div class="col-md-6">
                    <label class="admin-form-label"><i class="bi bi-calendar-year"></i> Year</label>
                    <select name="year" class="form-select form-select-lg" required>
                      <option value="1" <?php echo ($editGradeRow && $editGradeRow['year']=='1')?'selected':'';?>>1st Year</option>
                      <option value="2" <?php echo ($editGradeRow && $editGradeRow['year']=='2')?'selected':'';?>>2nd Year</option>
                      <option value="3" <?php echo ($editGradeRow && $editGradeRow['year']=='3')?'selected':'';?>>3rd Year</option>
                      <option value="4" <?php echo ($editGradeRow && $editGradeRow['year']=='4')?'selected':'';?>>4th Year</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="admin-form-label"><i class="bi bi-calendar3"></i> Semester</label>
                    <select name="semester" class="form-select form-select-lg" required>
                      <option value="First Semester" <?php echo ($editGradeRow && $editGradeRow['semester']=='First Semester')?'selected':'';?>>First Semester</option>
                      <option value="Second Semester" <?php echo ($editGradeRow && $editGradeRow['semester']=='Second Semester')?'selected':'';?>>Second Semester</option>
                    </select>
                  </div>
                </div>
                
                <div class="mb-3">
                  <label class="admin-form-label"><i class="bi bi-book"></i> Subject</label>
                  <input name="subject" class="form-control form-control-lg" placeholder="e.g. Mathematics" required value="<?php echo $editGradeRow ? htmlspecialchars($editGradeRow['subject']) : ''; ?>"/>
                </div>
                
                <div class="mb-3">
                  <label class="admin-form-label"><i class="bi bi-person-badge"></i> Instructor</label>
                  <input name="instructor" class="form-control form-control-lg" placeholder="e.g. Ms. Johnson" value="<?php echo $editGradeRow ? htmlspecialchars($editGradeRow['instructor'] ?? '') : ''; ?>"/>
                </div>
                
                <div class="row g-3 mb-3">
                  <div class="col-md-4">
                    <label class="admin-form-label"><i class="bi bi-1-circle"></i> Prelim</label>
                    <input name="prelim_grade" type="number" step="0.01" min="0" max="100" class="form-control form-control-lg" placeholder="88" value="<?php echo $editGradeRow ? htmlspecialchars($editGradeRow['prelim_grade'] ?? '') : ''; ?>"/>
                  </div>
                  <div class="col-md-4">
                    <label class="admin-form-label"><i class="bi bi-2-circle"></i> Midterm</label>
                    <input name="midterm_grade" type="number" step="0.01" min="0" max="100" class="form-control form-control-lg" placeholder="92" value="<?php echo $editGradeRow ? htmlspecialchars($editGradeRow['midterm_grade'] ?? '') : ''; ?>"/>
                  </div>
                  <div class="col-md-4">
                    <label class="admin-form-label"><i class="bi bi-3-circle"></i> Finals</label>
                    <input name="finals_grade" type="number" step="0.01" min="0" max="100" class="form-control form-control-lg" placeholder="90" value="<?php echo $editGradeRow ? htmlspecialchars($editGradeRow['finals_grade'] ?? '') : ''; ?>"/>
                  </div>
                </div>
                
                <button class="btn btn-primary btn-lg">
                  <i class="bi bi-check-circle me-2"></i><?php echo $editGradeRow ? 'Update Grade' : 'Save Grade'; ?>
                </button>
                <?php if ($editGradeRow): ?>
                  <a href="/TCC/public/admin_dashboard.php?section=grade_system" class="btn btn-secondary btn-lg ms-2">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                  </a>
                <?php endif; ?>
              </form>
            </div>
            
            <!-- Grade Filter -->
            <?php
            // Ensure sections table exists
            $conn->query("CREATE TABLE IF NOT EXISTS sections (
              id INT AUTO_INCREMENT PRIMARY KEY,
              year VARCHAR(10) NOT NULL,
              name VARCHAR(100) NOT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY uniq_year_name (year, name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            $availableYears = [];
            $yearOptions = $conn->query("SELECT DISTINCT year FROM student_grades ORDER BY CAST(year AS UNSIGNED)");
            if ($yearOptions) {
              while ($row = $yearOptions->fetch_assoc()) {
                $availableYears[] = (string)$row['year'];
                }
              }
            $availableSections = [];
            // Get all sections from the sections table, not just from user_assignments
            $sectionOptions = $conn->query("SELECT DISTINCT name as section FROM sections WHERE name IS NOT NULL AND name <> '' ORDER BY name");
            if ($sectionOptions) {
              while ($row = $sectionOptions->fetch_assoc()) {
                $availableSections[] = $row['section'];
              }
            }
            $selectedYearFilter = isset($_GET['grade_year']) ? trim($_GET['grade_year']) : '';
            $selectedSectionFilter = isset($_GET['grade_section']) ? trim($_GET['grade_section']) : '';
            ?>
            
            <?php if (!empty($availableYears) || !empty($availableSections)): ?>
            <div class="info-card mt-3 grade-filter-card">
              <div class="grade-filter-inner">
                <div class="grade-filter-head">
                  <div class="grade-filter-title">
                    <span class="grade-filter-icon"><i class="bi bi-funnel-fill"></i></span>
                    <div>
                      <h3>Filter by Level & Section</h3>
                      <p>Focus the overview by academic year or section.</p>
              </div>
                  </div>
                  <?php if ($selectedYearFilter !== '' || $selectedSectionFilter !== ''): ?>
                    <a href="/TCC/public/admin_dashboard.php?section=grade_system" class="grade-filter-reset">
                      <i class="bi bi-arrow-counterclockwise"></i> Reset view
                  </a>
                  <?php endif; ?>
                </div>
                <div class="grade-filter-actions">
                  <?php if (!empty($availableYears)): ?>
                  <div class="grade-filter-group">
                    <span class="grade-filter-label">Year Level</span>
                    <?php 
                    $filterBase = $_GET;
                    $filterBase['section'] = 'grade_system';
                    $yearBase = $filterBase;
                    unset($yearBase['grade_year']);
                    $yearAllUrl = '/TCC/public/admin_dashboard.php?' . htmlspecialchars(http_build_query($yearBase));
                    ?>
                    <a href="<?php echo $yearAllUrl; ?>" class="grade-chip <?php echo ($selectedYearFilter === '') ? 'active' : ''; ?>">
                      <i class="bi bi-layers"></i>
                      <span>All Years</span>
                    </a>
                    <?php foreach ($availableYears as $yearValue): 
                      $yearParams = $yearBase;
                      $yearParams['grade_year'] = $yearValue;
                      $yearUrl = '/TCC/public/admin_dashboard.php?' . htmlspecialchars(http_build_query($yearParams));
                      $yearLabel = $yearValue === '1' ? '1st Year' : ($yearValue === '2' ? '2nd Year' : ($yearValue === '3' ? '3rd Year' : ($yearValue === '4' ? '4th Year' : $yearValue . ' Year')));
                      ?>
                      <a href="<?php echo $yearUrl; ?>" class="grade-chip <?php echo ($selectedYearFilter === $yearValue) ? 'active' : ''; ?>">
                        <i class="bi bi-calendar-week"></i>
                        <span><?php echo htmlspecialchars($yearLabel); ?></span>
                    </a>
                  <?php endforeach; ?>
                </div>
                  <?php endif; ?>
                  
                  <?php if (!empty($availableSections)): ?>
                  <div class="grade-filter-group">
                    <span class="grade-filter-label">Section</span>
                    <?php
                    $sectionBase = $filterBase;
                    unset($sectionBase['grade_section']);
                    $sectionAllUrl = '/TCC/public/admin_dashboard.php?' . htmlspecialchars(http_build_query($sectionBase));
                    ?>
                    <a href="<?php echo $sectionAllUrl; ?>" class="grade-chip <?php echo ($selectedSectionFilter === '') ? 'active' : ''; ?>">
                      <i class="bi bi-grid-1x2"></i>
                      <span>All Sections</span>
                    </a>
                    <?php foreach ($availableSections as $sectionValue): 
                      $sectionParams = $sectionBase;
                      $sectionParams['grade_section'] = $sectionValue;
                      $sectionUrl = '/TCC/public/admin_dashboard.php?' . htmlspecialchars(http_build_query($sectionParams));
                      ?>
                      <a href="<?php echo $sectionUrl; ?>" class="grade-chip <?php echo ($selectedSectionFilter === $sectionValue) ? 'active' : ''; ?>">
                        <i class="bi bi-collection"></i>
                        <span><?php echo htmlspecialchars($sectionValue); ?></span>
                      </a>
                    <?php endforeach; ?>
                  </div>
                  <?php endif; ?>
                </div>
                <?php if ($selectedYearFilter !== '' || $selectedSectionFilter !== ''): ?>
                  <div class="grade-filter-note">
                    <i class="bi bi-info-circle"></i>
                    Showing grade records
                    <?php if ($selectedYearFilter !== ''): ?>
                      for <strong><?php echo htmlspecialchars($selectedYearFilter === '1' ? '1st Year' : ($selectedYearFilter === '2' ? '2nd Year' : ($selectedYearFilter === '3' ? '3rd Year' : ($selectedYearFilter === '4' ? '4th Year' : $selectedYearFilter . ' Year')))); ?></strong>
                    <?php endif; ?>
                    <?php if ($selectedSectionFilter !== ''): ?>
                      in section <strong><?php echo htmlspecialchars($selectedSectionFilter); ?></strong>
                    <?php endif; ?>
                    .
                  </div>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>
 
            <!-- Display Grades by Year -->
            <div class="grade-system-wrapper">
            <?php
            $years = ['1', '2', '3', '4'];
            foreach ($years as $yearNum):
              if ($selectedYearFilter !== '' && $selectedYearFilter !== $yearNum) {
                continue;
              }
              
              if ($selectedSectionFilter !== '') {
                $gradesQuery = $conn->prepare("SELECT sg.*, u.full_name, u.image_path, ua.section FROM student_grades sg LEFT JOIN users u ON sg.user_id = u.id LEFT JOIN user_assignments ua ON (ua.user_id = sg.user_id OR (ua.user_id IS NULL AND ua.username = sg.username)) WHERE sg.year = ? AND ua.section = ? ORDER BY sg.user_id, sg.username, sg.semester, sg.subject");
                $gradesQuery->bind_param('ss', $yearNum, $selectedSectionFilter);
              } else {
                $gradesQuery = $conn->prepare("SELECT sg.*, u.full_name, u.image_path, ua.section FROM student_grades sg LEFT JOIN users u ON sg.user_id = u.id LEFT JOIN user_assignments ua ON (ua.user_id = sg.user_id OR (ua.user_id IS NULL AND ua.username = sg.username)) WHERE sg.year = ? ORDER BY sg.user_id, sg.username, sg.semester, sg.subject");
                $gradesQuery->bind_param('s', $yearNum);
              }
              $gradesQuery->execute();
              $gradesResult = $gradesQuery->get_result();
              $yearGrades = [];
              while ($row = $gradesResult->fetch_assoc()) {
                $yearGrades[] = $row;
              }
              $gradesQuery->close();

              if (empty($yearGrades)) {
                continue;
              }

              $studentGroups = [];
              foreach ($yearGrades as $grade) {
                $studentId = !empty($grade['user_id']) ? (int)$grade['user_id'] : null;
                $studentIdentifier = $studentId !== null ? 'id_' . $studentId : 'name_' . strtolower(trim($grade['username'] ?? $grade['full_name'] ?? uniqid()));
                $displayName = $grade['full_name'] ?? $grade['username'] ?? 'Unnamed Student';
                $imagePath = $grade['image_path'] ?? '/TCC/public/images/sample.jpg';
                if (!isset($studentGroups[$studentIdentifier])) {
                  $studentGroups[$studentIdentifier] = [
                    'user_id' => $studentId,
                    'display' => $displayName,
                    'image_path' => $imagePath,
                    'semesters' => [
                      'First Semester' => [],
                      'Second Semester' => []
                    ]
                  ];
                }
                $semesterKey = ($grade['semester'] === 'Second Semester') ? 'Second Semester' : 'First Semester';
                $studentGroups[$studentIdentifier]['semesters'][$semesterKey][] = $grade;
              }

              if (empty($studentGroups)) {
                continue;
              }
            ?>
            <div class="info-card grade-year-card">
              <div class="card-header-modern">
                <i class="bi bi-calendar-year"></i>
                <h3><?php echo $yearNum; ?><?php echo $yearNum == '1' ? 'st' : ($yearNum == '2' ? 'nd' : ($yearNum == '3' ? 'rd' : 'th')); ?> Year</h3>
              </div>
              <div class="grade-year-body">
                <div class="grade-student-list">
                  <?php foreach ($studentGroups as $groupKey => $group): ?>
                    <?php
                      $displayName = $group['display'];
                      $imagePath = $group['image_path'] ?? '/TCC/public/images/sample.jpg';
                      $initial = strtoupper(substr(trim($displayName), 0, 1));
                      $subjectCount = 0;
                      $scoredSubjects = 0;
                      $totalScores = 0;
                      $semesterSummaries = [];
                      foreach (['First Semester', 'Second Semester'] as $semName) {
                        if (!empty($group['semesters'][$semName])) {
                          foreach ($group['semesters'][$semName] as $grade) {
                            $subjectCount++;
                            $gradeParts = [];
                            foreach (['prelim_grade','midterm_grade','finals_grade'] as $field) {
                              if (isset($grade[$field]) && $grade[$field] !== '' && $grade[$field] !== null && is_numeric($grade[$field])) {
                                $gradeParts[] = floatval($grade[$field]);
                      }
                            }
                            if (!empty($gradeParts)) {
                              $scoredSubjects++;
                              $totalScores += array_sum($gradeParts) / count($gradeParts);
                            }
                          }

                          $semSubjectCount = count($group['semesters'][$semName]);
                          $semScoreTotal = 0;
                          $semScoreCount = 0;
                          foreach ($group['semesters'][$semName] as $grade) {
                            $gradeParts = [];
                            foreach (['prelim_grade','midterm_grade','finals_grade'] as $field) {
                              if (isset($grade[$field]) && $grade[$field] !== '' && $grade[$field] !== null && is_numeric($grade[$field])) {
                                $gradeParts[] = floatval($grade[$field]);
                              }
                            }
                            if (!empty($gradeParts)) {
                              $semScoreCount++;
                              $semScoreTotal += array_sum($gradeParts) / count($gradeParts);
                            }
                          }
                          $semAverage = $semScoreCount > 0 ? round($semScoreTotal / $semScoreCount, 1) : null;
                          $semesterSummaries[] = [
                            'label' => $semName === 'Second Semester' ? '2nd Sem' : '1st Sem',
                            'count' => $semSubjectCount,
                            'average' => $semAverage
                          ];
                        }
                      }
                      $summaryText = $subjectCount > 0 ? $subjectCount . ' ' . ($subjectCount === 1 ? 'subject' : 'subjects') . ' recorded' : 'No grades yet';
                      $averageScore = $scoredSubjects > 0 ? round($totalScores / $scoredSubjects, 1) : null;
                      $yearLabel = $yearNum == '1' ? '1st Year' : ($yearNum == '2' ? '2nd Year' : ($yearNum == '3' ? '3rd Year' : '4th Year'));
                      $modalSemesters = [];
                      foreach (['First Semester', 'Second Semester'] as $semNameModal) {
                        if (!empty($group['semesters'][$semNameModal])) {
                          $subjectsModal = [];
                          foreach ($group['semesters'][$semNameModal] as $gradeModal) {
                            $subjectsModal[] = [
                              'id' => (int)$gradeModal['id'],
                              'subject' => $gradeModal['subject'],
                              'instructor' => $gradeModal['instructor'] ?? '',
                              'prelim' => $gradeModal['prelim_grade'],
                              'midterm' => $gradeModal['midterm_grade'],
                              'finals' => $gradeModal['finals_grade']
                            ];
                          }
                          $modalSemesters[] = [
                            'name' => $semNameModal,
                            'subjects' => $subjectsModal
                          ];
                        }
                      }
                      // Collect all grade IDs for delete functionality
                      $allGradeIds = [];
                      foreach (['First Semester', 'Second Semester'] as $semNameForIds) {
                        if (!empty($group['semesters'][$semNameForIds])) {
                          foreach ($group['semesters'][$semNameForIds] as $gradeForIds) {
                            if (isset($gradeForIds['id'])) {
                              $allGradeIds[] = (int)$gradeForIds['id'];
                            }
                          }
                        }
                      }
                      
                      $modalPayload = [
                        'student' => $displayName,
                        'yearLabel' => $yearLabel,
                        'subjectCount' => $subjectCount,
                        'averageScore' => $averageScore,
                        'semesters' => $modalSemesters
                      ];
                      if ($group['user_id'] !== null) {
                        $modalPayload['studentId'] = (int)$group['user_id'];
                      }
                      $modalJson = htmlspecialchars(json_encode($modalPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="student-grade-card">
                      <div class="student-grade-main">
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($displayName); ?>" class="student-profile-picture" onerror="this.src='/TCC/public/images/sample.jpg'; this.onerror=null;">
                        <div>
                          <span class="student-grade-name"><?php echo htmlspecialchars($displayName); ?></span>
                          <span class="student-grade-summary"><?php echo htmlspecialchars($summaryText); ?></span>
                                    </div>
                                      </div>
                      <div class="student-grade-meta">
                        <?php foreach ($semesterSummaries as $semInfo): ?>
                          <span class="meta-pill<?php echo ($semInfo['average'] !== null) ? ' meta-pill--has-avg' : ''; ?>">
                            <?php echo htmlspecialchars($semInfo['label']); ?>
                            <?php if ($semInfo['average'] !== null): ?>
                              <small><?php echo htmlspecialchars($semInfo['average']); ?> avg</small>
                                      <?php endif; ?>
                          </span>
                        <?php endforeach; ?>
                        <?php if ($averageScore !== null): ?>
                          <span class="meta-pill meta-pill-accent"><?php echo htmlspecialchars($averageScore); ?><small>avg</small></span>
                        <?php endif; ?>
                        <span class="meta-pill view-pill" data-bs-toggle="modal" data-bs-target="#gradeStudentModal" data-grade-info="<?php echo $modalJson; ?>">
                          <i class="bi bi-card-text"></i> View
                        </span>
                        <?php if (!empty($allGradeIds)): ?>
                        <form action="/TCC/BackEnd/admin/manage_grades.php" method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete all grades for <?php echo htmlspecialchars($displayName); ?>? This action cannot be undone.');" class="delete-student-grades-form">
                          <?php foreach ($allGradeIds as $gradeId): ?>
                            <input type="hidden" name="grade_ids[]" value="<?php echo $gradeId; ?>" />
                          <?php endforeach; ?>
                          <input type="hidden" name="action" value="delete_all" />
                          <input type="hidden" name="student_name" value="<?php echo htmlspecialchars($displayName); ?>" />
                          <button type="submit" class="meta-pill delete-pill" title="Delete all grades">
                                          <i class="bi bi-trash"></i> Delete
                                        </button>
                                      </form>
                        <?php endif; ?>
                                    </div>
                                  </div>
                                <?php endforeach; ?>
                              </div>
                            </div>
                                      </div>
                        <?php endforeach; ?>
                      </div>

                    </div>

            <div class="modal fade" id="gradeStudentModal" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content grade-modal-content">
                  <div class="modal-header grade-modal-header">
                    <div>
                      <h5 class="modal-title grade-modal-title">Student Grades</h5>
                      <p class="grade-modal-subtitle mb-0"></p>
                </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
                  <div class="modal-body grade-modal-body">
                    <div class="grade-modal-summary">
                      <div class="grade-modal-chip grade-modal-chip--subjects">
                        <i class="bi bi-journal-text"></i>
                        <span class="grade-modal-subject-count">0 subjects</span>
            </div>
                      <div class="grade-modal-chip grade-modal-chip--average d-none">
                        <i class="bi bi-graph-up"></i>
                        <span class="grade-modal-average-score">Average: 0</span>
                            </div>
                      </div>
                    <div class="grade-modal-sections"></div>
                    </div>
                  <div class="modal-footer grade-modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                      <i class="bi bi-x-circle me-1"></i>Close
                    </button>
                </div>
              </div>
            </div>
            </div>
              </div>
            </div>

            <?php // close section switch: if ($section === 'announcements') / elseif / elseif ... ?>
            <?php endif; ?>

      </main>
    </div>
    
    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer"></div>
    
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
      // Function to update section assignment form inputs
      function updateSectionForm(form, key) {
        // Find form elements by form attribute
        var buildingSelect = document.querySelector('select[form="assignForm_' + key + '"]');
        var floorInput = document.querySelector('input[name="floor"][form="assignForm_' + key + '"]');
        var roomInput = document.querySelector('input[name="room"][form="assignForm_' + key + '"]');
        
        if (!buildingSelect || !floorInput || !roomInput) {
          alert('Error: Could not find form fields. Please refresh the page and try again.');
          console.error('Missing form elements:', {buildingSelect, floorInput, roomInput, key});
          return false;
        }
        
        if (!buildingSelect.value || !floorInput.value || !roomInput.value.trim()) {
          alert('Please fill in all fields (Building, Floor, and Room)');
          return false;
        }
        
        // Values are already in the form inputs, so form will submit them directly
        return true;
      }
      
      // Save scroll position before form submission
      function saveScrollPosition() {
        sessionStorage.setItem('scrollPosition', window.pageYOffset || document.documentElement.scrollTop);
      }
      
      // Restore scroll position after page load
      function restoreScrollPosition() {
        const savedPosition = sessionStorage.getItem('scrollPosition');
        if (savedPosition !== null) {
          // Use multiple attempts to ensure DOM is ready and layout is complete
          const restore = () => {
            window.scrollTo(0, parseInt(savedPosition, 10));
            sessionStorage.removeItem('scrollPosition');
          };
          
          // Try immediately
          requestAnimationFrame(() => {
            restore();
            // Also try after a short delay to handle dynamic content
            setTimeout(restore, 100);
          });
        }
      }
      
      // Toast Notification System
      function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = 'toast-notification' + (type === 'error' ? ' error' : '');
        
        const icon = type === 'error' ? 'bi-exclamation-triangle' : 'bi-check-circle';
        
        toast.innerHTML = `
          <i class="bi ${icon}"></i>
          <div class="toast-content">${message}</div>
          <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="bi bi-x"></i>
          </button>
        `;
        
        container.appendChild(toast);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
          toast.style.animation = 'fadeOut 0.3s ease-in forwards';
          setTimeout(() => {
            if (toast.parentElement) {
              toast.remove();
            }
          }, 300);
        }, 3000);
      }
      
      document.addEventListener('DOMContentLoaded', ()=>{
        var t=document.querySelectorAll('[data-bs-toggle="tooltip"]');Array.from(t).forEach(el=>new bootstrap.Tooltip(el));
        
        // Restore scroll position if it was saved (form was submitted)
        restoreScrollPosition();
        
        // Clean up any lingering modal backdrops on page load
        var lingeringBackdrops = document.querySelectorAll('.modal-backdrop:not(.show)');
        lingeringBackdrops.forEach(function(backdrop) {
          backdrop.remove();
        });
        
        // Ensure grade system is clickable
        var gradeSystemWrapper = document.querySelector('.grade-system-wrapper');
        if (gradeSystemWrapper) {
          gradeSystemWrapper.style.pointerEvents = 'auto';
          gradeSystemWrapper.style.zIndex = '1';
        }
        
        // Ensure all student grade cards are properly styled
        var studentCards = document.querySelectorAll('.student-grade-card');
        studentCards.forEach(function(card) {
          card.style.pointerEvents = 'auto';
          card.style.cursor = 'default';
          card.style.zIndex = '1';
        });
        
        // Show toast notifications if message exists
        <?php if (!empty($toastMessage)): ?>
        showToast('<?php echo addslashes($toastMessage); ?>', '<?php echo $toastType; ?>');
        <?php endif; ?>
        
        // Attach scroll position saving to all forms that redirect
        const forms = document.querySelectorAll('form[action*="manage_section_assignments"], form[action*="manage_buildings"], form[action*="manage_users"], form[action*="delete_announcement"], form[action*="manage_grades"], form[action*="manage_sections"], form[action*="manage_schedules"]');
        forms.forEach(form => {
          form.addEventListener('submit', function() {
            saveScrollPosition();
          });
        });

        var gradeModal = document.getElementById('gradeStudentModal');
        if (gradeModal) {
          var modalInstance = bootstrap.Modal.getOrCreateInstance(gradeModal);
          var modalTitleEl = gradeModal.querySelector('.grade-modal-title');
          var modalSubtitleEl = gradeModal.querySelector('.grade-modal-subtitle');
          var subjectCountEl = gradeModal.querySelector('.grade-modal-subject-count');
          var averageChipEl = gradeModal.querySelector('.grade-modal-chip--average');
          var averageScoreEl = gradeModal.querySelector('.grade-modal-average-score');
          var sectionsEl = gradeModal.querySelector('.grade-modal-sections');
          
          // Force remove blur when modal opens
          gradeModal.addEventListener('show.bs.modal', function() {
            var backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
              backdrop.style.backdropFilter = 'none';
              backdrop.style.webkitBackdropFilter = 'none';
              backdrop.style.filter = 'none';
            }
            document.body.style.backdropFilter = 'none';
            document.body.style.webkitBackdropFilter = 'none';
            document.body.style.filter = 'none';
            // Remove blur from all elements
            var allElements = document.querySelectorAll('*');
            allElements.forEach(function(el) {
              if (el !== gradeModal && !gradeModal.contains(el)) {
                el.style.backdropFilter = 'none';
                el.style.webkitBackdropFilter = 'none';
                el.style.filter = 'none';
              }
            });
          });
          
          // Clean up when modal closes
          gradeModal.addEventListener('hidden.bs.modal', function() {
            var backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
              backdrop.style.backdropFilter = '';
              backdrop.style.webkitBackdropFilter = '';
              backdrop.style.filter = '';
              // Force remove backdrop if it's still there
              if (!backdrop.classList.contains('show')) {
                backdrop.remove();
              }
            }
            // Remove modal-open class from body if no other modals are open
            var openModals = document.querySelectorAll('.modal.show');
            if (openModals.length === 0) {
              document.body.classList.remove('modal-open');
              document.body.style.overflow = '';
              document.body.style.paddingRight = '';
            }
          });

          // Handle view-pill clicks to open modal
          var viewPills = document.querySelectorAll('.view-pill[data-grade-info]');
          viewPills.forEach(function(pill) {
            pill.addEventListener('click', function(e) {
              e.stopPropagation();
              var gradeInfo = pill.getAttribute('data-grade-info');
              if (gradeInfo) {
                try {
                  var data = JSON.parse(gradeInfo);
                  // Set modal data
                  gradeModal.setAttribute('data-grade-info', gradeInfo);
                  // Open modal
                  modalInstance.show();
                } catch (err) {
                  console.error('Error parsing grade info:', err);
                }
              }
            });
          });

          // Force close buttons to work
          var closeButtons = gradeModal.querySelectorAll('[data-bs-dismiss="modal"], .btn-close, .grade-modal-footer .btn');
          closeButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
              e.stopPropagation();
              modalInstance.hide();
            });
          });

          function formatGradeValue(val) {
            if (val === null || val === undefined || val === '') {
              return '—';
            }
            var num = Number(val);
            if (!Number.isNaN(num)) {
              return num.toFixed(2);
            }
            return String(val);
          }

          function createHiddenInput(name, value) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            return input;
          }

          function createGradeRow(record) {
            var row = document.createElement('tr');

            var subjectCell = document.createElement('td');
            subjectCell.className = 'grade-modal-table__subject';
            var subjectTitle = document.createElement('div');
            subjectTitle.className = 'grade-modal-table__subject-name';
            subjectTitle.textContent = record.subject || 'Untitled Subject';
            subjectCell.appendChild(subjectTitle);
            if (record.instructor) {
              var instructorTag = document.createElement('span');
              instructorTag.className = 'grade-modal-table__instructor';
              instructorTag.innerHTML = '<i class="bi bi-person-badge-fill"></i> ' + record.instructor;
              subjectCell.appendChild(instructorTag);
            }
            row.appendChild(subjectCell);

            var prelimCell = document.createElement('td');
            prelimCell.textContent = formatGradeValue(record.prelim);
            row.appendChild(prelimCell);

            var midtermCell = document.createElement('td');
            midtermCell.textContent = formatGradeValue(record.midterm);
            row.appendChild(midtermCell);

            var finalsCell = document.createElement('td');
            finalsCell.textContent = formatGradeValue(record.finals);
            row.appendChild(finalsCell);

            var actionsCell = document.createElement('td');
            actionsCell.className = 'grade-modal-table__actions';

            if (record.id && parseInt(record.id, 10) > 0) {
              var editLink = document.createElement('a');
              editLink.className = 'Btn Btn-edit';
              editLink.href = '/TCC/public/admin_dashboard.php?section=grade_system&edit_grade_id=' + encodeURIComponent(record.id);
              editLink.innerHTML = '<div class="svgWrapper"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 42 42" class="svgIcon"><path stroke-width="5" stroke="#fff" d="M21 5L7 19L5 37L23 35L37 21L21 5Z"></path><path stroke-width="3" stroke="#fff" d="M21 5L37 21"></path><path stroke-width="3" stroke="#fff" d="M15 19L23 27"></path></svg><div class="text">Edit</div></div>';
              actionsCell.appendChild(editLink);

              var deleteForm = document.createElement('form');
              deleteForm.method = 'post';
              deleteForm.action = '/TCC/BackEnd/admin/manage_grades.php';
              deleteForm.style.display = 'inline';
              deleteForm.style.marginLeft = '8px';
              deleteForm.appendChild(createHiddenInput('action', 'delete'));
              deleteForm.appendChild(createHiddenInput('id', record.id));
              var deleteBtn = document.createElement('button');
              deleteBtn.type = 'submit';
              deleteBtn.className = 'Btn Btn-delete';
              deleteBtn.innerHTML = '<div class="svgWrapper"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 42 42" class="svgIcon"><path stroke-width="5" stroke="#fff" d="M9.14073 2.5H32.8593C33.3608 2.5 33.8291 2.75065 34.1073 3.16795L39.0801 10.6271C39.3539 11.0378 39.5 11.5203 39.5 12.0139V21V37C39.5 38.3807 38.3807 39.5 37 39.5H5C3.61929 39.5 2.5 38.3807 2.5 37V21V12.0139C2.5 11.5203 2.6461 11.0378 2.91987 10.6271L7.89266 3.16795C8.17086 2.75065 8.63921 2.5 9.14073 2.5Z"></path><path stroke-width="5" stroke="#fff" d="M14 18L28 18M18 14V30M24 14V30"></path></svg><div class="text">Delete</div></div>';
              deleteForm.appendChild(deleteBtn);
              deleteForm.addEventListener('submit', function(ev){
                if (!confirm('Delete this grade record?')) {
                  ev.preventDefault();
                }
              });
              actionsCell.appendChild(deleteForm);
            } else {
              actionsCell.innerHTML = '<span class="text-muted">—</span>';
            }

            row.appendChild(actionsCell);
            return row;
          }

          gradeModal.addEventListener('show.bs.modal', function(event){
            var trigger = event.relatedTarget;
            var payload = null;
            
            // First try to get payload from trigger (Bootstrap data-bs-toggle)
            if (trigger) {
              payload = trigger.getAttribute('data-grade-info');
            }
            
            // If no trigger, check modal's data attribute (programmatic open)
            if (!payload) {
              payload = gradeModal.getAttribute('data-grade-info');
            }
            
            if (!payload) return;
            var data = null;
            try {
              data = JSON.parse(payload);
            } catch (err) {
              console.error('Unable to parse grade info payload', err);
              return;
            }
            if (!data) return;

            if (modalTitleEl) {
              modalTitleEl.textContent = data.student || 'Student Grades';
            }
            if (modalSubtitleEl) {
              if (data.yearLabel) {
                modalSubtitleEl.textContent = data.yearLabel;
                modalSubtitleEl.classList.remove('d-none');
              } else {
                modalSubtitleEl.textContent = '';
                modalSubtitleEl.classList.add('d-none');
              }
            }

            if (subjectCountEl) {
              var subjectCount = parseInt(data.subjectCount || 0, 10);
              subjectCountEl.textContent = subjectCount + ' ' + (subjectCount === 1 ? 'subject' : 'subjects');
            }

            if (averageChipEl && averageScoreEl) {
              if (data.averageScore !== null && data.averageScore !== undefined && data.averageScore !== '') {
                averageScoreEl.textContent = 'Average: ' + data.averageScore;
                averageChipEl.classList.remove('d-none');
              } else {
                averageChipEl.classList.add('d-none');
              }
            }

            if (sectionsEl) {
              sectionsEl.innerHTML = '';
              var semesters = Array.isArray(data.semesters) ? data.semesters : [];
              if (semesters.length === 0) {
                var empty = document.createElement('div');
                empty.className = 'grade-modal-section-empty';
                empty.textContent = 'No grade records available for this student yet.';
                sectionsEl.appendChild(empty);
              } else {
                semesters.forEach(function(semester){
                  var section = document.createElement('section');
                  section.className = 'grade-modal-section';
                  var heading = document.createElement('h4');
                  heading.className = 'grade-semester-title';
                  heading.textContent = semester.name || 'Semester';
                  section.appendChild(heading);
                  var subjects = Array.isArray(semester.subjects) ? semester.subjects : [];
                  if (subjects.length === 0) {
                    var none = document.createElement('div');
                    none.className = 'grade-modal-section-empty';
                    none.textContent = 'No subjects recorded for this semester.';
                    section.appendChild(none);
                  } else {
                    var tableWrapper = document.createElement('div');
                    tableWrapper.className = 'grade-modal-table-wrapper';
                    var table = document.createElement('table');
                    table.className = 'grade-modal-table';

                    var thead = document.createElement('thead');
                    thead.innerHTML = '<tr><th scope="col">Subject</th><th scope="col">Prelim</th><th scope="col">Midterm</th><th scope="col">Finals</th><th scope="col" class="text-end">Actions</th></tr>';
                    table.appendChild(thead);

                    var tbody = document.createElement('tbody');
                    subjects.forEach(function(record){
                      tbody.appendChild(createGradeRow(record));
                    });
                    table.appendChild(tbody);

                    tableWrapper.appendChild(table);
                    section.appendChild(tableWrapper);
                  }
                  sectionsEl.appendChild(section);
                });
              }
            }
          });
        }

        var editModal = document.getElementById('editUserModal');
        if (editModal) {
          editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var fullname = button.getAttribute('data-fullname') || '';
            var payment = button.getAttribute('data-payment') || 'paid';
            var sanctions = button.getAttribute('data-sanctions') || '';
            var department = button.getAttribute('data-department') || '';
            var owing = button.getAttribute('data-owing') || '';

            // display full name and set hidden input
            var display = document.getElementById('modalFullNameDisplay');
            var hidden = document.getElementById('modalFullName');
            if (display) display.textContent = fullname;
            if (hidden) hidden.value = fullname;

            var paymentEl = document.getElementById('modalPayment');
            var sanctionsEl = document.getElementById('modalSanctions');
            var deptEl = document.getElementById('modalDepartment');
            var owingEl = document.getElementById('modalOwingAmount');
            var owingRow = document.getElementById('owingRow');
            if (paymentEl) paymentEl.value = payment;
            if (sanctionsEl) sanctionsEl.value = sanctions;
            if (deptEl) deptEl.value = department;
            if (owingEl) owingEl.value = owing;

            if (owingRow) {
              owingRow.style.display = (payment === 'owing') ? '' : 'none';
            }
          });

          // toggle when payment select changes inside modal
          var paymentSelect = document.getElementById('modalPayment');
          if (paymentSelect) {
            paymentSelect.addEventListener('change', function(e){
              var owingRow = document.getElementById('owingRow');
              var owingEl = document.getElementById('modalOwingAmount');
              if (e.target.value === 'owing') {
                if (owingRow) owingRow.style.display = '';
              } else {
                if (owingRow) owingRow.style.display = 'none';
                if (owingEl) owingEl.value = '';
              }
            });
          }
        }
        // Autocomplete hookup for user search (with keyboard navigation)
        (function(){
          var input = document.getElementById('userSearchInput');
          var list = document.getElementById('userSearchList');
          var hidden = document.getElementById('existingUserIdHidden');
          var fullName = document.getElementById('assignFullName');
          var debounceTimer = null;
          var selectedIndex = -1;

          function highlightAt(idx) {
            var items = list.querySelectorAll('.admin-search-item');
            items.forEach(function(it, i){
              var sel = (i===idx);
              it.classList.toggle('active', sel);
              it.setAttribute('aria-selected', sel ? 'true' : 'false');
            });
            selectedIndex = (idx >= 0 && idx < items.length) ? idx : -1;
            // ensure visible
            if (selectedIndex !== -1) {
              var el = items[selectedIndex];
              if (el && el.scrollIntoView) el.scrollIntoView({block:'nearest'});
              // associate active descendant with input for screen readers
              if (input) input.setAttribute('aria-activedescendant', el.id || '');
            }
          }

          function clearList(){ 
            list.innerHTML = ''; 
            list.classList.remove('show');
            list.style.display = 'none';
          }

          function chooseItem(id, name, username){
            if (hidden) hidden.value = id ? id : '';
            if (fullName) fullName.value = name || username || '';
            if (input) input.value = (name || username || '');
            clearList();
            if (input) { input.setAttribute('aria-expanded','false'); input.removeAttribute('aria-activedescendant'); }
            selectedIndex = -1;
          }

          if (!input) return;
          input.addEventListener('input', function(e){
            var q = input.value.trim();
            if (debounceTimer) clearTimeout(debounceTimer);
            // clear existing selection when typing
            if (hidden) hidden.value = '';
            debounceTimer = setTimeout(function(){
              if (q.length < 2) { clearList(); return; }
              fetch('/TCC/BackEnd/admin/user_search.php?q=' + encodeURIComponent(q) + '&limit=12')
                .then(function(res){ return res.json(); })
                .then(function(data){
                  list.innerHTML = '';
                  if (!data || !data.results || data.results.length === 0) { clearList(); return; }
                  var _sugCounter = 0;
                  data.results.forEach(function(r){
                    var li = document.createElement('li');
                    li.className = 'admin-search-item';
                    li.style.cursor = 'pointer';
                    li.innerHTML = '<strong>' + (r.full_name || r.username) + '</strong> <span class="text-muted">(' + r.username + ')</span>';
                    li.dataset.id = r.id;
                    li.dataset.full = r.full_name || '';
                    li.dataset.user = r.username || '';
                    // accessibility attributes
                    li.id = 'useropt-' + (r.id || 'x') + '-' + (_sugCounter++);
                    li.setAttribute('role','option');
                    li.setAttribute('aria-selected','false');
                    li.addEventListener('click', function(){ chooseItem(li.dataset.id, li.dataset.full, li.dataset.user); });
                    li.addEventListener('mouseenter', function(){ highlightAt(_sugCounter - 1); });
                    list.appendChild(li);
                  });
                  // mark list visible for screen readers
                  list.setAttribute('aria-hidden','false');
                  input.setAttribute('aria-expanded','true');
                  // reset any keyboard selection
                  selectedIndex = -1;
                  list.style.display = 'block';
                  list.classList.add('show');
                  list.setAttribute('role','listbox');
                  list.setAttribute('aria-hidden','false');
                  input.setAttribute('aria-expanded','true');
                }).catch(function(){ clearList(); });
            }, 220);
          });

          // keyboard handling: up/down to move, enter to pick, esc to clear
          input.addEventListener('keydown', function(ev){
            var items = list.querySelectorAll('.admin-search-item');
            if (ev.key === 'ArrowDown') {
              ev.preventDefault();
              if (items.length === 0) return;
              var ni = selectedIndex + 1;
              if (ni >= items.length) ni = 0;
              highlightAt(ni);
            } else if (ev.key === 'ArrowUp') {
              ev.preventDefault();
              if (items.length === 0) return;
              var ni = selectedIndex - 1;
              if (ni < 0) ni = items.length - 1;
              highlightAt(ni);
            } else if (ev.key === 'Enter') {
              if (selectedIndex !== -1) {
                ev.preventDefault();
                var chosen = items[selectedIndex];
                if (chosen) chooseItem(chosen.dataset.id, chosen.dataset.full, chosen.dataset.user);
              }
            } else if (ev.key === 'Escape') {
              clearList();
            }
          });

          document.addEventListener('click', function(ev){ if (!input.contains(ev.target) && !list.contains(ev.target)) clearList(); });
        })();
        
        // Autocomplete hookup for teacher search (with keyboard navigation)
        (function(){
          var input = document.getElementById('teacherSearchInput');
          var list = document.getElementById('teacherSearchList');
          var hidden = document.getElementById('teacherUserIdHidden');
          var fullName = document.getElementById('teacherFullName');
          var debounceTimer = null;
          var selectedIndex = -1;

          function highlightAt(idx) {
            var items = list.querySelectorAll('.admin-search-item');
            items.forEach(function(it, i){
              var sel = (i===idx);
              it.classList.toggle('active', sel);
              it.setAttribute('aria-selected', sel ? 'true' : 'false');
            });
            selectedIndex = (idx >= 0 && idx < items.length) ? idx : -1;
            // ensure visible
            if (selectedIndex !== -1) {
              var el = items[selectedIndex];
              if (el && el.scrollIntoView) el.scrollIntoView({block:'nearest'});
              // associate active descendant with input for screen readers
              if (input) input.setAttribute('aria-activedescendant', el.id || '');
            }
          }

          function clearList(){ 
            list.innerHTML = ''; 
            list.classList.remove('show');
            list.style.display = 'none';
          }

          function chooseItem(id, name, username){
            if (hidden) hidden.value = id ? id : '';
            if (fullName) fullName.value = name || username || '';
            if (input) input.value = (name || username || '');
            clearList();
            if (input) { input.setAttribute('aria-expanded','false'); input.removeAttribute('aria-activedescendant'); }
            selectedIndex = -1;
          }

          if (!input) return;
          input.addEventListener('input', function(e){
            var q = input.value.trim();
            if (debounceTimer) clearTimeout(debounceTimer);
            // clear existing selection when typing
            if (hidden) hidden.value = '';
            debounceTimer = setTimeout(function(){
              if (q.length < 2) { clearList(); return; }
              fetch('/TCC/BackEnd/admin/user_search.php?q=' + encodeURIComponent(q) + '&limit=12&role=teacher')
                .then(function(res){ return res.json(); })
                .then(function(data){
                  list.innerHTML = '';
                  if (!data || !data.results || data.results.length === 0) { clearList(); return; }
                  var _sugCounter = 0;
                  data.results.forEach(function(r){
                    var li = document.createElement('li');
                    li.className = 'admin-search-item';
                    li.style.cursor = 'pointer';
                    li.innerHTML = '<strong>' + (r.full_name || r.username) + '</strong> <span class="text-muted">(' + r.username + ')</span>';
                    li.dataset.id = r.id;
                    li.dataset.full = r.full_name || '';
                    li.dataset.user = r.username || '';
                    // accessibility attributes
                    li.id = 'teacheropt-' + (r.id || 'x') + '-' + (_sugCounter++);
                    li.setAttribute('role','option');
                    li.setAttribute('aria-selected','false');
                    li.addEventListener('click', function(){ chooseItem(li.dataset.id, li.dataset.full, li.dataset.user); });
                    li.addEventListener('mouseenter', function(){ highlightAt(_sugCounter - 1); });
                    list.appendChild(li);
                  });
                  // mark list visible for screen readers
                  list.setAttribute('aria-hidden','false');
                  input.setAttribute('aria-expanded','true');
                  // reset any keyboard selection
                  selectedIndex = -1;
                  list.style.display = 'block';
                  list.classList.add('show');
                  list.setAttribute('role','listbox');
                  list.setAttribute('aria-hidden','false');
                  input.setAttribute('aria-expanded','true');
                }).catch(function(){ clearList(); });
            }, 220);
          });

          // keyboard handling: up/down to move, enter to pick, esc to clear
          input.addEventListener('keydown', function(ev){
            var items = list.querySelectorAll('.admin-search-item');
            if (ev.key === 'ArrowDown') {
              ev.preventDefault();
              if (items.length === 0) return;
              var ni = selectedIndex + 1;
              if (ni >= items.length) ni = 0;
              highlightAt(ni);
            } else if (ev.key === 'ArrowUp') {
              ev.preventDefault();
              if (items.length === 0) return;
              var ni = selectedIndex - 1;
              if (ni < 0) ni = items.length - 1;
              highlightAt(ni);
            } else if (ev.key === 'Enter') {
              if (selectedIndex !== -1) {
                ev.preventDefault();
                var chosen = items[selectedIndex];
                if (chosen) chooseItem(chosen.dataset.id, chosen.dataset.full, chosen.dataset.user);
              }
            } else if (ev.key === 'Escape') {
              clearList();
            }
          });

          document.addEventListener('click', function(ev){ if (!input.contains(ev.target) && !list.contains(ev.target)) clearList(); });
        })();
      });
    </script>
  </body>
</html>
