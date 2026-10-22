/**
 * Orders & Live OTP Poller
 */

function initOrderPoller(orderId, pollInterval = 3000) {
    let poller = setInterval(() => {
        fetch(`/api/order-status.php?order_id=${orderId}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;
                const order = data.order || data;
                const otp = order.otp_code || order.sms_code || data.otp_code || data.sms_code;
                
                // If code received
                if (otp) {
                    clearInterval(poller);
                    const codeEl = document.getElementById('sms-code-display');
                    if (codeEl) {
                        codeEl.innerText = otp;
                        codeEl.classList.remove('animate-pulse');
                    }
                    const fullSmsEl = document.getElementById('full-sms-display');
                    if (fullSmsEl && (order.full_sms || data.full_sms)) {
                        fullSmsEl.innerText = order.full_sms || data.full_sms;
                    }
                    const statusBadge = document.getElementById('order-status-badge');
                    if (statusBadge) {
                        statusBadge.className = 'px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200';
                        statusBadge.innerText = 'Completed';
                    }
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'OTP Received!',
                            text: `Your verification code is: ${otp}`,
                            confirmButtonColor: '#7c3aed',
                            confirmButtonText: 'Great!'
                        });
                    }
                } else if (order.status === 'cancelled' || order.status === 'expired') {
                    clearInterval(poller);
                    window.location.reload();
                }
            })
            .catch(err => console.error('Poller error:', err));
    }, pollInterval);
}
