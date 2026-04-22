<?php
ob_start();

// Include the database connection
include("includes/contractor-header.php");
include("includes/db-connection.php");

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $property_type = mysqli_real_escape_string($conn, $_POST['property_type']);
    $min_price = str_replace(',', '', mysqli_real_escape_string($conn, $_POST['min_price']));
    $cont_id = $_SESSION['user_id']; // Use the logged-in user's ID

    // Ensure upload directory exists
    $target_dir = "uploads/images/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Handle multiple image uploads
    $package_images = [];
    $upload_successful = true;

    if (isset($_FILES['fileToUpload']) && is_array($_FILES['fileToUpload']['name'])) {
        // Check if number of files exceeds limit
        if (count($_FILES['fileToUpload']['name']) > 5) {
            $message = "You can only upload up to 5 images.";
            $upload_successful = false;
        } else {
            // Process each uploaded file
            foreach ($_FILES['fileToUpload']['name'] as $key => $name) {
                if ($_FILES['fileToUpload']['error'][$key] === 0) {
                    $imageFileType = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                    // Validate file type
                    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png'])) {
                        $message = "Only JPG, JPEG, and PNG files are allowed.";
                        $upload_successful = false;
                        break;
                    }

                    // Generate unique filename
                    $unique_filename = uniqid() . '_' . $name;
                    $target_file = $target_dir . $unique_filename;

                    // Upload file
                    if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'][$key], $target_file)) {
                        $package_images[] = $unique_filename;
                    } else {
                        $message = "Failed to upload image: " . $name;
                        $upload_successful = false;
                        break;
                    }
                }
            }
        }
    } else {
        $message = "Please select at least one image.";
        $upload_successful = false;
    }

    // Insert into database if no errors
    if ($upload_successful && empty($message)) {
        $package_status = "Pending";
        $package_img = implode(',', $package_images); // Store image names as comma-separated string

        $sql = "INSERT INTO package (package_title, package_type, package_img, package_price, package_status, cont_id) 
                VALUES ('$title', '$property_type', '$package_img', '$min_price', '$package_status', '$cont_id')";

        if (mysqli_query($conn, $sql)) {
            echo "<script>
                alert('Package successfully posted!');
                window.location.href = 'contractor.php';
            </script>";
            exit;
        } else {
            $message = "Database Error: " . mysqli_error($conn);
        }
    }
}
?>

<div class="open">
    <p>Post a Package Now</p>
</div>

<div class="form-container">
    <?php if (!empty($message)): ?>
        <p class="message"><?= $message; ?></p>
    <?php endif; ?>
    <form action="package-posting.php" method="post" enctype="multipart/form-data">
        <label for="title">Package Title:</label>
        <input type="text" id="title" name="title" required>

        <label for="property-image">Package Image:</label>
        <div class="file-upload-box" id="uploadBox">
            <input type="file" name="fileToUpload[]" id="imageUpload" accept="image/*" required style="display: none;">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <p>Upload image</p>
            <span class="file-support">Supports: JPG, PNG</span>
            <div id="imagePreview" class="image-preview-container"></div>
        </div>

        <label for="property-type">Package Type:</label>
        <select id="property-type" name="property_type" required>
            <option value="">Select Type</option>
            <option value="Residential">Residential</option>
            <option value="Commercial">Commercial</option>
            <option value="Industrial">Industrial</option>
            <option value="Others">Others</option>
        </select>

        <div class="price-range">
            <label for="min-price">Min Price From (RM):</label>
            <input type="text" id="min-price" name="min_price" required>
        </div>

        <button class="postbtn" type="submit">Post Package</button>
    </form>
</div>

<script>
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

    // Price formatting
    document.getElementById('min-price').addEventListener('blur', function () {
        const value = this.value;
        if (!isNaN(value) && value.trim() !== "") {
            const formattedValue = Number(value.replace(/,/g, '')).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
            this.value = formattedValue;
        }
    });

    document.getElementById('min-price').addEventListener('focus', function () {
        this.value = this.value.replace(/,/g, '');
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

<?php
include("includes/footer.php");
ob_end_flush();
?>

<style>
    .preview-item {
        position: relative;
        display: inline-block;
        margin: 10px;
    }

    .remove-image {
        position: absolute;
        top: 0;
        right: 0;
        background: rgba(0, 0, 0, 0.5);
        color: white;
        border: none;
        font-size: 20px;
        padding: 5px 10px;
        cursor: pointer;
        border-radius: 50%;
    }

    .remove-image:hover {
        background: rgba(255, 0, 0, 0.8);
    }
</style>