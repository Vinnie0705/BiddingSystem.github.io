<?php
// Database connection
include("includes/db-connection.php");

// Fetch counts from the database
$userCounts = $conn->query("
    SELECT 
        user_type, 
        COUNT(*) AS total 
    FROM users 
    GROUP BY user_type
");

// Initialize counts for different user types
$clientCount = 0;
$contractorCount = 0;
$adminCount = 0;

while ($row = $userCounts->fetch_assoc()) {
    if ($row['user_type'] === 'client') {
        $clientCount = $row['total'];
    } elseif ($row['user_type'] === 'contractor') {
        $contractorCount = $row['total'];
    } elseif ($row['user_type'] === 'admin') {
        $adminCount = $row['total'];
    }
}

// Fetch counts for projects and bids
$activeProjectsCount = $conn->query("SELECT COUNT(*) AS total FROM project WHERE proj_status = 'approved'")->fetch_assoc()['total'];
$ongoingBidsCount = $conn->query("SELECT COUNT(*) AS total FROM bid WHERE bid_status = 'approved'")->fetch_assoc()['total'];
$feedbackCount = $conn->query("SELECT COUNT(*) AS total FROM feedback")->fetch_assoc()['total'];
?>
<div class="dashboard-summary">
    <div class="summary-box">
        <h2><?php echo $clientCount; ?></h2>
        <p>Total Clients</p>
    </div>
    <div class="summary-box">
        <h2><?php echo $contractorCount; ?></h2>
        <p>Total Contractors</p>
    </div>
    <div class="summary-box">
        <h2><?php echo $activeProjectsCount; ?></h2>
        <p>Active Projects</p>
    </div>
    <div class="summary-box">
        <h2><?php echo $ongoingBidsCount; ?></h2>
        <p>Ongoing Bids</p>
    </div>
    <div class="summary-box">
        <h2><?php echo $feedbackCount; ?></h2>
        <p>Feedback Received</p>
    </div>
</div>
<div class="charts-section">
    <div id="user-chart" class="chart-container">
        <h3>User Distribution</h3>
        <canvas id="userDistributionChart"></canvas>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // User Distribution Chart
        const userDistributionCtx = document.getElementById('userDistributionChart').getContext('2d');
        new Chart(userDistributionCtx, {
            type: 'pie',
            data: {
                labels: ['Clients', 'Contractors', 'Admins'],
                datasets: [{
                    data: [<?php echo $clientCount; ?>, <?php echo $contractorCount; ?>, <?php echo $adminCount; ?>],
                    backgroundColor: ['#3498db', '#e74c3c', '#9b59b6']
                }]
            },
            options: {
                responsive: true,
            }
        });
    });
</script>

<style>
    .dashboard-summary {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 40px;
        /* Add space below summary boxes */
        flex-wrap: nowrap;
        /* Ensure the summary boxes stay in one row */
    }

    .summary-box {
        flex: 1 1 calc(20% - 20px);
        /* Adjusted to have five boxes in one row */
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        padding: 25px;
        text-align: center;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        justify-content: center;
        height: 180px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .summary-box:hover {
        transform: translateY(-8px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
    }

    .summary-box h2 {
        font-size: 2rem;
        color: #333;
        margin-bottom: 10px;
        font-weight: bold;
    }

    .summary-box p {
        font-size: 1.1rem;
        color: #777;
        margin: 0;
    }

    .charts-section {
        display: flex;
        justify-content: center;
        /* Center the chart */
        margin-top: 40px;
        /* Space between summary boxes and chart */
    }

    .chart-container {
        background-color: #fff;
        padding: 25px;
        border: 1px solid #ddd;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        width: 80%;
        /* Ensure the chart takes up a good amount of space */
        height: 400px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .chart-container h3 {
        margin-bottom: 25px;
        font-size: 1.3rem;
        text-align: center;
        color: #444;
    }

    .chart-container canvas {
        display: block;
        max-width: 100%;
        height: auto;
        margin: 0 auto;
    }
</style>