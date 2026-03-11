<?php
/**
 * IPark Comprehensive System Diagnostic
 * Tests all major functionality and database connectivity
 */
session_start();
require_once 'db_connect.php';

$tests = [];
$all_pass = true;

// Test 1: Database Connection
$test_db_connection = ($conn && !mysqli_connect_errno());
$tests[] = [
    'name' => 'Database Connection',
    'pass' => $test_db_connection,
    'details' => $test_db_connection ? 'Connected to ' . DB_NAME : 'Connection failed: ' . mysqli_connect_error()
];
if (!$test_db_connection) $all_pass = false;

// Test 2: Test all required tables exist
$tables = ['ipark_admins', 'ipark_users', 'ipark_parking_slots', 'ipark_reservations', 'ipark_messages'];
$tables_exist = true;
$missing_tables = [];

foreach ($tables as $table) {
    $result = $conn->query("SELECT 1 FROM $table LIMIT 1");
    if (!$result) {
        $tables_exist = false;
        $missing_tables[] = $table;
    }
}

$tests[] = [
    'name' => 'Database Tables',
    'pass' => $tables_exist,
    'details' => $tables_exist ? 'All 5 tables found' : 'Missing: ' . implode(', ', $missing_tables)
];
if (!$tables_exist) $all_pass = false;

// Test 3: Check admin user exists
$admin_result = $conn->query("SELECT COUNT(*) as count FROM ipark_admins");
$admin_count = 0;
if ($admin_result) {
    $row = $admin_result->fetch_assoc();
    $admin_count = $row['count'];
}
$admin_exists = $admin_count > 0;

$tests[] = [
    'name' => 'Admin Account',
    'pass' => $admin_exists,
    'details' => $admin_exists ? $admin_count . ' admin account(s) found' : 'No admin accounts - auto-creation may have failed'
];
if (!$admin_exists) $all_pass = false;

// Test 4: Check table structure (sample columns)
$structure_check = true;
$structure_issues = [];

// Check ipark_users table columns
$result = $conn->query("DESCRIBE ipark_users");
$user_columns = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $user_columns[] = $row['Field'];
    }
}
$required_user_cols = ['id', 'first_name', 'last_name', 'email', 'password'];
foreach ($required_user_cols as $col) {
    if (!in_array($col, $user_columns)) {
        $structure_check = false;
        $structure_issues[] = "ipark_users missing column: $col";
    }
}

$tests[] = [
    'name' => 'Database Schema',
    'pass' => $structure_check,
    'details' => $structure_check ? 'All required columns present' : implode(', ', $structure_issues)
];
if (!$structure_check) $all_pass = false;

// Test 5: Test MySQLi functionality
$mysqli_test = true;
$mysqli_issues = [];

// Test prepared statement with bind_param
$test_sql = "SELECT id FROM ipark_users WHERE email = ? LIMIT 1";
$stmt = $conn->prepare($test_sql);
if (!$stmt) {
    $mysqli_test = false;
    $mysqli_issues[] = "Prepared statement failed: " . $conn->error;
}

// Test query execution
$result = $conn->query("SELECT COUNT(*) as count FROM ipark_users");
if (!$result) {
    $mysqli_test = false;
    $mysqli_issues[] = "Query execution failed: " . $conn->error;
}

$tests[] = [
    'name' => 'MySQLi Functionality',
    'pass' => $mysqli_test,
    'details' => $mysqli_test ? 'Prepared statements and queries working' : implode(', ', $mysqli_issues)
];
if (!$mysqli_test) $all_pass = false;

// Test 6: Session functionality
$session_test = true;
$_SESSION['test_key'] = 'test_value';
if ($_SESSION['test_key'] !== 'test_value') {
    $session_test = false;
}

$tests[] = [
    'name' => 'Session Support',
    'pass' => $session_test,
    'details' => $session_test ? 'Sessions working properly' : 'Session storage failed'
];
if (!$session_test) $all_pass = false;

