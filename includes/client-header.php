<?php
ob_start();
// Prevent page caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Start the session
session_start();

include_once("includes/db-connection.php");
?>
<!-- Rest of your HTML here -->

<!DOCTYPE html>
<html lang="en">

<head>
    <title>GYS Resources</title>
    <meta charset="UTF-8">
    <meta name="keywords" content="HTML, CSS, PHP">
    <meta name="author" content="Vinnie">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        .badge {
            font-size: 0.5em;
            padding: 0.3em 0.6em;
        }
    </style>
</head>

<body>
    <header class="sticky-top" style="display:flex; align-items:center; padding:10px;">
        <!-- Logo Section -->
        <div class="logo me-auto">
            <img src="img/gys.png" alt="GYS Logo" height="100" width="120">
        </div>

        <!-- Button Icons -->
        <div class="button d-flex align-items-center gap-3">
            <!-- Notification Button -->
            <span onclick="window.location.href='client-noti.php'">
                <i class="fa-solid fa-bell fs-4 position-relative">
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?php
                        // Count unread notifications
                        $unread_notifications = count_unread_notifications_client($_SESSION['user_id'] ?? null);
                        echo ($unread_notifications > 0) ? $unread_notifications : '';
                        ?>
                        <span class="visually-hidden">unread notifications</span>
                    </span>
                </i>
            </span>

            <!-- Profile Button -->
            <span onclick="window.location.href='client-profile.php'">
                <i class="fa-solid fa-circle-user fs-4"></i>
            </span>

            <!-- Offcanvas Navbar -->
            <nav class="navbar">
                <div class="container-fluid">
                    <!-- Offcanvas Toggle Button -->
                    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar"
                        aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <!-- Offcanvas Menu -->
                    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar"
                        aria-labelledby="offcanvasNavbarLabel">
                        <div class="offcanvas-header">
                            <h4 class="offcanvas-title" id="offcanvasNavbarLabel">GYS Resources</h4>
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body">
                            <ul class="navbar-nav justify-content-end flex-grow-1 pe-2">
                                <li class="nav-item">
                                    <a class="nav-link active" aria-current="page" href="client.php">Home</a>
                                </li>
                                <li class="nav-item mt-3">
                                    <a class="nav-link" href="packages-view.php">View Packages</a>
                                </li>
                                <li class="nav-item mt-3 mb-2">
                                    <a class="nav-link" href="package-posting.php">Project Posting</a>
                                </li>
                                <hr>
                                <li class="nav-item mt-3">
                                    <a class="nav-link" href="client-noti.php">Notification</a>
                                </li>
                                <li class="nav-item mt-3">
                                    <a class="nav-link" href="client-profile.php">Profile</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </header>
    <main>
        <!-- Content goes here -->