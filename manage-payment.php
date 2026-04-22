<?php
include("includes/db-connection.php");

// Debugging settings
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering to prevent header errors
ob_start();

// Define the base URL
$baseUrl = "http://localhost/GYS%20Resources/"; // Update this to match your domain and project path

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $paymentId = $_POST['payment_id'] ?? '';
    $action = $_POST['action'] ?? '';
    $reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, $_POST['reason']) : null;

    if (empty($paymentId) || empty($action)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing payment ID or action']);
        exit();
    }

    if ($action === 'approve') {
        $query = "UPDATE payment SET payment_status = 'verified' WHERE payment_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $paymentId);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Payment verified successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error verifying payment: ' . $stmt->error]);
        }
        $stmt->close();
    } elseif ($action === 'reject') {
        if (empty($reason)) {
            echo json_encode(['status' => 'error', 'message' => 'Please provide a reason for rejection.']);
            exit();
        }
        $query = "UPDATE payment SET payment_status = 'rejected', rejection_reason = ? WHERE payment_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $reason, $paymentId);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Payment rejected successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error rejecting payment: ' . $stmt->error]);
        }
        $stmt->close();
    }
    exit();
}

// Modified query with COALESCE to prevent NULL values
$query = "
    SELECT 
        p.payment_date,
        p.payment_id, 
        p.receipt_file, 
        p.payment_status, 
        COALESCE(u.user_name, 'Unknown Client') AS client_name, 
        n.ten_price, 
        n.thirty_price
    FROM payment p
    LEFT JOIN users u ON p.client_id = u.user_id
    LEFT JOIN notification n ON p.notification_id = n.noti_id
    ORDER BY p.payment_date DESC";
$result = mysqli_query($conn, $query);
?>

<div class="payment-management">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Client Name</th>
                    <th>Payment Date and Time</th>
                    <th>Receipt</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $row['payment_id']; ?></td>
                        <td><?= htmlspecialchars($row['client_name'] ?? 'Unknown Client'); ?></td>
                        <td><?= htmlspecialchars($row['payment_date']); ?></td>
                        <td>
                            <?php if (!empty($row['receipt_file'])): ?>
                                <a href="<?= htmlspecialchars($row['receipt_file']); ?>" target="_blank" class="receiptfile-link"><i class="fas fa-file-pdf"></i> View PDF</a>
                            <?php else: ?>
                                <span class="no-receipt">No receipt available</span>
                            <?php endif; ?>
                        </td>
                        <td class="status-cell <?= strtolower($row['payment_status'] ?? 'pending'); ?>">
                            <?= htmlspecialchars($row['payment_status'] ?? 'Pending'); ?>
                        </td>
                        <td>
                            <div class="action-dropdown">
                                <button class="action-btn">
                                    Actions <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="dropdown-content" style="position: absolute; top: 100%; right: 0; transform: translateY(-10px); z-index: 1002;">
                                    <button class="btn-approve-payment" data-id="<?= $row['payment_id']; ?>">
                                        <i class="fas fa-check"></i> Verify
                                    </button>
                                    <button class="btn-reject-payment" data-id="<?= $row['payment_id']; ?>">
                                        <i class="fas fa-times"></i> Reject
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

<!-- Modal for Rejection Reason -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">×</span>
        <h3>Reject Payment</h3>
        <p>Please provide a reason for rejecting this payment:</p>
        <textarea id="rejectReason" class="reject-reason-input" placeholder="Enter reason here..." rows="4"></textarea>
        <div class="modal-actions">
            <button id="confirmReject" class="btn btn-reject-modal">Confirm Reject</button>
            <button class="btn btn-cancel-modal" onclick="closeRejectModal()">Cancel</button>
        </div>
    </div>
</div>

<!-- Modal for Image View -->
<div id="imageModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">×</span>
        <img id="modalImage" src="" alt="Payment Receipt" style="width: 100%; max-width: 500px;">
    </div>
</div>

<style>
    /* Container Styling */
    .payment-management {
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
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        position: relative;
        z-index: 1;
        min-height: 500px;
    }

    /* Table Styling */
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

    /* Status Cell Styling */
    .status-cell {
        font-weight: 500;
        padding: 6px 12px !important;
        border-radius: 4px;
    }

    .status-cell.verified,
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

    /* Action Dropdown Styling */
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
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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

    /* Receipt Link Styling */
    .receipt-link {
        color: #4a90e2;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .receipt-link:hover {
        text-decoration: underline;
    }

    .no-receipt {
        color: #dc3545;
        font-style: italic;
    }

    /* Modal Styling */
    .modal {
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

    .modal-content {
        background: white;
        padding: 20px;
        border-radius: 8px;
        max-width: 500px;
        width: 90%;
        position: relative;
    }

    .close-modal {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 24px;
        cursor: pointer;
        color: #666;
    }

    .reject-reason-input {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
        border: 1px solid #ddd;
        border-radius: 4px;
        resize: vertical;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
    }

    .btn-reject-modal {
        background: #dc3545;
        color: white;
    }

    .btn-cancel-modal {
        background: #6c757d;
        color: white;
    }

    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .btn:hover {
        opacity: 0.9;
    }
</style>