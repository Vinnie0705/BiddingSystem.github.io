<?php
include("includes/db-connection.php");
include("includes/admin-header.php");

// Ensure admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo "Unauthorized access.";
    exit;
}

$adminId = $_SESSION['user_id']; // Get logged-in admin's ID

// Fetch notifications specifically for the logged-in admin
$query = "SELECT n.*, u.user_name AS client_name, p.receipt_file
          FROM notification n
          LEFT JOIN users u ON n.client_id = u.user_id
          LEFT JOIN payment p ON n.noti_id = p.notification_id
          WHERE n.admin_id = ? 
          AND (n.noti_message LIKE '%has been approved%' 
               OR n.noti_message LIKE '%has been rejected%' 
               OR n.noti_message LIKE '%uploaded BQ file%'
               OR n.noti_message LIKE '%Client has approved%'
               OR n.noti_message LIKE '%Client has rejected%'
               OR n.noti_message LIKE '%Payment done%'
               OR n.noti_message LIKE '%Package payment received%'
               OR n.noti_message LIKE '%A contractor has uploaded a new floor plan%'
               OR n.noti_message LIKE '%A contractor has uploaded a BQ file.%') 
          ORDER BY n.noti_date DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log("ERROR: Prepare failed - " . $conn->error);
    die("Error preparing the query.");
}

$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();

// Debug: Check the number of rows returned
$numRows = $result->num_rows;
?>

<div class="notification-header">
    <h2>Admin Notifications</h2>
    <p>Stay updated with client actions and BQ file uploads.</p>
</div>

<div class="notification-page">
    <div class="notification-list">
        <?php if ($numRows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="notification-item <?= $row['noti_readme'] ? 'read' : 'unread'; ?>">
                    <div class="noti-content">
                        <div class="noti-message">
                            <p class="notification-message"><?= htmlspecialchars($row['noti_message']); ?></p>

                            <!-- Show Download Floor Plan if available -->
                            <?php if (!empty($row['fp_file'])): ?>
                                <p><strong>Floor Plan: </strong><a href="<?= htmlspecialchars($row['fp_file']); ?>" target="_blank">View File</a></p>
                            <?php endif; ?>

                            <?php if (!empty($row['bq_file'])): ?>
                                <p><strong>Bill of Quantity (BQ): </strong><a href="<?= htmlspecialchars($row['bq_file']); ?>" target="_blank">View File</a></p>
                            <?php endif; ?>

                            <!-- Show Receipt if available -->
                            <?php if (!empty($row['receipt_file'])): ?>
                                <p><strong>Receipt: </strong><a href="<?= htmlspecialchars($row['receipt_file']); ?>" target="_blank">View File</a></p>
                            <?php endif; ?>
                        </div>

                        <div class="noti-date">
                            <span class="notification-time"><?= date("F j, Y, g:i a", strtotime($row['noti_date'])); ?></span>
                        </div>
                    </div>

                    <div class="noti-action">
                        <?php if (!$row['noti_readme']): ?>
                            <button class="action-btn mark-read-btn" data-id="<?= $row['noti_id']; ?>">
                                <i class="fas fa-check-circle"></i> Mark as Read
                            </button>
                        <?php else: ?>
                            <button class="action-btn read-btn" disabled>Read</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No notifications found.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    document.querySelectorAll(".mark-read-btn").forEach(button => {
        button.addEventListener("click", () => {
            const notiId = button.getAttribute("data-id");

            fetch("handle-admin-notification-action.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `noti_id=${notiId}&action=mark_read`
            })
            .then(response => response.text())
            .then(data => {
                alert(data);
                button.textContent = "Read";
                button.classList.remove("mark-read-btn");
                button.classList.add("read-btn");
                button.disabled = true;
            })
            .catch(error => console.error('Error:', error));
        });
    });
</script>

<style>
    /* Container and Header */
    .notification-header {
        text-align: center;
        margin: 20px 0;
        color: #333;
        background-color: #b2444f;
        padding-top: 35px;
        padding-bottom: 10px;
    }

    .notification-header h2 {
        font-size: 2em;
        font-weight: bold;
        color: white;
    }

    .notification-header p {
        font-size: 1.1em;
        color: rgb(195, 202, 202);
    }

    .notification-page {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
        background-color: #f9fafb;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .notification-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .notification-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-radius: 8px;
        background-color: #ffffff;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .notification-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }

    .notification-item.unread {
        background-color: #eaf6ff;
        font-weight: bold;
    }

    .notification-item.read {
        background-color: #f8f8f8;
    }

    .noti-content {
        flex-grow: 1;
    }

    .notification-message {
        font-size: 1.1em;
        color: #34495e;
        margin-bottom: 10px;
    }

    .notification-time {
        font-size: 0.9em;
        color: #95a5a6;
    }

    .noti-action {
        display: flex;
        gap: 10px;
    }

    .action-btn {
        padding: 8px 15px;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        font-size: 1em;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .mark-read-btn {
        background-color: #28a745;
        color: white;
    }

    .mark-read-btn:hover {
        background-color: #218838;
    }

    .read-btn {
        background-color: grey !important;
        color: white;
        cursor: not-allowed;
    }
</style>