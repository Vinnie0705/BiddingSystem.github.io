<?php
// Prevent page caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start(); // Start session
session_regenerate_id(true); // Regenerate session ID for security
session_unset(); // Clear any previous session data

require __DIR__ . '/vendor/autoload.php'; // Composer autoload

use Google\Client as Google_Client;
use Facebook\Facebook;

// Database connection
$mysqli = new mysqli("localhost", "root", "", "gys");
if ($mysqli->connect_errno) {
    $_SESSION['signin_error_message'] = '<div class="alert alert-danger">Failed to connect to MySQL: ' . $mysqli->connect_error . '</div>';
    header("Location: index.php");
    exit();
}

// Google Login Setup
$googleClient = new Google_Client();
$googleClient->setClientId('354317098133-d85d21dcgjvudtusnensac7997n0gimi.apps.googleusercontent.com');
$googleClient->setClientSecret('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');
$googleClient->setRedirectUri('http://localhost/GYS Resources/google-callback.php');
$googleClient->addScope('email');
$googleClient->addScope('profile');
$google_login_url = $googleClient->createAuthUrl();

// Facebook Login Setup
$fb = new Facebook([
    'app_id' => '961804599422002',
    'app_secret' => '589a2e996d6515f1821b4d3f5f3705f1',
    'default_graph_version' => 'v12.0',
]);
$helper = $fb->getRedirectLoginHelper();
$facebook_login_url = $helper->getLoginUrl('http://localhost/GYS Resources/facebook-callback.php', ['email']);

// Normal Email/Password Sign-in
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $_SESSION['signin_error_message'] = '<div class="alert alert-danger">Email and password are required.</div>';
        header("Location: index.php");
        exit();
    }

    $stmt = $mysqli->prepare("SELECT user_id, user_pw, user_type FROM Users WHERE user_email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($user_id, $stored_password, $user_type);
            $stmt->fetch();
            if (password_verify($password, $stored_password)) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_type'] = $user_type;

                // Redirect based on user type
                header("Location: " . ($user_type === 'client' ? "client.php" : ($user_type === 'contractor' ? "contractor.php" : "admin.php")));
                exit();
            } else {
                $_SESSION['signin_error_message'] = '<div class="alert alert-danger">Incorrect password.</div>';
            }
        } else {
            $_SESSION['signin_error_message'] = '<div class="alert alert-danger">User not found.</div>';
        }
        $stmt->close();
    }
    header("Location: index.php");
    exit();
}

$mysqli->close();
?>

<!-- Google & Facebook Login Buttons -->
<a href="<?= $google_login_url ?>" class="btn btn-danger">Login with Google</a>
<a href="<?= $facebook_login_url ?>" class="btn btn-primary">Login with Facebook</a>
