<?php
include("includes/db-connection.php");

// Handle AJAX delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = $_POST['delete_id'];
    $deleteQuery = "DELETE FROM users WHERE user_id = ?";
    $stmtDelete = $conn->prepare($deleteQuery);
    $stmtDelete->bind_param("i", $deleteId);
    echo $stmtDelete->execute() ? "success" : "error";
    exit;
}

// Handle AJAX edit request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user_id'])) {
    $userId = $_POST['edit_user_id'];
    $name = $_POST['edit_name'];
    $username = $_POST['edit_username'];
    $email = $_POST['edit_email'];
    $contact = $_POST['edit_contact'];

    $updateQuery = "UPDATE users SET user_name = ?, user_username = ?, user_email = ?, user_contact = ? WHERE user_id = ?";
    $stmtUpdate = $conn->prepare($updateQuery);
    $stmtUpdate->bind_param("ssssi", $name, $username, $email, $contact, $userId);
    echo $stmtUpdate->execute() ? "success" : "error";
    exit;
}

// Get filter input
$userType = isset($_GET['user_type']) ? strtolower($_GET['user_type']) : '';
$searchTerm = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Build the query based on filters - exclude admin users
$query = "SELECT * FROM users WHERE user_type != 'admin'";
$params = [];
$types = "";

if (!empty($userType)) {
    $query .= " AND LOWER(user_type) = LOWER(?)";
    $params[] = $userType;
    $types .= "s";
}

if (!empty($searchTerm)) {
    $query .= " AND (user_name LIKE ? OR user_username LIKE ? OR user_email LIKE ? OR user_contact LIKE ?)";
    $searchWildcard = "%$searchTerm%";
    $params = array_merge($params, [$searchWildcard, $searchWildcard, $searchWildcard, $searchWildcard]);
    $types .= "ssss";
}

