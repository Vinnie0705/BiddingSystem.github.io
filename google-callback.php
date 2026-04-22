<?php
session_start();
require __DIR__ . '/vendor/autoload.php';

use Google\Client as Google_Client;
use Google\Service\Oauth2;

$googleClient = new Google_Client();
$googleClient->setClientId('354317098133-d85d21dcgjvudtusnensac7997n0gimi.apps.googleusercontent.com');
$googleClient->setClientSecret('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');
$googleClient->setRedirectUri('http://localhost/GYS%20Resources/google-callback.php');
$googleClient->addScope('email');
$googleClient->addScope('profile');

if (isset($_GET['code'])) {
    $token = $googleClient->fetchAccessTokenWithAuthCode($_GET['code']);

    // Check if the token retrieval was successful
    if (isset($token['error'])) {
        $_SESSION['signin_error_message'] = "Google login failed: " . $token['error_description'];
        header("Location: index.php");
        exit();
    }

    // Set access token
    $googleClient->setAccessToken($token);

    // Get user info
    $oauth = new Oauth2($googleClient);
    $userInfo = $oauth->userinfo->get();

    $google_id = $userInfo->id;
    $email = $userInfo->email;
    $name = $userInfo->name;

    // Database connection
    $mysqli = new mysqli("localhost", "root", "", "gys");
    if ($mysqli->connect_errno) {
        $_SESSION['signin_error_message'] = "Database connection failed.";
        header("Location: index.php");
        exit();
    }

    // Check if user exists
    $stmt = $mysqli->prepare("SELECT user_id, user_type FROM Users WHERE user_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        // New user, insert into database
        $insert_stmt = $mysqli->prepare("INSERT INTO Users (user_email, user_name, oauth_provider) VALUES (?, ?, 'google')");
        $insert_stmt->bind_param("ss", $email, $name);
        $insert_stmt->execute();
        $user_id = $insert_stmt->insert_id;
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $name;
        
        // Redirect to choose user type page
        header("Location: choose-user-type.php");
        exit();
    } else {
        $stmt->bind_result($user_id, $user_type);
        $stmt->fetch();

        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_type'] = $user_type;
        
        // Redirect based on user type
        header("Location: " . ($user_type === 'client' ? "client.php" : ($user_type === 'contractor' ? "contractor.php" : "admin.php")));
        exit();
    }
} else {
    $_SESSION['signin_error_message'] = "Google login failed: No code received.";
    header("Location: index.php");
    exit();
}
