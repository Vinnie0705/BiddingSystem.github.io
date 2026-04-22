<?php
include("includes/db-connection.php");
include("includes/client-header.php");

// Fetch notifications and associated files for the logged-in client
$client_id = $_SESSION['user_id'];
$query = "
    SELECT n.*, b.bid_bq_file, n.fp_file, n.bq_file, b.bid_id
    FROM notification n
    LEFT JOIN bid b ON n.bid_id = b.bid_id
    WHERE n.client_id = '$client_id'
    ORDER BY n.noti_date DESC
";
$result = mysqli_query($conn, $query);

// Function to get the latest floor plan file for a specific bid_id
function getLatestFloorPlanForBid($conn, $bid_id)
{
    if (!$bid_id)
        return null;

    $query = "
        SELECT fp_file
        FROM notification 
        WHERE bid_id = ? AND fp_file IS NOT NULL AND admin_id IS NOT NULL
        ORDER BY noti_date DESC 
        LIMIT 1
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $bid_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['fp_file'] : null;
}

function getLatestBQForPackage($conn, $cp_id)
{
    if (!$cp_id)
        return null;

    $query = "
        SELECT fp_file
        FROM notification 
        WHERE bid_id = ? AND fp_file IS NOT NULL AND admin_id IS NOT NULL
        ORDER BY noti_date DESC 
        LIMIT 1
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $cp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['fp_file'] : null;
}
?>

<div class="notification-header">
    <h2>Notifications</h2>
    <p>Stay up to date with the latest updates and actions required for your projects.</p>
</div>

