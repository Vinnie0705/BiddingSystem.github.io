<?php
// Database connection
include("includes/db-connection.php");

// Process POST requests for marking read/unread and deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        $response = ['status' => 'error', 'message' => 'Unknown error occurred'];
        
        if ($_POST['action'] === 'toggle-read') {
            $feedbackId = mysqli_real_escape_string($conn, $_POST['id']);
            $readState = mysqli_real_escape_string($conn, $_POST['read']);
            
            $query = "UPDATE feedback SET fb_read = ? WHERE fb_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $readState, $feedbackId);
            
            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Feedback status updated successfully',
                    'newState' => $readState
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to update feedback status: ' . $stmt->error
                ];
            }
            $stmt->close();
        }
        elseif ($_POST['action'] === 'delete') {
            $feedbackId = mysqli_real_escape_string($conn, $_POST['id']);
            
            $query = "DELETE FROM feedback WHERE fb_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $feedbackId);
            
            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Feedback deleted successfully'
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Failed to delete feedback: ' . $stmt->error
                ];
            }
            $stmt->close();
        }
        
        echo json_encode($response);
        exit;
    }
}

// Fetch feedback statistics
$statsQuery = "SELECT 
    COUNT(*) as total_feedback,
    SUM(CASE WHEN fb_read = 1 THEN 1 ELSE 0 END) as read_feedback,
    SUM(CASE WHEN fb_read = 0 THEN 1 ELSE 0 END) as unread_feedback,
    SUM(CASE WHEN fb_firsttime = 0 THEN 1 ELSE 0 END) as new_users
    FROM feedback";
$statsResult = mysqli_query($conn, $statsQuery);
$stats = mysqli_fetch_assoc($statsResult);

// Fetch feedback from the database
$query = "SELECT * FROM feedback ORDER BY fb_read ASC, fb_date DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo "Error fetching feedback: " . mysqli_error($conn);
    exit;
}
?>

