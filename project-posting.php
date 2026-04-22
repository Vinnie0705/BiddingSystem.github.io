<?php
ob_start();

// Include header and database connection
include("includes/client-header.php");
include("includes/db-connection.php");

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $property_type = mysqli_real_escape_string($conn, $_POST['property_type']);
    $property_size = mysqli_real_escape_string($conn, $_POST['property_size']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $client_id = $_SESSION['user_id']; // Use the logged-in user's ID

    // Validate start date format
    $start_date_parsed = DateTime::createFromFormat('Y-m-d', $start_date);
    if (!$start_date_parsed) {
        $message = "Invalid start date format. Please use YYYY-MM-DD.";
    }

    // Handle multiple image uploads
    $project_images = [];
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
                    $target_file = "uploads/images/" . $unique_filename;

                    // Upload file
                    if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'][$key], $target_file)) {
                        $project_images[] = $unique_filename;
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

    // Handle proposal upload
    $proposal_dir = "uploads/proposals/";
    if (!is_dir($proposal_dir)) {
        mkdir($proposal_dir, 0777, true);
    }

    $proposal_name = basename($_FILES["proposal"]["name"]);
    $proposal_target_file = $proposal_dir . $proposal_name;
    $proposalFileType = strtolower(pathinfo($proposal_target_file, PATHINFO_EXTENSION));

    if (!in_array($proposalFileType, ['pdf', 'doc', 'docx'])) {
        $message = "Only PDF, DOC, and DOCX files are allowed for the proposal.";
    } elseif (!move_uploaded_file($_FILES["proposal"]["tmp_name"], $proposal_target_file)) {
        $message = "Failed to upload the proposal.";
    } else {
        $project_proposal = $proposal_name;
    }

    // Insert into database if no errors
    if ($upload_successful && empty($message)) {
        $project_status = "Pending";
        $project_img = implode(',', $project_images); // Store image names as comma-separated string

        $sql = "INSERT INTO project (proj_title, proj_type, proj_size, proj_location, proj_startdate, proj_img, proj_proposal, client_id) 
                VALUES ('$title', '$property_type', '$property_size', '$location', '$start_date', '$project_img', '$project_proposal', '$client_id')";

        if (mysqli_query($conn, $sql)) {
            echo "<script>
                alert('Project successfully posted!');
                window.location.href = 'client.php';
            </script>";
            exit; // Prevent further script execution
        } else {
            $message = "Database Error: " . mysqli_error($conn);
        }
    }
}
?>

<div class="open">
    <p>Post a Project Now</p>
</div>

<div class="form-container">
    <?php if (!empty($message)): ?>
        <p class="message"><?= $message; ?></p>
    <?php endif; ?>
    <form action="project-posting.php" method="post" enctype="multipart/form-data">
        <!-- Existing form fields -->
        <label for="title">Project Title:</label>
        <input type="text" id="title" name="title" required>

        <label for="property-image">Project Image:</label>
        <div class="file-upload-box" id="uploadBox">
            <input type="file" name="fileToUpload[]" id="imageUpload" accept="image/*" required style="display: none;"
                multiple>
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <p>Upload image</p>
            <span class="file-support">Supports: JPG, PNG</span>
            <div id="imagePreview" class="image-preview-container"></div>
        </div>

        <label for="property-type">Property Type:</label>
        <select id="property-type" name="property_type" required>
            <option value="">Select Type</option>
            <option value="Residential">Residential</option>
            <option value="Commercial">Commercial</option>
            <option value="Industrial">Industrial</option>
            <option value="Others">Others</option>
        </select>

        <label for="property-size">Property Size (sq ft):</label>
        <input type="number" id="property-size" name="property_size" required>

        <label for="location">Location:</label>
        <select id="location" name="location" required>
            <option value="Johor">Johor</option>
            <option value="Kedah">Kedah</option>
            <option value="Kelantan">Kelantan</option>
            <option value="Melaka">Melaka</option>
            <option value="Negeri Sembilan">Negeri Sembilan</option>
            <option value="Pahang">Pahang</option>
            <option value="Perak">Perak</option>
            <option value="Perlis">Perlis</option>
            <option value="Penang">Penang</option>
            <option value="Sabah">Sabah</option>
            <option value="Sarawak">Sarawak</option>
            <option value="Selangor">Selangor</option>
            <option value="Terengganu">Terengganu</option>
            <option value="Kuala Lumpur">Kuala Lumpur</option>
            <option value="Labuan">Labuan</option>
            <option value="Putrajaya">Putrajaya</option>
        </select>

        <div class="date-container">
            <div>
                <label for="start-date">Expected Start Date:</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>"
                    placeholder="yyyy-mm-dd" required>
            </div>
        </div>

        <!-- Proposal Upload Section with Preview -->
        <label for="proposal-upload">Upload Your Proposal:</label>
        <div class="file-upload-box" id="uploadBoxProposal">
            <input type="file" id="proposal" name="proposal" accept=".pdf,.doc,.docx" required style="display: none;">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <p>Upload File</p>
            <span class="file-support">Supports: PDF, DOC, DOCX (Max 5MB)</span>
            <div id="proposalPreview" class="file-preview-container"></div>
        </div>

        <button class="postbtn" type="submit">Post Project</button>
    </form>
</div>

<script>
    // File upload handling for project images
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

    // Handle file input change for proposal upload
    document.getElementById('proposal').addEventListener('change', function (event) {
        const file = event.target.files[0];
        const previewContainer = document.getElementById('proposalPreview');
        previewContainer.innerHTML = ""; // Clear previous content

        if (file) {
            const fileName = document.createElement('p');
            fileName.textContent = `File: ${file.name}`;
            previewContainer.appendChild(fileName);

            // Display an icon or thumbnail based on file type
            const fileType = file.type;
            if (fileType === 'application/pdf') {
                const pdfIcon = document.createElement('i');
                pdfIcon.className = 'fa-solid fa-file-pdf';
                previewContainer.prepend(pdfIcon);
            } else if (fileType === 'application/msword' || fileType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                const docIcon = document.createElement('i');
                docIcon.className = 'fa-solid fa-file-word';
                previewContainer.prepend(docIcon);
            }
        }
    });

    // Trigger file input when the upload box is clicked
    document.getElementById('uploadBoxProposal').addEventListener('click', function () {
        document.getElementById('proposal').click();
    });
</script>

<?php include("includes/footer.php");
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