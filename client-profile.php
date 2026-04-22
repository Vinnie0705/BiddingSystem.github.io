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

// Query to get user data
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<div class="profile">
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-picture">
            <i class="profile-header fa-solid fa-user"></i>
        </div>

        <div class="p-4">
            <form>
                <div class="mb-3">
                    <label for="name" class="form-label">Name:</label>
                    <input type="text" class="form-control" id="name"
                        value="<?= htmlspecialchars($user['user_name']) ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="username" class="form-label">Username:</label>
                    <input type="text" class="form-control" id="username"
                        value="<?= htmlspecialchars($user['user_username']) ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" class="form-control" id="email"
                        value="<?= htmlspecialchars($user['user_email']) ?>" readonly>
                </div>
                <div class="mb-3">
                    <label for="contact" class="form-label">Contact Number:</label>
                    <input type="tel" class="form-control" id="contact"
                        value="<?= htmlspecialchars($user['user_contact']) ?>" readonly>
                </div>
                <button type="button" class="btn btn-edit" onclick="window.location.href='client-edit-profile.php';">Edit Profile</button>
            </form>

            <!-- My Projects Section -->
            <h5 class="section-title">My Projects</h5>
            <div class="project-container">
                <?php
                $projectQuery = "SELECT proj_id, proj_title FROM project WHERE client_id = ?";
                $projectStmt = $conn->prepare($projectQuery);
                $projectStmt->bind_param("i", $user_id);
                $projectStmt->execute();
                $projectResult = $projectStmt->get_result();

                if ($projectResult->num_rows > 0) {
                    while ($project = $projectResult->fetch_assoc()) {
                        echo '
                    <div class="project-box">
                        <h6>' . htmlspecialchars($project['proj_title']) . '</h6>
                        <button class="btn btn-view" onclick="openProjectModal(' . $project['proj_id'] . ')">View Details</button>
                    </div>';
                    }
                } else {
                    echo '<p class="no-data">No projects found.</p>';
                }
                ?>
            </div>

            <!-- Project Details Modal -->
            <div id="projectModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeProjectModal()">&times;</span>
                    <h4 id="modalProjTitle"></h4>
                    <br>
                    <p><strong>Type:</strong> <span id="modalProjType"></span></p>
                    <p><strong>Size:</strong> <span id="modalProjSize"></span></p>
                    <p><strong>Location:</strong> <span id="modalProjLocation"></span></p>
                    <p><strong>Start Date:</strong> <span id="modalProjStartDate"></span></p>
                    <p><strong>Proposal:</strong> <span id="modalProjProposal"></span></p>
                    <p><strong>Created On:</strong> <span id="modalProjCreated"></span></p>
                    <img id="modalProjImg" class="modal-img" src="" alt="Project Image">
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="text-center action-buttons">
                <form action="index.php" method="post">
                    <button type="button" class="btn btn-logout" onclick="confirmLogout()">Logout</button>
                </form>
                <form id="deleteForm" action="delete-account.php" method="post">
                    <button type="submit" class="btn btn-delete" onclick="return confirmDelete()">Delete
                        Account</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>

<script>
    function openProjectModal(proj_id) {
        fetch('fetch-project-details.php?id=' + proj_id)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                } else {
                    document.getElementById("modalProjTitle").innerText = data.proj_title;
                    document.getElementById("modalProjType").innerText = data.proj_type;
                    document.getElementById("modalProjSize").innerText = data.proj_size;
                    document.getElementById("modalProjLocation").innerText = data.proj_location;
                    document.getElementById("modalProjStartDate").innerText = data.proj_startdate;
                    document.getElementById("modalProjProposal").innerText = data.proj_proposal;
                    document.getElementById("modalProjCreated").innerText = data.proj_created;
                    document.getElementById("modalProjImg").src = data.proj_img ? data.proj_img : 'uploads/images/';
                    document.getElementById("projectModal").style.display = "flex";
                }
            })
            .catch(error => console.error('Error fetching project details:', error));
    }

    function closeProjectModal() {
        document.getElementById("projectModal").style.display = "none";
    }

    function confirmLogout() {
        if (confirm("Are you sure you want to logout?")) {
            window.location.href = "index.php";
        }
    }

    function confirmDelete() {
        return confirm("Are you sure you want to delete your account? This action cannot be undone.");
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

    /* My Projects Section */
    .section-title {
        margin-top: 30px;
        font-size: 1.2rem;
        font-weight: bold;
        color: #333;
        text-align: center;
    }

    .project-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }

    .project-box {
        background-color: #f8f8f8;
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
        text-align: center;
        transition: transform 0.3s ease;
        height: 200px;
    }

    .project-box:hover {
        transform: translateY(-5px);
    }

    .btn-view {
        background: #b2444b;
        color: white;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        border: none;
        margin-top: 10px;
    }

    .btn-view:hover {
        background: rgba(154, 58, 58, 0.81);
        color: white;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 30px;
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

    /* Modal Styling */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 95%;
        background-color: rgba(0, 0, 0, 0.5);
        justify-content: center;
        align-items: center;
        margin-top: 5%;
    }

    .modal-content {
        background-color: #fff;
        padding: 20px;
        border-radius: 10px;
        width: 50%;
        max-width: 500px;
        text-align: center;
        position: relative;
    }

    .modal-img {
        display: block;
        width: 70%;
        margin-left: auto;
        margin-right: auto;
        border-radius: 10px;
        margin-top: 15px;
    }

    .close {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 24px;
        cursor: pointer;
        color: #333;
    }
</style>