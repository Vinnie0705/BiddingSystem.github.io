<?php
session_start();
include("includes/db-connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the notification ID and action from the POST request
    $notiId = $_POST['noti_id'];
    $action = $_POST['action'];
    $rejectionReason = isset($_POST['rejection_reason']) ? $_POST['rejection_reason'] : null;

    // Fetch the notification details
    $query = "SELECT * FROM notification WHERE noti_id = '$notiId'";
    $result = mysqli_query($conn, $query);
    $notification = mysqli_fetch_assoc($result);

    if ($notification) {
        $clientId = $notification['client_id'];
        $cpId = $notification['cp_id']; // Client package ID
        $bidId = $notification['bid_id']; // Bidding ID
        $notificationMessage = $notification['noti_message'];

        // Fetch project_id from the bid or clientpackage table
        if (!empty($bidId)) {
            // Fetch project_id from the bid table
            $projectQuery = "SELECT proj_id FROM bid WHERE bid_id = '$bidId'";
            $projectResult = mysqli_query($conn, $projectQuery);
            $projectRow = mysqli_fetch_assoc($projectResult);

            if ($projectRow) {
                $projId = $projectRow['proj_id'];

                // Fetch project_title from the project table
                $titleQuery = "SELECT proj_title FROM project WHERE proj_id = '$projId'";
                $titleResult = mysqli_query($conn, $titleQuery);
                $titleRow = mysqli_fetch_assoc($titleResult);

                if ($titleRow) {
                    $projectTitle = $titleRow['proj_title'];
                } else {
                    $projectTitle = "Unknown Project"; // Default if not found
                }
            } else {
                $projectTitle = "Unknown Project"; // Default if not found
            }
        } elseif (!empty($cpId)) {
            // Fetch project_id from the clientpackage table
            $projectQuery = "SELECT package_id FROM clientpackage WHERE cp_id = '$cpId'";
            $projectResult = mysqli_query($conn, $projectQuery);
            $projectRow = mysqli_fetch_assoc($projectResult);

            if ($projectRow) {
                $packageId = $projectRow['package_id'];

                // Fetch project_title from the project table
                $titleQuery = "SELECT package_title FROM package WHERE package_id = '$packageId'";
                $titleResult = mysqli_query($conn, $titleQuery);
                $titleRow = mysqli_fetch_assoc($titleResult);

                if ($titleRow) {
                    $packageTitle = $titleRow['package_title'];
                } else {
                    $packageTitle = "Unknown Package"; // Default if not found
                }
            } else {
                $packageTitle = "Unknown Package"; // Default if not found
            }
        } else {
            $packageTitle = "Unknown Package"; // Default if not found
        }

        if ($action === 'approve') {
            // Determine the new message based on the notification type
            if (strpos($notificationMessage, 'floor plan') !== false) {
                $newMessage = "You have approved the floor plan.";
            } elseif (strpos($notificationMessage, 'BQ') !== false) {
                $newMessage = "The Bill of Quantity (BQ) has been approved.";
            } else {
                $newMessage = "Action approved.";
            }

            // Update the notification message and status
            $updateQuery = "
                UPDATE notification
                SET noti_message = '$newMessage', noti_readme = 1
                WHERE noti_id = '$notiId'
            ";
            mysqli_query($conn, $updateQuery);

            // Fetch the admin user ID
            $adminQuery = "SELECT user_id FROM users WHERE user_type = 'admin' LIMIT 1";
            $adminResult = mysqli_query($conn, $adminQuery);
            $adminRow = mysqli_fetch_assoc($adminResult);

            if ($adminRow) {
                $adminId = $adminRow['user_id'];

                // Check if it's a client package or bid-related action
                if (!empty($cpId)) {
                    $adminMessage = strpos($notificationMessage, 'floor plan') !== false
                        ? "Client has approved the floor plan for Package: $packageTitle."
                        : "Client has approved the BQ for Package: $packageTitle.";

                    $adminInsertQuery = "
                        INSERT INTO notification (admin_id, cp_id, noti_message, noti_date, noti_readme)
                        VALUES ('$adminId', '$cpId', '$adminMessage', NOW(), 0)
                    ";
                } elseif (!empty($bidId)) {
                    $adminMessage = strpos($notificationMessage, 'floor plan') !== false
                        ? "Client has approved the floor plan for Package: $projectTitle."
                        : "Client has approved the BQ for Project: $projectTitle.";

                    $adminInsertQuery = "
                        INSERT INTO notification (admin_id, bid_id, noti_message, noti_date, noti_readme)
                        VALUES ('$adminId', '$bidId', '$adminMessage', NOW(), 0)
                    ";
                } else {
                    echo "Error: No valid reference ID found.";
                    exit;
                }

                mysqli_query($conn, $adminInsertQuery);
                echo "Approval successful!";
            } else {
                echo "Admin user not found.";
            }
        } elseif ($action === 'reject' && $rejectionReason) {
            // Update the notification message and status with the rejection reason
            if (strpos($notificationMessage, 'floor plan') !== false) {
                $newMessage = "The floor plan has been rejected. Reason: $rejectionReason";
            } elseif (strpos($notificationMessage, 'BQ') !== false) {
                $newMessage = "The Bill of Quantity (BQ) has been rejected. Reason: $rejectionReason";
            } else {
                $newMessage = "Action rejected. Reason: $rejectionReason";
            }

            $updateQuery = "
                UPDATE notification
                SET noti_message = '$newMessage', noti_readme = 1, rejection_reason = '$rejectionReason'
                WHERE noti_id = '$notiId'
            ";
            mysqli_query($conn, $updateQuery);

            // Fetch the admin user ID
            $adminQuery = "SELECT user_id FROM users WHERE user_type = 'admin' LIMIT 1";
            $adminResult = mysqli_query($conn, $adminQuery);
            $adminRow = mysqli_fetch_assoc($adminResult);

            if ($adminRow) {
                $adminId = $adminRow['user_id'];

                // Check if it's a client package or bid-related action
                if (!empty($cpId)) {
                    $adminMessage = strpos($notificationMessage, 'floor plan') !== false
                        ? "Client has rejected the floor plan for Project: $projectTitle. Reason: $rejectionReason"
                        : "Client has rejected the BQ for Project: $projectTitle. Reason: $rejectionReason";

                    $adminInsertQuery = "
                        INSERT INTO notification (admin_id, cp_id, noti_message, noti_date, noti_readme, rejection_reason)
                        VALUES ('$adminId', '$cpId', '$adminMessage', NOW(), 0, '$rejectionReason')
                    ";
                } elseif (!empty($bidId)) {
                    $adminMessage = strpos($notificationMessage, 'floor plan') !== false
                        ? "Client has rejected the floor plan for Project: $projectTitle. Reason: $rejectionReason"
                        : "Client has rejected the BQ for Project: $projectTitle. Reason: $rejectionReason";

                    $adminInsertQuery = "
                        INSERT INTO notification (admin_id, bid_id, noti_message, noti_date, noti_readme, rejection_reason)
                        VALUES ('$adminId', '$bidId', '$adminMessage', NOW(), 0, '$rejectionReason')
                    ";
                } else {
                    echo "Error: No valid reference ID found.";
                    exit;
                }

                mysqli_query($conn, $adminInsertQuery);
                echo "Rejection successful!";
            } else {
                echo "Admin user not found.";
            }
        } else {
            echo "Invalid action or missing rejection reason.";
        }
    } else {
        echo "Notification not found.";
    }
} else {
    echo "Invalid request method.";
}
?>