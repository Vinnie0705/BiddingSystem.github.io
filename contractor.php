<?php
ob_start(); // Start output buffering here as well
// Prevent page caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include("includes/contractor-header.php");
include("includes/db-connection.php"); // Database connection file

// Your existing PHP code here including the form submission handling

// Before the end of your PHP script, flush the output buffer
ob_end_flush();

// Fetch the 6 newest approved projects from the database
$query = "SELECT * FROM project WHERE proj_status = 'approved' ORDER BY proj_created DESC LIMIT 6";
$result = mysqli_query($conn, $query);

// Fetch the logged-in contractor's ID from session
$user_id = $_SESSION['user_id']; // Assuming user_id is stored in session when contractor logs in

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capture and sanitize form data
    $full_name = mysqli_real_escape_string($conn, $_POST['full-name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $first_visit = mysqli_real_escape_string($conn, $_POST['first-visit']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    
    // Convert first_visit 'yes'/'no' to '0'/'1'
    $first_visit = ($first_visit == 'yes') ? 0 : 1;
    
    // Prepare the SQL statement to insert feedback
    $query = "INSERT INTO feedback (fb_name, fb_email, fb_firsttime, fb_subject, fb_message, user_id) 
              VALUES ('$full_name', '$email', '$first_visit', '$subject', '$message', '$user_id')";
    
    // Execute the query and handle success or failure
    if (mysqli_query($conn, $query)) {
        // Redirect to avoid resubmission with a success message
        header("Location: contractor.php?feedback=success");
        exit();
    } else {
        echo "<script>alert('There was an error submitting your feedback. Please try again.');</script>";
    }
}

// Check if feedback was successfully submitted
if (isset($_GET['feedback']) && $_GET['feedback'] == 'success') {
    echo "<script>alert('Feedback successfully sent');</script>";
}
?>

<div class="open">
    <p>Welcome to Contractor Dashboard</p>
</div>

<div class="projects">
    <p>All Projects</p>
    <hr>
    <div class="packages-view2">
        <div class="packages-grid2">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="package-card2">
                        <h3><?= htmlspecialchars($row['proj_title']) ?></h3>
                        <p>Type: <?= htmlspecialchars($row['proj_type']) ?></p>
                        <p>Location: <?= htmlspecialchars($row['proj_location']) ?></p>
                        <p>Start Date: <?= htmlspecialchars(date('F j, Y', strtotime($row['proj_startdate']))) ?></p>
                        <div class="package-image2">
                            <img src="uploads/images/<?= htmlspecialchars($row['proj_img']) ?>"
                                alt="<?= htmlspecialchars($row['proj_title']) ?>" style="height:200px;" />
                        </div>
                        <div class="package-created"
                            style="opacity: 0.6; text-align: right; font-size: 12px; color: #777; padding-top: 2%;">
                            <p>Posted on: <?= htmlspecialchars(date('F j, Y', strtotime($row['proj_created']))) ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No projects found.</p>
            <?php endif; ?>
        </div>
    </div>
    <button class="project-view" onclick="window.location.href='projects-view.php'">View More</button>
</div>

<div class="post-package">
    <div class="post-now-section">
        <div class="post-now-content">
            <h2>Grow Your Business with Ease!</h2>
            <p>Showcase your services to thousands of potential clients. Join the platform trusted by contractors nationwide.</p>
            <ul class="post-benefits">
                <li>✔ Reach More Clients Easily.</li>
                <li>✔ Highlight Your Unique Packages.</li>
                <li>✔ Get Noticed and Win More Jobs.</li>
            </ul>
            <button class="post-now-btn" onclick="window.location.href='package-posting.php'">Post Your Package</button>
            <p class="cta-note">Don’t wait! Start posting your packages today!</p>
        </div>
    </div>
</div>

<div class="feedback-send">
    <p class="feedback-title">Feedback Form</p>
    <hr style="color:white;">
    <div class="feedback-form-section">
        <form method="POST">
            <label for="full-name">Full Name</label>
            <input type="text" id="full-name" name="full-name" placeholder="Enter your full name" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>

            <p>Is this the first time you visited this website?</p>
            <div class="radio-group">
                <input type="radio" id="yes" name="first-visit" value="yes">
                <label for="yes">Yes</label>
                <input type="radio" id="no" name="first-visit" value="no">
                <label for="no">No</label>
            </div>

            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" placeholder="Enter subject" required>

            <label for="message">Message</label>
            <textarea id="message" name="message" placeholder="Enter your message" rows="5" required></textarea>

            <button type="submit">Submit</button>
        </form>
    </div>
</div>

<?php include("includes/footer.php"); ?>