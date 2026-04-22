<?php
include("includes/db-connection.php");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function sendNotification($conn, $message, $client_id = null, $cont_id = null, $admin_id = null, $cp_id = null, $bq_file = null, $ten_price = null, $thirty_price = null, $fp_file = null)
{
    // Check if cp_id exists in clientpackage before inserting
    if ($cp_id !== null) {
        $checkCpQuery = "SELECT cp_id FROM clientpackage WHERE cp_id = ?";
        $stmt = $conn->prepare($checkCpQuery);
        $stmt->bind_param("i", $cp_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            error_log("Error: cp_id $cp_id does not exist in clientpackage.");
            return false;
        }

        $stmt->close();
    }

    $query = "INSERT INTO notification (noti_message, noti_readme, noti_date, client_id, cont_id, admin_id, cp_id, bq_file, ten_price, thirty_price, fp_file) 
              VALUES (?, 0, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("siiissdds", $message, $client_id, $cont_id, $admin_id, $cp_id, $bq_file, $ten_price, $thirty_price, $fp_file);

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }

    // Send email notification
    sendEmailNotification($conn, $message, $client_id, $cont_id, $admin_id, $cp_id, $bq_file, $fp_file);

    $stmt->close();
    return true;
}

/**
 * Function to send email notifications using PHPMailer.
 */
