<?php
session_start();
include("includes/db-connection.php");

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Fetch the correct form values
    $cpId = isset($_POST['cp_id']) ? $_POST['cp_id'] : null;
    $notiId = isset($_POST['notification_id']) ? $_POST['notification_id'] : null;
    $packageId = isset($_POST['package_id']) ? $_POST['package_id'] : null; // Add this line to fetch package_id

    // Validate package and notification IDs
    if (empty($cpId) || empty($notiId) || empty($packageId)) {
        die("<div class='message error'>Invalid package, notification, or package ID.</div>");
    }

    // Check if the notification ID exists in the `notification` table
    $checkNotificationQuery = "SELECT noti_id FROM notification WHERE noti_id = ?";
    $checkNotificationStmt = $conn->prepare($checkNotificationQuery);
    $checkNotificationStmt->bind_param("i", $notiId);
    $checkNotificationStmt->execute();
    $checkNotificationStmt->store_result();

    if ($checkNotificationStmt->num_rows === 0) {
        die("<div class='message error'>Invalid notification ID. The notification does not exist.</div>");
    }
    $checkNotificationStmt->close();

    // Handle file upload
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/package_payments/'; // Directory to store uploaded receipts
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true); // Create directory if it doesn't exist
        }

        $fileName = basename($_FILES['receipt']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'png', 'jpg', 'jpeg'];

        // Validate file type
        if (!in_array($fileExt, $allowedExts)) {
            die("<div class='message error'>Invalid file type. Only PDF, PNG, JPG, and JPEG files are allowed.</div>");
        }

        // Generate a unique file name
        $uniqueFileName = uniqid('receipt_', true) . '.' . $fileExt;
        $filePath = $uploadDir . $uniqueFileName;

        // Move the uploaded file to the destination directory
        if (move_uploaded_file($_FILES['receipt']['tmp_name'], $filePath)) {
            // Insert package payment details into the database
            $query = "INSERT INTO payment (package_id, cp_id, notification_id, receipt_file) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iiis", $packageId, $cpId, $notiId, $filePath); // Bind all four variables

            if ($stmt->execute()) {
                // Fetch admin user ID
                $adminQuery = "SELECT user_id FROM users WHERE user_type = 'admin' LIMIT 1";
                $adminResult = mysqli_query($conn, $adminQuery);
                $adminRow = mysqli_fetch_assoc($adminResult);

                if ($adminRow) {
                    $adminId = $adminRow['user_id'];

                    // Insert notification for the admin
                    $notiMessage = "Package payment received for Package ID: $packageId. Please check the receipt in payment management page.";
                    $insertNotiQuery = "
                        INSERT INTO notification (admin_id, noti_message, noti_date, noti_readme)
                        VALUES (?, ?, NOW(), 0)
                    ";
                    $stmtNoti = $conn->prepare($insertNotiQuery);
                    $stmtNoti->bind_param("is", $adminId, $notiMessage);

                    if ($stmtNoti->execute()) {
                        // Update the notification status
                        $updateQuery = "UPDATE notification SET noti_readme = 1, noti_message = 'Package payment received.' WHERE noti_id = ?";
                        $updateStmt = $conn->prepare($updateQuery);
                        $updateStmt->bind_param("i", $notiId);
                        $updateStmt->execute();
                        $updateStmt->close();

                        echo "
                            <div class='message success'>
                                <i class='fas fa-check-circle'></i>
                                <h2>Payment Receipt Uploaded Successfully!</h2>
                                <p>Your package payment receipt has been uploaded and recorded in the system.</p>
                                <a href='client.php' class='back-btn'><i class='fas fa-arrow-left'></i> Back to Client Dashboard</a>
                            </div>
                        ";
                    } else {
                        die("<div class='message error'>Error sending notification to admin.</div>");
                    }
                } else {
                    die("<div class='message error'>Admin user not found.</div>");
                }
            } else {
                die("<div class='message error'>Error saving payment details to the database.</div>");
            }

            $stmt->close();
        } else {
            die("<div class='message error'>Error uploading file.</div>");
        }
    } else {
        die("<div class='message error'>No file uploaded or file upload error.</div>");
    }
} else {
    die("<div class='message error'>Invalid request method.</div>");
}
?>

<style>
    body {
        font-family: 'Arial', sans-serif;
        background-color: #f8f9fa;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        text-align: center;
    }

    .message {
        max-width: 500px;
        background-color: #ffffff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        text-align: center;
        margin: auto;
    }

    .message.success {
        border-left: 5px solid #28a745;
        color: #155724;
        background-color: #d4edda;
    }

    .message.error {
        border-left: 5px solid #dc3545;
        color: #721c24;
        background-color: #f8d7da;
    }

    .message i {
        font-size: 40px;
        margin-bottom: 10px;
    }

    .message h2 {
        font-size: 1.8em;
        margin-bottom: 10px;
    }

    .message p {
        font-size: 1.1em;
        margin-bottom: 20px;
    }

    .back-btn {
        display: inline-block;
        padding: 10px 20px;
        font-size: 1em;
        color: white;
        background-color: #007bff;
        text-decoration: none;
        border-radius: 5px;
        transition: background 0.3s ease-in-out;
    }

    .back-btn i {
        margin-right: 8px;
    }

    .back-btn:hover {
        background-color: #0056b3;
    }
</style>