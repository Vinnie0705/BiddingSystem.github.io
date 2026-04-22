<?php
ob_start();
// Prevent page caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include("includes/client-header.php");  // client-header already has session_start()
include("includes/db-connection.php"); // Database connection file

// Fetch the 6 newest approved packages from the database
$query = "SELECT * FROM package WHERE package_status = 'approved' ORDER BY package_created DESC LIMIT 6";
$result = mysqli_query($conn, $query);

// Fetch the logged-in user's ID from session
$user_id = $_SESSION['user_id']; // Assuming user_id is stored in session when user logs in

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
        header("Location: client.php?feedback=success");
        exit();
    } else {
        echo "<script>alert('There was an error submitting your feedback. Please try again.');</script>";
    }
}

// Check if feedback was successfully submitted
if (isset($_GET['feedback']) && $_GET['feedback'] == 'success') {
    echo "<script>alert('Feedback successfully sent');</script>";
}

ob_end_flush();
?>

<div class="open">
  <p>Welcome to Client Dashboard</p>
</div>

<div class="packages">
  <p>All Packages</p>
  <hr>
  <div class="packages-view2">
    <div class="packages-grid2">
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="package-card2">
            <h3><?= htmlspecialchars($row['package_title']) ?></h3>
            <p>Type: <?= htmlspecialchars($row['package_type']) ?></p>
            <p>Price: from RM<?= number_format((float) $row['package_price'], 2, '.', ',') ?></p>
            <div class="package-image2">
              <img src="uploads/images/<?= htmlspecialchars($row['package_img']) ?>"
                alt="<?= htmlspecialchars($row['package_title']) ?>" style="height:200px;" />
            </div>
            <div class="package-created"
              style="opacity: 0.6; text-align: right; font-size: 12px; color: #777; padding-top: 2%;">
              <p>Created on: <?= htmlspecialchars(date('F j, Y', strtotime($row['package_created']))) ?></p>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>No packages found.</p>
      <?php endif; ?>
    </div>
  </div>
  <button class="package-view" onclick="window.location.href='packages-view.php'">View More</button>
</div>

<div class="post-project">
  <div class="post-now-section">
    <div class="post-now-content">
      <h2>Get Your Project Done Fast!</h2>
      <p>Save time and find the perfect contractor. Join over 1,000 clients who've successfully completed their
        projects.</p>
      <ul class="post-benefits">
        <li>✔ 24/7 Support.</li>
        <li>✔ Get Competitive Bids.</li>
        <li>✔ Work Well with Contractors.</li>
      </ul>
      <button class="post-now-btn" onclick="window.location.href='project-posting.php'">Post Now</button>
      <p class="cta-note">Post your first project now!</p>
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