function sendEmailNotification($conn, $message, $client_id = null, $cont_id = null, $admin_id = null, $cp_id = null, $bq_file = null, $fp_file = null)
{
    require 'vendor/autoload.php'; // Ensure PHPMailer is included

    $mail = new PHPMailer\PHPMailer\PHPMailer(true); // Enable exceptions

    try {
        // Server settings
        $mail->SMTPDebug = 2; // Enable verbose debug output (set to 0 in production)
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
            $query = "SELECT user_email, user_name FROM users WHERE user_id = ? AND user_type = 'client'";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $client_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $recipient_email = $user['user_email'];
                $recipient_name = $user['user_name'];
            }
            $stmt->close();
        } elseif ($cont_id) {
            $query = "SELECT user_email, user_name FROM users WHERE user_id = ? AND user_type = 'contractor'";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $cont_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $recipient_email = $user['user_email'];
                $recipient_name = $user['user_name'];
            }
            $stmt->close();
        } elseif ($admin_id) {
            $query = "SELECT user_email, user_name FROM users WHERE user_id = ? AND user_type = 'admin'";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $recipient_email = $user['user_email'];
                $recipient_name = $user['user_name'];
            }
            $stmt->close();
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
        if ($bq_file) {
            $mailBody .= "Bill of Quantity (BQ) file: <a href='http://localhost/GYS%20Resources/uploads/bq_files/$bq_file'>Download BQ File</a><br>";
        }
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
    $cpId = $_POST['cp_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if (!$cpId || !$action) {
        die(json_encode(["error" => "Missing package ID or action."]));
    }

    // Fetch client_id, cont_id, and package_title from clientpackage and package tables
    $cpQuery = "
        SELECT cp.client_id, cp.cont_id, p.package_title 
        FROM clientpackage cp
        JOIN package p ON cp.package_id = p.package_id
        WHERE cp.cp_id = ?
    ";
    $stmt = $conn->prepare($cpQuery);
    $stmt->bind_param("i", $cpId);
    $stmt->execute();
    $result = $stmt->get_result();
    $cp = $result->fetch_assoc();
    $stmt->close();

    if (!$cp) {
        die(json_encode(["error" => "Package not found."]));
    }

    $client_id = $cp['client_id'] ?? null;
    $cont_id = $cp['cont_id'] ?? null;
    $package_title = $cp['package_title'] ?? "Unknown Package"; // Fetch package title
    $admin_id = 1; // Replace with actual admin ID or fetch from session (e.g., $_SESSION['admin_id'])

    switch ($action) {
        case 'approve':
            $query = "UPDATE clientpackage SET package_status='approved' WHERE cp_id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $cpId);
            if ($stmt->execute()) {
                if (sendNotification($conn, "There is a new purchase on your package '$package_title'. Please upload the Bill of Quantity (BQ).", null, $cont_id, null, $cpId, null)) {
                    sendNotification($conn, "Your purchase for package '$package_title' has been approved. Awaiting contractor's BQ.", $client_id, null, null, $cpId);
                    sendNotification($conn, "A new package purchase for '$package_title' has been approved.", null, null, $admin_id, $cpId);
                    echo "Purchase approved successfully. Notifications sent.";
                } else {
                    echo "Purchase approved, but notification failed.";
                }
            } else {
                echo "Error approving purchase: " . $stmt->error;
            }
            $stmt->close();
            break;

        case 'reject':
            $rejectionReason = $_POST['reason'] ?? '';
            if (empty($rejectionReason)) {
                die("Error: Rejection reason is required.");
            }
            $query = "UPDATE clientpackage SET package_status='rejected', rejection_reason=? WHERE cp_id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $rejectionReason, $cpId);
            if ($stmt->execute()) {
                sendNotification($conn, "Your package purchase for '$package_title' has been rejected. Reason: " . $rejectionReason, $client_id, null, null, $cpId, null);
                sendNotification($conn, "Package purchase for '$package_title' has been rejected by admin.", null, $cont_id, null, $cpId);
                sendNotification($conn, "Package purchase for '$package_title' has been rejected.", null, null, $admin_id, $cpId);
                echo "Package purchase rejected successfully.";
            } else {
                echo "Error rejecting package purchase: " . $stmt->error;
            }
            $stmt->close();
            break;

        case 'send_bq_file':
            // Fetch the previous BQ file from the notification table
            $fetchBqQuery = "SELECT bq_file FROM notification WHERE cp_id = ? AND bq_file IS NOT NULL ORDER BY noti_date DESC LIMIT 1";
            $stmt = $conn->prepare($fetchBqQuery);
            $stmt->bind_param("i", $cpId);
            $stmt->execute();
            $result = $stmt->get_result();
            $bqFileRow = $result->fetch_assoc();
            $stmt->close();

            $bqFile = $bqFileRow['bq_file'] ?? null;

            if ($bqFile) {
                // Send notification with the previous BQ file
                if (sendNotification($conn, "The contractor has sent the BQ file for package '$package_title'. Please review.", $client_id, null, null, $cpId, $bqFile)) {
                    sendNotification($conn, "The BQ file for package '$package_title' has been sent to the client.", null, $cont_id, null, $cpId);
                    sendNotification($conn, "The contractor has sent the BQ file for package '$package_title'.", null, null, $admin_id, $cpId);
                    echo "BQ file sent successfully.";
                } else {
                    echo "Failed to send BQ file notification.";
                }
            } else {
                echo "Error: No BQ file found in previous notifications.";
            }
            break;

        case 'request_floor_plan':
            sendNotification($conn, "A request for the floor plan has been made for package '$package_title'. Please upload it.", null, $cont_id, null, $cpId, null);
            sendNotification($conn, "A floor plan has been requested for your package '$package_title'.", $client_id, null, null, $cpId);
            sendNotification($conn, "A floor plan request has been made for package '$package_title'.", null, null, $admin_id, $cpId);
            echo "Floor plan request sent.";
            break;

        case 'send_floor_plan':
            // Fetch the previous floor plan file from the notification table
            $fetchFpQuery = "SELECT fp_file FROM notification WHERE cp_id = ? AND fp_file IS NOT NULL ORDER BY noti_date DESC LIMIT 1";
            $stmt = $conn->prepare($fetchFpQuery);
            $stmt->bind_param("i", $cpId);
            $stmt->execute();
            $result = $stmt->get_result();
            $fpFileRow = $result->fetch_assoc();
            $stmt->close();

            $fpFile = $fpFileRow['fp_file'] ?? null;

            if ($fpFile) {
                // Send notification with the previous floor plan file
                if (sendNotification($conn, "The floor plan for package '$package_title' is now available. Please review.", $client_id, null, null, $cpId, null, null, null, $fpFile)) {
                    sendNotification($conn, "The floor plan for package '$package_title' has been sent to the client.", null, $cont_id, null, $cpId);
                    sendNotification($conn, "The floor plan for package '$package_title' has been sent to the client.", null, null, $admin_id, $cpId);
                    echo "Floor plan notification sent to client.";
                } else {
                    echo "Failed to send floor plan notification. Check error logs.";
                }
            } else {
                echo "Error: No floor plan file found in previous notifications.";
            }
            break;

        case 'collect_10_percent':
            $amount = $_POST['amount'] ?? null;

            if (!$amount || !is_numeric($amount) || floatval($amount) <= 0) {
                die("Error: Invalid payment amount.");
            }

            $tenPrice = floatval($amount);

            // Send notification for 10% payment
            if (sendNotification($conn, "Payment request for RM" . $tenPrice . " (10%) needs to be collected to proceed with package '$package_title'.", $client_id, $cont_id, null, $cpId, null, $tenPrice, null)) {
                sendNotification($conn, "A 10% payment request of RM $tenPrice has been made for package '$package_title'.", null, $cont_id, null, $cpId);
                sendNotification($conn, "A 10% payment request of RM $tenPrice has been made for package '$package_title'.", null, null, $admin_id, $cpId);
                echo "Payment request for RM" . $tenPrice . " (10%) recorded.";
            } else {
                echo "Failed to record payment request.";
            }
            break;

        case 'collect_30_percent':
            $amount = $_POST['amount'] ?? null;

            if (!$amount || !is_numeric($amount) || floatval($amount) <= 0) {
                die("Error: Invalid payment amount.");
            }

            $thirtyPrice = floatval($amount);

            // Send notification for 30% payment
            if (sendNotification($conn, "Payment request for RM" . $thirtyPrice . " (30%) needs to be collected to proceed with package '$package_title'.", $client_id, $cont_id, null, $cpId, null, null, $thirtyPrice)) {
                sendNotification($conn, "A 30% payment request of RM $thirtyPrice has been made for package '$package_title'.", null, $cont_id, null, $cpId);
                sendNotification($conn, "A 30% payment request of RM $thirtyPrice has been made for package '$package_title'.", null, null, $admin_id, $cpId);
                echo "Payment request for RM" . $thirtyPrice . " (30%) recorded.";
            } else {
                echo "Failed to record payment request.";
            }
            break;

        default:
            die("Error: Invalid action.");
    }
    exit();
}

