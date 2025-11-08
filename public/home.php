<?php
session_start();
require_once "../BackEnd/auth/login.php";
require_once "../BackEnd/database/db.php";

// Check if user is logged in
Auth::checkAuth();

$conn = Database::getInstance()->getConnection();
$username = $_SESSION['username'];
$query = "SELECT image_path FROM users WHERE username=?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$image = $row['image_path'];

// Get user's role
$isAdmin = Auth::isAdmin();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/home.css" />
    <title>Home</title>
  </head>
  <body>
    <div class="class-id">
      <img src="images/sample.jpg" alt="Home Background" class="home-bg" />
      <h1>
        Welcome,
        <?php echo $username; ?>!
      </h1>
    </div>
  </body>
</html>
