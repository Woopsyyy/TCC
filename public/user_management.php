<?php
session_start();
// only for admins
if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header('Location: /TCC/public/index.html');
  exit();
}

$image = $_SESSION['image_path'] ?? '/TCC/public/images/sample.jpg';
$adminName = $_SESSION['full_name'] ?? $_SESSION['username'];

require_once __DIR__ . '/../BackEnd/database/db.php';
$conn = Database::getInstance()->getConnection();
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
    <link rel="stylesheet" href="css/admin_dashboard.css" />
    <title>User Management</title>
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
        
        <div class="sidebar-top">
          <img src="<?php echo htmlspecialchars($image); ?>" class="sidebar-logo" alt="admin"/>
        </div>
        <nav class="sidebar-nav">
          <ul>
            <li>
              <a href="/TCC/public/admin_dashboard.php" class="nav-link" data-bs-toggle="tooltip" title="Admin Dashboard">
                <i class="bi bi-speedometer2"></i>
                <span class="nav-label">Dashboard</span>
              </a>
            </li>
            <li>
              <a href="/TCC/public/user_management.php" class="nav-link active" data-bs-toggle="tooltip" title="User Management">
                <i class="bi bi-people-fill"></i>
                <span class="nav-label">User Management</span>
              </a>
            </li>
            <li>
              <a href="/TCC/public/admin_dashboard.php?section=announcements" class="nav-link" data-bs-toggle="tooltip" title="Announcements">
                <i class="bi bi-megaphone-fill"></i>
                <span class="nav-label">Announcements</span>
              </a>
            </li>
            <li>
              <a href="/TCC/public/admin_dashboard.php?section=buildings" class="nav-link" data-bs-toggle="tooltip" title="Buildings">
                <i class="bi bi-building"></i>
                <span class="nav-label">Buildings</span>
              </a>
            </li>
            <li>
              <a href="/TCC/public/admin_dashboard.php?section=projects" class="nav-link" data-bs-toggle="tooltip" title="Projects">
                <i class="bi bi-folder-fill"></i>
                <span class="nav-label">Projects</span>
              </a>
            </li>
          </ul>
        </nav>
        <div class="sidebar-bottom">
          <a href="/TCC/BackEnd/auth/logout.php" class="btn logout-icon" title="Logout">
            <i class="bi bi-box-arrow-right"></i>
          </a>
        </div>
      </aside>

      <main class="home-main">
        <div class="records-container">
          <div class="records-header">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h2 class="records-title">
                  <i class="bi bi-people-fill"></i> User Management
                </h2>
                <p class="records-subtitle">
                  Manage user assignments, financial status, and sanctions
                </p>
              </div>
              <a href="/TCC/public/admin_dashboard.php" class="btn btn-primary" style="background-color: #28a745; border-color: #28a745; color: white; font-weight: 600; padding: 0.5rem 1.5rem;">
                <i class="bi bi-arrow-left-circle me-1"></i>Back to Dashboard
              </a>
            </div>
          </div>

          <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="bi bi-check-circle me-2"></i>User assignment updated successfully!
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php elseif (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <i class="bi bi-check-circle me-2"></i>User assignment deleted successfully!
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="bi bi-exclamation-triangle me-2"></i>Error: <?php echo htmlspecialchars($_GET['error']); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <!-- Assign User to Year / Section -->
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
                      <input 
                        type="text" 
                        id="userSearchInput" 
                        class="form-control form-control-lg" 
                        placeholder="Start typing a name or username" 
                        autocomplete="off" 
                        role="combobox" 
                        aria-autocomplete="list" 
                        aria-expanded="false" 
                        aria-controls="userSearchList" 
                        aria-haspopup="listbox"
                      />
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
                    <input 
                      type="text" 
                      id="assignFullName" 
                      name="full_name" 
                      class="form-control form-control-lg" 
                      placeholder="Full Name (e.g. Joshua Paculaba)" 
                      required
                    />
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
                    <input 
                      type="text" 
                      name="section" 
                      id="assignSection" 
                      class="form-control form-control-lg" 
                      placeholder="Benevolence" 
                      required
                    />
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

          <?php
          // filters
          $q = isset($_GET['q']) ? trim($_GET['q']) : '';
          $filterYear = isset($_GET['year_filter']) ? trim($_GET['year_filter']) : '';
          $filterDept = isset($_GET['dept_filter']) ? trim($_GET['dept_filter']) : '';
          $filterLacking = isset($_GET['lacking_payment']) ? true : false;
          $filterSanctions = isset($_GET['has_sanctions']) ? true : false;

          // Paginated load of user assignments
          $perPage = 10;
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
          $selSql = "SELECT id, username, year, section, department, payment, sanctions, owing_amount FROM user_assignments $where ORDER BY year, username LIMIT ? OFFSET ?";
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
          ?>

          <!-- Filter Users -->
          <div class="info-card mt-3">
            <div class="card-header-modern">
              <i class="bi bi-funnel"></i>
              <h3>Filter Users</h3>
            </div>
            <form method="get" class="admin-filter-form">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="admin-form-label"><i class="bi bi-search"></i> Search</label>
                  <input type="search" name="q" class="form-control form-control-lg" placeholder="full name, section..." value="<?php echo htmlspecialchars($q); ?>"/>
                </div>
                <div class="col-md-2">
                  <label class="admin-form-label"><i class="bi bi-calendar-year"></i> Year</label>
                  <select name="year_filter" class="form-select form-select-lg">
                    <option value="">All</option>
                    <option value="1" <?php echo $filterYear==='1'?'selected':'';?>>1st Year</option>
                    <option value="2" <?php echo $filterYear==='2'?'selected':'';?>>2nd Year</option>
                    <option value="3" <?php echo $filterYear==='3'?'selected':'';?>>3rd Year</option>
                    <option value="4" <?php echo $filterYear==='4'?'selected':'';?>>4th Year</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="admin-form-label"><i class="bi bi-building"></i> Department</label>
                  <select name="dept_filter" class="form-select form-select-lg">
                    <option value="">All</option>
                    <option value="IT" <?php echo $filterDept==='IT'?'selected':'';?>>IT</option>
                    <option value="HM" <?php echo $filterDept==='HM'?'selected':'';?>>HM</option>
                    <option value="BSEED" <?php echo $filterDept==='BSEED'?'selected':'';?>>BSEED</option>
                    <option value="BEED" <?php echo $filterDept==='BEED'?'selected':'';?>>BEED</option>
                    <option value="TOURISM" <?php echo $filterDept==='TOURISM'?'selected':'';?>>TOURISM</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="admin-form-label"><i class="bi bi-filter"></i> Filters</label>
                  <div class="d-flex flex-column gap-2">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="lacking_payment" name="lacking_payment" <?php echo $filterLacking ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="lacking_payment">Lacking Payment</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="has_sanctions" name="has_sanctions" <?php echo $filterSanctions ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="has_sanctions">Has Sanctions</label>
                    </div>
                  </div>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                  <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="bi bi-funnel-fill me-1"></i>Filter
                  </button>
                  <a href="/TCC/public/user_management.php" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-arrow-counterclockwise"></i>
                  </a>
                </div>
              </div>
            </form>
          </div>

          <!-- User Assignments List -->
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
                      <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-inbox"></i> No user assignments found.
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($ua as $r): 
                      $assignmentId = $r['id'] ?? null;
                      $fullName = $r['username'];
                      $year = $r['year'] ?? '';
                      $sectionName = $r['section'] ?? '';
                      $department = $r['department'] ?? '';
                      $payment = $r['payment'] ?? 'paid';
                      $sanctions = $r['sanctions'] ?? '';
                      $owingAmount = $r['owing_amount'] ?? '';
                      
                      // Parse sanctions for display
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
                          <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal"
                              data-fullname="<?php echo htmlspecialchars($fullName); ?>"
                              data-payment="<?php echo htmlspecialchars($payment); ?>"
                              data-sanctions="<?php echo htmlspecialchars($sanctions); ?>"
                              data-department="<?php echo htmlspecialchars($department); ?>"
                              data-owing="<?php echo htmlspecialchars($owingAmount); ?>"
                            >
                              <i class="bi bi-pencil"></i> Edit
                            </button>
                            <?php if ($assignmentId): ?>
                            <form method="post" action="/TCC/BackEnd/admin/manage_users.php" onsubmit="return confirm('Are you sure you want to delete this user assignment? This action cannot be undone.');" style="display:inline;">
                              <input type="hidden" name="action" value="delete" />
                              <input type="hidden" name="id" value="<?php echo (int)$assignmentId; ?>" />
                              <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i> Delete
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

            <!-- Pagination -->
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
      </main>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Enable tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function(el) {
          new bootstrap.Tooltip(el);
        });

        // Edit Modal Handler
        var editModal = document.getElementById('editUserModal');
        if (editModal) {
          editModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var fullname = button.getAttribute('data-fullname') || '';
            var payment = button.getAttribute('data-payment') || 'paid';
            var sanctions = button.getAttribute('data-sanctions') || '';
            var department = button.getAttribute('data-department') || '';
            var owing = button.getAttribute('data-owing') || '';

            document.getElementById('modalFullNameDisplay').textContent = fullname;
            document.getElementById('modalFullName').value = fullname;
            document.getElementById('modalPayment').value = payment;
            document.getElementById('modalSanctions').value = sanctions;
            document.getElementById('modalDepartment').value = department;
            document.getElementById('modalOwingAmount').value = owing;

            var owingRow = document.getElementById('owingRow');
            if (owingRow) {
              owingRow.style.display = (payment === 'owing') ? '' : 'none';
            }
          });

          // Toggle owing amount field
          var paymentSelect = document.getElementById('modalPayment');
          if (paymentSelect) {
            paymentSelect.addEventListener('change', function(e) {
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

        // User Search Autocomplete
        (function() {
          var input = document.getElementById('userSearchInput');
          var list = document.getElementById('userSearchList');
          var hidden = document.getElementById('existingUserIdHidden');
          var fullName = document.getElementById('assignFullName');
          var debounceTimer = null;
          var selectedIndex = -1;

          function highlightAt(idx) {
            var items = list.querySelectorAll('.admin-search-item');
            items.forEach(function(it, i) {
              var sel = (i === idx);
              it.classList.toggle('active', sel);
              it.setAttribute('aria-selected', sel ? 'true' : 'false');
            });
            selectedIndex = (idx >= 0 && idx < items.length) ? idx : -1;
            if (selectedIndex !== -1) {
              var el = items[selectedIndex];
              if (el && el.scrollIntoView) el.scrollIntoView({block: 'nearest'});
              if (input) input.setAttribute('aria-activedescendant', el.id || '');
            }
          }

          function clearList() {
            list.innerHTML = '';
            list.classList.remove('show');
            list.style.display = 'none';
          }

          function chooseItem(id, name, username) {
            if (hidden) hidden.value = id ? id : '';
            if (fullName) fullName.value = name || username || '';
            if (input) input.value = (name || username || '');
            clearList();
            if (input) {
              input.setAttribute('aria-expanded', 'false');
              input.removeAttribute('aria-activedescendant');
            }
            selectedIndex = -1;
          }

          if (!input) return;
          
          input.addEventListener('input', function(e) {
            var q = input.value.trim();
            if (debounceTimer) clearTimeout(debounceTimer);
            if (hidden) hidden.value = '';
            debounceTimer = setTimeout(function() {
              if (q.length < 2) {
                clearList();
                return;
              }
              fetch('/TCC/BackEnd/admin/user_search.php?q=' + encodeURIComponent(q) + '&limit=12')
                .then(function(res) { return res.json(); })
                .then(function(data) {
                  list.innerHTML = '';
                  if (!data || !data.results || data.results.length === 0) {
                    clearList();
                    return;
                  }
                  var _sugCounter = 0;
                  data.results.forEach(function(r) {
                    var li = document.createElement('li');
                    li.className = 'admin-search-item';
                    li.style.cursor = 'pointer';
                    li.innerHTML = '<strong>' + (r.full_name || r.username) + '</strong> <span class="text-muted">(' + r.username + ')</span>';
                    li.dataset.id = r.id;
                    li.dataset.full = r.full_name || '';
                    li.dataset.user = r.username || '';
                    li.id = 'useropt-' + (r.id || 'x') + '-' + (_sugCounter++);
                    li.setAttribute('role', 'option');
                    li.setAttribute('aria-selected', 'false');
                    li.addEventListener('click', function() {
                      chooseItem(li.dataset.id, li.dataset.full, li.dataset.user);
                    });
                    li.addEventListener('mouseenter', function() {
                      highlightAt(_sugCounter - 1);
                    });
                    list.appendChild(li);
                  });
                  list.setAttribute('aria-hidden', 'false');
                  input.setAttribute('aria-expanded', 'true');
                  selectedIndex = -1;
                  list.style.display = 'block';
                  list.classList.add('show');
                }).catch(function() { clearList(); });
            }, 220);
          });

          input.addEventListener('keydown', function(ev) {
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

          document.addEventListener('click', function(ev) {
            if (!input.contains(ev.target) && !list.contains(ev.target)) clearList();
          });
        })();
      });
    </script>
  </body>
</html>

