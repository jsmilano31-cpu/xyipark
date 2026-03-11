<?php
require_once '../auth.php';
require_once '../db_connect.php';

// Require admin authentication
requireAdmin();

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get and validate inputs
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$message = trim($_POST['message'] ?? '');

if (!$userId || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Verify user exists
$stmt = $conn->prepare("SELECT id FROM ipark_users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result->fetch_assoc()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error sending message']);
    exit;
}

// Insert message into database
$is_from_user = 0;
$stmt = $conn->prepare("
    INSERT INTO ipark_messages (user_id, admin_id, message, is_from_user)
    VALUES (?, ?, ?, ?)
");
if ($stmt) {
    $stmt->bind_param("iisi", $userId, $_SESSION['admin_id'], $message, $is_from_user);
    if ($stmt->execute()) {
        // Get the inserted message ID
        $messageId = $conn->insert_id;
        
        // Fetch admin data for response
        $stmt = $conn->prepare("
            SELECT username 
            FROM ipark_admins 
            WHERE id = ?
        ");
        if ($stmt) {
            $stmt->bind_param("i", $_SESSION['admin_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();
            
            // Prepare response data
            $response = [
                'success' => true,
                'message_id' => $messageId,
                'message' => $message,
                'sender_name' => $admin['username'],
                'created_at' => date('M d, Y h:i A')
            ];
            
            echo json_encode($response);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error sending message']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error sending message']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error sending message']);
}
?> 