<?php
require_once 'auth.php';
require_once 'db_connect.php';

// Require user authentication
requireUser();

// Set JSON response header
header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get and validate reservation ID
$reservation_id = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);
if (!$reservation_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid reservation ID']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if reservation exists and belongs to the user
    $sql = "
        SELECT r.*, p.id as slot_id 
        FROM ipark_reservations r 
        JOIN ipark_parking_slots p ON r.parking_slot_id = p.id 
        WHERE r.id = ? AND r.user_id = ? AND r.status = 'Pending'
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $reservation_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();

    if (!$reservation) {
        throw new Exception('Reservation not found or cannot be cancelled');
    }

    // Update reservation status
    $sql = "UPDATE ipark_reservations SET status = 'Cancelled' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();

    // Update parking slot status back to Vacant
    $sql = "UPDATE ipark_parking_slots SET status = 'Vacant' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $reservation['slot_id']);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Reservation cancelled successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 