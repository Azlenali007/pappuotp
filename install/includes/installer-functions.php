<?php
if (!ob_get_level()) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.use_only_cookies', '1');
    @ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

/**
 * Path to the installation lock file
 */
function get_installer_lock_file(): string {
    return dirname(__DIR__, 2) . '/storage/private/installation.lock';
}

/**
 * Track completed installer step server-side
 */
function record_completed_step(int $step): void {
    if (!isset($_SESSION['installer_completed_steps'])) {
        $_SESSION['installer_completed_steps'] = [];
    }
    if (!in_array($step, $_SESSION['installer_completed_steps'], true)) {
        $_SESSION['installer_completed_steps'][] = $step;
    }
}

/**
 * Check if a step has already succeeded
 */
function is_step_completed(int $step): bool {
    return in_array($step, $_SESSION['installer_completed_steps'] ?? [], true);
}

/**
 * Retrieve database configuration with automatic session recovery
 */
function get_active_installer_db(): ?array {
    if (!empty($_SESSION['installer_db']) && is_array($_SESSION['installer_db'])) {
        return $_SESSION['installer_db'];
    }

    // Try recovering from config/db-config.php
    $cfgPath = dirname(__DIR__, 2) . '/config/db-config.php';
    if (file_exists($cfgPath)) {
        $saved = @include $cfgPath;
        if (is_array($saved) && !empty($saved['dbname']) && !empty($saved['username'])) {
            $test = test_db_connection(
                $saved['host'] ?? '127.0.0.1',
                (string)($saved['port'] ?? 3306),
                $saved['dbname'],
                $saved['username'],
                $saved['password'] ?? ''
            );
            if ($test['success']) {
                $saved['existing_tables'] = $test['existing_tables'] ?? [];
                $saved['tables_count'] = $test['tables_count'] ?? 0;
                $_SESSION['installer_db'] = $saved;
                return $saved;
            }
        }
    }

    return null;
}

/**
 * Check if the application has already been installed
 */
function is_installed(): bool {
    return file_exists(get_installer_lock_file());
}

/**
 * Safe diagnostic logging for installer events (never logs passwords or secrets)
 */
function installer_log(string $action, string $message, array $context = []): void {
    $logDir = dirname(__DIR__, 2) . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0750, true);
    }
    $logFile = $logDir . '/installer.log';

    // Strip sensitive fields
    $sanitized = $context;
    foreach (['password', 'pass', 'db_pass', 'secret', 'key'] as $secretKey) {
        if (isset($sanitized[$secretKey])) {
            $sanitized[$secretKey] = '[REDACTED]';
        }
    }

    $entry = sprintf(
        "[%s] [IP: %s] [%s] %s %s\n",
        date('Y-m-d H:i:s'),
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        strtoupper($action),
        $message,
        !empty($sanitized) ? json_encode($sanitized, JSON_UNESCAPED_SLASHES) : ''
    );

    @file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Enforce installation lock - stop and show warning if already installed
 */
