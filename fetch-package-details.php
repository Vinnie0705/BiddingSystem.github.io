<?php
include("includes/db-connection.php");

if (isset($_GET['id'])) {
    $package_id = $_GET['id'];

    // Prepare the query to fetch package details
    $query = "SELECT package_title, package_type, package_img, package_proposal, package_price, package_created FROM package WHERE package_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $package = $result->fetch_assoc();
        
        // Add full path to the image
        $package['package_img'] = 'uploads/images/' . $package['package_img'];

        // Return the package details as JSON
        echo json_encode($package);
    } else {
        // If no package found, return an error message
        echo json_encode(['error' => 'Package not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>
