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
// database connection for admin pages
require_once __DIR__ . '/../BackEnd/database/db.php';
$conn = Database::getInstance()->getConnection();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="css/home.css" />
    <title>Admin Dashboard</title>
    <style>
      .admin-hero { padding:18px; }
      .form-small { max-width: 560px; }
      /* smaller pagination spacing for compact look */
      .pagination-sm .page-link { padding: .25rem .5rem; }
    </style>
  </head>
  <body>
    <div class="page-container">
      <aside class="sidebar">
        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <form method="post" action="/TCC/BackEnd/admin/manage_users.php">
                <input type="hidden" name="action" value="update" />
                <div class="modal-header"><h5 class="modal-title">Edit User</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                  <div class="mb-2"><label class="form-label">Full Name</label><p id="modalFullNameDisplay" class="form-control-plaintext"></p><input type="hidden" name="full_name" id="modalFullName" /></div>
                  <div class="row g-2">
                    <div class="col-md-6"><label class="form-label">Payment</label><select name="payment" id="modalPayment" class="form-select"><option value="paid">Paid</option><option value="owing">Lacking</option></select></div>
                    <div class="col-md-6"><label class="form-label">Department</label><select name="department" id="modalDepartment" class="form-select"><option value="">(dept)</option><option value="IT">IT</option><option value="HM">HM</option><option value="BSEED">BSEED</option><option value="BEED">BEED</option><option value="TOURISM">TOURISM</option></select></div>
                  </div>
                  <div class="mb-2 mt-2" id="owingRow" style="display:none;"><label class="form-label">Amount Owing</label><input name="owing_amount" id="modalOwingAmount" class="form-control" placeholder="e.g. 2350.00"/></div>
                  <div class="mb-2 mt-2"><label class="form-label">Sanctions (text)</label><input name="sanctions" id="modalSanctions" class="form-control" /></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save changes</button></div>
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
            <li><a href="/TCC/public/admin_dashboard.php?section=users" class="nav-link <?php echo ($section==='users')?'active':''?>" data-bs-toggle="tooltip" title="Users"><i class="bi bi-people-fill"></i><span class="nav-label">Users</span></a></li>
          </ul>
        </nav>
        <div class="sidebar-bottom"><a href="/TCC/BackEnd/auth/logout.php" class="btn logout-icon" title="Logout"><i class="bi bi-box-arrow-right"></i></a></div>
      </aside>

      <main class="home-main">
        <div class="container admin-hero">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h4>Admin Dashboard</h4>
              <div class="text-muted">Signed in as <?php echo htmlspecialchars($adminName); ?></div>
            </div>
            <div>
              <a href="/TCC/public/home.php" class="btn btn-outline-secondary">Switch to User View</a>
            </div>
          </div>

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
            <div class="card mb-3">
              <div class="card-body">
                <h5 class="card-title">Manage Announcements</h5>
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
            <div class="card">
              <div class="card-body">
                <h6>Existing Announcements</h6>
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
                  $baseParams['ann_page'] = $prevPage; echo '<li class="page-item ' . $prevClass . '"><a class="page-link" href="?' . htmlspecialchars(http_build_query($baseParams)) . '">&lt;</a></li>';

                  $showPages = min(5, $annTotalPages);
                  for ($p = 1; $p <= $showPages; $p++) {
                    $baseParams['ann_page'] = $p; $qstr = htmlspecialchars(http_build_query($baseParams));
                    $active = ($p === $annPage) ? ' active' : '';
                    echo '<li class="page-item' . $active . '"><a class="page-link" href="?' . $qstr . '">' . $p . '</a></li>';
                  }

                  $baseParams['ann_page'] = $nextPage; echo '<li class="page-item ' . $nextClass . '"><a class="page-link" href="?' . htmlspecialchars(http_build_query($baseParams)) . '">&gt;</a></li>';
                  ?>
                </ul>
              </nav>
              <?php endif; ?>
            </div>
            <?php
            // end announcements
            ?>

          <?php elseif ($section === 'buildings'): ?>
            <div class="card mb-3">
              <div class="card-body">
                <h5 class="card-title">Manage Buildings & Rooms</h5>
                <form class="row g-2 align-items-end" action="/TCC/BackEnd/admin/manage_buildings.php" method="post">
                  <div class="col-md-3"><label class="form-label">Building</label><input name="building" class="form-control" placeholder="A" required/></div>
                  <div class="col-md-3"><label class="form-label">Floors</label><input name="floors" type="number" class="form-control" value="4" min="1" required/></div>
                  <div class="col-md-3"><label class="form-label">Rooms per floor</label><input name="rooms" type="number" class="form-control" value="4" min="1" required/></div>
                  <div class="col-md-3"><button class="btn btn-primary">Save Building</button></div>
                </form>
              </div>
            </div>
            <?php
            $bPath = __DIR__ . '/../database/buildings.json';
            $buildings = [];
            if (file_exists($bPath)) { $buildings = json_decode(file_get_contents($bPath), true) ?: []; }
            // paginate buildings (convert assoc -> list of entries)
            $bldPerPage = 5;
            $bldPage = isset($_GET['bld_page']) ? max(1, intval($_GET['bld_page'])) : 1;
            $bEntries = [];
            foreach ($buildings as $bn => $binfo) { $bEntries[] = ['name'=>$bn, 'info'=>$binfo]; }
            $bldTotal = count($bEntries);
            $bldTotalPages = max(1, intval(ceil($bldTotal / $bldPerPage)));
            $bldSlice = array_slice($bEntries, ($bldPage-1)*$bldPerPage, $bldPerPage);
            ?>
            <div class="card">
              <div class="card-body">
                <h6>Configured Buildings</h6>
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
                  $baseParams['bld_page'] = $prevPage; echo '<li class="page-item ' . $prevClass . '"><a class="page-link" href="?' . htmlspecialchars(http_build_query($baseParams)) . '">&lt;</a></li>';
                  $showPages = min(5, $bldTotalPages);
                  for ($p = 1; $p <= $showPages; $p++) { $baseParams['bld_page'] = $p; $qstr = htmlspecialchars(http_build_query($baseParams)); $active = ($p === $bldPage) ? ' active' : ''; echo '<li class="page-item' . $active . '"><a class="page-link" href="?' . $qstr . '">' . $p . '</a></li>'; }
                  $baseParams['bld_page'] = $nextPage; echo '<li class="page-item ' . $nextClass . '"><a class="page-link" href="?' . htmlspecialchars(http_build_query($baseParams)) . '">&gt;</a></li>';
                  ?>
                </ul>
              </nav>
              <?php endif; ?>
            </div>
            <!-- section -> room assignments -->
            <div class="card mt-3">
              <div class="card-body">
                <h5 class="card-title">Assign Section to Building / Room</h5>
                <form class="row g-2 align-items-end" action="/TCC/BackEnd/admin/manage_section_assignments.php" method="post">
                  <div class="col-md-2"><label class="form-label">Year</label><select name="year" class="form-select"><option>1</option><option>2</option><option selected>3</option><option>4</option></select></div>
                  <div class="col-md-3"><label class="form-label">Section Name</label><input name="section" class="form-control" placeholder="Benevolence" required/></div>
                  <div class="col-md-2"><label class="form-label">Building</label><select name="building" class="form-select">
                    <?php foreach (array_keys($buildings) as $bn): ?>
                      <option><?php echo htmlspecialchars($bn); ?></option>
                    <?php endforeach; ?>
                  </select></div>
                  <div class="col-md-2"><label class="form-label">Floor</label><input name="floor" type="number" class="form-control" value="1" min="1" required/></div>
                  <div class="col-md-2"><label class="form-label">Room</label><input name="room" class="form-control" placeholder="301" required/></div>
                  <div class="col-md-1"><button class="btn btn-primary">Assign</button></div>
                </form>
              </div>
            </div>
            <?php
            $saPath = __DIR__ . '/../database/section_assignments.json';
            $sa = [];
            if (file_exists($saPath)) { $sa = json_decode(file_get_contents($saPath), true) ?: []; }
            ?>
            <div class="card mt-3">
              <div class="card-body">
                <h6>Section Assignments</h6>
                <ul class="list-group">
                  <?php if (empty($sa)): ?><li class="list-group-item text-muted">No section assignments yet.</li><?php endif; ?>
                  <?php foreach ($sa as $key => $info): ?>
                    <li class="list-group-item"><strong><?php echo htmlspecialchars($info['year'] . ' - ' . $info['section']); ?></strong> &mdash; Building <?php echo htmlspecialchars($info['building']); ?>, Floor <?php echo (int)$info['floor']; ?>, Room <?php echo htmlspecialchars($info['room']); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>

          <?php elseif ($section === 'projects'): ?>
            <div class="card mb-3">
              <div class="card-body">
                <h5 class="card-title">Manage Projects</h5>
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
            <div class="card">
              <div class="card-body">
                <h6>Projects</h6>
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
                  $baseParams['proj_page'] = $prevPage; echo '<li class="page-item ' . $prevClass . '"><a class="page-link" href="?' . htmlspecialchars(http_build_query($baseParams)) . '">&lt;</a></li>';
                  $showPages = min(5, $projTotalPages);
                  for ($p = 1; $p <= $showPages; $p++) { $baseParams['proj_page'] = $p; $qstr = htmlspecialchars(http_build_query($baseParams)); $active = ($p === $projPage) ? ' active' : ''; echo '<li class="page-item' . $active . '"><a class="page-link" href="?' . $qstr . '">' . $p . '</a></li>'; }
                  $baseParams['proj_page'] = $nextPage; echo '<li class="page-item ' . $nextClass . '"><a class="page-link" href="?' . htmlspecialchars(http_build_query($baseParams)) . '">&gt;</a></li>';
                  ?>
                </ul>
              </nav>
              <?php endif; ?>
            </div>

          <?php elseif ($section === 'users'): ?>
            <div class="card mb-3">
              <div class="card-body">
                <h5 class="card-title">Assign User to Year / Section</h5>
                <?php
                // Autocomplete-enabled user selector: keep a hidden existing_user_id and a text input for full name
                ?>
                <form class="row g-2 align-items-end" action="/TCC/BackEnd/admin/manage_users.php" method="post">
                  <input type="hidden" name="action" value="assign" />
                  <input type="hidden" id="existingUserIdHidden" name="existing_user_id" value="" />
                  <div class="col-md-4">
                    <label class="form-label">User (type to search or enter full name)</label>
                    <div style="position:relative;">
                      <input id="userSearchInput" type="text" class="form-control" placeholder="Start typing a name or username" autocomplete="off" />
                      <ul id="userSearchList" class="list-group" style="position:absolute;z-index:2000;top:100%;left:0;right:0;display:none;max-height:240px;overflow:auto;"></ul>
                    </div>
                    <small class="text-muted">Select a suggestion to map to an existing account; or type a full name to create an assignment without a user account.</small>
                  </div>
                  <div class="col-md-3"><label class="form-label">Full Name</label><input id="assignFullName" name="full_name" class="form-control" placeholder="Full Name (e.g. Joshua Paculaba)" required/></div>
                  <div class="col-md-2"><label class="form-label">Year</label><select name="year" class="form-select"><option>1</option><option>2</option><option selected>3</option><option>4</option></select></div>
                  <div class="col-md-3"><label class="form-label">Section</label><input name="section" class="form-control" placeholder="Benevolence" required/></div>
                  <div class="col-md-2"><label class="form-label">Department</label><select name="department" class="form-select"><option value="">(none)</option><option value="IT">IT</option><option value="HM">HM</option><option value="BSEED">BSEED</option><option value="BEED">BEED</option><option value="TOURISM">TOURISM</option></select></div>
                  <div class="col-md-2"><button class="btn btn-primary">Assign User</button></div>
                </form>
              </div>
            </div>
            <?php
            // filters
            $q = isset($_GET['q']) ? trim($_GET['q']) : '';
            $filterYear = isset($_GET['year_filter']) ? trim($_GET['year_filter']) : '';
            $filterDept = isset($_GET['dept_filter']) ? trim($_GET['dept_filter']) : '';
            $filterLacking = isset($_GET['lacking_payment']) ? true : false;
            $filterSanctions = isset($_GET['has_sanctions']) ? true : false;

            // Paginated load of user assignments
            $perPage = 5;
            $page = isset($_GET['ua_page']) ? max(1, intval($_GET['ua_page'])) : 1;
            $offset = ($page - 1) * $perPage;

            $conds = [];
            $types = '';
            $values = [];
            if ($q !== '') {
              $like = '%' . $q . '%';
              $conds[] = '(username LIKE ? OR section LIKE ? OR department LIKE ?)';
              $types .= 'sss';
              $values[] = $like; $values[] = $like; $values[] = $like;
            }
            if ($filterYear !== '') { $conds[] = 'year = ?'; $types .= 's'; $values[] = $filterYear; }
            if ($filterDept !== '') { $conds[] = 'department = ?'; $types .= 's'; $values[] = $filterDept; }
            if ($filterLacking) { $conds[] = 'payment = ?'; $types .= 's'; $values[] = 'owing'; }
            if ($filterSanctions) { $conds[] = "TRIM(COALESCE(sanctions,'')) <> ''"; }

            $where = count($conds) ? 'WHERE ' . implode(' AND ', $conds) : '';

            // total count
            $total = 0;
            $countSql = "SELECT COUNT(*) as c FROM user_assignments $where";
            $countStmt = $conn->prepare($countSql);
            if ($countStmt) {
              if ($types !== '') { $countStmt->bind_param($types, ...$values); }
              $countStmt->execute();
              $cres = $countStmt->get_result();
              if ($cr = $cres->fetch_assoc()) { $total = intval($cr['c']); }
              $countStmt->close();
            }

            $totalPages = max(1, intval(ceil($total / $perPage)));

            // fetch page rows
            $ua = [];
            $selSql = "SELECT username, year, section, department, payment, sanctions, owing_amount FROM user_assignments $where ORDER BY year, username LIMIT ? OFFSET ?";
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
                $ua[$r['username']] = ['year'=>$r['year'],'section'=>$r['section'],'department'=>$r['department'],'payment'=>$r['payment'],'sanctions'=>$r['sanctions'],'owing_amount'=>$r['owing_amount']];
              }
              $selStmt->close();
            }
            ?>
            <div class="card mb-3">
              <div class="card-body">
                <form method="get" class="row g-2 align-items-end">
                  <input type="hidden" name="section" value="users" />
                  <div class="col-md-4"><label class="form-label">Search</label><input type="search" name="q" class="form-control" placeholder="full name, section..." value="<?php echo htmlspecialchars($q); ?>"/></div>
                  <div class="col-md-2"><label class="form-label">Year</label><select name="year_filter" class="form-select"><option value="">All</option><option value="1" <?php echo $filterYear==='1'?'selected':'';?>>1</option><option value="2" <?php echo $filterYear==='2'?'selected':'';?>>2</option><option value="3" <?php echo $filterYear==='3'?'selected':'';?>>3</option><option value="4" <?php echo $filterYear==='4'?'selected':'';?>>4</option></select></div>
                  <div class="col-md-2"><label class="form-label">Department</label><select name="dept_filter" class="form-select"><option value="">All</option><option value="IT" <?php echo $filterDept==='IT'?'selected':'';?>>IT</option><option value="HM" <?php echo $filterDept==='HM'?'selected':'';?>>HM</option><option value="BSEED" <?php echo $filterDept==='BSEED'?'selected':'';?>>BSEED</option><option value="BEED" <?php echo $filterDept==='BEED'?'selected':'';?>>BEED</option><option value="TOURISM" <?php echo $filterDept==='TOURISM'?'selected':'';?>>TOURISM</option></select></div>
                  <div class="col-md-1"><div class="form-check" style="margin-top:6px;"><input class="form-check-input" type="checkbox" id="lacking_payment" name="lacking_payment" <?php echo $filterLacking ? 'checked' : ''; ?>><label class="form-check-label" for="lacking_payment">Lacking</label></div></div>
                  <div class="col-md-1"><div class="form-check" style="margin-top:6px;"><input class="form-check-input" type="checkbox" id="has_sanctions" name="has_sanctions" <?php echo $filterSanctions ? 'checked' : ''; ?>><label class="form-check-label" for="has_sanctions">Sanctions</label></div></div>
                  <div class="col-md-2"><button class="btn btn-secondary">Filter</button> <a href="/TCC/public/admin_dashboard.php?section=users" class="btn btn-link">Reset</a></div>
                </form>
              </div>
            </div>

            <div class="card">
              <div class="card-body">
                <h6>User Assignments</h6>
                <ul class="list-group">
                  <?php if (empty($ua)): ?><li class="list-group-item text-muted">No user assignments yet.</li><?php endif; ?>
                  <?php foreach ($ua as $fullName => $info):
                    $u = is_array($info) ? $info : ['year'=>'','section'=> (string)$info];
                    $year = $u['year'] ?? '';
                    $sectionName = $u['section'] ?? '';
                    $department = $u['department'] ?? '';
                    $payment = $u['payment'] ?? 'paid';
                    $sanctions = $u['sanctions'] ?? '';

                    // apply filters
                    if ($q !== '') {
                      $found = false;
                      if (stripos($fullName, $q) !== false) $found = true;
                      if (!$found && stripos($sectionName, $q) !== false) $found = true;
                      if (!$found && $department !== '' && stripos($department, $q) !== false) $found = true;
                      if (!$found) continue;
                    }
                    if ($filterYear !== '' && (string)$year !== $filterYear) continue;
                    if ($filterDept !== '' && ($department === '' || $department !== $filterDept)) continue;
                    if ($filterLacking && $payment === 'paid') continue;
                    if ($filterSanctions && trim($sanctions) === '') continue;
                  ?>
                    <li class="list-group-item">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <strong class="user-fullname"><?php echo htmlspecialchars($fullName); ?></strong>
                          <div class="small text-muted">Year <?php echo htmlspecialchars($year); ?> &mdash; Section <?php echo htmlspecialchars($sectionName); ?><?php if ($department !== ''): ?> &mdash; Dept <?php echo htmlspecialchars($department); ?><?php endif; ?></div>
                          <div class="mt-2">Payment: <span class="badge bg-<?php echo ($payment==='paid')? 'success':'danger'; ?>"><?php echo htmlspecialchars($payment); ?></span> &nbsp; Sanctions: <?php echo $sanctions!==''? htmlspecialchars($sanctions) : '<span class="text-muted">none</span>'; ?></div>
                        </div>
                        <div>
                          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editUserModal"
                            data-fullname="<?php echo htmlspecialchars($fullName); ?>"
                            data-year="<?php echo htmlspecialchars($year); ?>"
                            data-section="<?php echo htmlspecialchars($sectionName); ?>"
                            data-payment="<?php echo htmlspecialchars($payment); ?>"
                            data-sanctions="<?php echo htmlspecialchars($sanctions); ?>"
                            data-department="<?php echo htmlspecialchars($department); ?>"
                            data-owing="<?php echo htmlspecialchars($u['owing_amount'] ?? ''); ?>"
                          >Edit</button>
                        </div>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          <?php endif; ?>

        </div>
      </main>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
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
            var items = list.querySelectorAll('li');
            items.forEach(function(it, i){ it.classList.toggle('active', i===idx); });
            selectedIndex = (idx >= 0 && idx < items.length) ? idx : -1;
            // ensure visible
            if (selectedIndex !== -1) {
              var el = items[selectedIndex];
              if (el && el.scrollIntoView) el.scrollIntoView({block:'nearest'});
            }
          }

          function clearList(){ list.innerHTML = ''; list.style.display = 'none'; }

          function chooseItem(id, name, username){
            if (hidden) hidden.value = id ? id : '';
            if (fullName) fullName.value = name || username || '';
            if (input) input.value = (name || username || '');
            clearList();
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
                  data.results.forEach(function(r){
                    var li = document.createElement('li');
                    li.className = 'list-group-item list-group-item-action';
                    li.style.cursor = 'pointer';
                    li.textContent = (r.full_name || r.username) + ' (' + r.username + ')';
                    li.dataset.id = r.id;
                    li.dataset.full = r.full_name || '';
                    li.dataset.user = r.username || '';
                    li.addEventListener('click', function(){ chooseItem(li.dataset.id, li.dataset.full, li.dataset.user); });
                    list.appendChild(li);
                  });
                  // reset any keyboard selection
                  selectedIndex = -1;
                  list.style.display = 'block';
                }).catch(function(){ clearList(); });
            }, 220);
          });

          // keyboard handling: up/down to move, enter to pick, esc to clear
          input.addEventListener('keydown', function(ev){
            var items = list.querySelectorAll('li');
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