<div class="notification-page">
    <div class="notification-list">
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <div class="notification-item <?= $row['noti_readme'] ? 'read' : 'unread'; ?>">
                <div class="noti-content">
                    <div class="noti-message">
                        <p class="notification-message"><?= htmlspecialchars($row['noti_message']); ?></p>
                    </div>
                    <div class="noti-date">
                        <span class="notification-time"><?= date("F j, Y, g:i a", strtotime($row['noti_date'])); ?></span>
                    </div>
                </div>
                <div class="noti-action">
                    <?php
                    // Directly use the file paths from the database
                    $fileUrl = $row['bid_bq_file'];
                    $fileUrl2 = $row['fp_file'];
                    $fileUrl3 = $row['fp_file'];
                    $fileUrl4 = $row['bq_file'];
                    $bid_id = $row['bid_id'];

                    // Get the latest floor plan file for this bid if not present in the current notification
                    if (empty($fileUrl2) && strpos($row['noti_message'], "floor plan for project") !== false) {
                        $fileUrl2 = getLatestFloorPlanForBid($conn, $bid_id);
                    }

                    if (empty($fileUrl4) && strpos($row['noti_message'], "contractor has sent") !== false) {
                        $fileUrl2 = getLatestBQForPackage($conn, $cp_id);
                    }

                    // Display buttons based on notification message
                    if (strpos($row['noti_message'], 'new bid') !== false) {
                        echo '<button class="action-btn view-file-btn" data-file="' . htmlspecialchars($fileUrl) . '"><i class="fas fa-eye"></i> View File</button>';
                        echo '<button class="action-btn approve-btn" data-id="' . $row['noti_id'] . '"><i class="fas fa-check-circle"></i> Approve</button>';
                        echo '<button class="action-btn reject-btn" data-id="' . $row['noti_id'] . '"><i class="fas fa-times-circle"></i> Reject</button>';
                    } elseif (strpos($row['noti_message'], '10%') !== false) {
                        echo '<button class="action-btn complete-payment-btn-10" data-id="' . $row['noti_id'] . '"><i class="fas fa-credit-card"></i> Complete Payment</button>';
                    } elseif (strpos($row['noti_message'], 'floor plan for project') !== false) {
                        echo '<button class="action-btn view-file-btn" data-file="' . htmlspecialchars($fileUrl2) . '"><i class="fas fa-eye"></i> View File</button>';
                        echo '<button class="action-btn approve-btn" data-id="' . $row['noti_id'] . '"><i class="fas fa-check-circle"></i> Approve</button>';
                        echo '<button class="action-btn reject-btn" data-id="' . $row['noti_id'] . '"><i class="fas fa-times-circle"></i> Reject</button>';
                    } elseif (strpos($row['noti_message'], '30%') !== false) {
                        echo '<button class="action-btn complete-payment-btn-30" data-id="' . $row['noti_id'] . '"><i class="fas fa-credit-card"></i> Complete Payment</button>';
                    } elseif (strpos($row['noti_message'], 'floor plan for package') !== false) {
                        echo '<button class="action-btn view-file-btn" data-file="' . htmlspecialchars($fileUrl3) . '"><i class="fas fa-eye"></i> View File</button>';
                        echo '<button class="action-btn approve-btn" data-id="' . $row['noti_id'] . '"><i class="fas fa-check-circle"></i> Approve</button>';
                        echo '<button class="action-btn reject-btn" data-id="' . $row['noti_id'] . '"><i class="fas fa-times-circle"></i> Reject</button>';
                    } elseif (strpos($row['noti_message'], 'contractor has sent') !== false) {
                        echo '<button class="action-btn view-file-btn" data-file="' . htmlspecialchars($fileUrl4) . '"><i class="fas fa-eye"></i> View File</button>';
                        echo '<button class="action-btn approve-btn" data-id="' . $row['noti_id'] . '"><i class="fas fa-check-circle"></i> Approve</button>';
                        echo '<button class="action-btn reject-btn" data-id="' . $row['noti_id'] . '"><i class="fas fa-times-circle"></i> Reject</button>';
                    }
                    ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
    // Handle button clicks for "View File"
    document.querySelectorAll(".view-file-btn").forEach(button => {
        button.addEventListener("click", () => {
            const fileUrl = button.getAttribute("data-file");
            if (fileUrl) {
                // Open the file in a new tab
                window.open(fileUrl, "_blank");
            } else {
                alert("File not found.");
            }
        });
    });

    // Handle button clicks for actions: approve, reject, complete_payment
    document.querySelectorAll(".approve-btn, .reject-btn").forEach(button => {
        button.addEventListener("click", () => {
            const notiId = button.getAttribute("data-id");
            const action = button.classList.contains("approve-btn") ? 'approve' : 'reject';

            if (action === 'approve') {
                const notificationMessage = button.closest('.notification-item').querySelector('.notification-message').innerText;
                const confirmMessage = notificationMessage.includes('floor plan')
                    ? "Confirm to approve the floor plan?"
                    : "Confirm to approve the Bill of Quantity (BQ)?";

                if (confirm(confirmMessage)) {
                    fetch("handle-notification-action.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `noti_id=${notiId}&action=${action}`
                    })
                        .then(response => response.text())
                        .then(data => {
                            alert(data);
                            const notificationItem = button.closest('.notification-item');
                            notificationItem.classList.add('read');
                            button.disabled = true;
                            location.reload(); // Reload to reflect the updated notification message
                        })
                        .catch(error => console.error('Error:', error));
                }
            } else if (action === 'reject') {
                const reason = prompt("Please enter the reason for rejection:");
                if (reason !== null) {
                    fetch("handle-notification-action.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `noti_id=${notiId}&action=${action}&rejection_reason=${encodeURIComponent(reason)}`
                    })
                        .then(response => response.text())
                        .then(data => {
                            alert(data);
                            const notificationItem = button.closest('.notification-item');
                            notificationItem.classList.add('read');
                            button.disabled = true;
                            location.reload(); // Reload to reflect the updated notification message
                        })
                        .catch(error => console.error('Error:', error));
                }
            }
        });
    });

    // Redirect to collect-payment-10.php on "Complete Payment" button click
    document.querySelectorAll(".complete-payment-btn-10").forEach(button => {
        button.addEventListener("click", () => {
            const notiId = button.getAttribute("data-id");
            window.location.href = `collect-payment-10.php?noti_id=${notiId}`;
        });
    });

    document.querySelectorAll(".complete-payment-btn-30").forEach(button => {
        button.addEventListener("click", () => {
            const notiId = button.getAttribute("data-id");
            window.location.href = `collect-payment-30.php?noti_id=${notiId}`;
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
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .action-btn i {
        font-size: 1.2em;
    }

    .view-file-btn {
        background-color: #28a745;
        color: white;
    }

    .approve-btn {
        background-color: #17a2b8;
        color: white;
    }

    .reject-btn {
        background-color: #dc3545;
        color: white;
    }

    .complete-payment-btn {
        background-color: #ffc107;
        color: white;
    }

    .action-btn:hover {
        transform: translateY(-3px);
    }

    .action-btn:focus {
        outline: none;
    }

    .view-file-btn:hover {
        background-color: #218838;
    }

    .approve-btn:hover {
        background-color: #138496;
    }

    .reject-btn:hover {
        background-color: #c82333;
    }

    .complete-payment-btn:hover {
        background-color: #e0a800;
    }
</style>