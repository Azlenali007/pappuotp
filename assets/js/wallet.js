/**
 * Wallet & Deposit JavaScript
 */

function refreshWalletBalance() {
    fetch('/api/wallet-balance.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const balEls = document.querySelectorAll('.user-wallet-balance');
                balEls.forEach(el => {
                    el.innerText = data.formatted;
                });
            }
        })
        .catch(err => console.error('Failed to update balance', err));
}

// Auto-check balance every 30 seconds if on user pages
if (window.location.pathname.startsWith('/user/')) {
    setInterval(refreshWalletBalance, 30000);
}
