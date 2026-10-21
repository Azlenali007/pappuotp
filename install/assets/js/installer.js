/**
 * Web Installer Vanilla JavaScript Engine
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Database Connection Test
    const testDbBtn = document.getElementById('test-db-btn');
    if (testDbBtn) {
        testDbBtn.addEventListener('click', function () {
            const host = document.getElementById('db_host').value.trim();
            const port = document.getElementById('db_port').value.trim();
            const dbname = document.getElementById('db_name').value.trim();
            const username = document.getElementById('db_user').value.trim();
            const password = document.getElementById('db_pass').value;
            const csrfToken = document.querySelector('input[name="csrf_token"]').value;
            const statusDiv = document.getElementById('db-test-result');

            if (!host || !dbname || !username) {
                statusDiv.className = 'p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-sm font-medium flex items-center gap-2';
                statusDiv.innerHTML = '<svg class="w-5 h-5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg><span>Please fill in Database Host, Database Name, and Username.</span>';
                statusDiv.classList.remove('hidden');
                return;
            }

            const origContent = testDbBtn.innerHTML;
            testDbBtn.disabled = true;
            testDbBtn.innerHTML = '<span class="installer-spinner"></span> Connecting...';

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('host', host);
            formData.append('port', port);
            formData.append('dbname', dbname);
            formData.append('username', username);
            formData.append('password', password);

            fetch('/install/process/test-database.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                testDbBtn.disabled = false;
                testDbBtn.innerHTML = origContent;
                statusDiv.classList.remove('hidden');

                if (data.success) {
                    statusDiv.className = 'p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-start gap-2.5';
                    statusDiv.innerHTML = `
                        <svg class="w-5 h-5 shrink-0 text-emerald-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div>
                            <div class="font-bold">Connection Established!</div>
                            <div class="text-xs text-emerald-700 mt-0.5">MySQL Server: ${data.version} | Existing tables: ${data.tables_count}</div>
                        </div>
                    `;
                    const submitBtn = document.getElementById('save-db-btn');
                    if (submitBtn) {
                        submitBtn.classList.remove('opacity-50', 'pointer-events-none');
                    }
                } else {
                    statusDiv.className = 'p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium flex items-start gap-2.5';
                    statusDiv.innerHTML = `
                        <svg class="w-5 h-5 shrink-0 text-rose-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <div>
                            <div class="font-bold">Connection Failed</div>
                            <div class="text-xs text-rose-700 mt-0.5">${data.message}</div>
                        </div>
                    `;
                }
            })
            .catch(err => {
                testDbBtn.disabled = false;
                testDbBtn.innerHTML = origContent;
                statusDiv.classList.remove('hidden');
                statusDiv.className = 'p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-medium';
                statusDiv.innerText = 'Unable to reach the test endpoint. Please ensure server is running.';
            });
        });
    }

    // 2. Admin Password Strength Meter
    const adminPassInput = document.getElementById('admin_password');
    const adminPassMeter = document.getElementById('password-strength-meter');
    const adminPassLabel = document.getElementById('password-strength-label');

    if (adminPassInput && adminPassMeter && adminPassLabel) {
        adminPassInput.addEventListener('input', function () {
            const val = adminPassInput.value;
            let score = 0;
            if (val.length >= 8) score++;
            if (val.length >= 12) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            adminPassMeter.style.width = (score * 20) + '%';
            if (score <= 2) {
                adminPassMeter.className = 'h-1.5 rounded-full transition-all duration-300 bg-rose-500';
                adminPassLabel.innerText = 'Weak password';
                adminPassLabel.className = 'text-xs text-rose-600 font-medium';
            } else if (score <= 4) {
                adminPassMeter.className = 'h-1.5 rounded-full transition-all duration-300 bg-amber-500';
                adminPassLabel.innerText = 'Moderate password';
                adminPassLabel.className = 'text-xs text-amber-600 font-medium';
            } else {
                adminPassMeter.className = 'h-1.5 rounded-full transition-all duration-300 bg-emerald-500';
                adminPassLabel.innerText = 'Strong password';
                adminPassLabel.className = 'text-xs text-emerald-600 font-medium';
            }
        });
    }
});
