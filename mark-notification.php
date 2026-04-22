<?php 
include("includes/db-connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $noti_id = $_POST['noti_id'] ?? null;

    if (!$noti_id) {
        echo "Error: Notification ID is missing.";
        exit();
    }

    // Update notification status to 'read'
    $query = "UPDATE notification SET noti_readme = 1 WHERE noti_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $noti_id);

    if ($stmt->execute()) {
        echo "Notification marked as read.";
    } else {
        echo "Error marking notification as read: " . $stmt->error;
    }

    $stmt->close();
}
?>
