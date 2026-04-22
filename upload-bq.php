<?php
include("includes/db-connection.php");

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the contractor is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in.");
}

$cont_id = intval($_SESSION['user_id']); // Ensure it's an integer for security
$noti_id = intval($_POST['noti_id']); // Get the notification ID

// Check if a file was uploaded
if (isset($_FILES['bq_file']) && $_FILES['bq_file']['error'] == UPLOAD_ERR_OK) {
    $file = $_FILES['bq_file'];

    // Validate file type and size
    $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB

    if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxFileSize) {
        // Save the file to a directory
        $uploadDir = 'uploads/bq/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = uniqid() . '_' . basename($file['name']);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Update the notification message
            $updateQuery = "UPDATE notification SET noti_message = 'BQ Uploaded', bq_file = '$filePath' WHERE noti_id = '$noti_id' AND cont_id = '$cont_id'";
            if (mysqli_query($conn, $updateQuery)) {
                // Fetch admin user ID
                $adminQuery = "SELECT user_id FROM users WHERE user_type = 'admin' LIMIT 1";
                $adminResult = mysqli_query($conn, $adminQuery);
                $adminRow = mysqli_fetch_assoc($adminResult);

                if ($adminRow) {
                    $adminId = $adminRow['user_id'];

                    // Insert notification for the admin
                    $notiMessage = "A contractor has uploaded a BQ file.";
                    $insertNotiQuery = "
                        INSERT INTO notification (admin_id, cont_id, noti_message, noti_date, noti_readme, bq_file)
                        VALUES ('$adminId', '$cont_id', '$notiMessage', NOW(), 0, '$filePath')
                    ";

                    if (mysqli_query($conn, $insertNotiQuery)) {
                        echo "BQ uploaded successfully! Notification sent to admin.";
                    } else {
                        echo "Error sending notification to admin: " . mysqli_error($conn);
                    }
                } else {
                    echo "Admin user not found.";
                }
            } else {
                echo "Error updating notification: " . mysqli_error($conn);
            }
        } else {
            echo "Error uploading file.";
        }
    } else {
        echo "Invalid file type or size.";
    }
} else {
    echo "No file uploaded or an error occurred.";
}
?>