<?php
include("includes/db-connection.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (isset($_POST['action'])) {
        $response = ['status' => 'error', 'message' => 'Unknown error occurred'];
        $packageId = mysqli_real_escape_string($conn, $_POST['package_id']);

        if ($_POST['action'] === 'approve') {
            $query = "UPDATE package SET package_status='approved' WHERE package_id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $packageId);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Package approved successfully.'
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Error approving package: ' . $stmt->error
                ];
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'reject') {
            $reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, $_POST['reason']) : '';

            $query = "UPDATE package SET package_status='rejected', rejection_reason=? WHERE package_id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $reason, $packageId);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Package rejected successfully.'
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Error rejecting package: ' . $stmt->error
                ];
            }
            $stmt->close();
        }

        echo json_encode($response);
        exit;
    }
}

// Fetch package statistics
$statsQuery = "SELECT 
    COUNT(*) as total_packages,
    SUM(CASE WHEN package_status = 'pending' THEN 1 ELSE 0 END) as pending_packages,
    SUM(CASE WHEN package_status = 'approved' THEN 1 ELSE 0 END) as approved_packages,
    SUM(CASE WHEN package_status = 'rejected' THEN 1 ELSE 0 END) as rejected_packages
    FROM package";
$statsResult = mysqli_query($conn, $statsQuery);
$stats = mysqli_fetch_assoc($statsResult);

// Fetch all packages with package_id as the first column
$query = "SELECT package_id, package_title, package_type, package_img, package_price, package_status FROM package ORDER BY 
    CASE 
        WHEN package_status = 'pending' THEN 1
        WHEN package_status = 'approved' THEN 2
        WHEN package_status = 'rejected' THEN 3
    END";
$result = mysqli_query($conn, $query);
?>

