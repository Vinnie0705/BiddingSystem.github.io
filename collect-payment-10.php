<?php
include("includes/client-header.php");

// Database connection
$conn = new mysqli('localhost', 'root', '', 'gys');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get notification ID from URL
$notiId = $_GET['noti_id'] ?? 0;
if ($notiId == 0) {
    echo "<p>Invalid notification selected.</p>";
    include("includes/footer.php");
    exit;
}

// Fetch notification details to determine whether it's a project or package
$query = "SELECT n.ten_price, b.proj_id, cp.cp_id, cp.cp_type, cp.cp_size, cp.cp_images, u.user_id AS client_id, u2.user_id AS cont_id, cp.package_id
          FROM notification n
          LEFT JOIN bid b ON n.bid_id = b.bid_id
          LEFT JOIN clientpackage cp ON n.cp_id = cp.cp_id
          LEFT JOIN users u ON b.client_id = u.user_id
          LEFT JOIN users u2 ON b.cont_id = u2.user_id
          WHERE n.noti_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $notiId);
$stmt->execute();
$result = $stmt->get_result();
$notification = $result->fetch_assoc();
$stmt->close();

if (!$notification) {
    echo "<p>Notification details not found.</p>";
    include("includes/footer.php");
    exit;
}

$tenPrice = $notification['ten_price'];
$isProject = !empty($notification['proj_id']);
$isPackage = !empty($notification['cp_id']);
$clientId = $notification['client_id'];
$contId = $notification['cont_id'];
$packageId = $notification['package_id']; // Fetch package_id from the query

if ($isProject) {
    // Fetch project details
    $projId = $notification['proj_id'];
    $query = "SELECT * FROM project WHERE proj_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $projId);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();
    $stmt->close();

    if (!$project) {
        echo "<p>Project not found.</p>";
        include("includes/footer.php");
        exit;
    }
} elseif ($isPackage) {
    // Fetch package details
    $cpId = $notification['cp_id'];
    $cpType = $notification['cp_type'];
    $cpSize = $notification['cp_size'];
    $cpImages = json_decode($notification['cp_images'], true);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Payment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Payment Container */
        .payment-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            max-width: 2000px;
        }

        .payment-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            max-width: 1800px;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        /* Details Section */
        .details {
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .details h2 {
            font-size: 22px;
            color: #1d72b8;
            margin-bottom: 20px;
        }

        .card {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: linear-gradient(135deg, #f8fafc, #eef2f5);
            border-radius: 15px;
            padding: 20px;
            gap: 15px;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card .image,
        .images {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            text-align: center;
        }

        .card .image img,
        .images img {
            width: 180px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin: 10px auto;
            display: block;
        }

        .info {
            text-align: center;
        }
        </style>
</head>
<body>
    <div class="open">
        <p>Complete Payment</p>
    </div>

    <div class="payment-container">
        <div class="payment-grid">
            <?php if ($isProject): ?>
                <!-- Project Details Section -->
                <div class="details">
                    <h2>Project Details</h2>
                    <div class="card">
                        <div class="image">
                            <img src="uploads/images/<?= htmlspecialchars($project['proj_img']) ?>" alt="<?= htmlspecialchars($project['proj_title']) ?>">
                        </div>
                        <div class="info">
                            <h3><?= htmlspecialchars($project['proj_title']) ?></h3>
                            <p>Type: <?= htmlspecialchars($project['proj_type']) ?></p>
                            <p>Price: RM<?= number_format((float) $tenPrice, 2, '.', ',') ?></p>
                        </div>
                    </div>
                </div>
            <?php elseif ($isPackage): ?>
                <!-- Package Details Section -->
                <div class="details">
                    <h2>Package Details</h2>
                    <div class="card">
                        <div class="images">
                            <?php if (!empty($cpImages)): ?>
                                <?php foreach ($cpImages as $imagePath): ?>
                                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="Package Image">
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No images available for this package.</p>
                            <?php endif; ?>
                        </div>
                        <div class="info">
                            <h3><?= htmlspecialchars($cpType) ?></h3>
                            <p>Size: <?= htmlspecialchars($cpSize) ?></p>
                            <p>Price: RM<?= number_format((float) $tenPrice, 2, '.', ',') ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Payment Form -->
            <div class="payment-form">
                <h2>Upload Payment Receipt</h2>
                <form id="payment-form" action="<?= $isProject ? 'process-payment.php' : 'process-payment-package.php' ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="<?= $isProject ? 'proj_id' : 'cp_id' ?>" value="<?= htmlspecialchars($isProject ? $projId : $cpId) ?>">
                    <input type="hidden" name="notification_id" value="<?= htmlspecialchars($notiId) ?>">
                    <input type="hidden" name="client_id" value="<?= htmlspecialchars($clientId) ?>">
                    <input type="hidden" name="cont_id" value="<?= htmlspecialchars($contId) ?>">
                    <input type="hidden" name="package_id" value="<?= htmlspecialchars($packageId) ?>"> <!-- Add package_id here -->

                    <div class="form-group">
                        <label for="receipt">Upload Receipt (PDF, PNG, JPG)</label>
                        <label for="receipt" class="cloud">
                            <div class="cloud-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                            <p>Upload Receipt (PDF, PNG, JPG)</p>
                            <input type="file" id="receipt" name="receipt" accept=".pdf, .png, .jpg, .jpeg" required style="display: none;" onchange="displayFileName()">
                            <div id="file-preview"></div>
                        </label>
                    </div>

                    <button type="submit" class="submit-btn">Submit Payment</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function displayFileName() {
            var input = document.getElementById('receipt');
            var preview = document.getElementById('file-preview');

            if (input.files.length > 0) {
                var file = input.files[0];
                var fileType = file.type;
                var reader = new FileReader();

                if (fileType.includes("image")) {
                    reader.onload = function (e) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Receipt Preview" width="150" style="margin-top: 10px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">`;
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.innerHTML = `<p>${file.name}</p>`;
                }
            } else {
                preview.innerHTML = "";
            }
        }
    </script>

    <?php include("includes/footer.php"); ?>
</body>
</html>