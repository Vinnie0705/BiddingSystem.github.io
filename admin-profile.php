<?php
include("includes/admin-header.php");

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    // Redirect to login page if not an admin
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'gys');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the logged-in admin's ID from the session
$user_id = $_SESSION['user_id'];

// Query to get admin data
$query = "SELECT * FROM users WHERE user_id = ? AND user_type = 'admin'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Fetch admin data
    $admin = $result->fetch_assoc();
} else {
    echo "Admin not found.";
    exit();
}
?>

<div class="profile">
    <br><br><br>
    <div class="profile-container">
        <!-- Header Section -->
        <div class="profile-picture">
            <i class="profile-header fa-solid fa-user"></i>
        </div>
        <!-- Profile Info -->
        <div class="p-4">
            <form>
                <div class="mb-3">
                    <label for="name" class="form-label">Name:</label>
                    <input type="text" class="form-control" id="name"
                        value="<?= htmlspecialchars($admin['user_name']) ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" class="form-control" id="username"
                        value="<?= htmlspecialchars($admin['user_username']) ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" class="form-control" id="email"
                        value="<?= htmlspecialchars($admin['user_email']) ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="contact" class="form-label">Contact Number:</label>
                    <input type="tel" class="form-control" id="contact"
                        value="<?= htmlspecialchars($admin['user_contact']) ?>" readonly>
                </div>
                <button type="button" class="btn btn-edit" onclick="window.location.href='admin-edit-profile2.php';">Edit Profile</button>
            </form>

            <!-- Action Buttons -->
            <div class="text-center" style="display: flex; justify-content: center; margin-top:2%;">
                <form action="index.php" method="post">
                    <button type="button" class="btn btn-logout" onclick="confirmLogout()">Logout</button>
                </form>
                <form id="deleteForm" action="delete-account.php" method="post" style="padding-left: 1.5%;">
                    <button type="submit" class="btn btn-delete" onclick="return confirmDelete()">Delete
                        Account</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>

<script>
    function confirmDelete() {
        return confirm("Are you sure you want to delete your account? This action cannot be undone.");
    }

    function confirmLogout() {
        if (confirm("Are you sure you want to logout?")) {
            window.location.href = "index.php";
        }
    }
</script>

<style>
    .profile {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        padding: 50px 15px;
    }

    .profile-container {
        background-color: white;
        border-radius: 15px;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.4);
        max-width: 850px;
        width: 100%;
        padding: 40px;
        position: relative;
        text-align: center;
    }

    /* Profile Picture */
    .profile-picture {
        position: absolute;
        top: -50px;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 5%;
    }

    .profile-header {
        font-size: 2.5rem;
        color: white;
    }

    /* Form Styling */
    form {
        margin-top: 40px;
        text-align: left;
    }

    .mb-3 {
        margin-bottom: 15px;
    }

    .form-label {
        font-weight: bold;
        color: #333;
    }

    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        background-color: #f9f9f9;
    }

    .btn-edit {
        background: rgb(74, 151, 177);
        color: white;
        padding: 10px 20px;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        margin-top: 10px;
    }

    .btn-edit:hover {
        background: rgba(74, 151, 177, 0.74);
    }

    /* Action Buttons */
    .text-center {
        display: flex;
        justify-content: center;
        gap: 20px;
    }

    .btn-logout,
    .btn-delete {
        padding: 10px 20px;
        border-radius: 5px;
        text-decoration: none;
        text-align: center;
        color: white;
        cursor: pointer;
        border: none;
        min-width: 120px;
        font-size: 16px;
    }

    .btn-logout {
        background: #444;
    }

    .btn-logout:hover {
        background: #333;
        color: white;
    }

    .btn-delete {
        background: red;
    }

    .btn-delete:hover {
        background: darkred;
        color: white;
    }
</style>