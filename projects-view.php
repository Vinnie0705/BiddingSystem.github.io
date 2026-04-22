<?php
include("includes/contractor-header.php");

// Database connection
$conn = new mysqli('localhost', 'root', '', 'gys');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve search, filter, and sort parameters
$search = $_GET['search'] ?? '';
$filterType = $_GET['type'] ?? '';
$filterMinSize = $_GET['min_size'] ?? 0;
$filterMaxSize = $_GET['max_size'] ?? 9999999;
$filterLocation = $_GET['location'] ?? '';
$sortBy = $_GET['sort_by'] ?? 'proj_title'; // Default to project title
$order = $_GET['order'] ?? 'ASC';
$page = $_GET['page'] ?? 1;
$projectsPerPage = 9;
$offset = ($page - 1) * $projectsPerPage;

// Validate the sort_by parameter to avoid SQL injection
$validSortColumns = ['proj_title', 'proj_size', 'proj_startdate'];
if (!in_array($sortBy, $validSortColumns)) {
    $sortBy = 'proj_title'; // Default to proj_title if invalid
}

// Query to fetch filtered and sorted projects (ONLY APPROVED)
$query = "SELECT * FROM project WHERE 
    proj_title LIKE ? 
    AND (proj_type = ? OR ? = '') 
    AND proj_size BETWEEN ? AND ? 
    AND (proj_location = ? OR ? = '') 
    AND proj_status = 'approved'  -- ✅ Filter only approved projects
    ORDER BY $sortBy $order 
    LIMIT ?, ?";
$stmt = $conn->prepare($query);
$stmt->bind_param(
    "sssiiisii",
    $searchLike,
    $filterType,
    $filterType,
    $filterMinSize,
    $filterMaxSize,
    $filterLocation,
    $filterLocation,
    $offset,
    $projectsPerPage
);
$searchLike = "%$search%";
$stmt->execute();
$result = $stmt->get_result();

// Count total projects for pagination (ONLY APPROVED)
$totalQuery = "SELECT COUNT(*) as total FROM project WHERE 
    proj_title LIKE ? 
    AND (proj_type = ? OR ? = '') 
    AND proj_size BETWEEN ? AND ? 
    AND (proj_location = ? OR ? = '') 
    AND proj_status = 'approved'";
$totalStmt = $conn->prepare($totalQuery);
$totalStmt->bind_param(
    "sssiiis",
    $searchLike,
    $filterType,
    $filterType,
    $filterMinSize,
    $filterMaxSize,
    $filterLocation,
    $filterLocation
);
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalProjects = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalProjects / $projectsPerPage);
?>

