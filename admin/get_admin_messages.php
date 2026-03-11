<?php
require_once '../auth.php';
require_once '../db_connect.php';

// Require admin authentication
requireAdmin();

// Get and validate inputs
$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$lastId = filter_input(INPUT_GET, 'last_id', FILTER_VALIDATE_INT);

if (!$userId || $lastId === false) {
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
    echo json_encode(['success' => false, 'message' => 'Error fetching messages']);
    exit;
}

// Fetch new messages
$stmt = $conn->prepare("
    SELECT m.*, 
           CASE 
               WHEN m.is_from_user THEN u.first_name 
               ELSE a.username 
           END as sender_name,
           CASE 
               WHEN m.is_from_user THEN u.profile_picture 
               ELSE NULL 
           END as sender_avatar,
           CASE 
               WHEN m.is_from_user THEN CONCAT(UPPER(LEFT(u.first_name, 1)), UPPER(LEFT(u.last_name, 1)))
               ELSE NULL 
           END as sender_initials
    FROM ipark_messages m
    LEFT JOIN ipark_users u ON m.user_id = u.id
    LEFT JOIN ipark_admins a ON m.admin_id = a.id
    WHERE m.user_id = ? 
    AND m.id > ?
    AND (
        (m.is_from_user = TRUE AND m.admin_id IS NULL) OR 
        (m.is_from_user = FALSE AND m.admin_id = ?)
    )
    ORDER BY m.created_at ASC
");

$messages = [];
if ($stmt) {
    $stmt->bind_param("iii", $userId, $lastId, $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching messages']);
    exit;
}

// Format messages for response
$formattedMessages = array_map(function($message) {
    return [
        'id' => $message['id'],
        'message' => $message['message'],
        'is_from_user' => (bool)$message['is_from_user'],
        'sender_name' => $message['sender_name'],
        'sender_avatar' => $message['sender_avatar'],
        'sender_initials' => $message['sender_initials'],
        'created_at' => date('M d, Y h:i A', strtotime($message['created_at']))
    ];
}, $messages);

// Mark unread user messages as read
if (!empty($messages)) {
    $stmt = $conn->prepare("
        UPDATE ipark_messages 
        SET is_read = TRUE 
        WHERE user_id = ? AND is_from_user = TRUE AND is_read = FALSE
    ");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
    }
}

echo json_encode(['success' => true, 'messages' => $formattedMessages]);
?> 