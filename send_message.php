<?php
require_once 'auth.php';
require_once 'db_connect.php';

// Require user authentication
requireUser();

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get and validate message
$message = trim($_POST['message'] ?? '');
if (empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
    exit;
}

try {
    // Insert message into database
    $sql = "
        INSERT INTO ipark_messages (user_id, message, is_from_user)
        VALUES (?, ?, TRUE)
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $_SESSION['user_id'], $message);
    $stmt->execute();
    
    // Get the inserted message ID
    $messageId = $conn->insert_id;
    
    // Fetch user data for response
    $sql = "
        SELECT first_name, last_name, profile_picture 
        FROM ipark_users 
        WHERE id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Prepare response data
    $response = [
        'success' => true,
        'message_id' => $messageId,
        'message' => $message,
        'sender_name' => $user['first_name'] . ' ' . $user['last_name'],
        'user_avatar' => $user['profile_picture'],
        'user_initials' => strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)),
        'created_at' => date('M d, Y h:i A')
    ];
    
    echo json_encode($response);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error sending message']);
}
?> 