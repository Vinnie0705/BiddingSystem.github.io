<?php
include("includes/db-connection.php"); // Ensure correct database connection

if (isset($_GET['id'])) {
    $proj_id = $_GET['id'];

    // Fetch project details
    $query = "SELECT proj_id, proj_title, proj_type, proj_size, proj_location, proj_startdate, proj_proposal, proj_created, proj_img FROM project WHERE proj_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $proj_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $project = $result->fetch_assoc();
        
        // Add full path to the image
        $project['proj_img'] = 'uploads/images/' . $project['proj_img'];

        echo json_encode($project);
    } else {
        echo json_encode(["error" => "Project not found."]);
    }
} else {
    echo json_encode(["error" => "Invalid request."]);
}
?>
