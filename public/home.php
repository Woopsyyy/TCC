<?php
session_start();
require_once "../BackEnd/database/db.php";

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: /TCC/public/index.html');
    exit();
}

$conn = Database::getInstance()->getConnection();
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT image_path FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$image = $row['image_path'] ?? '/TCC/public/images/sample.jpg';
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
      <img src="<?php echo htmlspecialchars($image); ?>" alt="Home Background" class="home-bg" />
      <h1>
        Welcome,
        <?php echo htmlspecialchars($username); ?>!
      </h1>
    </div>
  </body>
</html>
