<?php
session_start();
include("includes/db-connection.php");

$message = "";

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Debugging - check if the token is correctly passed
    error_log("Raw token from form: " . $token);
    $token_hash = hash('sha256', $token);
    error_log("Hashed token from form: " . $token_hash);
    error_log("Current server time: " . date("Y-m-d H:i:s"));

    // Validate passwords
    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $message = "Password must be at least 8 characters long.";
    } else {
        // Check if the token is valid and not expired
        $query = "SELECT user_id, token_expiry FROM users WHERE reset_token = ? AND token_expiry > NOW()";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $token_hash);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $user_id = $user['user_id'];
            error_log("Token expiry from database: " . $user['token_expiry']);

            // Hash the new password
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

            // Update user's password and remove the reset token
            $update_query = "UPDATE users SET user_pw = ?, reset_token = NULL, token_expiry = NULL WHERE user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $password_hash, $user_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $message = "Your password has been reset successfully. You can now <a href='index.php'>login</a>.";
            } else {
                $message = "Failed to reset your password. Please try again.";
            }
        } else {
            $message = "Invalid or expired token. Please request a new password reset.";
            error_log("Token validation failed for token: $token_hash");
        }
    }
} elseif (isset($_GET['token'])) {
    // Get token from URL
    $token = urldecode($_GET['token']); // Decode the token if it was URL-encoded
    error_log("Token received from URL: " . $token); // Debug the received token
} else {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .reset-password-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 320px;
            text-align: center;
        }
        .reset-password-container h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .reset-password-container input[type="password"] {
            width: 93%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        .reset-password-container button {
            width: 100%;
            padding: 10px;
            background-color: #b2444b;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        .reset-password-container button:hover {
            background-color: #9a3a3a;
        }
        .message {
            margin-top: 15px;
            color: #b2444b;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="reset-password-container">
        <h2>Reset Password</h2>
        <form action="reset-password.php" method="post">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <input type="password" name="new_password" placeholder="New Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            <button type="submit">Reset Password</button>
        </form>
        <?php if (!empty($message)): ?>
            <p class="message"><?= $message; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>