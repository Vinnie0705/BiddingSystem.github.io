<?php
include("includes/db-connection.php");

// Process POST requests for approving/rejecting projects
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (isset($_POST['action'])) {
        $response = ['status' => 'error', 'message' => 'Unknown error occurred'];
        $projectId = mysqli_real_escape_string($conn, $_POST['project_id']);

        if ($_POST['action'] === 'approve') {
            $query = "UPDATE project SET proj_status='approved' WHERE proj_id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $projectId);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Project approved successfully.'
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Error approving project: ' . $stmt->error
                ];
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'reject') {
            $reason = isset($_POST['reason']) ? mysqli_real_escape_string($conn, $_POST['reason']) : '';

            $query = "UPDATE project SET proj_status='rejected', rejection_reason=? WHERE proj_id=?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $reason, $projectId);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Project rejected successfully.'
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Error rejecting project: ' . $stmt->error
                ];
            }
            $stmt->close();
        }

        echo json_encode($response);
        exit;
    }
}

// Fetch project statistics
$statsQuery = "SELECT 
    COUNT(*) as total_projects,
    SUM(CASE WHEN proj_status = 'pending' THEN 1 ELSE 0 END) as pending_projects,
    SUM(CASE WHEN proj_status = 'approved' THEN 1 ELSE 0 END) as approved_projects,
    SUM(CASE WHEN proj_status = 'rejected' THEN 1 ELSE 0 END) as rejected_projects
    FROM project";
$statsResult = mysqli_query($conn, $statsQuery);
$stats = mysqli_fetch_assoc($statsResult);

// Fetch all projects with proj_id as the first column
$query = "SELECT proj_id, proj_title, proj_type, proj_img, proj_size, proj_location, proj_startdate, proj_proposal, proj_status FROM project ORDER BY 
    CASE 
        WHEN proj_status = 'pending' THEN 1
        WHEN proj_status = 'approved' THEN 2
        WHEN proj_status = 'rejected' THEN 3
    END";
$result = mysqli_query($conn, $query);
?>

