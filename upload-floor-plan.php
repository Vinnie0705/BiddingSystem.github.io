<?php
session_start();
include("includes/db-connection.php");

// Ensure the contractor is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'contractor') {
    die("Unauthorized access.");
}

$contId = $_SESSION['user_id']; // Get logged-in contractor's ID
$notiId = $_POST['noti_id']; // Get notification ID from the form

// Check if a file was uploaded
if (isset($_FILES['fp_file']) && $_FILES['fp_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['fp_file'];

    // Validate file type (PDF, PNG, JPG)
    $allowedTypes = ['application/pdf', 'image/png', 'image/jpeg'];
    if (!in_array($file['type'], $allowedTypes)) {
        die("Invalid file type. Only PDF, PNG, and JPG files are allowed.");
    }

    // Save the file to the server
    $uploadDir = 'uploads/floor_plans/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = uniqid() . '_' . basename($file['name']);
    $filePath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        error_log("DEBUG: File uploaded successfully. Path: $filePath");

        // Update the notification for the contractor
        $updateQuery = "
            UPDATE notification
            SET noti_message = 'Floor plan uploaded.', noti_readme = 1, fp_file = ?
            WHERE noti_id = ?
        ";
        $stmt = $conn->prepare($updateQuery);
        if (!$stmt) {
            error_log("ERROR: Prepare failed for update query. Error: " . $conn->error);
            die("Error preparing the update query.");
        }

        $stmt->bind_param("si", $filePath, $notiId);

        if ($stmt->execute()) {
            error_log("DEBUG: Contractor notification updated successfully.");

            // Fetch admin user ID
            $adminQuery = "SELECT user_id FROM users WHERE user_type = 'admin' LIMIT 1";
            $adminResult = mysqli_query($conn, $adminQuery);
            if (!$adminResult) {
                error_log("ERROR: Failed to fetch admin user ID. Error: " . mysqli_error($conn));
                die("Error fetching admin user ID.");
            }

            $adminRow = mysqli_fetch_assoc($adminResult);
            if ($adminRow) {
                $adminId = $adminRow['user_id'];

                // Insert notification for the admin
                $notiMessage = "A contractor has uploaded a new floor plan.";
                $insertNotiQuery = "
                    INSERT INTO notification (admin_id, cont_id, noti_message, noti_date, noti_readme, fp_file)
                    VALUES (?, ?, ?, NOW(), 0, ?)
                ";
                $stmtNoti = $conn->prepare($insertNotiQuery);
                if (!$stmtNoti) {
                    error_log("ERROR: Prepare failed for insert query. Error: " . $conn->error);
                    die("Error preparing the insert query.");
                }

                $stmtNoti->bind_param("iiss", $adminId, $contId, $notiMessage, $filePath);

                if ($stmtNoti->execute()) {
                    error_log("DEBUG: Admin notification inserted successfully.");
                    echo "Floor plan uploaded successfully!";
                } else {
                    error_log("ERROR: Failed to insert admin notification. Error: " . $stmtNoti->error);
                    echo "Error sending notification to admin.";
                }
            } else {
                error_log("ERROR: Admin user not found.");
                echo "Admin user not found.";
            }
        } else {
            error_log("ERROR: Failed to update contractor notification. Error: " . $stmt->error);
            echo "Error updating contractor notification.";
        }
    } else {
        error_log("DEBUG: File upload failed.");
        echo "Error uploading file.";
    }
} else {
    error_log("DEBUG: No file uploaded or file upload error.");
    echo "No file uploaded or file upload error.";
}
?>