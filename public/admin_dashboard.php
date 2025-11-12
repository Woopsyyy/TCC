<?php
session_start();
// only for admins
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header('Location: /TCC/public/index.html');
  exit();
}

$image = $_SESSION['image_path'] ?? '/TCC/public/images/sample.jpg';
$adminName = $_SESSION['full_name'] ?? $_SESSION['username'];
$section = isset($_GET['section']) ? $_GET['section'] : 'announcements';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes" />
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="css/home.css" />
    <link rel="stylesheet" href="css/ui_tweaks.css" />
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin_dashboard.css" />
  </head>
  <body class="admin-dashboard">
    <div class="page-container">
      <aside class="sidebar">
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
        <div class="sidebar-top"><img src="<?php echo htmlspecialchars($image); ?>" class="sidebar-logo" alt="admin"/></div>
        <nav class="sidebar-nav">
          <ul>
            <li><a href="/TCC/public/admin_dashboard.php?section=announcements" class="nav-link <?php echo ($section==='announcements')?'active':''?>" data-bs-toggle="tooltip" title="Announcements"><i class="bi bi-megaphone-fill"></i><span class="nav-label">Announcements</span></a></li>
            <li><a href="/TCC/public/admin_dashboard.php?section=buildings" class="nav-link <?php echo ($section==='buildings')?'active':''?>" data-bs-toggle="tooltip" title="Buildings"><i class="bi bi-building"></i><span class="nav-label">Buildings</span></a></li>
            <li><a href="/TCC/public/admin_dashboard.php?section=projects" class="nav-link <?php echo ($section==='projects')?'active':''?>" data-bs-toggle="tooltip" title="Projects"><i class="bi bi-folder-fill"></i><span class="nav-label">Projects</span></a></li>
            <li><a href="/TCC/public/user_management.php" class="nav-link" data-bs-toggle="tooltip" title="User Management"><i class="bi bi-people-fill"></i><span class="nav-label">User Management</span></a></li>
            <li><a href="/TCC/public/admin_dashboard.php?section=grade_system" class="nav-link <?php echo ($section==='grade_system')?'active':''?>" data-bs-toggle="tooltip" title="Grade System"><i class="bi bi-journal-bookmark-fill"></i><span class="nav-label">Grade System</span></a></li>
          </ul>
        </nav>
        <div class="sidebar-bottom"><a href="/TCC/BackEnd/auth/logout.php" class="btn logout-icon" title="Logout"><i class="bi bi-box-arrow-right"></i></a></div>
      </aside>

      <main class="home-main">
          <div class="records-container">
            <div class="records-header">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h2 class="records-title">
                    <i class="bi bi-speedometer2"></i> Admin Dashboard
                  </h2>
                  <p class="records-subtitle">
                    Signed in as <strong><?php echo htmlspecialchars($adminName); ?></strong>
                  </p>
                </div>
                <a href="/TCC/public/home.php" class="btn btn-primary" style="background-color: #28a745; border-color: #28a745; color: white; font-weight: 600; padding: 0.5rem 1.5rem;">
                  <i class="bi bi-arrow-left-circle me-1"></i>Switch to User View
                </a>
              </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
              <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php 
                if ($_GET['success'] === '1'): 
                  echo 'Section assignment created successfully!';
                elseif ($_GET['success'] === 'updated'):
                  echo ($section === 'grade_system') ? 'Grade updated successfully!' : 'Section assignment updated successfully!';
                elseif ($_GET['success'] === 'deleted'):
                  echo ($section === 'grade_system') ? 'Grade deleted successfully!' : 'Section assignment deleted successfully!';
                elseif ($_GET['success'] === 'created'):
                  echo ($section === 'grade_system') ? 'Grade created successfully!' : 'Operation completed successfully!';
                else:
                  echo 'Operation completed successfully!';
                endif;
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php 
                if ($_GET['error'] === 'missing'): 
                  echo 'Please fill in all required fields.';
                else:
                  echo 'An error occurred. Please try again.';
                endif;
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php endif; ?>

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
            <div class="info-card">
              <div class="card-header-modern">
                <i class="bi bi-megaphone-fill"></i>
                <h3>Manage Announcements</h3>
              </div>
                <form class="form-small" action="/TCC/BackEnd/admin/save_announcement.php" method="post">
                  <?php if ($editRow): ?><input type="hidden" name="id" value="<?php echo (int)$editRow['id']; ?>" /><?php endif; ?>
                  <div class="mb-2"><label class="form-label">Title</label><input name="title" class="form-control" required value="<?php echo $editRow ? htmlspecialchars($editRow['title']) : ''; ?>"/></div>
                  <div class="mb-2"><label class="form-label">Content</label><textarea name="content" class="form-control" rows="3" required><?php echo $editRow ? htmlspecialchars($editRow['content']) : ''; ?></textarea></div>
                  <div class="row g-2 mb-2">
                    <div class="col"><label class="form-label">Year</label><select name="year" class="form-select"><option value="1" <?php echo ($editRow && $editRow['year']=='1')?'selected':'';?>>1</option><option value="2" <?php echo ($editRow && $editRow['year']=='2')?'selected':'';?>>2</option><option value="3" <?php echo ($editRow && $editRow['year']=='3')?'selected':'';?>>3</option><option value="4" <?php echo ($editRow && $editRow['year']=='4')?'selected':'';?>>4</option></select></div>
                    <div class="col"><label class="form-label">Department</label><select name="department" class="form-select"><option value="IT">IT</option><option value="HM">HM</option><option value="BSEED">BSEED</option><option value="BEED">BEED</option><option value="TOURISM">TOURISM</option></select></div>
                  </div>
                  <button class="btn btn-primary"><?php echo $editRow ? 'Update Announcement' : 'Save Announcement'; ?></button>
                  <?php if ($editRow): ?><a href="/TCC/public/admin_dashboard.php?section=announcements" class="btn btn-secondary ms-2">Cancel</a><?php endif; ?>
                </form>
              </div>
            </div>
            <div class="info-card mt-3">
              <div class="card-header-modern">
                <i class="bi bi-list-ul"></i>
                <h3>Existing Announcements</h3>
              </div>
                <ul class="list-group">
                  <?php if (empty($annList)): ?><li class="list-group-item text-muted">No announcements yet.</li><?php endif; ?>
                  <?php foreach ($annList as $a): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                      <div>
                        <strong><?php echo htmlspecialchars($a['title']); ?></strong> <small class="text-muted"><?php echo htmlspecialchars($a['date'] ?? ''); ?></small>
                        <div><?php echo nl2br(htmlspecialchars($a['content'])); ?></div>
                      </div>
                      <div class="btn-group">
                        <?php if (!empty($a['id'])): ?>
                        <a href="/TCC/public/admin_dashboard.php?section=announcements&edit_id=<?php echo (int)$a['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form method="post" action="/TCC/BackEnd/admin/delete_announcement.php" onsubmit="return confirm('Delete this announcement?');" style="display:inline;">
                          <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>" />
                          <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                        </form>
                        <?php endif; ?>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <!-- pagination for announcements -->
              <?php if (isset($annTotalPages) && $annTotalPages > 1): ?>
              <nav class="mt-2" aria-label="Announcements pages">
                <ul class="pagination pagination-sm">
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
            </div>
            <?php
            // end announcements
            ?>

          <?php elseif ($section === 'buildings'): ?>
            <?php
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
                    <li class="list-group-item"><strong>Building <?php echo htmlspecialchars($bname); ?></strong> — Floors: <?php echo (int)$binfo['floors']; ?>, Rooms/floor: <?php echo (int)$binfo['rooms']; ?></li>
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
              <div class="info-card buildings-card">
                <div class="card-header-modern">
                  <i class="bi bi-list-check"></i>
                  <h3>Section Building &amp; Room Assignments</h3>
                </div>
                <ul class="list-group">
                  <?php if (empty($sa)): ?><li class="list-group-item text-muted">No section assignments yet.</li><?php endif; ?>
                  <?php foreach ($sa as $info): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                      <div>
                        <strong><?php echo htmlspecialchars($info['year'] . ' - ' . $info['section']); ?></strong> 
                        &mdash; Building <?php echo htmlspecialchars($info['building']); ?>, 
                        Floor <?php echo (int)$info['floor']; ?>, 
                        Room <?php echo htmlspecialchars($info['room']); ?>
                      </div>
                      <div class="btn-group">
                        <?php if (!empty($info['id']) && $info['id'] > 0): ?>
                        <a href="/TCC/public/admin_dashboard.php?section=buildings&edit_section_id=<?php echo (int)$info['id']; ?>" class="btn btn-sm btn-outline-primary">
                          <i class="bi bi-pencil"></i> Edit
                        </a>
                        <form method="post" action="/TCC/BackEnd/admin/manage_section_assignments.php" onsubmit="return confirm('Delete this section assignment? This will remove the building/room assignment for this section.');" style="display:inline;">
                          <input type="hidden" name="action" value="delete" />
                          <input type="hidden" name="id" value="<?php echo (int)$info['id']; ?>" />
                          <input type="hidden" name="year" value="<?php echo htmlspecialchars($info['year']); ?>" />
                          <input type="hidden" name="section" value="<?php echo htmlspecialchars($info['section']); ?>" />
                          <button class="btn btn-sm btn-outline-danger" type="submit">
                            <i class="bi bi-trash"></i> Delete
                          </button>
                        </form>
                        <?php endif; ?>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
            <?php
            // Get all unique sections from user_assignments
            $availableSections = [];
            $existingAssignments = [];
            try {
              $connSections = Database::getInstance()->getConnection();
              $sectionsQuery = $connSections->query("SELECT DISTINCT year, section FROM user_assignments ORDER BY year, section");
              if ($sectionsQuery) {
                while ($row = $sectionsQuery->fetch_assoc()) {
                  $availableSections[] = $row;
                }
              }
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
                  <i class="bi bi-info-circle me-2"></i>No sections found. Please assign users to sections in User Management first.
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
                            <select name="building_<?php echo htmlspecialchars($key); ?>" class="form-select form-select-sm" form="assignForm_<?php echo htmlspecialchars($key); ?>">
                              <option value="">Select...</option>
                              <?php foreach (array_keys($buildings) as $bn): ?>
                                <option value="<?php echo htmlspecialchars($bn); ?>" <?php echo ($hasAssignment && $existing['building']===$bn)?'selected':'';?>
                                ><?php echo htmlspecialchars($bn); ?></option>
                              <?php endforeach; ?>
                            </select>
                          </td>
                          <td>
                            <input type="number" name="floor_<?php echo htmlspecialchars($key); ?>" class="form-control form-control-sm" 
                                   value="<?php echo $hasAssignment ? (int)$existing['floor'] : '1'; ?>" 
                                   min="1" form="assignForm_<?php echo htmlspecialchars($key); ?>" />
                          </td>
                          <td>
                            <input type="text" name="room_<?php echo htmlspecialchars($key); ?>" class="form-control form-control-sm" 
                                   value="<?php echo $hasAssignment ? htmlspecialchars($existing['room']) : ''; ?>" 
                                   placeholder="301" form="assignForm_<?php echo htmlspecialchars($key); ?>" />
                          </td>
                          <td>
                            <form id="assignForm_<?php echo htmlspecialchars($key); ?>" action="/TCC/BackEnd/admin/manage_section_assignments.php" method="post" style="display:inline;" onsubmit="return updateSectionForm(this, '<?php echo htmlspecialchars($key); ?>')">
                              <input type="hidden" name="action" value="<?php echo $hasAssignment ? 'update' : 'create'; ?>" />
                              <?php if ($hasAssignment): ?>
                                <input type="hidden" name="id" value="<?php echo (int)$existing['id']; ?>" />
                              <?php endif; ?>
                              <input type="hidden" name="year" value="<?php echo htmlspecialchars($sec['year']); ?>" />
                              <input type="hidden" name="section" value="<?php echo htmlspecialchars($sec['section']); ?>" />
                              <input type="hidden" name="building" value="" id="building_hidden_<?php echo htmlspecialchars($key); ?>" />
                              <input type="hidden" name="floor" value="" id="floor_hidden_<?php echo htmlspecialchars($key); ?>" />
                              <input type="hidden" name="room" value="" id="room_hidden_<?php echo htmlspecialchars($key); ?>" />
                              <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-<?php echo $hasAssignment ? 'pencil' : 'plus-circle'; ?>"></i> 
                                <?php echo $hasAssignment ? 'Update' : 'Assign'; ?>
                              </button>
                            </form>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
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
                      <select name="building" class="form-select form-select-lg">
                        <?php foreach (array_keys($buildings) as $bn): ?>
                          <option <?php echo ($editSectionRow['building']===$bn)?'selected':'';?>><?php echo htmlspecialchars($bn); ?></option>
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
                      <select name="year" class="form-select form-select-lg">
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3" selected>3rd Year</option>
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
                      <select name="building" class="form-select form-select-lg">
                        <?php foreach (array_keys($buildings) as $bn): ?>
                          <option><?php echo htmlspecialchars($bn); ?></option>
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
 
          <?php elseif ($section === 'projects'): ?>
            <div class="info-card">
              <div class="card-header-modern">
                <i class="bi bi-folder-fill"></i>
                <h3>Manage Projects</h3>
              </div>
                <form class="form-small" action="/TCC/BackEnd/admin/manage_projects.php" method="post">
                  <div class="mb-2"><label class="form-label">Project Name</label><input name="name" class="form-control" required/></div>
                  <div class="mb-2 row g-2"><div class="col"><label class="form-label">Budget</label><input name="budget" class="form-control" required/></div><div class="col"><label class="form-label">Started</label><input name="started" type="date" class="form-control" required/></div></div>
                  <div class="mb-2"><label class="form-label">Completed?</label><select name="completed" class="form-select"><option value="no">No</option><option value="yes">Yes</option></select></div>
                  <button class="btn btn-primary">Save Project</button>
                </form>
              </div>
            </div>
            <?php
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
            <div class="info-card mt-3">
              <div class="card-header-modern">
                <i class="bi bi-folder-check"></i>
                <h3>Projects</h3>
              </div>
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead><tr><th>Name</th><th>Budget</th><th>Started</th><th>Completed</th></tr></thead>
                    <tbody>
                      <?php foreach ($projectsPage as $proj): ?>
                        <tr><td><?php echo htmlspecialchars($proj['name']); ?></td><td><?php echo htmlspecialchars($proj['budget']); ?></td><td><?php echo htmlspecialchars($proj['started']); ?></td><td><?php echo ($proj['completed']==='yes')? 'Yes':'No'; ?></td></tr>
                      <?php endforeach; ?>
                      <?php if (empty($projectsPage)): ?><tr><td colspan="4" class="text-muted">No projects yet.</td></tr><?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <?php if ($projTotalPages > 1): ?>
              <nav class="mt-2" aria-label="Projects pages">
                <ul class="pagination pagination-sm">
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
            ?>
            
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
            
            <!-- Student Filter -->
            <?php
            // Get all unique students with grades
            $allStudentsWithGrades = [];
            $studentsQuery = $conn->query("SELECT DISTINCT sg.user_id, sg.username, u.full_name FROM student_grades sg LEFT JOIN users u ON sg.user_id = u.id ORDER BY u.full_name, sg.username");
            if ($studentsQuery) {
              while ($row = $studentsQuery->fetch_assoc()) {
                if (!empty($row['user_id']) || !empty($row['username'])) {
                  $allStudentsWithGrades[] = $row;
                }
              }
            }
            
            $selectedStudentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;
            $selectedStudentName = '';
            if ($selectedStudentId) {
              $nameQuery = $conn->prepare("SELECT full_name, username FROM users WHERE id = ? LIMIT 1");
              $nameQuery->bind_param('i', $selectedStudentId);
              $nameQuery->execute();
              $nameResult = $nameQuery->get_result();
              if ($nameRow = $nameResult->fetch_assoc()) {
                $selectedStudentName = $nameRow['full_name'] ?? $nameRow['username'] ?? '';
              }
              $nameQuery->close();
            }
            ?>
            
            <?php if (!empty($allStudentsWithGrades)): ?>
            <div class="info-card mt-3 grade-filter-card">
              <div class="card-header-modern">
                <i class="bi bi-funnel"></i>
                <h3>Filter by Student</h3>
              </div>
              <div class="p-3">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                  <a href="/TCC/public/admin_dashboard.php?section=grade_system" class="btn btn-sm <?php echo $selectedStudentId === null ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                    <i class="bi bi-x-circle"></i> All Students
                  </a>
                  <?php foreach ($allStudentsWithGrades as $student): ?>
                    <?php 
                    $studentId = !empty($student['user_id']) ? (int)$student['user_id'] : null;
                    $studentDisplayName = !empty($student['full_name']) ? $student['full_name'] : $student['username'];
                    $isSelected = $selectedStudentId === $studentId;
                    ?>
                    <a href="/TCC/public/admin_dashboard.php?section=grade_system&student_id=<?php echo $studentId; ?>" 
                       class="btn btn-sm <?php echo $isSelected ? 'btn-primary' : 'btn-outline-primary'; ?>">
                      <i class="bi bi-person"></i> <?php echo htmlspecialchars($studentDisplayName); ?>
                    </a>
                  <?php endforeach; ?>
                </div>
                <?php if ($selectedStudentId): ?>
                  <div class="mt-3 alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>Showing grades for: <strong><?php echo htmlspecialchars($selectedStudentName); ?></strong>
                  </div>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>
 
            <!-- Display Grades by Year -->
            <?php
            $years = ['1', '2', '3', '4'];
            foreach ($years as $yearNum):
              if ($selectedStudentId) {
                $gradesQuery = $conn->prepare("SELECT sg.*, u.full_name FROM student_grades sg LEFT JOIN users u ON sg.user_id = u.id WHERE sg.year = ? AND sg.user_id = ? ORDER BY sg.user_id, sg.username, sg.semester, sg.subject");
                $gradesQuery->bind_param('si', $yearNum, $selectedStudentId);
              } else {
                $gradesQuery = $conn->prepare("SELECT sg.*, u.full_name FROM student_grades sg LEFT JOIN users u ON sg.user_id = u.id WHERE sg.year = ? ORDER BY sg.user_id, sg.username, sg.semester, sg.subject");
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
                if (!isset($studentGroups[$studentIdentifier])) {
                  $studentGroups[$studentIdentifier] = [
                    'user_id' => $studentId,
                    'display' => $displayName,
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
            <div class="grade-year-card">
              <?php $collapseId = 'gradeYearCollapse' . $yearNum; ?>
              <button class="grade-year-header" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="<?php echo ($selectedStudentId ? 'true' : 'false'); ?>" aria-controls="<?php echo $collapseId; ?>">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-calendar-year"></i>
                  <span><?php echo $yearNum; ?><?php echo $yearNum == '1' ? 'st' : ($yearNum == '2' ? 'nd' : ($yearNum == '3' ? 'rd' : 'th')); ?> Year</span>
                </div>
                <i class="bi bi-chevron-down"></i>
              </button>
              <div id="<?php echo $collapseId; ?>" class="collapse <?php echo ($selectedStudentId ? 'show' : ''); ?> grade-year-body">
                <div class="grade-student-list">
                  <?php $studentIndex = 0; foreach ($studentGroups as $groupKey => $group): ?>
                    <?php
                      $studentCollapseId = 'gradeStudentCollapse' . $yearNum . '_' . $studentIndex;
                      $shouldOpen = false;
                      if ($selectedStudentId && $group['user_id'] !== null) {
                        $shouldOpen = ($group['user_id'] === $selectedStudentId);
                      } elseif (!$selectedStudentId && $studentIndex === 0) {
                        $shouldOpen = true;
                      }
                    ?>
                    <div class="student-grade-card">
                      <button class="student-grade-header" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $studentCollapseId; ?>" aria-expanded="<?php echo $shouldOpen ? 'true' : 'false'; ?>" aria-controls="<?php echo $studentCollapseId; ?>">
                        <span><i class="bi bi-person-vcard"></i> <?php echo htmlspecialchars($group['display']); ?></span>
                        <i class="bi bi-chevron-down"></i>
                      </button>
                      <div id="<?php echo $studentCollapseId; ?>" class="collapse student-grade-body <?php echo $shouldOpen ? 'show' : ''; ?>">
                        <?php $hasGrades = false; ?>
                        <?php foreach (['First Semester', 'Second Semester'] as $semName): ?>
                          <?php if (!empty($group['semesters'][$semName])): $hasGrades = true; ?>
                            <div class="mb-4">
                              <h4 class="grade-semester-title"><?php echo $semName; ?></h4>
                              <div class="grade-cards-container">
                                <?php foreach ($group['semesters'][$semName] as $grade): ?>
                                  <div class="grade-card">
                                    <div class="grade-card-header">
                                      <h5 class="grade-subject-name"><?php echo htmlspecialchars($grade['subject']); ?></h5>
                                      <?php if (!empty($grade['instructor'])): ?>
                                        <p class="grade-instructor"><i class="bi bi-person-badge"></i> <?php echo htmlspecialchars($grade['instructor']); ?></p>
                                      <?php endif; ?>
                                    </div>
                                    <div class="grade-details">
                                      <div class="grade-item">
                                        <span class="grade-label">Prelim</span>
                                        <span class="grade-value"><?php echo $grade['prelim_grade'] !== null ? htmlspecialchars($grade['prelim_grade']) : '-'; ?></span>
                                      </div>
                                      <div class="grade-item">
                                        <span class="grade-label">Midterm</span>
                                        <span class="grade-value"><?php echo $grade['midterm_grade'] !== null ? htmlspecialchars($grade['midterm_grade']) : '-'; ?></span>
                                      </div>
                                      <div class="grade-item">
                                        <span class="grade-label">Finals</span>
                                        <span class="grade-value"><?php echo $grade['finals_grade'] !== null ? htmlspecialchars($grade['finals_grade']) : '-'; ?></span>
                                      </div>
                                    </div>
                                    <div class="grade-card-actions">
                                      <a href="/TCC/public/admin_dashboard.php?section=grade_system&edit_grade_id=<?php echo (int)$grade['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                      </a>
                                      <form method="post" action="/TCC/BackEnd/admin/manage_grades.php" onsubmit="return confirm('Delete this grade record?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete" />
                                        <input type="hidden" name="id" value="<?php echo (int)$grade['id']; ?>" />
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                          <i class="bi bi-trash"></i> Delete
                                        </button>
                                      </form>
                                    </div>
                                  </div>
                                <?php endforeach; ?>
                              </div>
                            </div>
                          <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (!$hasGrades): ?>
                          <p class="text-muted mb-0">No grade records for this student yet.</p>
                        <?php endif; ?>
                      </div>
                    </div>
                    <?php $studentIndex++; endforeach; ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
            </div>

            <?php // close section switch: if ($section === 'announcements') / elseif / elseif ... ?>
            <?php endif; ?>

          </div>
      </main>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
      // Function to update section assignment form inputs
      function updateSectionForm(form, key) {
        var buildingSelect = document.querySelector('select[name="building_' + key + '"]');
        var floorInput = document.querySelector('input[name="floor_' + key + '"]');
        var roomInput = document.querySelector('input[name="room_' + key + '"]');
        var buildingHidden = document.getElementById('building_hidden_' + key);
        var floorHidden = document.getElementById('floor_hidden_' + key);
        var roomHidden = document.getElementById('room_hidden_' + key);
        
        if (!buildingSelect || !floorInput || !roomInput) {
          alert('Please fill in all fields (Building, Floor, and Room)');
          return false;
        }
        
        if (!buildingSelect.value || !floorInput.value || !roomInput.value.trim()) {
          alert('Please fill in all fields (Building, Floor, and Room)');
          return false;
        }
        
        if (buildingHidden) buildingHidden.value = buildingSelect.value;
        if (floorHidden) floorHidden.value = floorInput.value;
        if (roomHidden) roomHidden.value = roomInput.value.trim();
        
        return true;
      }
      
      document.addEventListener('DOMContentLoaded', ()=>{
        var t=document.querySelectorAll('[data-bs-toggle="tooltip"]');Array.from(t).forEach(el=>new bootstrap.Tooltip(el));
        // Rotate chevron icons on collapse show/hide
        document.querySelectorAll('.grade-year-header').forEach(function(btn){
          var target = btn.getAttribute('data-bs-target');
          var collapseEl = document.querySelector(target);
          if (!collapseEl) return;
          collapseEl.addEventListener('show.bs.collapse', function(){ btn.classList.add('open'); });
          collapseEl.addEventListener('hide.bs.collapse', function(){ btn.classList.remove('open'); });
        });

        // Rotate chevrons for student sections
        document.querySelectorAll('.student-grade-header').forEach(function(btn){
          var target = btn.getAttribute('data-bs-target');
          var collapseEl = document.querySelector(target);
          if (!collapseEl) return;
          collapseEl.addEventListener('show.bs.collapse', function(){ btn.classList.add('open'); });
          collapseEl.addEventListener('hide.bs.collapse', function(){ btn.classList.remove('open'); });
        });

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
      });
    </script>
  </body>
</html>