// Fetch package purchases with client and contractor names
$query = "SELECT cp.*, 
                 u1.user_name AS client_name, 
                 u2.user_name AS cont_name,
                 p.package_id AS package_id
          FROM clientpackage cp
          LEFT JOIN users u1 ON cp.client_id = u1.user_id AND u1.user_type = 'client'
          LEFT JOIN users u2 ON cp.cont_id = u2.user_id AND u2.user_type = 'contractor'
          LEFT JOIN package p ON cp.package_id = p.package_id";  
$result = mysqli_query($conn, $query);
?>

<div class="purchase-management">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Contractor Name</th>
                    <th>Package ID</th>
                    <th>Images</th>
                    <th>Type</th>
                    <th>Size</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['client_name'] ?? 'N/A'); ?></td>
                        <td><?= htmlspecialchars($row['cont_name'] ?? 'N/A'); ?></td>
                        <td><?= htmlspecialchars($row['package_id'] ?? 'N/A'); ?></td>
                        <td>
                            <?php
                            if (!empty($row['cp_images'])) {
                                $imagePaths = json_decode($row['cp_images'], true);
                                if (is_array($imagePaths)) {
                                    foreach ($imagePaths as $imagePath) {
                                        echo '<img src="' . htmlspecialchars($imagePath) . '" alt="Package Image" width="100"
                                class="clickable-image" data-src="' . htmlspecialchars($imagePath) . '"
                                style="margin: 5px; border-radius: 5px; cursor: pointer;">';
                                    }
                                } else {
                                    echo "Invalid image data";
                                }
                            } else {
                                echo "No Image";
                            }
                            ?>
                        </td>
                        <td><?= htmlspecialchars($row['cp_type']); ?></td>
                        <td><?= htmlspecialchars($row['cp_size']); ?></td>
                        <td><?= htmlspecialchars($row['cp_startdate']); ?></td>
                        <td><?= htmlspecialchars($row['cp_enddate']); ?></td>
                        <td class="status-cell <?= strtolower($row['package_status']); ?>">
                            <?= htmlspecialchars($row['package_status']); ?>
                        </td>
                        <td>
                            <div class="action-dropdown">
                                <button class="action-btn">
                                    Actions <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="dropdown-content" style="position: absolute; top: 100%; right: 0; transform: translateY(-10px); z-index: 1002;">
                                    <button class="btn-approve-cp" data-id="<?= $row['cp_id']; ?>">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="btn-reject-cp" data-id="<?= $row['cp_id']; ?>">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                    <button class="btn-send-bq-file" data-id="<?= $row['cp_id']; ?>">
                                        <i class="fas fa-file-pdf"></i> Send BQ File
                                    </button>
                                    <button class="btn-collect-payment" data-id="<?= $row['cp_id']; ?>">
                                        <i class="fas fa-money-bill"></i> Collect 10%
                                    </button>
                                    <button class="btn-request-floor-plan" data-id="<?= $row['cp_id']; ?>">
                                        <i class="fas fa-file-alt"></i> Request Floor Plan
                                    </button>
                                    <button class="btn-send-floor-plan" data-id="<?= $row['cp_id']; ?>">
                                        <i class="fas fa-share"></i> Send Floor Plan
                                    </button>
                                    <button class="btn-collect-30-percent" data-id="<?= $row['cp_id']; ?>">
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