<div class="package-management">
    <!-- Statistics Cards -->
    <div class="package-stats">
        <div class="stat-card total">
            <i class="fas fa-box"></i>
            <div class="stat-info">
                <h3>Total Packages</h3>
                <p><?= $stats['total_packages'] ?></p>
            </div>
        </div>
        <div class="stat-card pending">
            <i class="fas fa-hourglass-half"></i>
            <div class="stat-info">
                <h3>Pending</h3>
                <p><?= $stats['pending_packages'] ?></p>
            </div>
        </div>
        <div class="stat-card approved">
            <i class="fas fa-check-circle"></i>
            <div class="stat-info">
                <h3>Approved</h3>
                <p><?= $stats['approved_packages'] ?></p>
            </div>
        </div>
        <div class="stat-card rejected">
            <i class="fas fa-times-circle"></i>
            <div class="stat-info">
                <h3>Rejected</h3>
                <p><?= $stats['rejected_packages'] ?></p>
            </div>
        </div>
    </div>

    <!-- Package List -->
    <div class="package-header">
        <h2><i class="fas fa-boxes"></i></h2>
        <div class="package-filters">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="pending">Pending</button>
            <button class="filter-btn" data-filter="approved">Approved</button>
            <button class="filter-btn" data-filter="rejected">Rejected</button>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Package ID</th>
                    <th>Package</th>
                    <th>Type</th>
                    <th>Image</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr class="package-row" data-status="<?= strtolower($row['package_status']); ?>">
                        <td><?= htmlspecialchars($row['package_id']); ?></td>
                        <td><?= htmlspecialchars($row['package_title']); ?></td>
                        <td><?= htmlspecialchars($row['package_type']); ?></td>
                        <td>
                            <?php
                            $images = explode(',', $row['package_img']);
                            if (!empty($images[0])): ?>
                                <img src="uploads/images/<?= htmlspecialchars($images[0]); ?>" alt="Package Image" width="100"
                                    class="clickable-image-package"
                                    data-src="uploads/images/<?= htmlspecialchars($images[0]); ?>">
                            <?php else: ?>
                                <span class="no-image">No image</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['package_price']); ?></td>
                        <td>
                            <span class="status-badge <?= strtolower($row['package_status']); ?>">
                                <i class="fas fa-<?=
                                    $row['package_status'] == 'approved' ? 'check-circle' :
                                    ($row['package_status'] == 'rejected' ? 'times-circle' : 'clock')
                                    ?>"></i>
                                <?= ucfirst($row['package_status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($row['package_status'] == 'pending'): ?>
                                    <button class="btn-approve-package" data-id="<?= $row['package_id']; ?>">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="btn-reject-package" data-id="<?= $row['package_id']; ?>">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                <?php else: ?>
                                    <span class="no-action">No Action</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for Package Image View -->
<div id="packageImageModal" class="modal">
    <div class="modal-content">
        <span class="close">×</span>
        <img id="packageModalImage" src="" alt="Package Image" style="width: 100%; max-width: 500px;">
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Package Filtering
        const filterButtons = document.querySelectorAll('.filter-btn');
        const packageRows = document.querySelectorAll('.package-row');

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                button.classList.add('active');

                const filter = button.getAttribute('data-filter');

                packageRows.forEach(row => {
                    if (filter === 'all' || row.getAttribute('data-status') === filter) {
                        row.style.display = 'table-row';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });

        // Approve Package
        const approveButtons = document.querySelectorAll(".btn-approve-package");
        approveButtons.forEach((button) => {
            button.addEventListener("click", () => {
                const packageId = button.getAttribute("data-id");
                if (confirm("Are you sure you want to approve this package?")) {
                    fetch("manage-package.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `package_id=${packageId}&action=approve`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                alert(data.message);
                                updatePackageRow(packageId, 'approved');
                                updateStats();
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while approving the package');
                        });
                }
            });
        });

        // Reject Package
        const rejectButtons = document.querySelectorAll(".btn-reject-package");
        rejectButtons.forEach((button) => {
            button.addEventListener("click", () => {
                const packageId = button.getAttribute("data-id");
                const rejectionReason = prompt("Please provide a reason for rejecting this package:");

                if (rejectionReason !== null) {
                    fetch("manage-package.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `package_id=${packageId}&action=reject&reason=${encodeURIComponent(rejectionReason)}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                alert(data.message);
                                updatePackageRow(packageId, 'rejected');
                                updateStats();
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while rejecting the package');
                        });
                }
            });
        });

        // Update Package Row
        function updatePackageRow(packageId, newStatus) {
            const row = document.querySelector(`.btn-approve-package[data-id="${packageId}"]`).closest('tr');

            // Update status cell
            const statusCell = row.querySelector('td:nth-child(6)');
            const statusIcon = newStatus === 'approved' ? 'check-circle' : 'times-circle';
            statusCell.innerHTML = `
                <span class="status-badge ${newStatus}">
                    <i class="fas fa-${statusIcon}"></i>
                    ${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}
                </span>
            `;

            // Update actions cell
            const actionsCell = row.querySelector('td:nth-child(7)');
            actionsCell.innerHTML = `
                <div class="action-buttons">
                    <span class="no-action">No Action</span>
                </div>
            `;

            // Update row status attribute
            row.setAttribute('data-status', newStatus);
        }

        // Update Statistics
        function updateStats() {
            const totalPackages = document.querySelectorAll('.package-row').length;
            const pendingPackages = document.querySelectorAll('.package-row[data-status="pending"]').length;
            const approvedPackages = document.querySelectorAll('.package-row[data-status="approved"]').length;
            const rejectedPackages = document.querySelectorAll('.package-row[data-status="rejected"]').length;

            document.querySelector('.stat-card.total .stat-info p').textContent = totalPackages;
            document.querySelector('.stat-card.pending .stat-info p').textContent = pendingPackages;
            document.querySelector('.stat-card.approved .stat-info p').textContent = approvedPackages;
            document.querySelector('.stat-card.rejected .stat-info p').textContent = rejectedPackages;
        }

        // Image modal functionality for Package Section
        const clickableImagesPackage = document.querySelectorAll(".clickable-image-package");
        const packageImageModal = document.getElementById("packageImageModal");
        const packageModalImage = document.getElementById("packageModalImage");
        const closePackageImageModal = document.querySelector("#packageImageModal .close");

        clickableImagesPackage.forEach((image) => {
            image.addEventListener("click", () => {
                const imageUrl = image.getAttribute("data-src");
                packageModalImage.src = imageUrl;
                packageImageModal.style.display = "flex";
            });
        });

        closePackageImageModal.addEventListener("click", () => {
            packageImageModal.style.display = "none";
        });

        window.addEventListener("click", (e) => {
            if (e.target === packageImageModal) {
                packageImageModal.style.display = "none";
            }
        });
    });