<div class="projects-view">
    <!-- Search Section -->
    <div class="search-section">
        <h3 class="title">Approved Projects</h3>
        <form class="search-form" method="GET" action="projects-view.php">
            <input type="text" name="search" placeholder="Search projects..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form class="filter-form" method="GET" action="projects-view.php">
            <label>Type:
                <select name="type" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="Residential" <?= $filterType == 'Residential' ? 'selected' : '' ?>>Residential</option>
                    <option value="Commercial" <?= $filterType == 'Commercial' ? 'selected' : '' ?>>Commercial</option>
                    <option value="Industrial" <?= $filterType == 'Industrial' ? 'selected' : '' ?>>Industrial</option>
                    <option value="Others" <?= $filterType == 'Others' ? 'selected' : '' ?>>Others</option>
                </select>
            </label>
            <label>Property Size (sq ft):
                <input type="number" name="min_size" placeholder="Min Size"
                    value="<?= htmlspecialchars($filterMinSize) ?>" onchange="this.form.submit()">
                to
                <input type="number" name="max_size" placeholder="Max Size"
                    value="<?= htmlspecialchars($filterMaxSize) ?>" onchange="this.form.submit()">
            </label>
            <label>Location:
                <select name="location" onchange="this.form.submit()">
                    <option value="">All Locations</option>
                    <option value="Johor" <?= $filterLocation == 'Johor' ? 'selected' : '' ?>>Johor</option>
                    <option value="Kedah" <?= $filterLocation == 'Kedah' ? 'selected' : '' ?>>Kedah</option>
                    <option value="Kelantan" <?= $filterLocation == 'Kelantan' ? 'selected' : '' ?>>Kelantan</option>
                    <option value="Melaka" <?= $filterLocation == 'Melaka' ? 'selected' : '' ?>>Melaka</option>
                    <option value="Selangor" <?= $filterLocation == 'Selangor' ? 'selected' : '' ?>>Selangor</option>
                </select>
            </label>
        </form>
        <button type="button" id="clearFiltersBtn" class="clear-filters-btn">Clear Filters</button>
    </div>

    <!-- Sort Section -->
    <div class="sort-section">
        <form class="sort-form" method="GET" action="packages-view.php">
            <select name="sort_by" onchange="this.form.submit()">
                <option value="proj_title" <?= $sortBy == 'proj_title' ? 'selected' : '' ?>>Sort by: Title</option>
                <option value="proj_price" <?= $sortBy == 'proj_price' ? 'selected' : '' ?>>Sort by: Price</option>
            </select>
            <select name="order" onchange="this.form.submit()">
                <option value="ASC" <?= $order == 'ASC' ? 'selected' : '' ?>>Order: Ascending</option>
                <option value="DESC" <?= $order == 'DESC' ? 'selected' : '' ?>>Order: Descending</option>
            </select>
        </form>
        <br>
    </div>

    <!-- Projects Grid -->
    <div class="projects-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="project-card">
                    <h3><?= htmlspecialchars($row['proj_title']) ?></h3>
                    <p>Type: <?= htmlspecialchars($row['proj_type']) ?></p>
                    <p>Size: <?= htmlspecialchars($row['proj_size']) ?> sq ft</p>
                    <p>Location: <?= htmlspecialchars($row['proj_location']) ?></p>
                    <div class="package-image">
                        <img src="uploads/images/<?= htmlspecialchars($row['proj_img']) ?>"
                            alt="<?= htmlspecialchars($row['proj_title']) ?>" style="height:200px;" />
                    </div>
                    <div class="projectview-btn">
                        <!-- Proposal Link -->
                        <?php if (!empty($row['proj_proposal'])): ?>
                            <div class="project-proposal">
                                <!-- Display proposal file link (PDF, DOC, DOCX) -->
                                <a href="uploads/proposals/<?= htmlspecialchars($row['proj_proposal']) ?>" target="_blank"
                                    class="proposal-btn">
                                    View Proposal
                                </a>
                            </div>
                        <?php endif; ?>
                        <!-- Bid Now Button -->
                        <div class="bid-now">
                            <a href="bid-now.php?package_id=<?= $row['proj_id'] ?>" class="bid-now-btn">Bid Now</a>
                        </div>
                    </div>
                    <div class="proj-created"
                        style="opacity: 0.6; text-align: right; font-size: 12px; color: #777; padding-top: 2%;">
                        <p>Created on: <?= htmlspecialchars(date('F j, Y', strtotime($row['proj_created']))) ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No projects found.</p>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <div class="pagination" style="justify-content:center;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="projects-view.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($filterType) ?>&min_size=<?= urlencode($filterMinSize) ?>&max_size=<?= urlencode($filterMaxSize) ?>&location=<?= urlencode($filterLocation) ?>&sort_by=<?= urlencode($sortBy) ?>&order=<?= urlencode($order) ?>"
                class="<?= $i == $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
</div>

<script>
    document.getElementById('clearFiltersBtn').addEventListener('click', function () {
        // Clear the form and reset the filters
        window.location.href = "projects-view.php?page=1&search=&type=&min_size=0&max_size=999999&sort_by=proj_title&order=ASC";
    });
</script>

<?php include("includes/footer.php"); ?>