<div class="feedback-management">
    <!-- Statistics Cards -->
    <div class="feedback-stats">
        <div class="stat-card total">
            <i class="fas fa-comments"></i>
            <div class="stat-info">
                <h3>Total Feedback</h3>
                <p><?= $stats['total_feedback'] ?></p>
            </div>
        </div>
        <div class="stat-card unread">
            <i class="fas fa-envelope"></i>
            <div class="stat-info">
                <h3>Unread</h3>
                <p><?= $stats['unread_feedback'] ?></p>
            </div>
        </div>
        <div class="stat-card read">
            <i class="fas fa-check-circle"></i>
            <div class="stat-info">
                <h3>Read</h3>
                <p><?= $stats['read_feedback'] ?></p>
            </div>
        </div>
        <div class="stat-card new-users">
            <i class="fas fa-user-plus"></i>
            <div class="stat-info">
                <h3>New Users</h3>
                <p><?= $stats['new_users'] ?></p>
            </div>
        </div>
    </div>

    <!-- Feedback List -->
    <div class="feedback-container">
        <div class="feedback-header">
            <h2><i class="fas fa-inbox"></i></h2>
            <div class="feedback-filters">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="unread">Unread</button>
                <button class="filter-btn" data-filter="read">Read</button>
            </div>
        </div>

        <div class="feedback-list">
            <?php while ($row = mysqli_fetch_assoc($result)) { 
                $readStatus = $row['fb_read'] == 1 ? 'read' : 'unread';
                $dateFormatted = date("M d, Y - h:i A", strtotime($row['fb_date']));
            ?>
                <div class="feedback-item <?= $readStatus ?>" data-status="<?= $readStatus ?>">
                    <div class="feedback-item-header">
                        <div class="user-info">
                            <span class="user-avatar">
                                <?= strtoupper(substr($row['fb_name'], 0, 1)) ?>
                            </span>
                            <div class="user-details">
                                <h3><?= htmlspecialchars($row['fb_name']) ?></h3>
                                <span class="feedback-date">
                                    <i class="far fa-clock"></i> <?= $dateFormatted ?>
                                </span>
                            </div>
                        </div>
                        <div class="feedback-badges">
                            <?php if ($row['fb_firsttime'] == '0'): ?>
                                <span class="badge new-user">New User</span>
                            <?php endif; ?>
                            <span class="badge <?= $readStatus ?>">
                                <i class="fas fa-<?= $readStatus == 'read' ? 'check-circle' : 'envelope' ?>"></i>
                                <?= ucfirst($readStatus) ?>
                            </span>
                        </div>
                    </div>

                    <div class="feedback-content">
                        <div class="feedback-subject">
                            <strong><i class="fas fa-tag"></i> <?= htmlspecialchars($row['fb_subject']) ?></strong>
                        </div>
                        <div class="feedback-message">
                            <?= nl2br(htmlspecialchars($row['fb_message'])) ?>
                        </div>
                        <div class="feedback-contact">
                            <i class="fas fa-envelope"></i> <?= htmlspecialchars($row['fb_email']) ?>
                        </div>
                    </div>

                    <div class="feedback-actions">
                        <button class="btn-mark-read" data-id="<?= $row['fb_id'] ?>" data-read="<?= $row['fb_read'] ?>">
                            <i class="fas fa-<?= $row['fb_read'] == 1 ? 'envelope' : 'check' ?>"></i>
                            <?= $row['fb_read'] == 1 ? 'Mark as Unread' : 'Mark as Read' ?>
                        </button>
                        <button class="btn-delete-feedback" data-id="<?= $row['fb_id'] ?>">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<?php
// Close the database connection
mysqli_close($conn);
?>

<style>
    .feedback-management {
        padding: 20px;
        background: #f8f9fa;
    }

    /* Statistics Cards */
    .feedback-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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

    .stat-card.unread i {
        background: #fbe9e7;
        color: #d84315;
    }

    .stat-card.read i {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .stat-card.new-users i {
        background: #ede7f6;
        color: #5e35b1;
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

    /* Feedback Container */
    .feedback-container {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .feedback-header {
        padding: 20px;
        background: #fff;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .feedback-header h2 {
        margin: 0;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .feedback-filters {
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

    /* Feedback Items */
    .feedback-list {
        padding: 20px;
    }

    .feedback-item {
        background: white;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: transform 0.2s;
        border: 1px solid #eee;
    }

    .feedback-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .feedback-item.unread {
        border-left: 4px solid #d84315;
    }

    .feedback-item.read {
        border-left: 4px solid #2e7d32;
    }

    .feedback-item-header {
        padding: 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        background: #1976d2;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .user-details h3 {
        margin: 0;
        font-size: 1.1em;
        color: #333;
    }

    .feedback-date {
        font-size: 0.9em;
        color: #666;
    }

    .feedback-badges {
        display: flex;
        gap: 10px;
    }

    .badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.8em;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .badge.unread {
        background: #fbe9e7;
        color: #d84315;
    }

    .badge.read {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .badge.new-user {
        background: #ede7f6;
        color: #5e35b1;
    }

    .feedback-content {
        padding: 20px;
    }

    .feedback-subject {
        margin-bottom: 15px;
        color: #333;
    }

    .feedback-message {
        color: #666;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    .feedback-contact {
        color: #666;
        font-size: 0.9em;
    }

    .feedback-actions {
        padding: 15px;
        border-top: 1px solid #eee;
        display: flex;
        gap: 10px;
    }

    .feedback-actions button {
        padding: 8px 16px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
    }

    .btn-mark-read {
        background: #1976d2;
        color: white;
    }

    .btn-delete-feedback {
        background: #dc3545;
        color: white;
    }

    .feedback-actions button:hover {
        opacity: 0.9;
    }

    @media (max-width: 768px) {
        .feedback-stats {
            grid-template-columns: repeat(2, 1fr);
        }

        .feedback-header {
            flex-direction: column;
            gap: 15px;
        }

        .feedback-item-header {
            flex-direction: column;
            gap: 10px;
        }

        .feedback-badges {
            justify-content: flex-start;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filter functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    const feedbackItems = document.querySelectorAll('.feedback-item');

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            button.classList.add('active');

            const filter = button.getAttribute('data-filter');
            
            feedbackItems.forEach(item => {
                if (filter === 'all' || item.getAttribute('data-status') === filter) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });

    // Mark as Read/Unread functionality
    document.querySelectorAll('.btn-mark-read').forEach(button => {
        button.addEventListener('click', function() {
            const feedbackId = this.getAttribute('data-id');
            const currentReadState = this.getAttribute('data-read');
            const newReadState = currentReadState == '1' ? '0' : '1';
            const feedbackItem = this.closest('.feedback-item');

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle-read&id=${feedbackId}&read=${newReadState}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update button text and icon
                    this.innerHTML = newReadState == '1' ? 
                        '<i class="fas fa-envelope"></i> Mark as Unread' : 
                        '<i class="fas fa-check"></i> Mark as Read';
                    
                    // Update feedback item status
                    feedbackItem.className = `feedback-item ${newReadState == '1' ? 'read' : 'unread'}`;
                    feedbackItem.setAttribute('data-status', newReadState == '1' ? 'read' : 'unread');
                    
                    // Update data attribute
                    this.setAttribute('data-read', newReadState);
                    
                    // Update badge
                    const badge = feedbackItem.querySelector('.badge:not(.new-user)');
                    badge.className = `badge ${newReadState == '1' ? 'read' : 'unread'}`;
                    badge.innerHTML = newReadState == '1' ? 
                        '<i class="fas fa-check-circle"></i> Read' : 
                        '<i class="fas fa-envelope"></i> Unread';

                    // Update statistics
                    updateStats();
                } else {
                    alert('Error updating feedback status: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the feedback status');
            });
        });
    });

    // Delete functionality
    document.querySelectorAll('.btn-delete-feedback').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this feedback?')) {
                const feedbackId = this.getAttribute('data-id');
                const feedbackItem = this.closest('.feedback-item');

                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete&id=${feedbackId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        feedbackItem.style.animation = 'fadeOut 0.3s';
                        setTimeout(() => {
                            feedbackItem.remove();
                            updateStats();
                        }, 300);
                    } else {
                        alert('Error deleting feedback: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the feedback');
                });
            }
        });
    });

    function updateStats() {
        const totalFeedback = document.querySelectorAll('.feedback-item').length;
        const readFeedback = document.querySelectorAll('.feedback-item.read').length;
        const unreadFeedback = document.querySelectorAll('.feedback-item.unread').length;
        const newUsers = document.querySelectorAll('.badge.new-user').length;

        // Update statistics cards
        document.querySelector('.stat-card.total .stat-info p').textContent = totalFeedback;
        document.querySelector('.stat-card.read .stat-info p').textContent = readFeedback;
        document.querySelector('.stat-card.unread .stat-info p').textContent = unreadFeedback;
        document.querySelector('.stat-card.new-users .stat-info p').textContent = newUsers;
    }
});
</script>
