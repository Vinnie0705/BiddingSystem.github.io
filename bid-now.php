<?php
include("includes/db-connection.php");
include("includes/contractor-header.php");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure proj_id is passed as a GET parameter
$proj_id = isset($_GET['package_id']) ? intval($_GET['package_id']) : null;

if ($proj_id) {
    // Fetch project details and validate client_id
    $query = "SELECT * FROM project WHERE proj_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $proj_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $project = $result->fetch_assoc();
        $client_id = $project['client_id']; // Ensure this column exists in the `project` table
    } else {
        die("Project not found.");
    }
    $stmt->close();
} else {
    die("Project ID is missing.");
}

// Form submission handling
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if contractor is logged in
    if (!isset($_SESSION['user_id'])) {
        die("You must be logged in to submit a bid.");
    }

    // Retrieve contractor ID from session
    $cont_id = $_SESSION['user_id'];

    // Retrieve admin_id (assuming one admin exists in your database)
    $admin_query = "SELECT user_id FROM users WHERE user_type = 'admin' LIMIT 1";
    $admin_result = $conn->query($admin_query);
    if ($admin_result->num_rows > 0) {
        $admin_row = $admin_result->fetch_assoc();
        $admin_id = $admin_row['user_id'];
    } else {
        die("No admin found in the database.");
    }

    // Get and sanitize form inputs
    $contractor_name = mysqli_real_escape_string($conn, $_POST['contractor_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Handle BQ file upload (single file)
    $bq_file = $_FILES['bq_file']['name'];
    $bq_file_path = "uploads/bq/" . basename($bq_file);
    if (!move_uploaded_file($_FILES['bq_file']['tmp_name'], $bq_file_path)) {
        $error = "Error uploading the BQ file.";
    }

    // Insert the bid into the database
    $insert_query = "INSERT INTO bid (proj_id, cont_id, admin_id, client_id, bid_name, bid_email, bid_date, bid_bq_file) 
                     VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iiiisss", $proj_id, $cont_id, $admin_id, $client_id, $contractor_name, $email, $bq_file_path);

    if ($stmt->execute()) {
        echo "<script>
        alert('Bid submitted successfully!');
        window.location.href = 'contractor.php'; 
    </script>";
    } else {
        echo "<script>
        alert('Failed to submit your bid. Please try again.');
        window.location.href = 'bid-now.php.php'; 
    </script>";
    }
    $stmt->close();
}

$conn->close();
?>

<!-- HTML code for displaying success or error message -->
<div class="open">
    <p>Complete Your Bid</p>
</div>

<!-- Bid Form -->
<div class="bid-form-container">
    <h2>Bid for Project: <?= htmlspecialchars($project['proj_title'] ?? 'Unknown Project') ?></h2>

    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="proj_id" value="<?= htmlspecialchars($proj_id) ?>">

        <label for="contractor_name">Full Name:</label>
        <input type="text" name="contractor_name" id="contractor_name" required>

        <label for="email">Email Address:</label>
        <input type="email" name="email" id="email" required>

        <label for="email">Bill of Quantity (BQ):</label>
        <label for="bq_file" class="cloud">
            <div class="cloud-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
            <p>Upload BQ File</p>
            <input type="file" id="bq_file" name="bq_file" accept=".pdf,.doc,.docx" required style="display:none;"
                onchange="displayFileName()">
            <div id="file-preview">
                <!-- File name will be shown here after selection -->
            </div>
        </label>

        <button type="submit" class="submit-btn">Submit Bid</button>
    </form>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="offinfo">
        <h2>Office Information</h2>
        <h4>GYS Resources</h4>
        <p>25, Taman Sentosa, <br>Batu 10, Jalan Kapar, <br>Kapar, Malaysia.</p>
    </div>
    <div class="contact">
        <div class="us">
            <ul>
                <h4>Contact Us</h4>
                <li><i class="fa fa-phone"></i> 012-655 7817</li>
                <li><i class="fa fa-envelope"></i> enquiry.gys@gmail.com</li>
            </ul>
        </div>
        <div class="icons">
            <a href="https://www.facebook.com/gysresources/" class="fa-brands fa-facebook" target="_blank"></a>
            <a href="https://www.instagram.com/gysresources/" class="fa-brands fa-instagram" target="_blank"></a>
        </div>
    </div>
    <div id="footer_logo">
        <img src="img/gys.png" alt="GYS Logo" height="100" width="120">
    </div>
</footer>
<div class="copyright">
    <p>Copyright 2020 All Rights Reserved Company Name</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"></script>
<script src="js/totop.js"></script>
<script src="js/noti.js"></script>
<script src="js/cart.js"></script>
<script src="js/read.js"></script>
<script>
    function displayFileName() {
        var fileInput = document.getElementById('bq_file');
        var fileName = fileInput.files[0].name;

        // Show file name below the upload box
        var preview = document.getElementById('file-preview');
        preview.innerHTML = `<p><strong>Selected File:</strong> ${fileName}</p>`;
    }
</script>
</body>

</html>

<style>
    /* Bid Form Container Styling */
    .bid-form-container {
        background-color: #fff;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        max-width: 700px;
        margin: 50px auto;
    }

    .bid-form-container h2 {
        color: #b2444f;
        font-size: 28px;
        font-weight: bold;
        margin-bottom: 30px;
        text-align: center;
    }

    .bid-form-container label {
        display: block;
        margin-top: 15px;
        font-size: 16px;
        color: #444;
    }

    .bid-form-container input[type="text"],
    .bid-form-container input[type="email"],
    .bid-form-container input[type="file"] {
        width: 100%;
        padding: 12px;
        margin-top: 25px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
        transition: all 0.3s ease;
    }

    .bid-form-container input[type="file"] {
        padding: 10px;
    }

    .bid-form-container input[type="text"]:focus,
    .bid-form-container input[type="email"]:focus,
    .bid-form-container input[type="file"]:focus {
        border-color: #b2444f;
        outline: none;
        box-shadow: 0 0 5px rgba(178, 68, 79, 0.6);
    }

    /* Button Styling */
    .bid-form-container button {
        margin-top: 30px;
        background-color: #b2444f;
        color: white;
        padding: 15px 20px;
        border: none;
        border-radius: 5px;
        width: 100%;
        font-size: 18px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .bid-form-container button:hover {
        background-color: #8d3540;
        transform: scale(1.05);
    }

    .bid-form-container button:active {
        background-color: #8d3540;
        box-shadow: 0 0 10px rgba(178, 68, 79, 0.3);
    }

    /* Cloud Upload Section */
    .cloud {
        text-align: center;
        margin-top: 20px;
    }

    .cloud-icon {
        font-size: 35px;
        color: #b2444f;
        margin-bottom: 10px;
    }

    .cloud p {
        font-size: 16px;
        color: #444;
    }

    .custom-file-upload {
        display: inline-block;
        padding: 10px 20px;
        background-color: #b2444f;
        color: white;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        transition: all 0.3s ease;
    }

    .custom-file-upload:hover {
        background-color: #8d3540;
    }

    input[type="file"] {
        display: none;
        /* Hide the default file input */
    }

    /* Custom file input button */
    input[type="file"]:focus+.custom-file-upload {
        background-color: #8d3540;
        box-shadow: 0 0 5px rgba(178, 68, 79, 0.6);
    }

    @media (max-width: 768px) {
        .bid-form-container {
            padding: 20px;
        }

        .bid-form-container h2 {
            font-size: 24px;
        }

        .bid-form-container button {
            font-size: 16px;
        }

        .open {
            font-size: 20px;
        }
    }
</style>