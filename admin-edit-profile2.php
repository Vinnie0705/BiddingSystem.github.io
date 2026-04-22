<?php
include("includes/client-header.php");

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'gys');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Fetch user data
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);

    if (!empty($name) && !empty($username) && !empty($email) && !empty($contact)) {
        $updateQuery = "UPDATE users SET user_name = ?, user_username = ?, user_email = ?, user_contact = ? WHERE user_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ssssi", $name, $username, $email, $contact, $user_id);

        if ($updateStmt->execute()) {
            $successMessage = "Profile updated successfully!";
            echo "<script>window.location.href = 'admin-profile.php';</script>";
        } else {
            $errorMessage = "Error updating profile: " . $updateStmt->error;
        }
        $updateStmt->close();
    } else {
        $errorMessage = "All fields are required!";
    }
}

$conn->close();
?>

<div class="profile">
    <div class="profile-container">
        <h2>Edit Profile</h2>
        <?php 
        if (isset($successMessage)) {
            echo "<div class='alert alert-success'>$successMessage</div>";
        }
        if (isset($errorMessage)) {
            echo "<div class='alert alert-danger'>$errorMessage</div>";
        }
        ?>
        <form method="POST" action="admin-edit-profile.php" class="edit-profile-form">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['user_name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['user_username']) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['user_email']) ?>" required>
            </div>
            <div class="form-group">
                <label for="contact">Contact Number:</label>
                <input type="tel" class="form-control" id="contact" name="contact" value="<?= htmlspecialchars($user['user_contact']) ?>" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-edit">Save Changes</button>
                <a href="profile.php" class="btn btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include("includes/footer.php"); ?>

<style>
    body {
        background-color: #ffffff; /* White background */
        font-family: 'Arial', sans-serif;
        margin: 0;
        padding: 0;
    }

    .profile {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }

    .profile-container {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        max-width: 500px;
        width: 100%;
        padding: 30px;
        text-align: center;
    }

    .edit-profile-form {
        text-align: left;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }

    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 16px;
    }

    .form-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        font-size: 16px;
        transition: background 0.3s ease;
    }

    .btn-edit {
        background-color: #007bff;
        color: white;
    }

    .btn-edit:hover {
        background-color: #0056b3;
    }

    .btn-cancel {
        background-color: #e0e0e0;
        color: black;
    }

    .btn-cancel:hover {
        background-color: #c0c0c0;
    }

    .alert {
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 5px;
        font-size: 14px;
    }

    .alert-success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
    }

    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }
</style>
