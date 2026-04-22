<?php
include("includes/db-connection.php");
include("includes/admin-header.php");

// Debugging: Check if user_id is provided
if (!isset($_GET['user_id'])) {
    $_SESSION['edit_error_message'] = "User ID is missing!";
    header("Location: admin.php");
    exit();
}

$user_id = $_GET['user_id'];
// Fetch user data from the database
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if the user exists
if (!$user) {
    $_SESSION['edit_error_message'] = "User not found!";
    header("Location: admin.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['user_name'];
    $username = $_POST['user_username'];
    $email = $_POST['user_email'];
    $contact = $_POST['user_contact'];
    $user_type = $_POST['user_type'];

    $query = "UPDATE users SET user_name = ?, user_username = ?, user_email = ?, user_contact = ?, user_type = ? WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssssi", $name, $username, $email, $contact, $user_type, $user_id);
    $stmt->execute();

    $_SESSION['edit_success_message'] = "User updated successfully!";
    header("Location: admin.php");
    exit();
}
?>

    <style>
        body {
            background-color: #f8f9fa;
        }
        .form-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-container h2 {
            margin-bottom: 20px;
            font-weight: bold;
            color: #b2444b;
        }
        .form-container .form-control {
            border-radius: 5px;
            padding: 10px;
        }
        .form-container .btn {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
        }
        .form-container .btn-primary {
            background-color: #007bff;
            border: none;
        }
        .form-container .btn-primary:hover {
            background-color: #0056b3;
        }
    </style>

    <div class="form-container">
        <h2 class="text-center"><i class="fas fa-user-edit"></i> Edit User</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="user_name" value="<?php echo htmlspecialchars($user['user_name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="user_username" value="<?php echo htmlspecialchars($user['user_username']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="user_email" value="<?php echo htmlspecialchars($user['user_email']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="contact" class="form-label">Contact</label>
                <input type="text" class="form-control" id="contact" name="user_contact" value="<?php echo htmlspecialchars($user['user_contact']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="user_type" class="form-label">User Type</label>
                <select class="form-select" id="user_type" name="user_type" required>
                    <option value="client" <?php echo ($user['user_type'] == 'client') ? 'selected' : ''; ?>>Client</option>
                    <option value="contractor" <?php echo ($user['user_type'] == 'contractor') ? 'selected' : ''; ?>>Contractor</option>
                    <option value="admin" <?php echo ($user['user_type'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update User</button>
        </form>
    </div>

    <?php include("includes/footer.php"); ?>
