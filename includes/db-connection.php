<?php
// Database connection details
$host = "localhost";
$username = "root";
$password = "";
$database = "gys";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_errno) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to count unread notifications for clients
if (!function_exists('count_unread_notifications_client')) {
    function count_unread_notifications_client($client_id) {
        global $conn;

        $query = "SELECT COUNT(*) as count FROM notification WHERE client_id = ? AND noti_readme = 0";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();

        return $row['count'];
    }
}

// Function to count unread notifications for contractors
if (!function_exists('count_unread_notifications_contractor')) {
    function count_unread_notifications_contractor($contractor_id) {
        global $conn;

        $query = "SELECT COUNT(*) as count FROM notification WHERE cont_id = ? AND noti_readme = 0";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $contractor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();

        return $row['count'];
    }
}

// Function to count unread notifications for admins
if (!function_exists('count_unread_notifications_admin')) {
    function count_unread_notifications_admin($admin_id) {
        global $conn;

        $query = "SELECT COUNT(*) as count FROM notification WHERE admin_id = ? AND noti_readme = 0";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();

        return $row['count'];
    }
}
?>