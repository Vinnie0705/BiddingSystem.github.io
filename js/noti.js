// Function to toggle all notifications as read or unread
document.getElementById('select-all').addEventListener('click', function() {
    var checkboxes = document.querySelectorAll('.notification-checkbox');
    checkboxes.forEach(function(checkbox) {
      checkbox.checked = true;
      checkbox.closest('.notification-item').classList.add('read');
      checkbox.closest('.notification-item').classList.remove('unread');
    });
  });
  
  document.getElementById('mark-read').addEventListener('click', function() {
    var selectedItems = document.querySelectorAll('.notification-checkbox:checked');
    selectedItems.forEach(function(item) {
      item.closest('.notification-item').classList.add('read');
      item.closest('.notification-item').classList.remove('unread');
      item.checked = false; // Unselect the checkbox after marking as read
    });
  });
  
  document.getElementById('mark-unread').addEventListener('click', function() {
    var selectedItems = document.querySelectorAll('.notification-checkbox:checked');
    selectedItems.forEach(function(item) {
      item.closest('.notification-item').classList.add('unread');
      item.closest('.notification-item').classList.remove('read');
      item.checked = false; // Unselect the checkbox after marking as unread
    });
  });
  
  // Simulate viewing notification details
  function viewNotificationDetails(notificationId) {
    // Here, you can add a function to open a modal or navigate to a detailed page
    alert("Viewing details for notification ID: " + notificationId);
  }
  