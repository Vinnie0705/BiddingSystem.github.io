<?php
include("includes/db-connection.php");
session_start();

// Get notification ID and type
$noti_id = $_GET['noti_id'] ?? null;
$noti_type = $_GET['type'] ?? null;

// Fetch notification details
$query = "SELECT * FROM notification WHERE noti_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $noti_id);
$stmt->execute();
$result = $stmt->get_result();
$notification = $result->fetch_assoc();
$stmt->close();

if (!$notification) {
    die("Notification not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notification Details</title>
</head>
<body>

<h2>Notification Details</h2>
<p><?= htmlspecialchars($notification['noti_message']); ?></p>
<p>Date: <?= $notification['noti_date']; ?></p>

<?php if ($noti_type === "payment_request_10"): ?>
    <h3>Make a 10% Payment</h3>
    <form action="process-payment.php" method="POST">
        <input type="hidden" name="noti_id" value="<?= $noti_id; ?>">
        <input type="hidden" name="amount" value="10%">
        <button type="submit">Proceed to Payment</button>
    </form>

<?php elseif ($noti_type === "request_floor_plan"): ?>
    <h3>Upload Floor Plan</h3>
    <form action="upload-floor-plan.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="noti_id" value="<?= $noti_id; ?>">
        <input type="file" name="floor_plan" accept="application/pdf" required>
        <button type="submit">Upload</button>
    </form>

<?php elseif ($noti_type === "send_floor_plan_to_client"): ?>
    <h3>Review Floor Plan</h3>
    <p>Your contractor has submitted a floor plan. Click below to view:</p>
    <a href="floorplans/<?= htmlspecialchars($notification['file_path']); ?>" target="_blank">View Floor Plan</a>

<?php elseif ($noti_type === "collect_30_percent_payment"): ?>
    <h3>Make a 30% Payment</h3>
    <form action="process-payment.php" method="POST">
        <input type="hidden" name="noti_id" value="<?= $noti_id; ?>">
        <input type="hidden" name="amount" value="30%">
        <button type="submit">Proceed to Payment</button>
    </form>

<?php else: ?>
    <p>Unknown action.</p>
<?php endif; ?>

</body>
</html>
