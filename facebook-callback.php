<?php
session_start();
require __DIR__ . '/vendor/autoload.php';

use Facebook\Facebook;

$fb = new Facebook([
    'app_id' => '961804599422002',
    'app_secret' => '589a2e996d6515f1821b4d3f5f3705f1',
    'default_graph_version' => 'v12.0',
]);

$helper = $fb->getRedirectLoginHelper();

try {
    $accessToken = $helper->getAccessToken();
    if (!isset($accessToken)) {
        $_SESSION['signin_error_message'] = "Facebook login failed.";
        header("Location: index.php");
        exit();
    }

    // Get user details
    $response = $fb->get('/me?fields=id,name,email', $accessToken);
    $user = $response->getGraphUser();

    $facebook_id = $user['id'];
    $email = $user['email'];
    $name = $user['name'];

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
        $insert_stmt = $mysqli->prepare("INSERT INTO Users (user_email, user_name, oauth_provider) VALUES (?, ?, 'facebook')");
        $insert_stmt->bind_param("ss", $email, $name);
        $insert_stmt->execute();
        $user_id = $insert_stmt->insert_id;
        // Set session variables but don't decide on user_type yet
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $name;
        // Redirect to choose user type page
        header("Location: choose-user-type.php");
        exit();
    } else {
        $stmt->bind_result($user_id, $user_type);
        $stmt->fetch();
        // User exists, proceed as usual
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_type'] = $user_type;
        header("Location: " . ($user_type === 'client' ? "client.php" : ($user_type === 'contractor' ? "contractor.php" : "admin.php")));
        exit();
    }
} catch (Exception $e) {
    $_SESSION['signin_error_message'] = "Facebook login error: " . $e->getMessage();
    header("Location: index.php");
    exit();
}
?>