<!-- Modal for Image View -->
<div id="imageModal" class="modal">
    <div class="modal-content">
        <span class="close">×</span>
        <img id="modalImage" src="" alt="Package Image" style="width: 100%; max-width: 500px;">
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Handles all button actions in one event listener
        document.querySelectorAll(".btn-approve-cp, .btn-reject-cp, .btn-collect-payment, .btn-request-floor-plan, .btn-send-floor-plan, .btn-collect-30-percent, .btn-send-bq-file").forEach(button => {
            button.addEventListener("click", function () {
                let action = getActionFromClass(this.classList);
                let cpId = this.getAttribute("data-id") || this.dataset.cpId; // Support for both attributes
                let successMessage = getSuccessMessage(action);
                let requestBody = `action=${action}&cp_id=${cpId}`;

                if (!cpId) {
                    alert("Error: Client Package ID is missing.");
                    return;
                }

                // Handle special cases: payment, rejection, and confirmations
                if (action === "send_floor_plan" && !confirm("Are you sure you want to send the floor plan to the client?")) {
                    return;
                }
                else if (action === "send_bq_file" && !confirm("Are you sure you want to send the BQ file to the client?")) {
                    return;
                }
                else if (action === "collect_10_percent" || action === "collect_30_percent") {
                    let amount = prompt("Enter the payment amount (RM):");
                    if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
                        alert("Invalid payment amount. Please enter a valid number.");
                        return;
                    }
                    requestBody += `&amount=${amount}`;
                }
                else if (action === "reject") {
                    let rejectionReason = prompt("Enter the rejection reason:");
                    if (!rejectionReason) {
                        alert("Rejection reason is required.");
                        return;
                    }
                    requestBody += `&reason=${encodeURIComponent(rejectionReason)}`;
                }

                // Disable the button to prevent multiple clicks
                this.disabled = true;

                // Send AJAX request
                fetch("manage-packagepurchase.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: requestBody
                })
                    .then(response => {
                        if (!response.ok) throw new Error("Network response was not ok");
                        return response.text();
                    })
                    .then(data => {
                        alert(data);
                        this.innerText = successMessage;
                        this.style.backgroundColor = "#6c757d";
                        this.style.cursor = "not-allowed";
                    })
                    .catch(error => {
                        alert("Error: " + error.message);
                        this.disabled = false;
                    });
            });
        });

        function getActionFromClass(classList) {
            if (classList.contains("btn-approve-cp")) return "approve";
            if (classList.contains("btn-reject-cp")) return "reject";
            if (classList.contains("btn-collect-payment")) return "collect_10_percent";
            if (classList.contains("btn-request-floor-plan")) return "request_floor_plan";
            if (classList.contains("btn-send-floor-plan")) return "send_floor_plan";
            if (classList.contains("btn-collect-30-percent")) return "collect_30_percent";
            if (classList.contains("btn-send-bq-file")) return "send_bq_file";
            return null;
        }

        function getSuccessMessage(action) {
            const messages = {
                "approve": "Approved",
                "reject": "Rejected",
                "collect_10_percent": "10% Payment Collected",
                "request_floor_plan": "Floor Plan Requested",
                "send_floor_plan": "Floor Plan Sent",
                "collect_30_percent": "30% Payment Collected",
                "send_bq_file": "BQ File Sent"
            };
            return messages[action] || "Action Completed";
        }
    });

    // Get the modal
    const modal = document.getElementById("imageModal");
    const modalImg = document.getElementById("modalImage");
    const closeBtn = document.getElementsByClassName("close")[0];

    // Add click event to all clickable images
    document.querySelectorAll('.clickable-image').forEach(img => {
        img.addEventListener('click', function() {
            modal.style.display = "flex"; // Changed from "block" to "flex" for better centering
            modalImg.src = this.getAttribute('data-src');
        });
    });

    // Close the modal when clicking the × button
    closeBtn.addEventListener('click', function() {
        modal.style.display = "none";
    });

    // Close the modal when clicking outside the image
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            modal.style.display = "none";
        }
    });
</script>

