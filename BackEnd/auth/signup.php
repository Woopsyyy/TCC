<?php
require_once '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $image_path = 'uploads/default.jpg'; // Default image path

    // Handle file upload if a file was provided
    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == 0) {
        $target_dir = "../database/pictures/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES["profileImage"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        // Check if image file is an actual image
        $check = getimagesize($_FILES["profileImage"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["profileImage"]["tmp_name"], $target_file)) {
                $image_path = '../database/pictures/' . $new_filename;
            }
        }
    }

    try {
        $conn = new PDO("mysql:host=localhost;dbname=accountmanager", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, image_path, role) VALUES (?, ?, ?, ?, 'student')");
        $stmt->execute([$username, $password, $full_name, $image_path]);

        header("Location: ../public/index.html?signup=success");
        exit();
    } catch(PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry error
            header("Location: ../public/signup.php?error=usernametaken");
        } else {
            header("Location: ../public/signup.php?error=dberror");
        }
        exit();
    }
}
?>