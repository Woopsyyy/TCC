<?php
session_start();
if (!isset($_SESSION['username'])) {
  header('Location: /TCC/public/index.html');
  exit();
}
$image = $_SESSION['image_path'] ?? '/TCC/public/images/sample.jpg';
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="css/home.css" />
    <title>Settings</title>
  </head>
  <body>
    <div class="page-container">
      <aside class="sidebar">
        <div class="sidebar-top">
          <img src="<?php echo htmlspecialchars($image); ?>" alt="User" class="sidebar-logo" />
        </div>

        <nav class="sidebar-nav" aria-label="Main navigation">
          <ul>
            <li>
              <a href="/TCC/public/home.php?view=announcements" class="nav-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Announcements">
                <i class="bi bi-megaphone-fill"></i>
                <span class="nav-label">Announcements</span>
              </a>
            </li>
            <li>
              <a href="/TCC/public/home.php?view=records" class="nav-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Records">
                <i class="bi bi-journal-text"></i>
                <span class="nav-label">Records</span>
              </a>
            </li>
            <li>
              <a href="/TCC/public/home.php?view=transparency" class="nav-link" data-bs-toggle="tooltip" data-bs-placement="right" title="Transparency">
                <i class="bi bi-graph-up"></i>
                <span class="nav-label">Transparency</span>
              </a>
            </li>
            <li>
              <a href="/TCC/public/settings.php" class="nav-link active" data-bs-toggle="tooltip" data-bs-placement="right" title="Settings">
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
                    <a href="/TCC/public/home.php" class="btn btn-outline-secondary">
                      <i class="bi bi-arrow-left me-2"></i>Cancel
                    </a>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </main>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
      // preview image
      document.getElementById('profile_image').addEventListener('change', function (e) {
        const f = e.target.files[0];
        if (!f) return;
        const url = URL.createObjectURL(f);
        document.getElementById('preview').src = url;
      });

      // debounce
      function debounce(fn, wait){ let t; return function(...args){ clearTimeout(t); t = setTimeout(()=>fn.apply(this,args), wait); } }

      // availability checks
      const usernameInput = document.getElementById('username');
      const fullInput = document.getElementById('full_name');

      usernameInput.addEventListener('input', debounce(function(){
        const val = this.value.trim();
        if (!val) return;
        fetch('/TCC/BackEnd/auth/check_availability.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'type=username&value='+encodeURIComponent(val) })
          .then(r=>r.json()).then(j=>{
            const fb = document.getElementById('usernameFeedback');
            if (!j.success) { fb.style.display='block'; fb.textContent='Error checking availability'; }
            else if (!j.available) { fb.style.display='block'; fb.textContent='Username already taken'; }
            else { fb.style.display='none'; }
          });
      }, 400));

      fullInput.addEventListener('input', debounce(function(){
        const val = this.value.trim();
        if (!val) return;
        fetch('/TCC/BackEnd/auth/check_availability.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'type=full_name&value='+encodeURIComponent(val) })
          .then(r=>r.json()).then(j=>{
            const fb = document.getElementById('fullnameFeedback');
            if (!j.success) { fb.style.display='block'; fb.textContent='Error checking availability'; }
            else if (!j.available) { fb.style.display='block'; fb.textContent='Full name already used'; }
            else { fb.style.display='none'; }
          });
      }, 400));
    </script>
  </body>
</html>