<div class="project-management">
    <!-- Statistics Cards -->
    <div class="project-stats">
        <div class="stat-card total">
            <i class="fas fa-project-diagram"></i>
            <div class="stat-info">
                <h3>Total Projects</h3>
                <p><?= $stats['total_projects'] ?></p>
            </div>
        </div>
        <div class="stat-card pending">
            <i class="fas fa-hourglass-half"></i>
            <div class="stat-info">
                <h3>Pending</h3>
                <p><?= $stats['pending_projects'] ?></p>
            </div>
        </div>
        <div class="stat-card approved">
            <i class="fas fa-check-circle"></i>
            <div class="stat-info">
                <h3>Approved</h3>
                <p><?= $stats['approved_projects'] ?></p>
            </div>
        </div>
        <div class="stat-card rejected">
            <i class="fas fa-times-circle"></i>
            <div class="stat-info">
                <h3>Rejected</h3>
                <p><?= $stats['rejected_projects'] ?></p>
            </div>
        </div>
    </div>

    <!-- Project List -->
    <div class="project-header">
        <h2><i class="fas fa-clipboard-list"></i></h2>
        <div class="project-filters">
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
                    <th>Project ID</th>
                    <th>Project</th>
                    <th>Type</th>
                    <th>Image</th>
                    <th>Size</th>
                    <th>Location</th>
                    <th>Proposal</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr class="project-row" data-status="<?= strtolower($row['proj_status']); ?>">
                        <td><?= htmlspecialchars($row['proj_id']); ?></td>
                        <td><?= htmlspecialchars($row['proj_title']); ?></td>
                        <td><?= htmlspecialchars($row['proj_type']); ?></td>
                        <td>
                            <?php if (!empty($row['proj_img'])): ?>
                                <img src="uploads/images/<?= $row['proj_img']; ?>" alt="Project Image" width="100"
                                    class="clickable-image" data-src="uploads/images/<?= $row['proj_img']; ?>">
                            <?php else: ?>
                                <span class="no-image">No image</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['proj_size']); ?></td>
                        <td><?= htmlspecialchars($row['proj_location']); ?></td>
                        <td>
                            <?php if (!empty($row['proj_proposal'])): ?>
                                <a href="uploads/proposals/<?= $row['proj_proposal']; ?>" target="_blank" class="proposal-link">
                                    <i class="fas fa-file-pdf"></i> View Proposal
                                </a>
                            <?php else: ?>
                                <span class="no-proposal">No proposal</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge <?= strtolower($row['proj_status']); ?>">
                                <i class="fas fa-<?=
                                    $row['proj_status'] == 'approved' ? 'check-circle' :
                                    ($row['proj_status'] == 'rejected' ? 'times-circle' : 'clock')
                                    ?>"></i>
                                <?= ucfirst($row['proj_status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($row['proj_status'] == 'pending'): ?>
                                    <button class="btn-approve-project" data-id="<?= $row['proj_id']; ?>">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="btn-reject-project" data-id="<?= $row['proj_id']; ?>">
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

<!-- Modal for Image View -->
<div id="imageModal" class="modal">
    <div class="modal-content">
        <span class="close">×</span>
        <img id="modalImage" src="" alt="Project Image">
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Project Filtering
        const filterButtons = document.querySelectorAll('.filter-btn');
        const projectRows = document.querySelectorAll('.project-row');

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                button.classList.add('active');

                const filter = button.getAttribute('data-filter');

                projectRows.forEach(row => {
                    if (filter === 'all' || row.getAttribute('data-status') === filter) {
                        row.style.display = 'table-row';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });

        // Approve Project
        const approveButtons = document.querySelectorAll(".btn-approve-project");
        approveButtons.forEach((button) => {
            button.addEventListener("click", () => {
                const projectId = button.getAttribute("data-id");
                if (confirm("Are you sure you want to approve this project?")) {
                    fetch("manage-project.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `project_id=${projectId}&action=approve`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                alert(data.message);
                                updateProjectRow(projectId, 'approved');
                                updateStats();
                            } else {
                                alert(data.message);
                            }
                        })
                }
            });
        });

        // Reject Project
        const rejectButtons = document.querySelectorAll(".btn-reject-project");
        rejectButtons.forEach((button) => {
            button.addEventListener("click", () => {
                const projectId = button.getAttribute("data-id");
                const rejectionReason = prompt("Please provide a reason for rejecting this project:");

                if (rejectionReason !== null) {
                    fetch("manage-project.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `project_id=${projectId}&action=reject&reason=${encodeURIComponent(rejectionReason)}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                alert(data.message);
                                updateProjectRow(projectId, 'rejected');
                                updateStats();
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('An error occurred while rejecting the project');
                        });
                }
            });
        });

        // Update Project Row
        function updateProjectRow(projectId, newStatus) {
            const row = document.querySelector(`.btn-approve-project[data-id="${projectId}"]`).closest('tr');

            // Update status cell
            const statusCell = row.querySelector('td:nth-child(9)');
            const statusIcon = newStatus === 'approved' ? 'check-circle' : 'times-circle';
            statusCell.innerHTML = `
        <span class="status-badge ${newStatus}">
            <i class="fas fa-${statusIcon}"></i>
            ${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}
        </span>
    `;

            // Update actions cell
            const actionsCell = row.querySelector('td:nth-child(10)');
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
            const totalProjects = document.querySelectorAll('.project-row').length;
            const pendingProjects = document.querySelectorAll('.project-row[data-status="pending"]').length;
            const approvedProjects = document.querySelectorAll('.project-row[data-status="approved"]').length;
            const rejectedProjects = document.querySelectorAll('.project-row[data-status="rejected"]').length;

            document.querySelector('.stat-card.total .stat-info p').textContent = totalProjects;
            document.querySelector('.stat-card.pending .stat-info p').textContent = pendingProjects;
            document.querySelector('.stat-card.approved .stat-info p').textContent = approvedProjects;
            document.querySelector('.stat-card.rejected .stat-info p').textContent = rejectedProjects;
        }

        // Image modal functionality
        const clickableImages = document.querySelectorAll(".clickable-image");
        const imageModal = document.getElementById("imageModal");
        const modalImage = document.getElementById("modalImage");
        const closeButtons = document.querySelectorAll(".close");

        clickableImages.forEach((image) => {
            image.addEventListener("click", () => {
                const imageUrl = image.getAttribute("data-src");
                modalImage.src = imageUrl;
                imageModal.style.display = "flex";
            });
        });

        closeButtons.forEach(button => {
            button.addEventListener("click", () => {
                imageModal.style.display = "none";
                document.getElementById("detailsModal").style.display = "none";
            });
        });

        window.addEventListener("click", (e) => {
            if (e.target === imageModal) {
                imageModal.style.display = "none";
            }
            if (e.target === document.getElementById("detailsModal")) {
                document.getElementById("detailsModal").style.display = "none";
            }
        });
    });
</script>

<style>
    .project-management {
        padding: 20px;
        background: #f8f9fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Statistics Cards */
    .project-stats {
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

    /* Project Container */
    .project-container {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .project-header {
        padding: 20px;
        background: #fff;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .project-header h2 {
        margin: 0;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .project-filters {
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

    /* Table styling */
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
        flex-direction: column; /* Changed to column layout for vertical stacking */
        gap: 8px; /* Maintains spacing between buttons */
        align-items: center; /* Centers buttons vertically */
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
        width: 100%; /* Ensures buttons take full width of the container for consistency */
    }

    .btn-approve-project {
        background: #2e7d32;
        color: white;
    }

    .btn-reject-project {
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

    /* Images and Links */
    .clickable-image {
        cursor: pointer;
        border-radius: 5px;
        transition: transform 0.2s;
    }

    .clickable-image:hover {
        transform: scale(1.05);
    }

    .proposal-link {
        color: #1976d2;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: color 0.2s;
    }

    .proposal-link:hover {
        color: #0d47a1;
        text-decoration: underline;
    }

    .no-image,
    .no-proposal {
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

    .details-content {
        width: 600px;
        max-width: 90%;
    }

    .details-header {
        border-bottom: 1px solid #eee;
        margin-bottom: 20px;
        padding-bottom: 10px;
    }

    .details-header h2 {
        margin: 0;
        color: #333;
    }

    /* Modal Image */
    #modalImage {
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

    /* No Action Text */
    .no-action {
        color: #999;
        /* Grey color */
        font-style: italic;
        font-size: 0.9em;
        padding: 6px 12px;
        border-radius: 5px;
        background-color: #f0f0f0;
        /* Light grey background */
        display: inline-block;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .project-stats {
            grid-template-columns: repeat(2, 1fr);
        }

        .project-header {
            flex-direction: column;
            gap: 15px;
        }

        .action-buttons {
            flex-direction: column;
        }
    }
</style>