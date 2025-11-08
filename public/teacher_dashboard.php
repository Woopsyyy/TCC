<?php
session_start();
require_once "../BackEnd/db.php";

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.html");
    exit();
}

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/home.css">
    <title>Teacher Dashboard</title>
</head>
<body>
    <div class="container mt-4">
        <h1>Teacher Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($username); ?>!</p>
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Class Management</h5>
                        <p class="card-text">Manage your classes and students</p>
                        <a href="#" class="btn btn-primary">View Classes</a>
                    </div>
                </div>
            </div>
            <!-- Add more teacher features here -->
        </div>
    </div>
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>