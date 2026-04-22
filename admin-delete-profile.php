<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "gys");

if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $query = "DELETE FROM Users WHERE user_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $_SESSION['delete_success_message'] = "User deleted successfully!";
    header("Location: admin.php");
    exit();
}
?>