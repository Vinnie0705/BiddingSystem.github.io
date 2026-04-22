<?php
include("includes/db-connection.php");
include("includes/admin-header.php");

if ($conn->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $query = "INSERT INTO users (user_username, user_email, user_pw, user_contact, user_type) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $username, $email, $hashed_password, $contact, $user_type);
    $stmt->execute();

    $_SESSION['add_user_success_message'] = "User added successfully!";
    header("Location: admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
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
</head>
<body>
    <div class="form-container">
        <h2 class="text-center"><i class="fas fa-user-plus"></i> Add New User</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
            </div>
            <div class="mb-3">
                <label for="contact" class="form-label">Contact</label>
                <input type="text" class="form-control" id="contact" name="contact" placeholder="Enter contact number" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
            </div>
            <div class="mb-3">
                <label for="user_type" class="form-label">User Type</label>
                <select class="form-select" id="user_type" name="user_type" required>
                    <option value="client">Client</option>
                    <option value="contractor">Contractor</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add User</button>
        </form>
    </div>

    <?php include("includes/footer.php"); ?>