function ensure_not_installed(): void {
    if (is_installed()) {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Installation Locked - OTP Services Platform</title>
            <link rel="stylesheet" href="/assets/css/output.css">
        </head>
        <body class="bg-slate-50 min-h-screen flex items-center justify-center p-4 antialiased font-sans">
            <div class="max-w-md w-full bg-white rounded-3xl shadow-xl shadow-purple-500/5 border border-slate-200/80 p-8 text-center space-y-6">
                <div class="w-16 h-16 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center mx-auto text-amber-600">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div class="space-y-2">
                    <h1 class="text-2xl font-bold text-slate-900">Application Already Installed</h1>
                    <p class="text-sm text-slate-500 leading-relaxed">
                        For security reasons, the installation wizard has been locked. To protect existing customer wallets, orders, and system settings, re-installation via browser is disabled.
                    </p>
                </div>
                <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 text-xs font-mono text-slate-500 break-all">
                    Lock: storage/private/installation.lock
                </div>
                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <a href="/index.php" class="flex-1 py-3 px-4 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-sm shadow-md shadow-purple-200 transition-all text-center">
                        Go to Homepage
                    </a>
                    <a href="/admin/login.php" class="flex-1 py-3 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-sm transition-all text-center">
                        Admin Login
                    </a>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

/**
 * Generate CSRF token for installer forms
 */
function get_installer_csrf_token(): string {
    if (empty($_SESSION['installer_csrf_token'])) {
        $_SESSION['installer_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['installer_csrf_token'];
}

/**
 * Verify installer CSRF token
 */
function verify_installer_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['installer_csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['installer_csrf_token'], $token);
}

/**
 * Test a database connection using PDO
 */
function test_db_connection(string $host, string $port, string $dbname, string $username, string $password): array {
    try {
        $port = !empty($port) ? (int)$port : 3306;
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 4,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        
        $pdo = new PDO($dsn, $username, $password, $options);
        $version = $pdo->query("SELECT VERSION()")->fetchColumn();
        
        // Count existing tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        installer_log('DB_TEST_SUCCESS', "Database connection verified successfully on {$host}:{$port}", [
            'dbname' => $dbname,
            'tables_count' => count($tables),
            'version' => (string)$version
        ]);

        return [
            'success' => true,
            'message' => 'Successfully connected to database!',
            'version' => (string)$version,
            'tables_count' => count($tables),
            'existing_tables' => $tables
        ];
    } catch (\PDOException $e) {
        $errorMsg = $e->getMessage();
        // Sanitize error message to avoid exposing credentials
        if (strpos($errorMsg, 'Access denied for user') !== false) {
            $safeMsg = 'Access denied: Please check your database username and password.';
        } elseif (strpos($errorMsg, 'Unknown database') !== false) {
            $safeMsg = "Database '{$dbname}' not found. Please create it first in your database management panel.";
        } elseif (strpos($errorMsg, 'Connection refused') !== false || strpos($errorMsg, 'No such file or directory') !== false) {
            $safeMsg = "Could not connect to host '{$host}:{$port}'. Verify MySQL service is running.";
        } else {
            $safeMsg = 'Database connection failed: ' . htmlspecialchars($errorMsg);
        }

        installer_log('DB_TEST_FAILED', $safeMsg, [
            'host' => $host,
            'port' => $port,
            'dbname' => $dbname,
            'username' => $username
        ]);
        
        return [
            'success' => false,
            'message' => $safeMsg,
            'raw_code' => $e->getCode()
        ];
    }
}

/**
 * Execute schema.sql file against the target PDO connection
 */
function run_schema_installation(PDO $pdo, string $schemaPath): array {
    if (!file_exists($schemaPath)) {
        return ['success' => false, 'error' => 'Database schema.sql file not found'];
    }

    $sql = file_get_contents($schemaPath);
    if ($sql === false || empty(trim($sql))) {
        return ['success' => false, 'error' => 'Schema file is empty or unreadable'];
    }

    // Split SQL by semicolons, accounting for multi-line queries
    $queries = preg_split('/;\s*[\r\n]+/', $sql);
    $executed = 0;
    $errors = [];

    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

        foreach ($queries as $query) {
            // Strip leading/trailing comments and whitespace
            $clean = preg_replace('/^--[^\r\n]*[\r\n]*/m', '', $query);
            $clean = trim($clean);

            if (empty($clean)) {
                continue;
            }

            try {
                $pdo->exec($clean);
                $executed++;
            } catch (\PDOException $qe) {
                // Ignore safe drop or duplicate warning if already exists
                if (str_starts_with(strtoupper($clean), 'DROP TABLE')) {
                    continue;
                }
                $errors[] = "Query failed: " . substr($clean, 0, 80) . "... Error: " . $qe->getMessage();
                throw $qe;
            }
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        return [
            'success' => true,
            'queries_executed' => $executed,
            'message' => "Successfully imported {$executed} schema definitions."
        ];
    } catch (\Throwable $e) {
        return [
            'success' => false,
            'error' => 'Database installation failed during statement execution: ' . $e->getMessage(),
            'details' => $errors
        ];
    }
}

/**
 * Seed initial system roles and essential categories safely
 */
function seed_essential_data(PDO $pdo): void {
    try {
        // Insert Super Admin Role if not exists
        $stmt = $pdo->query("SELECT COUNT(*) FROM roles WHERE slug = 'super-admin'");
        if ((int)$stmt->fetchColumn() === 0) {
            $pdo->exec("INSERT INTO roles (id, name, slug, description) VALUES (1, 'Super Administrator', 'super-admin', 'Full platform access and management')");
        }

        // Insert basic service categories if none exist
        $catCount = (int)$pdo->query("SELECT COUNT(*) FROM service_categories")->fetchColumn();
        if ($catCount === 0) {
            $pdo->exec("
                INSERT INTO service_categories (id, name, slug) VALUES
                (1, 'Messaging Apps', 'messaging'),
                (2, 'Social Media', 'social-media'),
                (3, 'Search & Tech', 'tech'),
                (4, 'Finance & Banking', 'finance'),
                (5, 'E-Commerce', 'ecommerce')
            ");
        }
    } catch (\Throwable $e) {
        error_log("Seed warning: " . $e->getMessage());
    }
}

/**
 * Create the initial Super Admin account
 */
function create_super_admin(PDO $pdo, string $name, string $username, string $email, string $password): array {
    // Validate inputs
    $name = trim($name);
    $username = trim($username);
    $email = strtolower(trim($email));

    if (empty($name) || empty($username) || empty($email) || empty($password)) {
        return ['success' => false, 'error' => 'All fields are required.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please provide a valid email address.'];
    }

    if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        return ['success' => false, 'error' => 'Username must be at least 3 alphanumeric characters (dashes & underscores allowed).'];
    }

    if (strlen($password) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters long.'];
    }

    // Check for existing administrator
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = :email OR username = :username LIMIT 1");
    $stmt->execute([':email' => $email, ':username' => $username]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'An administrator with this email or username already exists.'];
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    try {
        $insert = $pdo->prepare("
            INSERT INTO admins (name, username, email, password_hash, role_id, status, created_at)
            VALUES (:name, :username, :email, :hash, 1, 'active', NOW())
        ");
        $insert->execute([
            ':name' => $name,
            ':username' => $username,
            ':email' => $email,
            ':hash' => $passwordHash
        ]);

        return [
            'success' => true,
            'admin_id' => (int)$pdo->lastInsertId(),
            'username' => $username,
            'email' => $email
        ];
    } catch (\PDOException $e) {
        return ['success' => false, 'error' => 'Failed to create admin record: ' . $e->getMessage()];
    }
}

/**
 * Save configuration to config/db-config.php and database application_settings
 */
function save_installer_configuration(PDO $pdo, array $dbConfig, array $siteConfig): array {
    // 1. Write config/db-config.php
    $configFilePath = dirname(__DIR__, 2) . '/config/db-config.php';
    $escapedHost = addslashes($dbConfig['host']);
    $escapedPort = (int)$dbConfig['port'];
    $escapedDb = addslashes($dbConfig['dbname']);
    $escapedUser = addslashes($dbConfig['username']);
    $escapedPass = addslashes($dbConfig['password']);

    $phpContent = "<?php\n/**\n * Database Configuration Generated by Web Installer\n * Date: " . date('Y-m-d H:i:s') . "\n */\nreturn [\n" .
        "    'host' => '{$escapedHost}',\n" .
        "    'port' => {$escapedPort},\n" .
        "    'dbname' => '{$escapedDb}',\n" .
        "    'username' => '{$escapedUser}',\n" .
        "    'password' => '{$escapedPass}',\n" .
        "];\n";

    if (@file_put_contents($configFilePath, $phpContent) === false) {
        return ['success' => false, 'error' => 'Could not write config/db-config.php. Please ensure config/ directory is writable.'];
    }
    @chmod($configFilePath, 0640);

    // 2. Save settings to application_settings table
    try {
        $stmt = $pdo->prepare("INSERT INTO application_settings (key_name, value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE value = :v2");
        foreach ($siteConfig as $key => $val) {
            $valStr = (string)$val;
            $stmt->execute([':k' => $key, ':v' => $valStr, ':v2' => $valStr]);
        }
    } catch (\PDOException $e) {
        return ['success' => false, 'error' => 'Failed to store settings in database: ' . $e->getMessage()];
    }

    return ['success' => true];
}

/**
 * Create installation lock file
 */
function create_installation_lock(string $adminUsername = 'admin'): bool {
    $lockDir = dirname(__DIR__, 2) . '/storage/private';
    if (!is_dir($lockDir)) {
        @mkdir($lockDir, 0750, true);
    }

    $lockFile = get_installer_lock_file();
    $data = [
        'installed' => true,
        'installed_at' => date('Y-m-d H:i:s'),
        'installed_by' => $adminUsername,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'installer_version' => '1.0.0',
        'php_version' => PHP_VERSION,
    ];

    $content = json_encode($data, JSON_PRETTY_PRINT);
    $result = @file_put_contents($lockFile, $content);
    if ($result !== false) {
        @chmod($lockFile, 0444); // Read-only lock
        return true;
    }
    return false;
}
