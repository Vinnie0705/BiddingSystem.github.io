<?php
include("includes/db-connection.php");

// Enable detailed error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Function to send notifications and emails.
 */
function sendNotification($conn, $message, $client_id = null, $cont_id = null, $admin_id = null, $bid_id = null, $ten_price = null, $thirty_price = null, $fp_file = null)
{
    error_log("DEBUG: sendNotification() called with message: '$message', client_id: " . ($client_id ?? 'NULL') . ", cont_id: " . ($cont_id ?? 'NULL') . ", admin_id: " . ($admin_id ?? 'NULL') . ", bid_id: " . ($bid_id ?? 'NULL') . ", ten_price: " . ($ten_price ?? 'NULL') . ", thirty_price: " . ($thirty_price ?? 'NULL') . ", fp_file: " . ($fp_file ?? 'NULL'));

    if ($client_id === null && $cont_id === null && $admin_id === null) {
        error_log("Notification error: client_id, cont_id, and admin_id are NULL. No notification sent.");
        return false;
    }

    // Insert notification into the database
    $query = "INSERT INTO notification (noti_message, noti_readme, noti_date, client_id, cont_id, admin_id, bid_id, ten_price, thirty_price, fp_file) 
              VALUES (?, 0, NOW(), ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("MySQL Prepare Error (INSERT): " . $conn->error);
        return false;
    }
    $stmt->bind_param("siiiidds", $message, $client_id, $cont_id, $admin_id, $bid_id, $ten_price, $thirty_price, $fp_file);

    if (!$stmt->execute()) {
        error_log("MySQL Execute Error: " . $stmt->error);
        return false;
    }

    // Send email notification
    sendEmailNotification($conn, $message, $client_id, $cont_id, $admin_id, $bid_id, $fp_file);

    $stmt->close();
    return true;
}

/**
 * Function to send email notifications using PHPMailer.
 */
