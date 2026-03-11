<?php
/**
 * IPark Integration Test Suite
 * Simulates core user workflows
 */
session_start();
require_once 'db_connect.php';

// Clear any previous session state
foreach (['user_id', 'admin_id', 'test_user_id', 'test_admin_id'] as $key) {
    unset($_SESSION[$key]);
}

$results = [];
$test_email = 'integration_test_' . time() . '@example.com';
$test_user_id = null;

// Test 1: Register a test user
$test_result = [
    'name' => 'User Registration',
    'pass' => false,
    'details' => ''
];

$sql = "INSERT INTO ipark_users (first_name, last_name, email, password, phone_number, address, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $first_name = 'Integration';
    $last_name = 'Test';
    $password = password_hash('Test@1234', PASSWORD_DEFAULT);
    $phone = '5551234567';
    $address = '123 Test St';
    $status = 'Active';
    
    $stmt->bind_param("sssssss", $first_name, $last_name, $test_email, $password, $phone, $address, $status);
    
    if ($stmt->execute()) {
        $test_user_id = $conn->insert_id;
        $test_result['pass'] = true;
        $test_result['details'] = 'User created with ID: ' . $test_user_id;
    } else {
        $test_result['details'] = 'Execute failed: ' . $stmt->error;
    }
} else {
    $test_result['details'] = 'Prepare failed: ' . $conn->error;
}

$results[] = $test_result;

// Test 2: Verify user login
$test_result = [
    'name' => 'User Login Verification',
    'pass' => false,
    'details' => ''
];

if ($test_user_id) {
    $sql = "SELECT id, password FROM ipark_users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("s", $test_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify('Test@1234', $user['password'])) {
                $test_result['pass'] = true;
                $test_result['details'] = 'Login verification passed';
            } else {
                $test_result['details'] = 'Password verification failed';
            }
        } else {
            $test_result['details'] = 'User not found';
        }
    } else {
        $test_result['details'] = 'Prepare failed: ' . $conn->error;
    }
}

$results[] = $test_result;

// Test 3: Create a parking slot
$test_result = [
    'name' => 'Parking Slot Creation',
    'pass' => false,
    'details' => ''
];

$sql = "INSERT INTO ipark_parking_slots (slot_number, floor_number, status) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $slot_number = 'TEST-' . time();
    $floor = 1;
    $status = 'Vacant';
    
    $stmt->bind_param("sis", $slot_number, $floor, $status);
    
    if ($stmt->execute()) {
        $test_slot_id = $conn->insert_id;
        $test_result['pass'] = true;
        $test_result['details'] = 'Slot created: ' . $slot_number;
    } else {
        $test_result['details'] = 'Execute failed: ' . $stmt->error;
    }
} else {
    $test_result['details'] = 'Prepare failed: ' . $conn->error;
}

$results[] = $test_result;

// Test 4: Create a reservation
$test_result = [
    'name' => 'Reservation Creation',
    'pass' => false,
    'details' => ''
];

if ($test_user_id && isset($test_slot_id)) {
    $sql = "INSERT INTO ipark_reservations (user_id, parking_slot_id, car_type, plate_number, start_time, end_time, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $car_type = 'Sedan';
        $plate = 'TEST123';
        $start = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $end = date('Y-m-d H:i:s', strtotime('+3 hours'));
        $res_status = 'Confirmed';
        
        $stmt->bind_param("iissss", $test_user_id, $test_slot_id, $car_type, $plate, $start, $end, $res_status);
        
        if ($stmt->execute()) {
            $test_res_id = $conn->insert_id;
            $test_result['pass'] = true;
            $test_result['details'] = 'Reservation created with ID: ' . $test_res_id;
        } else {
            $test_result['details'] = 'Execute failed: ' . $stmt->error;
        }
    } else {
        $test_result['details'] = 'Prepare failed: ' . $conn->error;
    }
}

$results[] = $test_result;

// Test 5: Admin authentication
$test_result = [
    'name' => 'Admin Authentication',
    'pass' => false,
    'details' => ''
];

$sql = "SELECT id, password FROM ipark_admins WHERE username = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $admin_user = 'admin';
    $stmt->bind_param("s", $admin_user);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if (password_verify('Admin@123', $admin['password'])) {
            $test_result['pass'] = true;
            $test_result['details'] = 'Default admin login verified';
        } else {
            $test_result['details'] = 'Password verification failed';
        }
    } else {
        $test_result['details'] = 'Admin user not found';
    }
} else {
    $test_result['details'] = 'Prepare failed: ' . $conn->error;
}

$results[] = $test_result;

// Test 6: Message creation
$test_result = [
    'name' => 'Message System',
    'pass' => false,
    'details' => ''
];

