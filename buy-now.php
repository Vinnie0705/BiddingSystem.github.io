<?php
include("includes/client-header.php");
include("includes/db-connection.php");

// Get the logged-in client ID
$client_id = $_SESSION['user_id'] ?? 0;
if ($client_id == 0) {
    die("Error: User not logged in.");
}

// Get the package ID from URL
$packageId = $_GET['package_id'] ?? 0;
if ($packageId == 0) {
    echo "<p>Invalid package selected.</p>";
    include("includes/footer.php");
    exit;
}

// Fetch package details
$query = "SELECT * FROM package WHERE package_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $packageId);
$stmt->execute();
$result = $stmt->get_result();
$package = $result->fetch_assoc();
if (!$package) {
    echo "<p>Package not found.</p>";
    include("includes/footer.php");
    exit;
}

// Get contractor ID from the package
$cont_id = $package['cont_id'] ?? null;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cp_name = $_POST['cp_name'] ?? '';
    $cp_email = $_POST['cp_email'] ?? '';
    $cp_type = $_POST['cp_type'] ?? '';
    $cp_size = $_POST['cp_size'] ?? '';
    $cp_startdate = $_POST['cp_startdate'] ?? '';
    $cp_enddate = $_POST['cp_enddate'] ?? '';
    $created_at = date("Y-m-d H:i:s");
    $updated_at = date("Y-m-d H:i:s");

    // Handle multiple file uploads
    $imagePaths = [];
    if (!empty($_FILES['cp_images']['name'][0])) {
        $target_dir = "uploads/images/";
        foreach ($_FILES['cp_images']['name'] as $key => $filename) {
            $fileTmpPath = $_FILES['cp_images']['tmp_name'][$key];
            $fileType = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            // Validate file type
            $allowed_types = ["jpg", "jpeg", "png"];
            if (!in_array($fileType, $allowed_types)) {
                die("Error: Only JPG, JPEG, and PNG files are allowed.");
            }

            // Generate unique file name & save
            $uniqueName = uniqid("img_", true) . "." . $fileType;
            $filePath = $target_dir . $uniqueName;
            if (move_uploaded_file($fileTmpPath, $filePath)) {
                $imagePaths[] = $filePath;
            } else {
                die("Error: Failed to upload an image.");
            }
        }
    }

    // Convert image paths array to JSON
    $cp_images = json_encode($imagePaths);

    // Insert into `clientpackage` table
    $query = "INSERT INTO clientpackage (package_id, client_id, cont_id, cp_name, cp_email, cp_images, cp_type, cp_size, cp_startdate, cp_enddate, created_at, updated_at) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Error: " . $conn->error);
    }
    $stmt->bind_param("iiisssssssss", $packageId, $client_id, $cont_id, $cp_name, $cp_email, $cp_images, $cp_type, $cp_size, $cp_startdate, $cp_enddate, $created_at, $updated_at);

    if ($stmt->execute()) {
        // Show success message
        echo "<script>alert('Package purchase successful!');</script>";
        echo "<script>window.location.href = 'client.php';</script>";
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>

<div class="open">
    <p>Complete Your Purchase</p>
</div>

<div class="buy-now-container">
    <div class="buy-now-grid">
        <!-- Left Side: Package Details -->
        <div class="package-details">
            <h2>Package Details</h2>
            <div class="package-card">
                <div class="package-image">
                    <img src="uploads/images/<?= htmlspecialchars($package['package_img']) ?>"
                        alt="<?= htmlspecialchars($package['package_title']) ?>">
                </div>
                <div class="package-info">
                    <h3><?= htmlspecialchars($package['package_title']) ?></h3>
                    <p class="package-type">Type: <?= htmlspecialchars($package['package_type']) ?></p>
                    <p class="package-price">Price from:
                        RM<?= number_format((float) $package['package_price'], 2, '.', ',') ?></p>
                    <?php if (!empty($package['package_proposal'])): ?>
                        <div class="package-proposal">
                            <a href="uploads/proposals/<?= htmlspecialchars($package['package_proposal']) ?>"
                                target="_blank" class="proposal-btn">
                                <i class="fa-solid fa-file-pdf"></i> View Proposal
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Side: Purchase Form -->
        <div class="purchase-form">
            <h2>Property Information</h2>
            <form id="purchase-form" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="package_id" value="<?= $packageId ?>">

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="cp_name" required placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="cp_email" required placeholder="Enter your email">
                </div>

                <div class="form-group">
                    <label for="property-image">Project Images</label>
                    <input type="file" name="cp_images[]" multiple accept="image/*" required>
                </div>

                <div class="form-group">
                    <label for="property-type">Property Type</label>
                    <select id="property-type" name="cp_type" required>
                        <option value="Residential">Residential</option>
                        <option value="Commercial">Commercial</option>
                        <option value="Industrial">Industrial</option>
                        <option value="Others">Others</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="property-size">Property Size (sq ft)</label>
                    <input type="number" id="property-size" name="cp_size" required placeholder="Enter property size">
                </div>

                <div class="form-group">
                    <label for="start-date">Expected Start Date</label>
                    <input type="date" id="start-date" name="cp_startdate" required>
                </div>

                <div class="form-group">
                    <label for="end-date">Expected End Date</label>
                    <input type="date" id="end-date" name="cp_enddate" required>
                </div>

                <button type="submit" class="submit-btn">Complete Purchase</button>
            </form>
        </div>
    </div>
</div>

<script>
    // Add date validation
    document.getElementById('start-date').addEventListener('change', function () {
        document.getElementById('end-date').min = this.value;
    });

    document.getElementById('end-date').addEventListener('change', function () {
        document.getElementById('start-date').max = this.value;
    });

    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('start-date').min = today;
    document.getElementById('end-date').min = today;

    // File upload handling
    const uploadBox = document.getElementById('uploadBox');
    const imageUpload = document.getElementById('imageUpload');
    const imagePreview = document.getElementById('imagePreview');

    uploadBox.addEventListener('click', () => {
        imageUpload.click();
    });

    imageUpload.addEventListener('change', function () {
        imagePreview.innerHTML = ''; // Clear existing previews

        if (this.files.length > 5) {
            alert('You can only upload up to 5 images');
            this.value = '';
            return;
        }

        Array.from(this.files).forEach(file => {
            if (!file.type.startsWith('image/')) {
                alert('Please upload only image files');
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                const previewContainer = document.createElement('div');
                previewContainer.className = 'preview-item';

                const img = document.createElement('img');
                img.src = e.target.result;

                const removeBtn = document.createElement('button');
                removeBtn.innerHTML = '×';
                removeBtn.className = 'remove-image';
                removeBtn.onclick = function (evt) {
                    evt.stopPropagation(); // Prevent triggering uploadBox click
                    previewContainer.remove();
                    // Reset file input if all previews are removed
                    if (imagePreview.children.length === 0) {
                        imageUpload.value = '';
                    }
                };

                previewContainer.appendChild(img);
                previewContainer.appendChild(removeBtn);
                imagePreview.appendChild(previewContainer);
            };
            reader.readAsDataURL(file);
        });
    });

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadBox.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Handle drop
    uploadBox.addEventListener('drop', function (e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        imageUpload.files = files;

        // Trigger change event manually
        const event = new Event('change');
        imageUpload.dispatchEvent(event);
    });
</script>

<?php include("includes/footer.php"); ?>