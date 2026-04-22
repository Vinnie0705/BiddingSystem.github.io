<?php
session_start(); // Start the session
include("db-connection.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Establish database connection
    $mysqli = new mysqli("localhost", "root", "", "gys");
    if ($mysqli->connect_errno) {
        $_SESSION['error_message3'] = '<div class="alert alert-danger alert-dismissible" role="alert">';
        $_SESSION['error_message3'] .= "<p>Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . htmlspecialchars($mysqli->connect_error) . "</p>";
        $_SESSION['error_message3'] .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        $_SESSION['error_message3'] .= "</div>";
        header("Location: profile.php");
        exit();
    }

    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error_message3'] = '<div class="alert alert-danger alert-dismissible" role="alert">';
        $_SESSION['error_message3'] .= "<p>You need to be logged in to delete your account.</p>";
        $_SESSION['error_message3'] .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        $_SESSION['error_message3'] .= "</div>";
        header("Location: login.php");
        exit();
    }

    // Get the user ID from session
    $user_id = $_SESSION['user_id'];

    // Delete user account from the 'users' table
    $query = "DELETE FROM users WHERE user_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        // Destroy the session and log out the user
        session_destroy();
        echo '<script>alert("Your account has been successfully deleted.");
        window.location.href = "index.php";
        </script>';
        exit();
    } else {
        $_SESSION['error_message3'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        $_SESSION['error_message3'] .= "<strong>Error: </strong>An error occurred while deleting your account. Please try again.";
        $_SESSION['error_message3'] .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        $_SESSION['error_message3'] .= "</div>";
        header("Location: profile.php");
        exit();
    }

    $stmt->close();
    $mysqli->close(); // Close database connection
} else {
    // If the request method is not POST, redirect to profile page
    header("Location: profile.php");
    exit();
}
?>
