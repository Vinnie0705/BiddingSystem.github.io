<?php
include("includes/client-header.php");

// Database connection
$conn = new mysqli('localhost', 'root', '', 'gys');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve search, filter, and sort parameters
$search = $_GET['search'] ?? '';
$filterType = $_GET['type'] ?? '';
$filterMinPrice = $_GET['min_price'] ?? 0;
$filterMaxPrice = $_GET['max_price'] ?? 9999999;
$sortBy = $_GET['sort_by'] ?? 'package_title'; // Default to package_title
$order = $_GET['order'] ?? 'ASC';
$page = $_GET['page'] ?? 1;
$packagesPerPage = 9;
$offset = ($page - 1) * $packagesPerPage;

// Validate the sort_by parameter to avoid SQL injection
$validSortColumns = ['package_title', 'package_price', 'package_type'];
if (!in_array($sortBy, $validSortColumns)) {
    $sortBy = 'package_title'; // Default to package_title if invalid
}

// Query to fetch filtered and sorted packages
$query = "SELECT * FROM package WHERE 
    package_title LIKE ? 
    AND (package_type = ? OR ? = '') 
    AND package_price BETWEEN ? AND ?
    AND package_status = 'approved'  -- Filter only approved packages
    ORDER BY $sortBy $order 
    LIMIT ?, ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("sssiiii", $searchLike, $filterType, $filterType, $filterMinPrice, $filterMaxPrice, $offset, $packagesPerPage);
$searchLike = "%$search%";
$stmt->execute();
$result = $stmt->get_result();

// Count total packages for pagination
$totalQuery = "SELECT COUNT(*) as total FROM package WHERE 
    package_title LIKE ? 
    AND (package_type = ? OR ? = '') 
    AND package_price BETWEEN ? AND ?
    AND package_status = 'approved'";
$totalStmt = $conn->prepare($totalQuery);
$totalStmt->bind_param("sssii", $searchLike, $filterType, $filterType, $filterMinPrice, $filterMaxPrice);
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalPackages = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalPackages / $packagesPerPage);
?>

<div class="packages-view">
    <!-- Search Section -->
    <div class="search-section">
        <h3 class="title">Approved Packages</h3>
        <form class="search-form" method="GET" action="packages-view.php">
            <input type="text" name="search" placeholder="Search packages..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form class="filter-form" method="GET" action="packages-view.php">
            <label>Type:
                <select name="type" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="residential" <?= $filterType == 'residential' ? 'selected' : '' ?>>Residential</option>
                    <option value="commercial" <?= $filterType == 'commercial' ? 'selected' : '' ?>>Commercial</option>
                    <option value="industrial" <?= $filterType == 'industrial' ? 'selected' : '' ?>>Industrial</option>
                    <option value="others" <?= $filterType == 'others' ? 'selected' : '' ?>>Others</option>
                </select>
            </label>
            <label>
                Min Price:
                <input type="number" name="min_price" placeholder="Min Price"
                    value="<?= htmlspecialchars($filterMinPrice) ?>" onchange="this.form.submit()">
            </label>
            <label>
                Max Price:
                <input type="number" name="max_price" placeholder="Max Price"
                    value="<?= htmlspecialchars($filterMaxPrice) ?>" onchange="this.form.submit()">
            </label>
        </form>
        <!-- Clear Filters Button -->
        <button type="button" id="clearFiltersBtn" class="clear-filters-btn">Clear Filters</button>
    </div>

    <!-- Sort Section -->
    <div class="sort-section">
        <form class="sort-form" method="GET" action="packages-view.php">
            <select name="sort_by" onchange="this.form.submit()">
                <option value="package_title" <?= $sortBy == 'package_title' ? 'selected' : '' ?>>Sort by: Title</option>
                <option value="package_price" <?= $sortBy == 'package_price' ? 'selected' : '' ?>>Sort by: Price</option>
            </select>
            <select name="order" onchange="this.form.submit()">
                <option value="ASC" <?= $order == 'ASC' ? 'selected' : '' ?>>Order: Ascending</option>
                <option value="DESC" <?= $order == 'DESC' ? 'selected' : '' ?>>Order: Descending</option>
            </select>
        </form>
        <br>
    </div>

    <!-- Packages Grid -->
    <div class="packages-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="package-card">
                    <h3><?= htmlspecialchars($row['package_title']) ?></h3>
                    <p>Type: <?= htmlspecialchars($row['package_type']) ?></p>
                    <p>Price: from RM<?= number_format((float) $row['package_price'], 2, '.', ',') ?></p>
                    <div class="package-image">
                        <img src="uploads/images/<?= htmlspecialchars($row['package_img']) ?>"
                            alt="<?= htmlspecialchars($row['package_title']) ?>" style="height:200px;" />
                    </div>

                    <div class="packageview-btn">
                        <!-- Buy Now Button -->
                        <div class="buy-now">
                            <a href="buy-now.php?package_id=<?= $row['package_id'] ?>" class="buy-now-btn">Purchase Now</a>
                        </div>
                    </div>
                    <div class="package-created" style="opacity: 0.6; text-align: right; font-size: 12px; color: #777; padding-top: 2%;">
                        <p>Created on: <?= htmlspecialchars(date('F j, Y', strtotime($row['package_created']))) ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No packages found.</p>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <div class="pagination" style="justify-content:center;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="packages-view.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($filterType) ?>&min_price=<?= urlencode($filterMinPrice) ?>&max_price=<?= urlencode($filterMaxPrice) ?>&sort_by=<?= urlencode($sortBy) ?>&order=<?= urlencode($order) ?>"
                class="<?= $i == $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
</div>

<script>
document.getElementById('clearFiltersBtn').addEventListener('click', function() {
    // Clear the form and reset the filters
    window.location.href = "packages-view.php?page=1&search=&type=&min_price=0&max_price=999999&sort_by=package_title&order=ASC";
});
</script>

<?php include("includes/footer.php"); ?>