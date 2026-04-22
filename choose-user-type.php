<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email']) || !isset($_SESSION['user_name'])) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['user_type'])) {
    $user_type = $_POST['user_type'];
    $user_id = $_SESSION['user_id'];

    // Database connection
    $mysqli = new mysqli("localhost", "root", "", "gys");
    if ($mysqli->connect_errno) {
        $_SESSION['signin_error_message'] = "Database connection failed.";
        header("Location: index.php");
        exit();
    }

    // Update user type in database
    $update_stmt = $mysqli->prepare("UPDATE Users SET user_type = ? WHERE user_id = ?");
    $update_stmt->bind_param("si", $user_type, $user_id);
    if ($update_stmt->execute()) {
        $_SESSION['user_type'] = $user_type;
        header("Location: " . ($user_type === 'client' ? "client.php" : "contractor.php"));
        exit();
    } else {
        $_SESSION['signin_error_message'] = "Failed to update user type.";
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Choose User Type</title>
    <!-- Include necessary CSS here -->
</head>
<body>
    <form action="choose-user-type.php" method="post">
    <h2>Choose Your User Type</h2>
        <input type="radio" id="client" name="user_type" value="client" required>
        <label for="client">Client</label><br>
        <input type="radio" id="contractor" name="user_type" value="contractor" required>
        <label for="contractor">Contractor</label><br>
        <button type="submit">Submit</button>
    </form>
</body>
</html>

<style>
        body {
            font-family: 'Roboto', Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .center-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            /* This ensures the content is centered within the body's flex container */
            width: 100%;
            height: 100%;
        }

        h2 {
            color: #2c3e50;
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 30px;
            font-family: 'Playfair Display', serif;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }

        form {
            background: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.19), 0 6px 6px rgba(0,0,0,0.23);
            width: 450px;
            text-align: center;
            position: relative;
            animation: fadeIn 1s ease-out;
            /* Center the form content */
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        input[type="radio"] {
            display: none;
        }

        label {
            cursor: pointer;
            padding: 20px 25px;
            background: #ecf0f1;
            border-radius: 8px;
            margin: 15px 0;
            width: 80%;
            text-align: center;
            transition: all 0.3s ease;
            font-size: 1.3em;
            color: #34495e;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        input[type="radio"]:checked + label {
            background: rgb(70, 67, 70);
            color: white;
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
        }

        button {
            background: #b2444b;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            margin-top: 30px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        button:hover {
            background: rgba(178, 68, 75, 0.7);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        /* Creative elements */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Adding a subtle background animation for creativity */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: repeating-linear-gradient(
                -45deg,
                rgba(255,255,255,0.1),
                rgba(255,255,255,0.1) 10px,
                transparent 10px,
                transparent 20px
            );
            animation: bgAnimation 15s ease infinite;
            z-index: -1;
        }

        @keyframes bgAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Adding a fun hover effect for labels */
        label:hover {
            background: #bdc3c7;
            transform: scale(1.02);
        }
    </style>