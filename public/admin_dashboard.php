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
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
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
            <li><a href="/TCC/public/admin_dashboard.php?section=grades" class="nav-link <?php echo ($section==='grades')?'active':''?>" data-bs-toggle="tooltip" title="Grade System"><i class="bi bi-journal-text"></i><span class="nav-label">Grade System</span></a></li>
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
                  echo 'Section assignment updated successfully!';
                elseif ($_GET['success'] === 'deleted'):
                  echo 'Section assignment deleted successfully!';
                elseif ($_GET['success'] === 'created'):
                  echo ($section === 'grades') ? 'Grade created successfully!' : 'Operation completed successfully!';
                elseif ($_GET['success'] === 'updated' && $section === 'grades'):
                  echo 'Grade updated successfully!';
                elseif ($_GET['success'] === 'deleted' && $section === 'grades'):
                  echo 'Grade deleted successfully!';
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
                <h3>Manage Buildings & Rooms</h3>
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
            ?>
            <div class="info-card mt-3">
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
              </div>

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
            
            <!-- Assign Building & Room to Available Sections -->
            <?php
            // Get all unique sections from user_assignments
            $availableSections = [];
            try {
              $connSections = Database::getInstance()->getConnection();
              $sectionsQuery = $connSections->query("SELECT DISTINCT year, section FROM user_assignments ORDER BY year, section");
              if ($sectionsQuery) {
                while ($row = $sectionsQuery->fetch_assoc()) {
                  $availableSections[] = $row;
                }
              }
              
              // Get existing section assignments to show which are already assigned
              $existingAssignments = [];
              $existingQuery = $connSections->query("SELECT id, year, section, building, floor, room FROM section_assignments");
              if ($existingQuery) {
                while ($row = $existingQuery->fetch_assoc()) {
                  $key = $row['year'] . '|' . $row['section'];
                  $existingAssignments[$key] = $row;
                }
              }
            } catch (Throwable $ex) {
              // Fallback to empty array
              $availableSections = [];
              $existingAssignments = [];
            }
            ?>
            <div class="info-card mt-3">
              <div class="card-header-modern">
                <i class="bi bi-list-check"></i>
                <h3>Assign Building & Room to Available Sections</h3>
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
                                <option value="<?php echo htmlspecialchars($bn); ?>" <?php echo ($hasAssignment && $existing['building']===$bn)?'selected':'';?>>
                                  <?php echo htmlspecialchars($bn); ?>
                                </option>
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
            
            <!-- section -> room assignments -->
            <?php if (!$editSectionRow): ?>
            <div class="info-card mt-3">
              <div class="card-header-modern">
                <i class="bi bi-door-open"></i>
                <h3>Setup Section Building & Room Assignment</h3>
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
            </div>
            <?php endif; ?>
            <?php
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
            
            <!-- Edit Section Assignment Form (shown when editing) -->
            <?php if ($editSectionRow): ?>
            <div class="info-card mt-3">
              <div class="card-header-modern">
                <i class="bi bi-pencil-square"></i>
                <h3>Edit Section Building & Room Assignment</h3>
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
            <?php endif; ?>
            
            <div class="info-card mt-3">
              <div class="card-header-modern">
                <i class="bi bi-list-check"></i>
                <h3>Section Building & Room Assignments</h3>
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

            <?php // close section switch: if ($section === 'announcements') / elseif / elseif ... ?>
            <?php endif; ?>

          <?php elseif ($section === 'grades'): ?>
            <?php
            require_once __DIR__ . '/../BackEnd/database/db.php';
            $connGrades = Database::getInstance()->getConnection();
            
            // Ensure grades table exists
            $connGrades->query("CREATE TABLE IF NOT EXISTS grades (
              id INT AUTO_INCREMENT PRIMARY KEY,
              user_id INT DEFAULT NULL,
              username VARCHAR(200) NOT NULL,
              semester VARCHAR(50) NOT NULL,
              subject VARCHAR(200) NOT NULL,
              teacher VARCHAR(200) NOT NULL,
              prelim DECIMAL(5,2) DEFAULT NULL,
              midterm DECIMAL(5,2) DEFAULT NULL,
              finals DECIMAL(5,2) DEFAULT NULL,
              created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX idx_user_id (user_id),
              INDEX idx_username (username),
              INDEX idx_semester (semester)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            // Get all grades grouped by semester
            $gradesBySemester = [];
            $gradesQuery = $connGrades->query("SELECT id, username, semester, subject, teacher, prelim, midterm, finals FROM grades ORDER BY semester, subject");
            if ($gradesQuery) {
              while ($row = $gradesQuery->fetch_assoc()) {
                $sem = $row['semester'];
                if (!isset($gradesBySemester[$sem])) {
                  $gradesBySemester[$sem] = [];
                }
                $gradesBySemester[$sem][] = $row;
              }
            }
            ?>
            
            <!-- Add Grade Form -->
            <div class="info-card">
              <div class="card-header-modern">
                <i class="bi bi-plus-circle"></i>
                <h3>Add Grade</h3>
              </div>
              <div class="card-body p-3">
                <form class="row g-3" action="/TCC/BackEnd/admin/manage_grades.php" method="post">
                  <input type="hidden" name="action" value="create" />
                  <div class="col-md-3">
                    <label class="form-label fw-bold" style="color: var(--color-bark);">Student Name</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter student name" required style="background-color: var(--color-ethereal); color: var(--color-bark); border-color: var(--color-sage);"/>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label fw-bold" style="color: var(--color-bark);">Semester</label>
                    <select name="semester" class="form-select" required style="background-color: var(--color-ethereal); color: var(--color-bark); border-color: var(--color-sage);">
                      <option value="First Semester">First Semester</option>
                      <option value="Second Semester">Second Semester</option>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label fw-bold" style="color: var(--color-bark);">Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="Mathematics" required style="background-color: var(--color-ethereal); color: var(--color-bark); border-color: var(--color-sage);"/>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label fw-bold" style="color: var(--color-bark);">Teacher</label>
                    <input type="text" name="teacher" class="form-control" placeholder="Ms. Johnson" required style="background-color: var(--color-ethereal); color: var(--color-bark); border-color: var(--color-sage);"/>
                  </div>
                  <div class="col-md-1">
                    <label class="form-label fw-bold" style="color: var(--color-bark);">Prelim</label>
                    <input type="number" name="prelim" class="form-control" min="0" max="100" step="0.01" style="background-color: var(--color-ethereal); color: var(--color-bark); border-color: var(--color-sage);"/>
                  </div>
                  <div class="col-md-1">
                    <label class="form-label fw-bold" style="color: var(--color-bark);">Midterm</label>
                    <input type="number" name="midterm" class="form-control" min="0" max="100" step="0.01" style="background-color: var(--color-ethereal); color: var(--color-bark); border-color: var(--color-sage);"/>
                  </div>
                  <div class="col-md-1">
                    <label class="form-label fw-bold" style="color: var(--color-bark);">Finals</label>
                    <input type="number" name="finals" class="form-control" min="0" max="100" step="0.01" style="background-color: var(--color-ethereal); color: var(--color-bark); border-color: var(--color-sage);"/>
                  </div>
                  <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-check-circle me-2"></i>Add Grade
                    </button>
                  </div>
                </form>
              </div>
            </div>
            
            <!-- Display Grades by Semester -->
            <?php if (empty($gradesBySemester)): ?>
              <div class="info-card mt-3">
                <div class="alert alert-info">
                  <i class="bi bi-info-circle me-2"></i>No grades recorded yet. Add grades using the form above.
                </div>
              </div>
            <?php else: ?>
              <?php foreach ($gradesBySemester as $semester => $grades): ?>
                <div class="info-card mt-3">
                  <div class="card-header-modern" style="background: var(--color-ethereal); border-bottom: 3px solid var(--color-flora);">
                    <div style="display: flex; align-items: center; gap: 12px;">
                      <div style="width: 6px; height: 40px; background-color: var(--color-flora); border-radius: 3px;"></div>
                      <h3 style="color: var(--color-bark); margin: 0; font-size: 1.75rem; font-weight: 700;"><?php echo htmlspecialchars($semester); ?></h3>
                    </div>
                  </div>
                  <div class="card-body p-4" style="background: var(--color-ethereal);">
                    <div class="grades-scroll-container" style="overflow-x: auto; padding-bottom: 10px;">
                      <div class="grades-cards-wrapper" style="display: flex; gap: 20px; min-width: max-content;">
                        <?php foreach ($grades as $grade): ?>
                          <div class="grade-card" style="min-width: 280px; background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 1px solid var(--color-sage);">
                            <h4 style="color: var(--color-bark); font-size: 1.25rem; font-weight: 700; margin-bottom: 8px;"><?php echo htmlspecialchars($grade['subject']); ?></h4>
                            <p style="color: var(--color-cliff); font-size: 0.9rem; margin-bottom: 16px;"><?php echo htmlspecialchars($grade['teacher']); ?></p>
                            <div class="grade-scores" style="display: flex; flex-direction: column; gap: 10px;">
                              <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--color-cliff); font-size: 0.9rem;">Prelim</span>
                                <strong style="color: var(--color-flora); font-size: 1.1rem; font-weight: 700;"><?php echo $grade['prelim'] !== null ? number_format($grade['prelim'], 0) : '-'; ?></strong>
                              </div>
                              <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--color-cliff); font-size: 0.9rem;">Midterm</span>
                                <strong style="color: var(--color-flora); font-size: 1.1rem; font-weight: 700;"><?php echo $grade['midterm'] !== null ? number_format($grade['midterm'], 0) : '-'; ?></strong>
                              </div>
                              <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="color: var(--color-cliff); font-size: 0.9rem;">Finals</span>
                                <strong style="color: var(--color-flora); font-size: 1.1rem; font-weight: 700;"><?php echo $grade['finals'] !== null ? number_format($grade['finals'], 0) : '-'; ?></strong>
                              </div>
                            </div>
                            <div class="grade-actions mt-3" style="display: flex; gap: 8px;">
                              <a href="/TCC/public/admin_dashboard.php?section=grades&edit_grade_id=<?php echo (int)$grade['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Edit
                              </a>
                              <form method="post" action="/TCC/BackEnd/admin/manage_grades.php" onsubmit="return confirm('Delete this grade record?');" style="display:inline;">
                                <input type="hidden" name="action" value="delete" />
                                <input type="hidden" name="id" value="<?php echo (int)$grade['id']; ?>" />
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                  <i class="bi bi-trash"></i> Delete
                                </button>
                              </form>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Edit Grade Form (shown when editing) -->
            <?php
            $editGradeId = isset($_GET['edit_grade_id']) ? intval($_GET['edit_grade_id']) : 0;
            $editGradeRow = null;
            if ($editGradeId > 0) {
              $editGradeStmt = $connGrades->prepare("SELECT id, username, semester, subject, teacher, prelim, midterm, finals FROM grades WHERE id = ? LIMIT 1");
              $editGradeStmt->bind_param('i', $editGradeId);
              $editGradeStmt->execute();
              $editGradeRes = $editGradeStmt->get_result();
              $editGradeRow = $editGradeRes->fetch_assoc();
              $editGradeStmt->close();
            }
            ?>
            <?php if ($editGradeRow): ?>
            <div class="info-card mt-3">
              <div class="card-header-modern">
                <i class="bi bi-pencil-square"></i>
                <h3>Edit Grade</h3>
              </div>
              <div class="card-body p-3">
                <form class="row g-3" action="/TCC/BackEnd/admin/manage_grades.php" method="post">
                  <input type="hidden" name="action" value="update" />
                  <input type="hidden" name="id" value="<?php echo (int)$editGradeRow['id']; ?>" />
                  <div class="col-md-3">
                    <label class="form-label fw-bold" style="color: var(--color-bark);">Student Name</label>
                    <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($editGradeRow['username']); ?>" required style="background-color: var(--color-ethereal); color: var(--color-bark); border-color: var(--color-sage);"/>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label fw-bold" style="color: var(--color-bark);">Semester</label>
                    <select name="semester" class="form-select" required style="background-color: var(--color-ethereal); color: var(--color-bark); border-color: var(--color-sage);">
                      <option value="First Semester" <?php echo ($editGradeRow['semester']==='First Semester')?'selected':'';?>>First Semester</option>
                      <option value="Second Semester" <?php echo ($editGradeRow['semester']==='Second Semester')?'selected':'';?>>Second Semester</option>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label fw-bold" style="color: var(--color-bark);">Subject</label>
                    <input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($editGradeRow['subject']); ?>" required style="background-color: var(--color-ethereal); color: var(--color-bark); border-color: var(--color-sage);"/>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label fw-bold" style="color: var(--color-bark);">Teacher</label>
                    <input type="text" name="teacher" class="form-control" value="<?php echo htmlspecialchars($editGradeRow['teacher']); ?>" required style="background-color: var(--color-ethereal); color: var(--color-bark); border-color: var(--color-sage);"/>
                  </div>
                  <div class="col-md-1">
                    <label class="form-label fw-bold" style="color: var(--color-bark);">Prelim</label>
                    <input type="number" name="prelim" class="form-control" value="<?php echo $editGradeRow['prelim'] !== null ? $editGradeRow['prelim'] : ''; ?>" min="0" max="100" step="0.01" style="background-color: var(--color-ethereal); color: var(--color-bark); border-color: var(--color-sage);"/>
                  </div>
                  <div class="col-md-1">
                    <label class="form-label fw-bold" style="color: var(--color-bark);">Midterm</label>
                    <input type="number" name="midterm" class="form-control" value="<?php echo $editGradeRow['midterm'] !== null ? $editGradeRow['midterm'] : ''; ?>" min="0" max="100" step="0.01" style="background-color: var(--color-ethereal); color: var(--color-bark); border-color: var(--color-sage);"/>
                  </div>
                  <div class="col-md-1">
                    <label class="form-label fw-bold" style="color: var(--color-bark);">Finals</label>
                    <input type="number" name="finals" class="form-control" value="<?php echo $editGradeRow['finals'] !== null ? $editGradeRow['finals'] : ''; ?>" min="0" max="100" step="0.01" style="background-color: var(--color-ethereal); color: var(--color-bark); border-color: var(--color-sage);"/>
                  </div>
                  <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-check-circle me-2"></i>Update Grade
                    </button>
                    <a href="/TCC/public/admin_dashboard.php?section=grades" class="btn btn-secondary ms-2">
                      <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                  </div>
                </form>
              </div>
            </div>
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
