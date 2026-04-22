function toggleStatus(button) {
    if (button.classList.contains('unread')) {
        button.classList.remove('unread');
        button.classList.add('read');
        button.textContent = 'Mark as Unread';
    } else {
        button.classList.remove('read');
        button.classList.add('unread');
        button.textContent = 'Mark as Read';
    }
}