function sendEmailNotification($conn, $message, $client_id = null, $cont_id = null, $admin_id = null, $bid_id = null, $fp_file = null)
{
    require 'vendor/autoload.php'; // Ensure PHPMailer is included

    $mail = new PHPMailer\PHPMailer\PHPMailer(true); // Enable exceptions

    try {
        // Server settings
        $mail->SMTPDebug = 2; // Enable verbose debug output (for testing, set to 0 in production)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tan.vinnie@ypccollege.edu.my'; // Replace with your SMTP username
        $mail->Password = 'sjup ozfk ylcb etpx'; // Replace with your SMTP password or app-specific password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Determine recipient based on available IDs
        $recipient_email = null;
        $recipient_name = "User";

        if ($client_id) {
            $query = "SELECT user_email, user_name FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $client_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $recipient_email = $user['user_email'];
                $recipient_name = $user['user_name'];
            }
        } elseif ($cont_id) {
            $query = "SELECT user_email, user_name FROM users WHERE user_id = ?"; // Assuming contractors are in the users table
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $cont_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $recipient_email = $user['user_email'];
                $recipient_name = $user['user_name'];
            }
        } elseif ($admin_id) {
            $query = "SELECT user_email, user_name FROM users WHERE user_id = ?"; // Assuming admins are in the users table
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $recipient_email = $user['user_email'];
                $recipient_name = $user['user_name'];
            }
        }

        if (!$recipient_email) {
            error_log("No email address found for client_id: $client_id, cont_id: $cont_id, or admin_id: $admin_id");
            return false;
        }

        // Recipients
        $mail->setFrom('no-reply@yourdomain.com', 'GYS Notification');
        $mail->addAddress($recipient_email, $recipient_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New Notification from GYS';
        $mailBody = "Dear $recipient_name,<br><br>You have a new notification:<br><br>$message<br><br>";
        if ($fp_file) {
            $mailBody .= "Floor plan file: <a href='http://localhost/GYS%20Resources/uploads/floor_plans/$fp_file'>Download Floor Plan</a><br>";
        }
        $mailBody .= "Regards,<br>GYS Team";
        $mail->Body = $mailBody;

        // Send the email
        $mail->send();
        error_log("Email notification sent successfully to $recipient_email with message: $message");
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bidId = $_POST['bid_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if (!$bidId || !$action) {
        die("Error: Missing bid ID or action.");
    }

    // Fetch bid details and project title
    $bidQuery = "SELECT b.client_id, b.cont_id, p.proj_title 
                 FROM bid b
                 JOIN project p ON b.proj_id = p.proj_id
                 WHERE b.bid_id = ?";
    $stmt = $conn->prepare($bidQuery);
    $stmt->bind_param("i", $bidId);
    $stmt->execute();
    $result = $stmt->get_result();
    $bid = $result->fetch_assoc();
    $stmt->close();

    if (!$bid) {
        die("Error: Bid not found.");
    }

    $client_id = $bid['client_id'] ?? null;
    $cont_id = $bid['cont_id'] ?? null;
    $proj_title = $bid['proj_title'] ?? "Unknown Project"; // Get project title
    $adminId = 1; // Replace with the actual admin ID or fetch from session (e.g., $_SESSION['admin_id'])

    error_log("DEBUG: Handling action '$action' for bid ID $bidId - Client ID: " . ($client_id ?? 'NULL') . ", Contractor ID: " . ($cont_id ?? 'NULL'));

    switch ($action) {
        case 'approve':
            $query = "UPDATE bid SET bid_status='approved' WHERE bid_id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $bidId);
            if ($stmt->execute()) {
                // Notify the client
                sendNotification($conn, "Your project '$proj_title' bid has been approved. Please review the Bill of Quantity (BQ).", $client_id, null, null, $bidId);

                // Notify the contractor
                sendNotification($conn, "Your bid for project '$proj_title' has been approved by the admin. Please wait for client approval.", null, $cont_id, null, $bidId);

                // Notify the admin
                sendNotification($conn, "The bid for project '$proj_title' has been approved. Awaiting client action.", null, null, $adminId, $bidId);

                echo "Bid approved successfully.";
            } else {
                echo "Error approving bid: " . $stmt->error;
            }
            $stmt->close();
            break;

        case 'reject':
            $rejectionReason = $_POST['reason'] ?? '';
            if (empty($rejectionReason)) {
                die("Error: Rejection reason is required.");
            }
            $query = "UPDATE bid SET bid_status='rejected', rejection_reason=? WHERE bid_id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $rejectionReason, $bidId);
            if ($stmt->execute()) {
                sendNotification($conn, "Your bid for project '$proj_title' has been rejected. Reason: " . $rejectionReason, null, $cont_id, null, $bidId);
                echo "Bid rejected successfully.";
            } else {
                echo "Error rejecting bid: " . $stmt->error;
            }
            $stmt->close();
            break;

        case 'request_floor_plan':
            sendNotification($conn, "A request for the floor plan has been made for project '$proj_title'. Please upload it.", null, $cont_id, null, $bidId);
            echo "Floor plan request sent.";
            break;

        case 'send_floor_plan':
            // Fetch the latest floor plan file sent to the admin for this bid
            $fpFileQuery = "SELECT fp_file 
                            FROM notification 
                            WHERE bid_id = ? AND admin_id IS NOT NULL AND fp_file IS NOT NULL 
                            ORDER BY noti_date DESC 
                            LIMIT 1";
            $stmt = $conn->prepare($fpFileQuery);
            $stmt->bind_param("i", $bidId);
            $stmt->execute();
            $fpResult = $stmt->get_result();
            $fpRow = $fpResult->fetch_assoc();
            $fp_file = $fpRow ? $fpRow['fp_file'] : null;
            $stmt->close();

            // Notify the client with the retrieved floor plan file
            if ($fp_file) {
                if (sendNotification($conn, "The floor plan for project '$proj_title' is now available. Please review.", $client_id, null, null, $bidId, null, null, $fp_file)) {
                    echo "Floor plan notification sent to client with file.";
                } else {
                    echo "Failed to send floor plan notification. Check error logs.";
                }
            } else {
                sendNotification($conn, "The floor plan for project '$proj_title' is now available. Please review.", $client_id, null, null, $bidId);
            }
            break;

        case 'collect_10_percent':
            $amount = $_POST['amount'] ?? null;

            if (!$amount || !is_numeric($amount) || floatval($amount) <= 0) {
                die("Error: Invalid payment amount.");
            }

            $tenPrice = floatval($amount);

            // Send notification for 10% payment
            if (sendNotification($conn, "Payment request for RM $tenPrice (10%) needs to be collected to proceed with the project '$proj_title'.", $client_id, $cont_id, null, $bidId, $tenPrice, null)) {
                echo "Payment request for RM $tenPrice (10%) recorded.";
            } else {
                echo "Failed to record payment request. Check error logs.";
            }
            break;

        case 'collect_30_percent':
            $amount = $_POST['amount'] ?? null;

            if (!$amount || !is_numeric($amount) || floatval($amount) <= 0) {
                die("Error: Invalid payment amount.");
            }

            $thirtyPrice = floatval($amount);

            // Send notification for 30% payment
            if (sendNotification($conn, "Payment request for RM $thirtyPrice (30%) needs to be collected to proceed with the project '$proj_title'.", $client_id, $cont_id, null, $bidId, null, $thirtyPrice)) {
                echo "Payment request for RM $thirtyPrice (30%) recorded.";
            } else {
                echo "Failed to record payment request. Check error logs.";
            }
            break;

        case 'upload_bq':
            // Handle BQ file upload logic here
            // Notify the admin
            sendNotification($conn, "The contractor has uploaded the BQ for project '$proj_title'. Please review.", null, null, $adminId, $bidId);
            echo "BQ uploaded successfully.";
            break;

        case 'upload_floor_plan':
            // Handle floor plan file upload logic here
            $fp_file = $_POST['fp_file'] ?? null; // Get the file path from the form
            if (!$fp_file) {
                die("Error: Floor plan file path is required.");
            }

            // Notify the admin
            if (sendNotification($conn, "The contractor has uploaded the floor plan for project '$proj_title'. Please review.", null, null, $adminId, $bidId, null, null, $fp_file)) {
                echo "Floor plan uploaded successfully.";
            } else {
                echo "Failed to send floor plan notification. Check error logs.";
            }
            break;

        default:
            die("Error: Invalid action.");
    }

    exit();
}

