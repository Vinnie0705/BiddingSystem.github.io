<?php
// Prevent page caching
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("includes/admin-header.php");

function displayAlert($sessionKey)
{
    if (isset($_SESSION[$sessionKey])) {
        echo "
        <div class='alert alert-success'>
            {$_SESSION[$sessionKey]}
            <button type='button' class='close-btn' onclick='this.parentElement.style.display=\"none\";'>×</button>
        </div>";
        unset($_SESSION[$sessionKey]);
    }
}

displayAlert('edit_success_message');
displayAlert('delete_success_message');
displayAlert('add_user_success_message');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Admin Dashboard</title>
    <style>
        .badge {
            font-size: 0.5em;
            padding: 0.3em 0.6em;
        }

        .admin-dashboard {
            display: flex;
            flex-direction: row;
            height: 100vh;
        }

        .side-nav {
            width: 200px;
            padding: 20px;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .side-nav ul {
            list-style: none;
            padding: 0;
        }

        .side-nav ul li {
            padding: 10px;
            cursor: pointer;
            margin-bottom: 5px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .side-nav ul li.active {
            background-color: #b2444f;
            color: white;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            background-color: #fff;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .tab-content {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            background-color: #fafafa;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: none;
        }

        .tab-content.active {
            display: block !important;
        }

        .alert {
            padding: 10px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            position: relative;
            margin-bottom: 10px;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 18px;
            font-weight: bold;
            color: #155724;
            position: absolute;
            right: 10px;
            top: 5px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="admin-dashboard">
        <!-- Side Navigation -->
        <nav class="side-nav">
            <h2>Admin Panel</h2>
            <ul>
                <li class="active" data-tab="dashboard-tab">Dashboard</li>
                <li data-tab="user-management-tab">User Management</li>
                <li data-tab="project-management-tab">Project Management</li>
                <li data-tab="package-management-tab">Package Management</li>
                <li data-tab="bid-management-tab">Bid Management</li>
                <li data-tab="package-purchase-management-tab">Package Purchase</li>
                <li data-tab="payment-management-tab">Payment Management</li>
                <li data-tab="feedback-tab">Feedback Received</li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <section id="dashboard-tab" class="tab-content active">
                <h1>Dashboard</h1>
                <?php include("admin-dashboard.php"); ?>
            </section>
            <section id="user-management-tab" class="tab-content">
                <h1>User Management</h1>
                <div id="user-management-content"></div>
            </section>
            <section id="project-management-tab" class="tab-content">
                <h1>Project Management</h1>
                <?php include("manage-project.php"); ?>
            </section>
            <section id="package-management-tab" class="tab-content">
                <h1>Package Management</h1>
                <?php include("manage-package.php"); ?>
            </section>
            <section id="bid-management-tab" class="tab-content">
                <h1>Bid Management</h1>
                <?php include("manage-bid.php"); ?>
            </section>
            <section id="package-purchase-management-tab" class="tab-content">
                <h1>Package Purchase Management</h1>
                <div id="package-purchase-management-content"></div>
            </section>
            <section id="payment-management-tab" class="tab-content">
                <h1>Payment Management</h1>
                <div id="payment-management-content"></div>
            </section>
            <section id="feedback-tab" class="tab-content">
                <h1>Feedback Received</h1>
                <?php include("manage-feedback.php"); ?>
            </section>
        </main>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Get tab from URL parameter if it exists
            const urlParams = new URLSearchParams(window.location.search);
            const tabFromUrl = urlParams.get('tab');

            const sideNavItems = document.querySelectorAll(".side-nav ul li");
            const tabContents = document.querySelectorAll(".tab-content");
            const paymentManagementTab = document.querySelector('[data-tab="payment-management-tab"]');
            const paymentManagementContent = document.getElementById("payment-management-content");
            const packagePurchaseTab = document.querySelector('[data-tab="package-purchase-management-tab"]');
            const packagePurchaseContent = document.getElementById("package-purchase-management-content");
            const userManagementTab = document.querySelector('[data-tab="user-management-tab"]');
            const userManagementContent = document.getElementById("user-management-content");

            // Function to set the active tab
            function setActiveTab(tabId) {
                console.log(`Setting active tab: ${tabId}`); // Debugging

                // Remove active class from all navigation items and tab contents
                sideNavItems.forEach(navItem => navItem.classList.remove("active"));
                tabContents.forEach(content => content.classList.remove("active"));

                // Add active class to the selected navigation item and tab content
                const selectedNav = document.querySelector(`[data-tab="${tabId}"]`);
                const selectedContent = document.getElementById(tabId);

                if (selectedNav && selectedContent) {
                    selectedNav.classList.add("active");
                    selectedContent.classList.add("active");
                }

                // Save the active tab to localStorage
                localStorage.setItem('activeAdminTab', tabId);
            }

            // Function to initialize user management functionality
            function initializeUserManagement() {
                console.log("Initializing user management functionality");

                // Button references
                const selectAllBtn = document.getElementById('select-all-btn');
                const deselectAllBtn = document.getElementById('deselect-all-btn');
                const printSelectedBtn = document.getElementById('print-selected-btn');
                const savePdfBtn = document.getElementById('save-pdf-btn');
                const selectAllCheckbox = document.getElementById('select-all-checkbox');
                const userCheckboxes = document.querySelectorAll('.user-checkbox');

                console.log("Found buttons:", {
                    selectAllBtn: !!selectAllBtn,
                    deselectAllBtn: !!deselectAllBtn,
                    printSelectedBtn: !!printSelectedBtn,
                    savePdfBtn: !!savePdfBtn,
                    selectAllCheckbox: !!selectAllCheckbox,
                    userCheckboxes: userCheckboxes.length
                });

                // Function to toggle row selection
                function toggleRowSelection(checkbox) {
                    if (!checkbox) return;
                    const row = checkbox.closest('tr');
                    if (!row) return;

                    if (checkbox.checked) {
                        row.classList.add('selected-row');
                    } else {
                        row.classList.remove('selected-row');
                    }
                }

                // Function to update "Select All" checkbox state
                function updateSelectAllCheckbox() {
                    if (!selectAllCheckbox) return;

                    const checkboxes = document.querySelectorAll('.user-checkbox');
                    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
                    selectAllCheckbox.checked = checkboxes.length > 0 && checkboxes.length === checkedBoxes.length;
                    selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < checkboxes.length;
                }

                // Select All button
                if (selectAllBtn) {
                    selectAllBtn.addEventListener('click', function () {
                        console.log("Select All button clicked");
                        userCheckboxes.forEach(checkbox => {
                            checkbox.checked = true;
                            toggleRowSelection(checkbox);
                        });
                        if (selectAllCheckbox) {
                            selectAllCheckbox.checked = true;
                            selectAllCheckbox.indeterminate = false;
                        }
                    });
                }

                // Deselect All button
                if (deselectAllBtn) {
                    deselectAllBtn.addEventListener('click', function () {
                        console.log("Deselect All button clicked");
                        userCheckboxes.forEach(checkbox => {
                            checkbox.checked = false;
                            toggleRowSelection(checkbox);
                        });
                        if (selectAllCheckbox) {
                            selectAllCheckbox.checked = false;
                            selectAllCheckbox.indeterminate = false;
                        }
                    });
                }

                // Print Selected button
                if (printSelectedBtn) {
                    printSelectedBtn.addEventListener('click', function () {
                        console.log("Print Selected button clicked");
                        // Show only the selected rows for printing
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

                        // Check if any rows are selected
                        if (selectedCount === 0) {
                            alert('Please select at least one user to print.');
                            // Reset display of rows
                            rows.forEach(row => {
                                row.style.display = '';
                            });
                            return;
                        }

                        window.print();

                        // After printing, show all rows again
                        rows.forEach(row => {
                            row.style.display = '';
                        });
                    });
                }

                // Save as PDF button
                if (savePdfBtn) {
                    savePdfBtn.addEventListener('click', function () {
                        console.log("Save as PDF button clicked");
                        // Check if any rows are selected
                        const selectedRows = document.querySelectorAll('.user-checkbox:checked');
                        if (selectedRows.length === 0) {
                            alert('Please select at least one user to save as PDF.');
                            return;
                        }

                        // Show only the selected rows for the PDF
                        const rows = document.querySelectorAll('tbody tr');
                        rows.forEach(row => {
                            const checkbox = row.querySelector('.user-checkbox');
                            if (checkbox && !checkbox.checked) {
                                row.style.display = 'none';
                            }
                        });

                        // Hide the checkboxes and action buttons for PDF
                        document.querySelectorAll('.no-print').forEach(el => {
                            el.style.display = 'none';
                        });

                        // Show the print heading
                        const printHeading = document.querySelector('.print-heading');
                        if (printHeading) {
                            printHeading.style.display = 'block';
                        }

                        // Configure PDF options
                        const element = document.getElementById('print-section');
                        if (!element) {
                            console.error('Print section not found');
                            return;
                        }

                        const opt = {
                            margin: {
                                top: 15,
                                right: 15,
                                bottom: 15,
                                left: 15
                            },
                            filename: 'user-management-report.pdf',
                            image: {
                                type: 'jpeg',
                                quality: 0.99
                            },
                            html2canvas: {
                                scale: 3,
                                useCORS: true,
                                logging: false,
                                letterRendering: true
                            },
                            jsPDF: {
                                unit: 'mm',
                                format: 'a4',
                                orientation: 'landscape',
                                compress: true,
                                precision: 2,
                                putOnlyUsedFonts: true
                            }
                        };

                        // Generate PDF
                        if (typeof html2pdf === 'function') {
                            html2pdf().set(opt).from(element).save().then(() => {
                                // After generating PDF, reset everything
                                rows.forEach(row => {
                                    row.style.display = '';
                                });
                                document.querySelectorAll('.no-print').forEach(el => {
                                    el.style.display = '';
                                });
                                if (printHeading) {
                                    printHeading.style.display = 'none';
                                }
                            });
                        } else {
                            console.error('html2pdf function not available');
                            alert('PDF generation library not loaded. Please check your internet connection.');
                        }
                    });
                }

                // Individual checkbox functionality
                if (userCheckboxes.length > 0) {
                    userCheckboxes.forEach(checkbox => {
                        checkbox.addEventListener('change', function () {
                            toggleRowSelection(this);
                            updateSelectAllCheckbox();
                        });
                    });
                }

                // Select All checkbox in table header
                if (selectAllCheckbox) {
                    selectAllCheckbox.addEventListener('change', function () {
                        userCheckboxes.forEach(checkbox => {
                            checkbox.checked = this.checked;
                            toggleRowSelection(checkbox);
                        });
                    });
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
                if (closeModal) {
                    closeModal.addEventListener('click', () => {
                        document.getElementById('editModal').classList.remove('active');
                    });
                }

                // Save changes in modal
                const editForm = document.getElementById('editForm');
                if (editForm) {
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
                }

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
            }

            // Set the active tab on page load
            const activeTab = tabFromUrl || localStorage.getItem('activeAdminTab') || 'dashboard-tab';
            setActiveTab(activeTab);

            // Add click event listeners to navigation items
            sideNavItems.forEach(item => {
                item.addEventListener("click", () => {
                    const tabId = item.getAttribute("data-tab");
                    console.log(`Clicked on tab: ${tabId}`); // Debugging
                    setActiveTab(tabId);

                    // Load content dynamically for specific tabs
                    if (tabId === "payment-management-tab" && paymentManagementContent.innerHTML.trim() === "") {
                        fetch("manage-payment.php")
                            .then(response => response.text())
                            .then(data => {
                                paymentManagementContent.innerHTML = data;
                                initializePaymentActions();
                            })
                            .catch(error => console.error("Error loading payment management content:", error));
                    }

                    // Load package purchase management content dynamically
                    if (tabId === "package-purchase-management-tab" && packagePurchaseContent.innerHTML.trim() === "") {
                        fetch("manage-packagepurchase.php")
                            .then(response => response.text())
                            .then(data => {
                                packagePurchaseContent.innerHTML = data;
                                initializePackagePurchaseActions(); // Initialize actions after content is loaded
                            })
                            .catch(error => console.error("Error loading package purchase content:", error));
                    }

                    // Load user management content dynamically
                    if (tabId === "user-management-tab") {
                        const searchParams = new URLSearchParams(window.location.search);
                        let userManagementUrl = "manage-user.php";

                        // Add search parameters if they exist
                        if (searchParams.has('user_type') || searchParams.has('search')) {
                            userManagementUrl += '?' + searchParams.toString();
                        }

                        fetch(userManagementUrl)
                            .then(response => response.text())
                            .then(data => {
                                userManagementContent.innerHTML = data;
                                // Important: Initialize user management functionality after content is loaded
                                setTimeout(initializeUserManagement, 100);
                            })
                            .catch(error => console.error("Error loading user management content:", error));
                    }
                });
            });

            // Load payment management content if the active tab is payment management
            if (activeTab === "payment-management-tab" && paymentManagementContent.innerHTML.trim() === "") {
                fetch("manage-payment.php")
                    .then(response => response.text())
                    .then(data => {
                        paymentManagementContent.innerHTML = data;
                        initializePaymentActions();
                    })
                    .catch(error => console.error("Error loading payment management content:", error));
            }

            // Load package purchase management content if the active tab is package purchase management
            if (activeTab === "package-purchase-management-tab" && packagePurchaseContent.innerHTML.trim() === "") {
                fetch("manage-packagepurchase.php")
                    .then(response => response.text())
                    .then(data => {
                        packagePurchaseContent.innerHTML = data;
                        initializePackagePurchaseActions(); // Initialize actions after content is loaded
                    })
                    .catch(error => console.error("Error loading package purchase content:", error));
            }

            // Load user management content if the active tab is user management
            if (activeTab === "user-management-tab" && userManagementContent.innerHTML.trim() === "") {
                const searchParams = new URLSearchParams(window.location.search);
                let userManagementUrl = "manage-user.php";

                // Add search parameters if they exist
                if (searchParams.has('user_type') || searchParams.has('search')) {
                    userManagementUrl += '?' + searchParams.toString();
                }

                fetch(userManagementUrl)
                    .then(response => response.text())
                    .then(data => {
                        userManagementContent.innerHTML = data;
                        // Important: Initialize user management functionality after content is loaded
                        setTimeout(initializeUserManagement, 100);
                    })
                    .catch(error => console.error("Error loading user management content:", error));
            }
        });

        // Function to initialize package purchase actions
        function initializePackagePurchaseActions() {
            console.log("Initializing package purchase actions..."); // Debugging

            document.querySelectorAll(".btn-approve-cp, .btn-reject-cp, .btn-collect-payment, .btn-request-floor-plan, .btn-send-floor-plan, .btn-collect-30-percent, .btn-send-bq-file").forEach(button => {
                button.addEventListener("click", function () {
                    let action = getActionFromClass(this.classList);
                    let cpId = this.getAttribute("data-id") || this.dataset.cpId; // Support for both attributes
                    let successMessage = getSuccessMessage(action);
                    let requestBody = `action=${action}&cp_id=${cpId}`;

                    if (!cpId) {
                        alert("Error: Client Package ID is missing.");
                        return;
                    }

                    // Handle special cases: payment, rejection, and confirmations
                    if (action === "send_floor_plan" && !confirm("Are you sure you want to send the floor plan to the client?")) {
                        return;
                    }
                    else if (action === "collect_10_percent" || action === "collect_30_percent") {
                        let amount = prompt("Enter the payment amount (RM):");
                        if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
                            alert("Invalid payment amount. Please enter a valid number.");
                            return;
                        }
                        requestBody += `&amount=${amount}`;
                    }
                    else if (action === "reject") {
                        let rejectionReason = prompt("Enter the rejection reason:");
                        if (!rejectionReason) {
                            alert("Rejection reason is required.");
                            return;
                        }
                        requestBody += `&reason=${encodeURIComponent(rejectionReason)}`;
                    }

                    // Special case for "Send BQ File" button
                    if (action === "send_bq_file") {
                        this.disabled = true;
                        this.textContent = "Sending...";
                    } else {
                        this.disabled = true; // Disable other buttons too
                    }

                    // Send AJAX request
                    fetch("manage-packagepurchase.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: requestBody
                    })
                        .then(response => {
                            if (!response.ok) throw new Error("Network response was not ok");
                            return response.text();
                        })
                        .then(data => {
                            alert(data);
                            this.innerText = successMessage;
                            this.style.backgroundColor = "#6c757d";
                            this.style.cursor = "not-allowed";
                            if (action === "send_bq_file") {
                                this.textContent = "BQ File Sent"; // Ensure the button updates correctly
                            }
                        })
                        .catch(error => {
                            alert("Error: " + error.message);
                            this.disabled = false;
                            if (action === "send_bq_file") this.textContent = "Send BQ File";
                        });
                });
            });

            function getActionFromClass(classList) {
                if (classList.contains("btn-approve-cp")) return "approve";
                if (classList.contains("btn-reject-cp")) return "reject";
                if (classList.contains("btn-collect-payment")) return "collect_10_percent";
                if (classList.contains("btn-request-floor-plan")) return "request_floor_plan";
                if (classList.contains("btn-send-floor-plan")) return "send_floor_plan";
                if (classList.contains("btn-collect-30-percent")) return "collect_30_percent";
                if (classList.contains("btn-send-bq-file")) return "send_bq_file"; // Added BQ File action
                return null;
            }

            function getSuccessMessage(action) {
                const messages = {
                    "approve": "Approved",
                    "reject": "Rejected",
                    "collect_10_percent": "10% Payment Collected",
                    "request_floor_plan": "Floor Plan Requested",
                    "send_floor_plan": "Floor Plan Sent",
                    "collect_30_percent": "30% Payment Collected",
                    "send_bq_file": "BQ File Sent" // Added message for BQ File action
                };
                return messages[action] || "Action Completed";
            }
        }

        function initializePaymentActions() {
            const rejectModal = document.getElementById("rejectModal");
            const imageModal = document.getElementById("imageModal");
            const modalImage = document.getElementById("modalImage");
            let currentPaymentId = null;

            // Handle Verify Button Click
            document.querySelectorAll(".btn-approve-payment").forEach(button => {
                button.addEventListener("click", function () {
                    const paymentId = this.getAttribute("data-id");
                    if (confirm("Are you sure you want to verify this payment?")) {
                        handlePaymentAction(paymentId, 'approve');
                    }
                });
            });

            // Handle Reject Button Click
            document.querySelectorAll(".btn-reject-payment").forEach(button => {
                button.addEventListener("click", function () {
                    currentPaymentId = this.getAttribute("data-id");
                    rejectModal.style.display = "flex";
                });
            });

            // Handle Confirm Reject in Modal
            document.getElementById("confirmReject")?.addEventListener("click", function () {
                const reason = document.getElementById("rejectReason").value.trim();
                if (!reason) {
                    alert("Please provide a reason for rejection.");
                    return;
                }
                handlePaymentAction(currentPaymentId, 'reject', reason);
            });

            // Close Modals
            document.querySelectorAll(".close-modal, .btn-cancel-modal").forEach(button => {
                button.addEventListener("click", function () {
                    rejectModal.style.display = "none";
                    imageModal.style.display = "none";
                    document.getElementById("rejectReason").value = "";
                });
            });

            // Handle Receipt View
            document.querySelectorAll(".receipt-link").forEach(link => {
                link.addEventListener("click", function (e) {
                    e.preventDefault();
                    modalImage.src = this.href;
                    imageModal.style.display = "flex";
                });
            });

            // Close Modal on Outside Click
            window.addEventListener("click", function (e) {
                if (e.target === rejectModal || e.target === imageModal) {
                    rejectModal.style.display = "none";
                    imageModal.style.display = "none";
                    document.getElementById("rejectReason").value = "";
                }
            });

            function handlePaymentAction(paymentId, action, reason = null) {
                const formData = new FormData();
                formData.append('payment_id', paymentId);
                formData.append('action', action);
                if (reason) {
                    formData.append('reason', reason);
                }

                fetch('manage-payment.php', {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // Close modal if open
                            rejectModal.style.display = "none";
                            document.getElementById("rejectReason").value = "";

                            // Find the row and update status
                            const row = document.querySelector(`button[data-id="${paymentId}"]`).closest('tr');
                            const statusCell = row.querySelector('.status-cell');
                            if (statusCell) {
                                const newStatus = action === 'approve' ? 'verified' : 'rejected';
                                statusCell.className = `status-cell ${newStatus}`;
                                statusCell.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                            }

                            // Update the action dropdown
                            const actionDropdown = row.querySelector('.action-dropdown');
                            if (actionDropdown) {
                                const newStatus = action === 'approve' ? 'Verified' : 'Rejected';
                                actionDropdown.innerHTML = `
                                <button class="action-btn" disabled style="background-color: #6c757d;">
                                    <i class="fas fa-check-circle"></i> ${newStatus}
                                </button>
                            `;
                            }

                            // Show success message
                            alert(data.message);

                            // Refresh the payment management content
                            fetch("manage-payment.php")
                                .then(response => response.text())
                                .then(html => {
                                    document.getElementById("payment-management-content").innerHTML = html;
                                    // Reinitialize the payment actions
                                    initializePaymentActions();
                                })
                                .catch(error => console.error('Error refreshing content:', error));

                        } else {
                            alert(data.message || 'An error occurred');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while processing your request');
                    });
            }
        }
    </script>

    <?php include("includes/footer.php"); ?>
</body>

</html>