<style>
    .purchase-management {
        padding: 20px;
        background: transparent;
        border-radius: 0;
        box-shadow: none;
    }

    .table-container {
        position: relative;
        overflow-x: auto;
        margin: 20px 0;
        background: white;
        border-radius: 8px;
        box-shadow: 0 0 15px rgba(0,0,0,0.05);
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
        position: relative;
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
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-radius: 4px;
        z-index: 9999;
        top: 100%;
        margin-top: 5px;
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

    .purchase-management .table-container td:nth-child(3) {
        max-width: 150px;
        overflow: hidden;
    }

    /* Style for images in the "Images" column */
    .purchase-management .table-container td:nth-child(3) img {
        width: 100px;
        height: auto;
        margin: 5px;
        border-radius: 5px;
        cursor: pointer;
        display: inline-block;
    }

    .purchase-management .table-container th,
    .purchase-management .table-container td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    .purchase-management .table-container th {
        background-color: #f4f4f4;
    }

    .btn-approve-cp {
        color: #fff;
        background-color: #28a745;
        border: none;
        padding: 5px 10px;
        cursor: pointer;
        border-radius: 4px;
    }

    .btn-reject-cp {
        color: #fff;
        background-color: #dc3545;
        border: none;
        padding: 5px 10px;
        cursor: pointer;
        border-radius: 4px;
    }

    .btn-approve-cp:hover,
    .btn-reject-cp:hover {
        opacity: 0.9;
    }

    .cp-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        max-width: 300px;
    }

    .cp-actions button {
        flex: 1 1 calc(33.33% - 10px);
        color: #fff;
        border: none;
        padding: 5px 10px;
        cursor: pointer;
        border-radius: 4px;
        text-align: center;
    }

    .btn-approve-cp {
        background-color: #28a745;
    }

    .btn-reject-cp {
        background-color: #dc3545;
    }

    .btn-collect-payment {
        background-color: #007bff;
    }

    .btn-request-floor-plan {
        background-color: #ff9800;
    }

    .btn-send-floor-plan {
        background-color: rgb(171, 114, 170);
    }

    .btn-collect-30-percent {
        background-color: #17a2b8;
    }

    .cp-actions button:hover {
        opacity: 0.9;
    }

    .btn-send-bq-file {
        background-color: #8e44ad;
        color: #fff;
        border: none;
        padding: 5px 10px;
        cursor: pointer;
        border-radius: 4px;
    }

    .btn-send-bq-file:hover {
        opacity: 0.9;
    }

    /* Modal container styling */
    #imageModal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.9);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    /* Modal content */
    #imageModal .modal-content {
        position: relative;
        max-width: 90%;
        max-height: 90vh;
        margin: auto;
        display: block;
        animation: zoom 0.3s ease-in-out;
    }

    @keyframes zoom {
        from {transform: scale(0.1)}
        to {transform: scale(1)}
    }

    #modalImage {
        max-width: 100%;
        max-height: 90vh;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .close {
        position: absolute;
        top: -30px;
        right: 0;
        color: #f1f1f1;
        font-size: 30px;
        font-weight: bold;
        cursor: pointer;
        z-index: 1001;
        width: 30px;
        height: 30px;
        text-align: center;
        line-height: 30px;
        background: rgba(0,0,0,0.5);
        border-radius: 50%;
    }

    .close:hover {
        color: #fff;
        background: rgba(255,0,0,0.5);
    }

    /* Styling for images in the table */
    .clickable-image {
        transition: transform 0.2s ease-in-out;
        cursor: pointer;
    }

    .clickable-image:hover {
        transform: scale(1.05);
    }

    /* Image gallery styling */
    .image-gallery {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        max-width: 300px;
    }

    .image-thumbnail {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 4px;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .image-thumbnail:hover {
        transform: scale(1.05);
    }

    /* Add this to handle dropdown position when near the bottom of the screen */
    .action-dropdown:last-child .dropdown-content {
        bottom: auto;
        top: 100%;
        margin-bottom: 0;
    }

    /* Optional: Add a small arrow to the dropdown */
    .dropdown-content::before {
        content: '';
        position: absolute;
        top: -6px;
        right: 10px;
        width: 12px;
        height: 12px;
        background: white;
        transform: rotate(45deg);
        box-shadow: -2px -2px 5px rgba(0,0,0,0.05);
    }

    .action-dropdown:last-child .dropdown-content::before {
        top: -6px;
        bottom: auto;
        box-shadow: -2px -2px 5px rgba(0,0,0,0.05);
    }

    /* Optional: Add animation to dropdown */
    @keyframes dropdownFade {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .dropdown-content {
        animation: dropdownFade 0.2s ease-out;
    }
</style>