$query .= " ORDER BY user_type, user_name";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .user-management {
            padding: 20px;
        }

        .filter-container {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 15px;
        }

        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-form select,
        .filter-form input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .filter-form button,
        .filter-form a {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .actions-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .bulk-actions {
            display: flex;
            gap: 10px;
        }

        .bulk-actions button {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background-color: #007bff;
            color: white;
        }

        .bulk-actions button.btn-print {
            background-color: #28a745;
        }

        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            margin: 0 auto;
            max-width: 1200px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .checkbox-cell {
            width: 40px;
            text-align: center;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }

        .modal input {
            width: 100%;
            padding: 8px 12px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .selected-row {
            background-color: #e6f7ff !important;
        }

        /* Print-specific styles */
        @media print {
            body * {
                visibility: hidden;
            }

            #print-section,
            #print-section * {
                visibility: visible;
                position: relative;
                width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
                border: none;
            }

            #print-section {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                height: auto;
                background: white !important;
            }

            .no-print {
                display: none !important;
            }

            .print-heading {
                display: block;
                text-align: center;
                margin: 20px 0;
                font-size: 24px;
                font-weight: bold;
                color: #333;
            }

            table {
                width: 100% !important;
                border-collapse: collapse;
                margin: 0 !important;
                padding: 0 !important;
            }

            th,
            td {
                border: 1px solid #ddd;
                padding: 10px !important;
                font-size: 12px;
                text-align: left;
            }

            th {
                background-color: #f8f9fa !important;
                color: #495057 !important;
                font-weight: bold;
            }

            .filter-container,
            .actions-container,
            .bulk-actions {
                display: none !important;
            }

            body {
                margin: 0 !important;
                padding: 0 !important;
            }

            .table-container {
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            /* Force landscape orientation */
            @page {
                size: landscape;
                margin: 10mm;
            }
        }

        /* Screen-specific styles */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
    </style>
</head>

<body>
    <div class="user-management">
        <div class="filter-container">
            <form class="filter-form" method="GET" action="admin.php">
                <input type="hidden" name="tab" value="user-management-tab">
                <select name="user_type" id="user-type-filter">
                    <option value="">All Users</option>
                    <option value="client" <?= $userType === 'client' ? 'selected' : ''; ?>>Client</option>
                    <option value="contractor" <?= $userType === 'contractor' ? 'selected' : ''; ?>>Contractor</option>
                </select>
                <input type="text" name="search" id="user-search" placeholder="Search by name, username, email..."
                    value="<?= htmlspecialchars($searchTerm); ?>">
                <button type="submit">
                    <i class="fas fa-search"></i> Search
                </button>
                <a href="admin.php?tab=user-management-tab" class="reset-button">
                    <i class="fas fa-undo"></i> Reset
                </a>
                <a href="admin-add-user.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add User
                </a>
            </form>
        </div>

        <div class="actions-container">
            <div class="bulk-actions">
                <button id="select-all-btn"><i class="fas fa-check-square"></i> Select All</button>
                <button id="deselect-all-btn"><i class="fas fa-square"></i> Deselect All</button>
                <button id="print-selected-btn" class="btn-print"><i class="fas fa-print"></i> Print Selected</button>
            </div>
        </div>

        <div class="table-container" id="print-section">
            <h2 class="print-heading">User Management Report</h2>
            <table>
                <thead>
                    <tr>
                        <th class="checkbox-cell no-print"><input type="checkbox" id="select-all-checkbox"></th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>User Type</th>
                        <th class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr data-user-id="<?= $row['user_id']; ?>">
                            <td class="checkbox-cell no-print">
                                <input type="checkbox" class="user-checkbox" value="<?= $row['user_id']; ?>">
                            </td>
                            <td><?= htmlspecialchars($row['user_name']); ?></td>
                            <td><?= htmlspecialchars($row['user_username']); ?></td>
                            <td><?= htmlspecialchars($row['user_email']); ?></td>
                            <td><?= htmlspecialchars($row['user_contact']); ?></td>
                            <td><?= htmlspecialchars($row['user_type']); ?></td>
                            <td class="no-print">
                                <a href="admin-edit-profile.php?user_id=<?= $row['user_id']; ?>">
                                    <button class="btn-edit" type="button">Edit</button>
                                </a>
                                <a href="admin-delete-profile.php?user_id=<?= $row['user_id']; ?>"
                                    onclick="return confirm('Are you sure you want to delete this user?');">
                                    <button class="btn-delete" type="button">Delete</button>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3>Edit User</h3>
            <form id="editForm">
                <input type="hidden" id="editUserId">
                <input type="text" id="editName" placeholder="Name">
                <input type="text" id="editUsername" placeholder="Username">
                <input type="email" id="editEmail" placeholder="Email">
                <input type="text" id="editContact" placeholder="Contact">
                <div class="modal-buttons">
                    <button type="submit">Save Changes</button>
                    <button type="button" id="closeModal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Button references
            const selectAllBtn = document.getElementById('select-all-btn');
            const deselectAllBtn = document.getElementById('deselect-all-btn');
            const printSelectedBtn = document.getElementById('print-selected-btn');
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            const userCheckboxes = document.querySelectorAll('.user-checkbox');

            // Select All functionality
            selectAllBtn.addEventListener('click', function () {
                userCheckboxes.forEach(checkbox => {
                    checkbox.checked = true;
                    toggleRowSelection(checkbox);
                });
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            });

            // Deselect All functionality
            deselectAllBtn.addEventListener('click', function () {
                userCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                    toggleRowSelection(checkbox);
                });
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            });

            // Print Selected functionality
            printSelectedBtn.addEventListener('click', function () {
                const rows = document.querySelectorAll('tbody tr');
                let selectedCount = 0;

                rows.forEach(row => {
                    const checkbox = row.querySelector('.user-checkbox');
                    if (checkbox && !checkbox.checked) {
                        row.style.display = 'none';
                    } else if (checkbox && checkbox.checked) {
                        selectedCount++;
                    }
                });

                if (selectedCount === 0) {
                    alert('Please select at least one user to print.');
                    rows.forEach(row => {
                        row.style.display = '';
                    });
                    return;
                }

                window.print();

                // Reset display of rows after printing
                rows.forEach(row => {
                    row.style.display = '';
                });
            });

            // Individual checkbox functionality
            userCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    toggleRowSelection(this);
                    updateSelectAllCheckbox();
                });
            });

            selectAllCheckbox.addEventListener('change', function () {
                userCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                    toggleRowSelection(checkbox);
                });
            });

            // Toggle row selection
            function toggleRowSelection(checkbox) {
                const row = checkbox.closest('tr');
                if (checkbox.checked) {
                    row.classList.add('selected-row');
                } else {
                    row.classList.remove('selected-row');
                }
            }

            // Update "Select All" checkbox
            function updateSelectAllCheckbox() {
                const checkboxes = document.querySelectorAll('.user-checkbox');
                const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
                selectAllCheckbox.checked = checkboxes.length > 0 && checkboxes.length === checkedBoxes.length;
                selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < checkboxes.length;
            }

            // Edit button functionality
            document.querySelectorAll('.btn-edit').forEach(button => {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    let parentRow = this.closest('tr');
                    let userId = parentRow.dataset.userId;
                    let name = parentRow.cells[1].textContent;
                    let username = parentRow.cells[2].textContent;
                    let email = parentRow.cells[3].textContent;
                    let contact = parentRow.cells[4].textContent;

                    document.getElementById('editUserId').value = userId;
                    document.getElementById('editName').value = name;
                    document.getElementById('editUsername').value = username;
                    document.getElementById('editEmail').value = email;
                    document.getElementById('editContact').value = contact;
                    document.getElementById('editModal').classList.add('active');
                });
            });

            // Close modal
            const closeModal = document.getElementById('closeModal');
            closeModal.addEventListener('click', () => {
                document.getElementById('editModal').classList.remove('active');
            });

            // Save changes in modal
            const editForm = document.getElementById('editForm');
            editForm.addEventListener('submit', function (e) {
                e.preventDefault();
                let userId = document.getElementById('editUserId').value;
                let name = document.getElementById('editName').value;
                let username = document.getElementById('editUsername').value;
                let email = document.getElementById('editEmail').value;
                let contact = document.getElementById('editContact').value;

                fetch("manage-user.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `edit_user_id=${userId}&edit_name=${encodeURIComponent(name)}&edit_username=${encodeURIComponent(username)}&edit_email=${encodeURIComponent(email)}&edit_contact=${encodeURIComponent(contact)}`
                }).then(res => res.text()).then(data => {
                    if (data === "success") location.reload();
                    else alert("Error updating user.");
                });
            });

            // Delete button functionality
            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (confirm("Are you sure you want to delete this user?")) {
                        let userId = this.closest('tr').dataset.userId;
                        fetch("manage-user.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: `delete_id=${userId}`
                        }).then(res => res.text()).then(data => {
                            if (data === "success") location.reload();
                            else alert("Error deleting user.");
                        });
                    }
                });
            });
        });
    </script>
</body>

</html>