// Fetch all bids for display
$query = "SELECT bid_id, bid_name, bid_email, bid_price, bid_date, bid_status, bid_bq_file, bid_images, proj_id FROM bid";
$result = mysqli_query($conn, $query);
?>

<!-- HTML for displaying bid management table -->
<div class="bid-management">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Bid Name</th>
                    <th>Email</th>
                    <th>Date</th>
                    <th>Project ID</th>
                    <th>Bid BQ File</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['bid_name']); ?></td>
                        <td><?= htmlspecialchars($row['bid_email']); ?></td>
                        <td><?= htmlspecialchars($row['bid_date']); ?></td>
                        <td><?= htmlspecialchars($row['proj_id']); ?></td>
                        <td>
                            <?php if (!empty($row['bid_bq_file'])): ?>
                                <a href="<?= htmlspecialchars($row['bid_bq_file']); ?>" target="_blank" class="bqfile-link"><i class="fas fa-file-pdf"></i> View PDF</a>
                            <?php else: ?>
                                <span style="color: red;">No File</span>
                            <?php endif; ?>
                        </td>
                        <td class="status-cell <?= strtolower($row['bid_status']); ?>">
                            <?= htmlspecialchars($row['bid_status']); ?>
                        </td>
                        <td>
                            <div class="action-dropdown">
                                <button class="action-btn">
                                    Actions <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="dropdown-content" style="position: absolute; top: 100%; right: 0; transform: translateY(-10px); z-index: 1002;">
                                    <button class="btn-approve-bid" data-id="<?= $row['bid_id']; ?>">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="btn-reject-bid" data-id="<?= $row['bid_id']; ?>">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                    <button class="btn-collect-payment" data-id="<?= $row['bid_id']; ?>">
                                        <i class="fas fa-money-bill"></i> Collect 10%
                                    </button>
                                    <button class="btn-request-floor-plan" data-id="<?= $row['bid_id']; ?>">
                                        <i class="fas fa-file-alt"></i> Request Floor Plan
                                    </button>
                                    <button class="btn-send-floor-plan" data-id="<?= $row['bid_id']; ?>">
                                        <i class="fas fa-share"></i> Send Floor Plan
                                    </button>
                                    <button class="btn-collect-30-percent" data-id="<?= $row['bid_id']; ?>">
                                        <i class="fas fa-money-check"></i> Collect 30%
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- JavaScript code for handling actions -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Ensure event listeners are added only once
        if (!window.eventListenersAdded) {
            document.querySelectorAll(".btn-approve-bid, .btn-reject-bid, .btn-collect-payment, .btn-request-floor-plan, .btn-collect-30-percent, .btn-send-floor-plan").forEach(button => {
                button.addEventListener("click", function (e) {
                    e.preventDefault(); // Prevent default behavior if any
                    const action = getActionFromClass(this.classList);
                    const bidId = this.getAttribute("data-id");
                    const row = this.closest("tr");

                    // Disable the button to prevent multiple clicks
                    this.disabled = true;

                    let requestBody = `action=${action}&bid_id=${bidId}`;

                    if (action === "reject") {
                        const rejectionReason = prompt("Enter the rejection reason:");
                        if (!rejectionReason) {
                            alert("Rejection reason is required.");
                            this.disabled = false;
                            return;
                        }
                        requestBody += `&reason=${encodeURIComponent(rejectionReason)}`;
                    } else if (action === "collect_10_percent" || action === "collect_30_percent") {
                        // Ensure prompt is only shown once by using a flag
                        if (window.paymentPromptShown) return;
                        window.paymentPromptShown = true;

                        const amount = prompt("Enter the payment amount (RM):");
                        window.paymentPromptShown = false; // Reset flag after prompt

                        if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
                            alert("Invalid payment amount. Please enter a valid number.");
                            this.disabled = false;
                            return;
                        }
                        requestBody += `&amount=${amount}`;
                    } else if (action === "send_floor_plan") {
                        const confirmSend = confirm("Confirm to send the floor plan to client?");
                        if (!confirmSend) {
                            this.disabled = false; // Re-enable the button if the user cancels
                            return;
                        }
                    }

                    fetch('manage-bid.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: requestBody
                    })
                        .then(response => response.text())
                        .then(data => {
                            // Update the status in the table row
                            const statusCell = row.querySelector("td:nth-child(6)");
                            if (statusCell) {
                                statusCell.textContent = getSuccessMessage(action);
                                statusCell.style.color = action === "approve" ? "green" : action === "reject" ? "red" : "blue";
                            }

                            // Update the button text and style
                            this.innerText = getSuccessMessage(action);
                            this.style.backgroundColor = "#6c757d";
                            this.style.cursor = "not-allowed";

                            // Show a success message
                            alert(data);
                        })
                        .catch(error => {
                            alert("Error: " + error);
                            this.disabled = false; // Re-enable the button if there's an error
                        })
                        .finally(() => {
                            this.disabled = false; // Re-enable the button after the request completes, regardless of success or failure
                        });
                });
            });

            window.eventListenersAdded = true; // Flag to ensure listeners are added only once
        }

        function getActionFromClass(classList) {
            if (classList.contains("btn-approve-bid")) return "approve";
            if (classList.contains("btn-reject-bid")) return "reject";
            if (classList.contains("btn-collect-payment")) return "collect_10_percent";
            if (classList.contains("btn-request-floor-plan")) return "request_floor_plan";
            if (classList.contains("btn-send-floor-plan")) return "send_floor_plan";
            if (classList.contains("btn-collect-30-percent")) return "collect_30_percent";
            return null;
        }

        function getSuccessMessage(action) {
            const messages = {
                "approve": "Approved",
                "reject": "Rejected",
                "collect_10_percent": "10% Payment Collected",
                "request_floor_plan": "Floor Plan Requested",
                "send_floor_plan": "Floor Plan Sent",
                "collect_30_percent": "30% Payment Collected"
            };
            return messages[action] || "Action Completed";
        }
    });
