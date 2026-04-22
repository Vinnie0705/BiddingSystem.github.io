<?php
session_start();
include("includes/db-connection.php");

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Check if the email exists in the database
    $query = "SELECT user_id, user_email FROM users WHERE user_email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['user_id'];

        // Generate a unique token
        $token = bin2hex(random_bytes(32)); // Raw token
        $token_hash = hash('sha256', $token); // Hashed token

        // Set token expiry to 24 hours from now (for testing)
        $expiry = date("Y-m-d H:i:s", time() + 86400);

        $query = "UPDATE users SET reset_token = ?, token_expiry = ? WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $token_hash, $expiry, $user_id);
        $stmt->execute();
        
        // Debugging: Log token and expiry
        error_log("Raw token generated: " . $token);
        error_log("Hashed token stored: " . $token_hash);
        error_log("Token expiry set to: " . $expiry);

        if ($stmt->affected_rows > 0) {
            // Include PHPMailer
            require 'vendor/autoload.php';

            // Create a new PHPMailer instance
            $mail = new PHPMailer\PHPMailer\PHPMailer(true); // Enable exceptions

            try {
                // Server settings
                $mail->isSMTP();                                            // Send using SMTP
                $mail->Host       = 'smtp.gmail.com';                       // Set the SMTP server to send through
                $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
                $mail->Username   = 'tan.vinnie@ypccollege.edu.my';         // SMTP username
                $mail->Password   = 'sjup ozfk ylcb etpx';                  // SMTP password
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
                $mail->Port       = 587;                                    // TCP port to connect to

                // Recipients
                $mail->setFrom('no-reply@yourdomain.com', 'Mailer');
                $mail->addAddress($email);                                 // Add the user's email address

                // Content
                $mail->isHTML(true);                                        // Set email format to HTML
                $mail->Subject = 'Password Reset Request';
                $resetLink = "http://localhost/GYS%20Resources/reset-password.php?token=" . urlencode($token); // URL-encode the raw token
                $mail->Body    = "Click the link below to reset your password:<br><br><a href='$resetLink'>Reset Password</a>";

                // Send the email
                $mail->send();
                $message = 'A password reset link has been sent to your email address.';
            } catch (Exception $e) {
                $message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                error_log("PHPMailer Error: {$mail->ErrorInfo}"); // Log the error
            }
        } else {
            $message = "Failed to generate a password reset token. Please try again.";
        }
    } else {
        $message = "No account found with that email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
        .forgot-password-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 320px;
            text-align: center;
        }
        .forgot-password-container h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .forgot-password-container input[type="email"] {
            width: 93%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        .forgot-password-container button {
            width: 100%;
            padding: 10px;
            background-color: #b2444b;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        .forgot-password-container button:hover {
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
    <div class="forgot-password-container">
        <h2>Forgot Password</h2>
        <form action="forgot-password.php" method="post">
            <input type="email" name="email" placeholder="Enter your email address" required>
            <button type="submit">Send Reset Link</button>
        </form>
        <?php if (!empty($message)): ?>
            <p class="message"><?= $message; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>