<?php
session_start();
if (!isset($_SESSION['username'])) {
  header('Location: /TCC/public/index.html');
  exit();
}
$image = $_SESSION['image_path'] ?? '/TCC/public/images/sample.jpg';
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="css/home.css" />
    <title>Settings</title>
    <style>
      .profile-preview { width:96px; height:96px; border-radius:12px; object-fit:cover; }
    </style>
  </head>
  <body>
    <div class="container py-4">
      <h4>Account Settings</h4>
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Profile updated successfully.</div>
      <?php elseif (isset($_GET['error'])): ?>
        <div class="alert alert-danger">An error occurred: <?php echo htmlspecialchars($_GET['error']); ?></div>
      <?php endif; ?>

      <form id="settingsForm" action="/TCC/BackEnd/auth/update_profile.php" method="post" enctype="multipart/form-data">
        <div class="row g-3">
          <div class="col-md-4 text-center">
            <img id="preview" src="<?php echo htmlspecialchars($image); ?>" class="profile-preview mb-2" alt="profile" />
            <div>
              <input type="file" name="profile_image" id="profile_image" accept="image/*" class="form-control form-control-sm" />
            </div>
          </div>

          <div class="col-md-8">
            <div class="mb-3">
              <label for="username" class="form-label">Username</label>
              <input id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required />
              <div id="usernameFeedback" class="form-text text-danger" style="display:none"></div>
            </div>

            <div class="mb-3">
              <label for="full_name" class="form-label">Full Name</label>
              <input id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($full_name); ?>" required />
              <div id="fullnameFeedback" class="form-text text-danger" style="display:none"></div>
            </div>

            <div class="mb-3">
              <label for="password" class="form-label">New Password (leave blank to keep current)</label>
              <input id="password" name="password" type="password" class="form-control" />
            </div>

            <div>
              <button class="btn btn-primary" type="submit">Save changes</button>
              <a href="/TCC/public/home.php" class="btn btn-outline-secondary ms-2">Back</a>
            </div>
          </div>
        </div>
      </form>
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
