/**
 * Global OTP Platform JavaScript
 */

function copyToClipboard(text, btnElement = null) {
    if (!text) return;
    navigator.clipboard.writeText(text).then(() => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Copied to clipboard!',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
        }
        if (btnElement) {
            const origText = btnElement.innerText;
            btnElement.innerText = 'Copied!';
            setTimeout(() => { btnElement.innerText = origText; }, 2000);
        }
    }).catch(err => {
        console.error('Failed to copy', err);
    });
}

function showToast(message, icon = 'info') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: icon,
            title: message,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    } else {
        alert(message);
    }
}