// Test 7: Password hashing
$hash_test = true;
$test_hash = password_hash('test123', PASSWORD_DEFAULT);
$verify_test = password_verify('test123', $test_hash);

$tests[] = [
    'name' => 'Password Hashing',
    'pass' => $verify_test,
    'details' => $verify_test ? 'Password_hash/verify working' : 'Password hashing failed'
];
if (!$verify_test) $all_pass = false;

// Test 8: Data count stats
$user_count = 0;
$slot_count = 0;
$reservation_count = 0;
$message_count = 0;

$result = $conn->query("SELECT COUNT(*) as count FROM ipark_users");
if ($result) $user_count = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM ipark_parking_slots");
if ($result) $slot_count = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM ipark_reservations");
if ($result) $reservation_count = $result->fetch_assoc()['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM ipark_messages");
if ($result) $message_count = $result->fetch_assoc()['count'];

$tests[] = [
    'name' => 'Data Statistics',
    'pass' => true,
    'details' => "Users: $user_count | Slots: $slot_count | Reservations: $reservation_count | Messages: $message_count"
];

// Output HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPark System Diagnostic</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .header h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .header p { opacity: 0.9; }
        .content { padding: 2rem; }
        .test-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            background: #f8f9fa;
            border-left: 4px solid #ccc;
        }
        .test-item.pass {
            background: #f0fdf4;
            border-left-color: #22c55e;
        }
        .test-item.fail {
            background: #fef2f2;
            border-left-color: #ef4444;
        }
        .test-icon {
            font-size: 1.5rem;
            min-width: 2rem;
        }
        .test-content { flex: 1; }
        .test-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
            color: #1f2937;
        }
        .test-details {
            font-size: 0.9rem;
            color: #6b7280;
        }
        .summary {
            background: #f3f4f6;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
            border: 1px solid #e5e7eb;
        }
        .summary h3 {
            margin-bottom: 1rem;
            color: #1f2937;
        }
        .summary p {
            margin: 0.5rem 0;
            color: #4b5563;
        }
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .status-pass {
            background: #d1fae5;
            color: #065f46;
        }
        .status-fail {
            background: #fee2e2;
            color: #7f1d1d;
        }
        .footer {
            background: #f9fafb;
            padding: 1.5rem 2rem;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 0.9rem;
        }
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .quick-link {
            display: inline-block;
            padding: 0.75rem 1rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            text-align: center;
            transition: background 0.3s;
        }
        .quick-link:hover {
            background: #764ba2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚗 IPark System Diagnostic</h1>
            <p>Comprehensive System Health Check</p>
        </div>

        <div class="content">
            <?php foreach ($tests as $test): ?>
                <div class="test-item <?php echo $test['pass'] ? 'pass' : 'fail'; ?>">
                    <div class="test-icon">
                        <?php echo $test['pass'] ? '✅' : '❌'; ?>
                    </div>
                    <div class="test-content">
                        <div class="test-name"><?php echo htmlspecialchars($test['name']); ?></div>
                        <div class="test-details"><?php echo htmlspecialchars($test['details']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="summary">
                <h3>📊 System Status</h3>
                <p>
                    Overall Status:
                    <span class="status-badge <?php echo $all_pass ? 'status-pass' : 'status-fail'; ?>">
                        <?php echo $all_pass ? '✅ OPERATIONAL' : '⚠️ ISSUES DETECTED'; ?>
                    </span>
                </p>
                <p>Total Tests: <strong><?php echo count($tests); ?></strong></p>
                <p>Passed: <strong><?php echo count(array_filter($tests, fn($t) => $t['pass'])); ?></strong></p>
                <p>Failed: <strong><?php echo count(array_filter($tests, fn($t) => !$t['pass'])); ?></strong></p>

                <div class="quick-links">
                    <a href="index.php" class="quick-link">👤 User Login</a>
                    <a href="admin_login.php" class="quick-link">👨‍💼 Admin Login</a>
                    <a href="register.php" class="quick-link">📝 Register</a>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>IPark Parking Management System | Diagnostic Report Generated: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
<?php unset($_SESSION['test_key']); ?>
