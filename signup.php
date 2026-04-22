<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start(); // Ensure the session starts here

// Create new MySQL interface object
$mysqli = new mysqli("localhost", "root", "", "gys");

if ($mysqli->connect_errno) {
    $_SESSION['signup_error_message'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    $_SESSION['signup_error_message'] .= "<p>Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error . "</p>";
    $_SESSION['signup_error_message'] .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    $_SESSION['signup_error_message'] .= "</div>";
    $_SESSION['show_modal'] = 'signupModal';
    header("Location: index.php");
    exit();
} else {
    // Get POST data
    $email = $_POST['email'] ?? '';
    $contact = $_POST['contactNumber'] ?? '';
    $password = $_POST['password'] ?? '';
    $username = $_POST['username'] ?? '';
    $user_type = $_POST['user_type'] ?? ''; // 'client', 'contractor', or 'admin'

    // Query to check if email or contact already exists in the Users table
    $query = "SELECT user_id FROM Users WHERE user_email = ? OR user_contact = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ss", $email, $contact);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Email or contact already exists
        $_SESSION['signup_error_message'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        $_SESSION['signup_error_message'] .= "<strong>Error: </strong>Email or Contact already registered.";
        $_SESSION['signup_error_message'] .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        $_SESSION['signup_error_message'] .= "</div>";
        $_SESSION['show_modal'] = 'signupModal';
        header("Location: index.php");
        exit();
    } else {
        // Proceed with registration
        $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password for security
        $insert_query = "INSERT INTO Users (user_username, user_email, user_pw, user_contact, user_type) VALUES (?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($insert_query);
        $stmt->bind_param("sssss", $username, $email, $hashed_password, $contact, $user_type);
        $stmt->execute();

        // Successful registration
        $_SESSION['signup_success_message'] = '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        $_SESSION['signup_success_message'] .= "Registration successful! Login now!";
        $_SESSION['signup_success_message'] .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        $_SESSION['signup_success_message'] .= "</div>";
        $_SESSION['show_modal'] = 'signupModal';
        header("Location: index.php");
        exit();
    }
}

// Close the connection
$stmt->close();
$mysqli->close();
?>