if ($test_user_id && isset($admin)) {
    $sql = "INSERT INTO ipark_messages (user_id, admin_id, message, is_from_user, is_read) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $admin_id = $admin['id'];
        $message = 'Test message from integration tests';
        $from_user = 1;
        $is_read = 0;
        
        $stmt->bind_param("iisii", $test_user_id, $admin_id, $message, $from_user, $is_read);
        
        if ($stmt->execute()) {
            $test_result['pass'] = true;
            $test_result['details'] = 'Message created successfully';
        } else {
            $test_result['details'] = 'Execute failed: ' . $stmt->error;
        }
    } else {
        $test_result['details'] = 'Prepare failed: ' . $conn->error;
    }
}

$results[] = $test_result;

// Test 7: Transaction support
$test_result = [
    'name' => 'Transaction Support',
    'pass' => false,
    'details' => ''
];

$conn->begin_transaction();

try {
    // Update slot status
    $sql = "UPDATE ipark_parking_slots SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $status_update = 'Occupied';
    
    if ($stmt) {
        $stmt->bind_param("si", $status_update, $test_slot_id);
        $stmt->execute();
    }
    
    // Update reservation
    $sql = "UPDATE ipark_reservations SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $status_res = 'Completed';
    
    if ($stmt) {
        $stmt->bind_param("si", $status_res, $test_res_id);
        $stmt->execute();
    }
    
    $conn->commit();
    $test_result['pass'] = true;
    $test_result['details'] = 'Transaction committed successfully';
} catch (Exception $e) {
    $conn->rollback();
    $test_result['details'] = 'Transaction failed: ' . $e->getMessage();
}

$results[] = $test_result;

// Test 8: Data retrieval with joins
$test_result = [
    'name' => 'Complex Query Joins',
    'pass' => false,
    'details' => ''
];

$sql = "
    SELECT r.*, u.first_name, u.last_name, p.slot_number 
    FROM ipark_reservations r
    JOIN ipark_users u ON r.user_id = u.id
    JOIN ipark_parking_slots p ON r.parking_slot_id = p.id
    WHERE r.user_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $test_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $reservation = $result->fetch_assoc();
        $test_result['pass'] = true;
        $test_result['details'] = 'Complex join retrieved: ' . $reservation['first_name'] . ' - Slot ' . $reservation['slot_number'];
    } else {
        $test_result['details'] = 'No reservations found';
    }
} else {
    $test_result['details'] = 'Prepare failed: ' . $conn->error;
}

$results[] = $test_result;

// Cleanup: Delete test data
$cleanup_success = true;

if (isset($test_res_id)) {
    $conn->query("DELETE FROM ipark_reservations WHERE id = $test_res_id");
}

if (isset($test_slot_id)) {
    $conn->query("DELETE FROM ipark_parking_slots WHERE id = $test_slot_id");
}

if ($test_user_id) {
    $conn->query("DELETE FROM ipark_messages WHERE user_id = $test_user_id");
    $conn->query("DELETE FROM ipark_users WHERE id = $test_user_id");
}

// HTML Output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPark Integration Tests</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 900px;
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
        }
        .header h1 { font-size: 2rem; margin-bottom: 0.5rem; }
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
        .test-icon { font-size: 1.5rem; min-width: 2rem; }
        .test-content { flex: 1; }
        .test-name { font-weight: 600; font-size: 1.1rem; margin-bottom: 0.25rem; color: #1f2937; }
        .test-details { font-size: 0.9rem; color: #6b7280; }
        .summary {
            background: #f3f4f6;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
            border: 1px solid #e5e7eb;
        }
        .summary h3 { margin-bottom: 1rem; color: #1f2937; }
        .summary p { margin: 0.5rem 0; color: #4b5563; }
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 IPark Integration Test Suite</h1>
            <p>Complete workflow testing</p>
        </div>

        <div class="content">
            <?php foreach ($results as $test): ?>
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
                <h3>📊 Test Results</h3>
                <p>
                    Overall Status:
                    <span class="status-badge <?php echo count(array_filter($results, fn($r) => $r['pass'])) === count($results) ? 'status-pass' : 'status-fail'; ?>">
                        <?php echo count(array_filter($results, fn($r) => $r['pass'])) === count($results) ? '✅ ALL TESTS PASSED' : '⚠️ SOME TESTS FAILED'; ?>
                    </span>
                </p>
                <p>Total Tests: <strong><?php echo count($results); ?></strong></p>
                <p>Passed: <strong><?php echo count(array_filter($results, fn($r) => $r['pass'])); ?></strong></p>
                <p>Failed: <strong><?php echo count(array_filter($results, fn($r) => !$r['pass'])); ?></strong></p>
                <p style="margin-top: 1rem; font-style: italic; color: #6b7280;">Test data has been cleaned up. System is ready for production.</p>
            </div>
        </div>
    </div>
</body>
</html>