</script>

<style>
    .bid-management {
        padding: 20px;
        background: transparent;
        border-radius: 0;
        box-shadow: none;
    }

    .table-container {
        overflow-x: auto;
        margin: 20px 0;
        background: white;
        border-radius: 8px;
        box-shadow: 0 0 15px rgba(0,0,0,0.05);
        position: relative;
        z-index: 1;
        min-height: 500px;
    }

    .table-container table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin: 0;
    }

    .table-container th,
    .table-container td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .table-container th {
        background-color: #f8f9fa;
        color: #495057;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }

    .table-container tr:hover {
        background-color: #f8f9fa;
    }

    /* Status badges */
    .status-cell {
        font-weight: 500;
        padding: 6px 12px !important;
        border-radius: 4px;
    }

    .status-cell.approved {
        background-color: #e6ffe6 !important;
        color: #008000 !important;
    }

    .status-cell.rejected {
        background-color: #ffe6e6 !important;
        color: #ff0000 !important;
    }

    .status-cell.pending {
        background-color: #fff3cd !important;
        color: #856404 !important;
    }

    /* Dropdown menu for actions */
    .action-dropdown {
        position: relative;
        display: inline-block;
    }

    .action-btn {
        background: #4a90e2;
        color: white;
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .action-btn:hover {
        background: #357abd;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background: white;
        min-width: 200px;
        max-height: 300px;
        overflow-y: auto;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-radius: 4px;
        z-index: 1002;
    }

    .dropdown-content button {
        display: block;
        width: 100%;
        padding: 10px 15px;
        text-align: left;
        border: none;
        background: none;
        cursor: pointer;
        color: #333;
        font-size: 0.9rem;
        transition: background 0.2s;
    }

    .dropdown-content button:hover {
        background: #f8f9fa;
    }

    .dropdown-content button i {
        margin-right: 8px;
        width: 16px;
    }

    /* Show dropdown on hover */
    .action-dropdown:hover .dropdown-content {
        display: block;
    }

    /* File link styling */
    .file-link {
        color: #4a90e2;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .file-link:hover {
        text-decoration: underline;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .table-container {
            margin: 10px -15px;
            border-radius: 0;
        }
        
        .table-container th,
        .table-container td {
            padding: 10px;
        }
    }

    .bqfile-link {
        color: #4a90e2;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .bqfile-link:hover {
        text-decoration: underline;
    }
</style>