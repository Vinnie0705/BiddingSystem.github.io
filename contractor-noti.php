<?php
include("includes/contractor-header.php");
include("includes/db-connection.php");

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the contractor is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in.");
}

// Get the logged-in contractor ID
$cont_id = intval($_SESSION['user_id']); // Ensure it's an integer for security

// Debugging: Log contractor ID
error_log("DEBUG: Logged-in contractor ID: " . $cont_id);

// Fetch contractor's details from the user table
$contractorQuery = "SELECT * FROM users WHERE user_id = '$cont_id' AND user_type = 'contractor'";
$contractorResult = mysqli_query($conn, $contractorQuery);

if ($contractorResult && mysqli_num_rows($contractorResult) > 0) {
    $contractorData = mysqli_fetch_assoc($contractorResult);
    $contractorName = htmlspecialchars($contractorData['user_name']);
    $contractorUsername = htmlspecialchars($contractorData['user_username']);
    $contractorEmail = htmlspecialchars($contractorData['user_email']);

    // Debugging: Log contractor's details
    error_log("DEBUG: Contractor Details - Name: $contractorName, Username: $contractorUsername, Email: $contractorEmail");
} else {
    die("Error: Contractor account not found.");
}

// Fetch notifications for the logged-in contractor
$query = "SELECT * FROM notification WHERE cont_id = '$cont_id' 
AND (noti_message LIKE '%wait for client approval%' 
               OR noti_message LIKE '%request for the floor plan%' 
               OR noti_message LIKE '%a new purchase on your package%'
               OR noti_message LIKE '%BQ uploaded%'
               OR noti_message LIKE '%Floor Plan uploaded%'
               OR noti_message LIKE '%Payment done%') 
ORDER BY noti_date DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    error_log("ERROR: Query failed - " . mysqli_error($conn));
    die("Error fetching notifications.");
}

$numRows = mysqli_num_rows($result);
error_log("DEBUG: Number of notifications found: " . $numRows);
?>

<div class="notification-header">
    <h2>Notifications</h2>
    <p>Stay up to date with your tasks and any required actions.</p>
</div>

<div class="notification-page">
    <div class="notification-list">
        <?php if ($numRows > 0): ?>
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
                        if (strpos($row['noti_message'], 'request for the floor plan') !== false) {
                            echo '<button class="action-btn upload-floor-plan-btn" data-id="' . $row['noti_id'] . '"><i class="fas fa-upload"></i> Upload Floor Plan</button>';
                        }
                        if (strpos($row['noti_message'], 'Bill of Quantity') !== false) {
                            echo '<button class="action-btn upload-bq-btn" data-id="' . $row['noti_id'] . '"><i class="fas fa-upload"></i> Upload BQ</button>';
                        }
                        ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No notifications found.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    document.querySelectorAll(".upload-floor-plan-btn").forEach(button => {
        button.addEventListener("click", () => {
            const notiId = button.getAttribute("data-id");

            const fileInput = document.createElement("input");
            fileInput.type = "file";
            fileInput.accept = ".pdf, .jpg, .jpeg, .png";  
            fileInput.addEventListener("change", () => {
                const file = fileInput.files[0];

                if (file) {
                    const formData = new FormData();
                    formData.append("noti_id", notiId);
                    formData.append("fp_file", file);

                    fetch("upload-floor-plan.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        alert(data);
                        location.reload(); 
                    })
                    .catch(error => console.error('Error:', error));
                }
            });

            fileInput.click();
        });
    });

    document.querySelectorAll(".upload-bq-btn").forEach(button => {
        button.addEventListener("click", () => {
            const notiId = button.getAttribute("data-id");

            const fileInput = document.createElement("input");
            fileInput.type = "file";
            fileInput.accept = ".pdf, .jpg, .jpeg, .png";  
            fileInput.addEventListener("change", () => {
                const file = fileInput.files[0];

                if (file) {
                    const formData = new FormData();
                    formData.append("noti_id", notiId);
                    formData.append("bq_file", file);

                    fetch("upload-bq.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        alert(data);
                        location.reload(); 
                    })
                    .catch(error => console.error('Error:', error));
                }
            });

            fileInput.click();
        });
    });
</script>

<style>
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

    .upload-bq-btn {
        background-color: #28a745;
        color: white;
    }

    .upload-floor-plan-btn {
        background-color: #17a2b8;
        color: white;
    }

    .action-btn:hover {
        transform: translateY(-3px);
    }
</style>
