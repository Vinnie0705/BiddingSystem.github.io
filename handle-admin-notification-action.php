<?php
include("includes/db-connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["noti_id"]) && isset($_POST["action"])) {
    $notiId = intval($_POST["noti_id"]);
    $action = $_POST["action"];

    if ($action === "mark_read") {
        // Update notification status to 'read'
        $query = "UPDATE notification SET noti_readme = 1 WHERE noti_id = ?";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("i", $notiId);
            if ($stmt->execute()) {
                echo "Notification marked as read successfully.";
            } else {
                echo "Error updating notification: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error preparing statement.";
        }
    } else {
        echo "Invalid action.";
    }
} else {
    echo "Invalid request.";
}
?>