</script>

<style>
    .package-management {
        padding: 20px;
    }

    /* Statistics Cards */
    .package-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        gap: 15px;
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-card i {
        font-size: 2.5em;
        padding: 15px;
        border-radius: 50%;
    }

    .stat-card.total i {
        background: #e3f2fd;
        color: #1976d2;
    }

    .stat-card.pending i {
        background: #fff8e1;
        color: #ffa000;
    }

    .stat-card.approved i {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .stat-card.rejected i {
        background: #fbe9e7;
        color: #d84315;
    }

    .stat-info h3 {
        margin: 0;
        font-size: 0.9em;
        color: #666;
    }

    .stat-info p {
        margin: 5px 0 0;
        font-size: 1.8em;
        font-weight: bold;
        color: #333;
    }

    /* Package Header and Filters */
    .package-header {
        padding: 20px;
        background: #fff;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .package-header h2 {
        margin: 0;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .package-filters {
        display: flex;
        gap: 10px;
    }

    .filter-btn {
        padding: 8px 16px;
        border: none;
        border-radius: 20px;
        background: #f0f0f0;
        color: #666;
        cursor: pointer;
        transition: all 0.3s;
    }

    .filter-btn.active {
        background: #1976d2;
        color: white;
    }

    /* Table Styling */
    .table-container {
        padding: 20px;
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    th,
    td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    th {
        background-color: #f8f9fa;
        color: #333;
        font-weight: 600;
    }

    tr:hover {
        background-color: #f5f5f5;
    }

    /* Status Badge */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.9em;
    }

    .status-badge.pending {
        background: #fff8e1;
        color: #ffa000;
    }

    .status-badge.approved {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .status-badge.rejected {
        background: #fbe9e7;
        color: #d84315;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 8px;
    }

    .action-buttons button {
        padding: 6px 12px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.9em;
        transition: all 0.2s;
    }

    .btn-approve-package {
        background: #2e7d32;
        color: white;
    }

    .btn-reject-package {
        background: #d84315;
        color: white;
    }

    .btn-view-details {
        background: #1976d2;
        color: white;
    }

    .action-buttons button:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }

    /* No Action Text */
    .no-action {
        color: #999;
        font-style: italic;
        font-size: 0.9em;
        padding: 6px 12px;
        border-radius: 5px;
        background-color: #f0f0f0;
        display: inline-block;
    }

    /* Images and Links */
    .clickable-image-package {
        cursor: pointer;
        border-radius: 5px;
        transition: transform 0.2s;
    }

    .clickable-image-package:hover {
        transform: scale(1.05);
    }

    .no-image {
        color: #999;
        font-style: italic;
        font-size: 0.9em;
    }

    /* Modal Container */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    /* Modal Content */
    .modal-content {
        position: relative;
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        max-width: 80%;
        max-height: 80%;
    }

    /* Modal Image */
    #packageModalImage {
        max-width: 100%;
        max-height: 70vh;
        display: block;
        margin: 0 auto;
        border-radius: 5px;
    }

    /* Close Button */
    .close {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 28px;
        font-weight: bold;
        color: #999;
        cursor: pointer;
        transition: color 0.2s;
    }

    .close:hover {
        color: #333;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .package-stats {
            grid-template-columns: repeat(2, 1fr);
        }

        .package-header {
            flex-direction: column;
            gap: 15px;
        }

        .action-buttons {
            flex-direction: column;
        }
    }
</style>