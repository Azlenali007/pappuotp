/**
 * Notifications Client Handler
 */

function markNotificationAsRead(id, element) {
    const formData = new FormData();
    formData.append('action', 'mark_read');
    formData.append('id', id);

    fetch('/api/notifications.php', {
        method: 'POST',
        body: formData
    }).then(res => res.json()).then(data => {
        if (data.success && element) {
            element.classList.remove('bg-purple-50/40');
            element.classList.add('bg-white');
        }
    });
}
